<?php
/**
 * FoodBridge — my-deliveries.php
 * Volunteer's accepted tasks: see active deliveries, mark them delivered, view history.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_role('volunteer');

$u   = current_user();
$uid = (int) $u['id'];

const POINTS_PER_DELIVERY = 10;

/* ------------------------------------------------------------------ *
 *  Handle "Mark as Delivered" (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_delivered'])) {
    $taskId = (int) ($_POST['task_id'] ?? 0);

    if ($taskId > 0) {
        // Complete only if this volunteer owns it and it is still 'assigned'
        $stmt = $conn->prepare(
            "UPDATE requests SET status = 'delivered'
             WHERE id = ? AND volunteer_id = ? AND status = 'assigned'"
        );
        $stmt->bind_param('ii', $taskId, $uid);
        $stmt->execute();
        $done = $stmt->affected_rows > 0;
        $stmt->close();

        if ($done) {
            // Reward the volunteer: +points and +1 delivery
            $stmt = $conn->prepare(
                'UPDATE volunteers SET points = points + ?, deliveries = deliveries + 1 WHERE user_id = ?'
            );
            $pts = POINTS_PER_DELIVERY;
            $stmt->bind_param('ii', $pts, $uid);
            $stmt->execute();
            $stmt->close();

            // Find the receiver + linked food post
            $stmt = $conn->prepare('SELECT receiver_id, food_post_id FROM requests WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $taskId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                // Mark the linked donation as completed (if any)
                if (!empty($row['food_post_id'])) {
                    $stmt = $conn->prepare("UPDATE food_posts SET status = 'completed' WHERE id = ?");
                    $stmt->bind_param('i', $row['food_post_id']);
                    $stmt->execute();
                    $stmt->close();
                }
                // Notify the receiver
                $title = 'Your food has been delivered 🍱';
                $body  = 'Delivered by ' . $u['name'] . '. Enjoy your meal!';
                $stmt  = $conn->prepare(
                    "INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'package-check')"
                );
                $stmt->bind_param('iss', $row['receiver_id'], $title, $body);
                $stmt->execute();
                $stmt->close();
            }
            set_flash('success', 'Delivery complete! You earned ' . POINTS_PER_DELIVERY . ' points. 🏆');
        } else {
            set_flash('error', 'Could not complete that delivery.');
        }
    }
    redirect('my-deliveries.php');
}

/* ------------------------------------------------------------------ *
 *  Fetch active + completed deliveries for this volunteer
 * ------------------------------------------------------------------ */
