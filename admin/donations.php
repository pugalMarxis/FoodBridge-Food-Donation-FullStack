<?php
/**
 * FoodBridge — admin/donations.php
 * Admin views and manages all donated food. Can remove fake/bad posts (watchdog).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

/* ------------------------------------------------------------------ *
 *  Handle Remove (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $postId = (int) ($_POST['post_id'] ?? 0);

    if ($postId > 0) {
        // Grab the owner + name first (so we can notify them)
        $stmt = $conn->prepare('SELECT user_id, food_name FROM food_posts WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $post = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Delete the donation
        $stmt = $conn->prepare('DELETE FROM food_posts WHERE id = ?');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $removed = $stmt->affected_rows > 0;
        $stmt->close();

        if ($removed && $post) {
            $title = 'A donation was removed';
            $body  = 'Your donation "' . $post['food_name'] . '" was removed by the admin.';
            $stmt  = $conn->prepare(
                "INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'trash-2')"
            );
            $stmt->bind_param('iss', $post['user_id'], $title, $body);
            $stmt->execute();
            $stmt->close();

            set_flash('success', 'Donation removed successfully.');
        } else {
            set_flash('error', 'Could not remove that donation.');
        }
    }
    redirect('admin/donations.php');
}

/* ------------------------------------------------------------------ *
 *  Stats
 * ------------------------------------------------------------------ */
$available = scalar_count("SELECT COUNT(*) c FROM food_posts WHERE status = 'available'");
$claimed   = scalar_count("SELECT COUNT(*) c FROM food_posts WHERE status = 'claimed'");
$completed = scalar_count("SELECT COUNT(*) c FROM food_posts WHERE status = 'completed'");
$total     = scalar_count("SELECT COUNT(*) c FROM food_posts");

/* ------------------------------------------------------------------ *
 *  All donations (newest first)
 * ------------------------------------------------------------------ */
$rows = $conn->query(
    "SELECT fp.*, u.name AS donor
     FROM food_posts fp
     JOIN users u ON u.id = fp.user_id
     ORDER BY fp.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$active     = 'donations';
$page_title = 'Manage Donations';
require __DIR__ . '/../includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Manage Donations 🍱</h3>
        <p class="fb-text-secondary fb-mb-0">View all donated food. Remove any fake or wrong posts.</p>
      </div>

      <?= render_flash() ?>

      <!-- Stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Available</p><p class="fb-stat-value"><?= $available ?></p></div>
            <span class="fb-stat-icon green"><i data-lucide="package"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Claimed</p><p class="fb-stat-value"><?= $claimed ?></p></div>
            <span class="fb-stat-icon orange"><i data-lucide="hand"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Completed</p><p class="fb-stat-value"><?= $completed ?></p></div>
            <span class="fb-stat-icon blue"><i data-lucide="check-circle"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Donations</p><p class="fb-stat-value"><?= $total ?></p></div>
            <span class="fb-stat-icon purple"><i data-lucide="gift"></i></span>
          </div>
        </div>
      </div>

      <!-- Donations table -->
      <?php if (!$rows): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="package-open" style="width:44px;height:44px;"></i>
          <p class="fb-mb-0 fb-mt-3">No donations yet.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel" style="padding:0;overflow:hidden;">
          <div class="table-responsive">
            <table class="fb-table" style="width:100%;margin:0;">
              <thead>
                <tr>
                  <th>Donor</th>
                  <th>Food</th>
                  <th>Quantity</th>
                  <th>Pick-up</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="fb-text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td class="fb-fw-600"><?= e($r['donor']) ?></td>
                    <td>
                      <div class="fb-flex fb-items-center fb-gap-2">
                        <i data-lucide="<?= food_type_icon($r['food_type']) ?>" style="width:18px;height:18px;"></i>
                        <span><?= e($r['food_name']) ?></span>
                      </div>
                      <div class="fb-caption fb-text-muted"><?= ucfirst($r['food_type']) ?></div>
                    </td>
                    <td><?= e($r['quantity']) ?> <?= e($r['unit']) ?></td>
                    <td class="fb-text-muted fb-small">
                      <?= $r['pickup_date'] ? e(date('d M', strtotime($r['pickup_date']))) : '—' ?>
                      <?= $r['pickup_time'] ? e(date('g:i A', strtotime($r['pickup_time']))) : '' ?>
                    </td>
                    <td><span class="fb-badge <?= status_badge_class($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="fb-text-muted"><?= time_ago($r['created_at']) ?></td>
                    <td class="fb-text-right">
                      <form method="post" onsubmit="return confirm('Remove this donation? This cannot be undone.');">
                        <input type="hidden" name="post_id" value="<?= (int) $r['id'] ?>">
                        <button type="submit" name="action" value="delete"
                                class="fb-btn fb-btn-danger" style="padding:8px 12px;">
                          <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                        </button>
                      </form>
                    </td>
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

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>