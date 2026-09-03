<?php
/**
 * FoodBridge — tasks.php
 * Volunteer "Available Tasks": see food requests that need delivery and accept one.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_role('volunteer');

$u   = current_user();
$uid = (int) $u['id'];

/* ------------------------------------------------------------------ *
 *  Handle "Accept Task" (POST) — assign this volunteer to a request
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_task'])) {
    $taskId = (int) ($_POST['task_id'] ?? 0);

    if ($taskId > 0) {
        // Claim only if still free (prevents two volunteers grabbing the same task)
        $stmt = $conn->prepare(
            "UPDATE requests
             SET volunteer_id = ?, status = 'assigned'
             WHERE id = ? AND volunteer_id IS NULL AND status IN ('pending','approved')"
        );
        $stmt->bind_param('ii', $uid, $taskId);
        $stmt->execute();
        $claimed = $stmt->affected_rows > 0;
        $stmt->close();

        if ($claimed) {
            // Notify the receiver that help is on the way
            $stmt = $conn->prepare('SELECT receiver_id FROM requests WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $taskId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                $title = 'A volunteer accepted your request';
                $body  = $u['name'] . ' is coming to help you. Call: ' . $u['phone'];

                $stmt  = $conn->prepare(
                    "INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'bike')"
                );
                $stmt->bind_param('iss', $row['receiver_id'], $title, $body);
                $stmt->execute();
                $stmt->close();
            }
            set_flash('success', 'Task accepted! Open "My Deliveries" to finish it. 🚴');
        } else {
            set_flash('error', 'Sorry, that task was already taken by someone else.');
        }
    }
    redirect('tasks.php');
}

/* ------------------------------------------------------------------ *
 *  Stats for the top cards
 * ------------------------------------------------------------------ */
$availCount = (int) $conn
    ->query("SELECT COUNT(*) c FROM requests WHERE volunteer_id IS NULL AND status IN ('pending','approved')")
    ->fetch_assoc()['c'];

$stmt = $conn->prepare("SELECT COUNT(*) c FROM requests WHERE volunteer_id = ? AND status = 'assigned'");
$stmt->bind_param('i', $uid);
$stmt->execute();
$activeCount = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

$stmt = $conn->prepare('SELECT points, rating, deliveries FROM volunteers WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $uid);
$stmt->execute();
$vp = $stmt->get_result()->fetch_assoc() ?: ['points' => 0, 'rating' => 0, 'deliveries' => 0];
$stmt->close();

/* ------------------------------------------------------------------ *
 *  Available task list (most urgent first)
 * ------------------------------------------------------------------ */
$stmt = $conn->prepare(
    "SELECT r.*, u.name AS requester
     FROM requests r
     JOIN users u ON u.id = r.receiver_id
     WHERE r.volunteer_id IS NULL AND r.status IN ('pending','approved')
     ORDER BY FIELD(r.urgency,'urgent','today','anytime'), r.created_at DESC"
);
$stmt->execute();
$tasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Urgency display: [label, badge-class, dot-color]
$urgencyMeta = [
    'urgent'  => ['Urgent',  'fb-badge-danger',   'var(--fb-danger)'],
    'today'   => ['Today',   'fb-badge-pending',  'var(--fb-warning)'],
    'anytime' => ['Anytime', 'fb-badge-success',  'var(--fb-success)'],
];

$active     = 'tasks';
$page_title = 'Available Tasks';
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
          <h3 class="fb-mb-0">Available Tasks 🚴</h3>
          <p class="fb-text-secondary fb-mb-0">Accept a task to deliver food to someone in need.</p>
        </div>
        <a href="<?= url('my-deliveries.php') ?>" class="fb-btn fb-btn-secondary">
          <i data-lucide="package-check"></i> My Deliveries
        </a>
      </div>

      <?= render_flash() ?>

      <!-- Stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat fb-anim-slide fb-delay-1">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">Available Tasks</p>
              <p class="fb-stat-value"><?= $availCount ?></p>
            </div>
            <span class="fb-stat-icon green"><i data-lucide="list-todo"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-2">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">My Active Deliveries</p>
              <p class="fb-stat-value"><?= $activeCount ?></p>
            </div>
            <span class="fb-stat-icon orange"><i data-lucide="bike"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-3">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">My Points</p>
              <p class="fb-stat-value"><?= (int) $vp['points'] ?></p>
            </div>
            <span class="fb-stat-icon purple"><i data-lucide="award"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-4">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">Deliveries Done</p>
              <p class="fb-stat-value"><?= (int) $vp['deliveries'] ?></p>
            </div>
            <span class="fb-stat-icon blue"><i data-lucide="check-circle"></i></span>
          </div>
        </div>
      </div>

      <!-- Task list -->
      <?php if (!$tasks): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:48px 0;">
          <i data-lucide="party-popper" style="width:48px;height:48px;"></i>
          <h4 class="fb-mt-4 fb-mb-1">No tasks right now</h4>
          <p class="fb-mb-0">Great job! There are no pending deliveries. Please check back later.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($tasks as $t):
            [$uLabel, $uBadge, $uDot] = $urgencyMeta[$t['urgency']] ?? $urgencyMeta['today'];
          ?>
            <div class="col-lg-4 col-md-6">
              <div class="fb-card" style="height:100%;display:flex;flex-direction:column;">

                <!-- Urgency + time -->
                <div class="fb-flex fb-justify-between fb-items-center fb-mb-3">
                  <span class="fb-badge <?= $uBadge ?>">
                    <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $uDot ?>;margin-right:6px;"></span>
                    <?= $uLabel ?>
                  </span>
                  <span class="fb-list-meta"><?= time_ago($t['created_at']) ?></span>
                </div>

                <!-- Food -->
                <div class="fb-flex fb-items-center fb-gap-3 fb-mb-3">
                  <div class="fb-list-thumb"><i data-lucide="<?= food_type_icon($t['food_type']) ?>"></i></div>
                  <div>
                    <p class="fb-list-title fb-mb-0"><?= ucfirst($t['food_type']) ?> Food</p>
                    <p class="fb-list-sub fb-mb-0"><?= e($t['description']) ?></p>
                  </div>
                </div>

                <!-- Details -->
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
                </div>

                <!-- Accept button -->
                <form method="post" style="margin-top:auto;padding-top:16px;">
                  <input type="hidden" name="task_id" value="<?= (int) $t['id'] ?>">
                  <button type="submit" name="accept_task" class="fb-btn fb-btn-primary" style="width:100%;">
                    <i data-lucide="check"></i> Accept Task
                  </button>
                </form>

              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<!-- Mobile bottom tab bar -->
<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('tasks.php') ?>" class="active"><i data-lucide="list-todo"></i>Tasks</a>
  <a href="<?= url('my-deliveries.php') ?>"><i data-lucide="package-check"></i>Deliveries</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>