$stmt = $conn->prepare(
    "SELECT r.*, u.name AS requester, u.phone AS req_phone
     FROM requests r
     JOIN users u ON u.id = r.receiver_id
     WHERE r.volunteer_id = ? AND r.status = 'assigned'
     ORDER BY r.created_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$activeJobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$stmt->close();

$stmt = $conn->prepare(
    "SELECT r.*, u.name AS requester
     FROM requests r
     JOIN users u ON u.id = r.receiver_id
     WHERE r.volunteer_id = ? AND r.status = 'delivered'
     ORDER BY r.created_at DESC LIMIT 50"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$done = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$page_title = 'My Deliveries';
$active     = 'deliveries';   // sidebar highlight key

require __DIR__ . '/includes/head.php';
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-6">
        <div>
          <h3 class="fb-mb-0">My Deliveries 📦</h3>
          <p class="fb-text-secondary fb-mb-0">Complete your accepted tasks and earn points.</p>
        </div>
        <a href="<?= url('tasks.php') ?>" class="fb-btn fb-btn-primary">
          <i data-lucide="list-todo"></i> Find More Tasks
        </a>
      </div>

      <?= render_flash() ?>

      <!-- ACTIVE deliveries -->
      <h4 class="fb-mb-4">Active Deliveries (<?= count($activeJobs) ?>)</h4>

      <?php if (!$activeJobs): ?>

        <div class="fb-panel fb-text-center fb-text-muted fb-mb-8" style="padding:40px 0;">
          <i data-lucide="package-open" style="width:44px;height:44px;"></i>
          <p class="fb-mb-3 fb-mt-3">No active deliveries right now.</p>
          <a href="<?= url('tasks.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="list-todo"></i> Accept a Task</a>
        </div>
      <?php else: ?>
        <div class="row g-4 fb-mb-8">
          <?php foreach ($activeJobs as $t): ?>
            <div class="col-lg-4 col-md-6">
              <div class="fb-card" style="height:100%;display:flex;flex-direction:column;">

                <div class="fb-flex fb-justify-between fb-items-center fb-mb-3">
                  <span class="fb-badge fb-badge-approved">
                    <i data-lucide="bike" style="width:14px;height:14px;"></i> In Progress
                  </span>
                  <span class="fb-list-meta"><?= time_ago($t['created_at']) ?></span>
                </div>

                <div class="fb-flex fb-items-center fb-gap-3 fb-mb-3">
                  <div class="fb-list-thumb"><i data-lucide="<?= food_type_icon($t['food_type']) ?>"></i></div>
                  <div>
                    <p class="fb-list-title fb-mb-0"><?= ucfirst($t['food_type']) ?> Food</p>
                    <p class="fb-list-sub fb-mb-0"><?= e($t['description']) ?></p>
                  </div>
                </div>

                <div class="fb-text-secondary fb-small" style="line-height:2;">
                  <div class="fb-flex fb-items-center fb-gap-2">
                    <i data-lucide="users" style="width:16px;height:16px;"></i> <?= (int) $t['people_count'] ?> people
                  </div>
                  <div class="fb-flex fb-items-center fb-gap-2">
                    <i data-lucide="map-pin" style="width:16px;height:16px;"></i> <?= e($t['location'] ?: 'Not given') ?>
                  </div>
                  <div class="fb-flex fb-items-center fb-gap-2">
                    <i data-lucide="user" style="width:16px;height:16px;"></i> <?= e($t['requester']) ?>
                  </div>
                  <div class="fb-flex fb-items-center fb-gap-2">
                    <i data-lucide="phone" style="width:16px;height:16px;"></i>
                    <a href="tel:<?= e($t['req_phone']) ?>" class="fb-text-primary fb-fw-600"><?= e($t['req_phone']) ?></a>
                  </div>
                </div>

                <?php
                  $dest = ($t['latitude'] !== null && $t['longitude'] !== null)
                        ? $t['latitude'] . ',' . $t['longitude']
                        : urlencode($t['location'] ?? '');
                ?>
                <div style="margin-top:auto;padding-top:16px;">
                  <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $dest ?>" target="_blank"
                     class="fb-btn fb-btn-secondary fb-mb-2" style="width:100%;">
                    <i data-lucide="navigation"></i> Get Directions
                  </a>
                  <form method="post" onsubmit="return confirm('Mark this delivery as complete?');">
                    <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                    <button type="submit" name="mark_delivered" class="fb-btn fb-btn-primary" style="width:100%;">
                      <i data-lucide="check-check"></i> Mark as Delivered
                    </button>
                  </form>
                </div>


              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- COMPLETED history -->
      <h4 class="fb-mb-4">Completed Deliveries</h4>

      <?php if (!$done): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:32px 0;">
          <i data-lucide="history" style="width:40px;height:40px;"></i>
          <p class="fb-mb-0 fb-mt-3">No completed deliveries yet.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel" style="padding:0;overflow:hidden;">
          <div class="table-responsive">
            <table class="fb-table" style="width:100%;margin:0;">
              <thead>
                <tr>
                  <th>Food</th>
                  <th>For</th>
                  <th>People</th>
                  <th>Status</th>
                  <th>When</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($done as $d): ?>
                  <tr>
                    <td>
                      <span class="fb-flex fb-items-center fb-gap-2">
                        <i data-lucide="<?= food_type_icon($d['food_type']) ?>" style="width:18px;height:18px;"></i>
                        <?= e($d['description']) ?>
                      </span>
                    </td>
                    <td><?= e($d['requester']) ?></td>
                    <td><?= (int) $d['people_count'] ?></td>
                    <td><span class="fb-badge fb-badge-success">Delivered</span></td>
                    <td class="fb-text-muted"><?= time_ago($d['created_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Mobile bottom tab bar -->
<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('tasks.php') ?>"><i data-lucide="list-todo"></i>Tasks</a>
  <a href="<?= url('my-deliveries.php') ?>" class="active"><i data-lucide="package-check"></i>Deliveries</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>