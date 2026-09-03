<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];

// Handle "Remove donation" — only the owner's OWN, still-available donations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_donation'])) {
    $postId = (int) ($_POST['post_id'] ?? 0);
    $stmt = $conn->prepare("DELETE FROM food_posts WHERE id = ? AND user_id = ? AND status = 'available'");
    $stmt->bind_param('ii', $postId, $uid);
    $stmt->execute();
    $removed = $stmt->affected_rows > 0;
    $stmt->close();
    if ($removed) {
        set_flash('success', 'Donation removed. It no longer shows on the map.');
    } else {
        set_flash('error', 'Could not remove it (maybe someone already claimed it).');
    }
    redirect('my-donations.php');
}

// Handle "Mark as Given" — completes a direct pickup (no volunteer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_given'])) {
    $postId = (int) ($_POST['post_id'] ?? 0);
    $stmt = $conn->prepare("UPDATE food_posts SET status = 'completed' WHERE id = ? AND user_id = ? AND status = 'claimed'");
    $stmt->bind_param('ii', $postId, $uid);
    $stmt->execute();
    $ok = $stmt->affected_rows > 0;
    $stmt->close();

    if ($ok) {
        $stmt = $conn->prepare("UPDATE requests SET status = 'delivered' WHERE food_post_id = ? AND status IN ('pending','approved','assigned')");
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('SELECT receiver_id FROM requests WHERE food_post_id = ? LIMIT 1');
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $title = 'Food received 🎉';
            $body  = 'Enjoy your meal! Thank you for using FoodBridge.';
            $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'party-popper')");
            $stmt->bind_param('iss', $row['receiver_id'], $title, $body);
            $stmt->execute();
            $stmt->close();
        }
        set_flash('success', 'Thank you! This donation is now Completed. 🎉');
    } else {
        set_flash('error', 'Could not update that donation.');
    }
    redirect('my-donations.php');
}

// Food this user donated
$donations = recent_donations($uid, 100);

// People this user is helping (requests they took from the map)
$stmt = $conn->prepare(
    "SELECT r.*, u.name AS requester, u.phone AS req_phone
     FROM requests r JOIN users u ON u.id = r.receiver_id
     WHERE r.volunteer_id = ? AND r.status IN ('assigned','delivered')
     ORDER BY r.created_at DESC"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$helping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$active = 'mydonations';
$page_title = 'My Donations';
require __DIR__ . '/includes/head.php';
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-6">
        <div>
          <h3 class="fb-mb-0">My Donations ▪</h3>
          <p class="fb-text-secondary fb-mb-0">All the food you have shared.</p>
        </div>
        <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="gift"></i> Donate Food</a>
      </div>

      <?= render_flash() ?>

      <?php if (!$donations): ?>
        <div class="fb-panel fb-text-center" style="padding:56px 24px;">
          <i data-lucide="package-open" style="width:56px;height:56px;color:var(--fb-text-muted);"></i>
          <h4 class="fb-mt-4 fb-mb-2">No donations yet</h4>
          <p class="fb-text-secondary fb-mb-6">Share your first meal and help someone today.</p>
          <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="plus-circle"></i> Donate Now</a>
        </div>
      <?php else: ?>
        <div class="fb-table-wrap">
          <table class="fb-table">
            <thead>
              <tr>
                <th>Food</th>
                <th>Type</th>
                <th>Quantity</th>
                <th>Pick-up</th>
                <th>Status</th>
                <th>Date</th>
                <th class="fb-text-right">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($donations as $d): ?>
              <tr>
                <td>
                  <div class="fb-flex fb-items-center fb-gap-3">
                    <span class="fb-list-thumb" style="width:36px;height:36px;">
                      <i data-lucide="<?= food_type_icon($d['food_type']) ?>" style="width:18px;height:18px;"></i>
                    </span>
                    <span class="fb-fw-600"><?= e($d['food_name']) ?></span>
                  </div>
                </td>
                <td><?= ucfirst($d['food_type']) ?></td>
                <td><?= e($d['quantity']) ?> <?= e($d['unit']) ?></td>
                <td class="fb-text-secondary">
                  <?= $d['pickup_date'] ? e(date('d M', strtotime($d['pickup_date']))) : '—' ?>
                  <?= $d['pickup_time'] ? e(date('g:i A', strtotime($d['pickup_time']))) : '' ?>
                </td>
                <td><span class="fb-badge <?= status_badge_class($d['status']) ?>"><?= ucfirst($d['status']) ?></span></td>
                <td class="fb-text-muted fb-small"><?= time_ago($d['created_at']) ?></td>
                <td class="fb-text-right">
                  <?php if ($d['status'] === 'available'): ?>
                    <form method="post" onsubmit="return confirm('Remove this donation? It will be taken off the map.');">
                      <input type="hidden" name="post_id" value="<?= (int) $d['id'] ?>">
                      <button type="submit" name="remove_donation" class="fb-btn fb-btn-danger" style="padding:6px 12px;">
                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                      </button>
                    </form>
                  <?php elseif ($d['status'] === 'claimed'): ?>
                    <form method="post" onsubmit="return confirm('Mark this food as given to the person?');">
                      <input type="hidden" name="post_id" value="<?= (int) $d['id'] ?>">
                      <button type="submit" name="mark_given" class="fb-btn fb-btn-success" style="padding:6px 12px;">
                        <i data-lucide="check-check" style="width:14px;height:14px;"></i> Given
                      </button>
                    </form>
                  <?php else: ?>
                    <span class="fb-text-muted">—</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

      <!-- ============ People I'm Helping ============ -->
      <div class="fb-flex fb-items-center fb-gap-2 fb-mt-8 fb-mb-4">
        <i data-lucide="heart-handshake" style="color:var(--fb-primary);"></i>
        <h4 class="fb-mb-0">People I'm Helping (<?= count($helping) ?>)</h4>
      </div>

      <?php if (!$helping): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:32px 0;">
          <i data-lucide="hand-helping" style="width:40px;height:40px;"></i>
          <p class="fb-mb-0 fb-mt-3">You haven't helped anyone from the map yet. Open the <a href="<?= url('map.php') ?>">Live Map</a> and help a red pin. 💚</p>
        </div>
      <?php else: ?>
        <div class="fb-table-wrap">
          <table class="fb-table">
            <thead>
              <tr>
                <th>Person</th>
                <th>What they need</th>
                <th>People</th>
                <th>Call</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($helping as $h): ?>
              <tr>
                <td class="fb-fw-600"><?= e($h['requester']) ?></td>
                <td><?= e($h['description']) ?></td>
                <td><?= (int) $h['people_count'] ?></td>
                <td>
                  <a href="tel:<?= e($h['req_phone']) ?>" class="fb-btn fb-btn-secondary" style="padding:6px 12px;">
                    <i data-lucide="phone" style="width:14px;height:14px;"></i> <?= e($h['req_phone']) ?>
                  </a>
                </td>
                <td><span class="fb-badge <?= status_badge_class($h['status']) ?>"><?= ucfirst($h['status']) ?></span></td>
                <td class="fb-text-muted fb-small"><?= time_ago($h['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('my-donations.php') ?>" class="active"><i data-lucide="gift"></i>Donations</a>
  <a href="<?= url('donate.php') ?>"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
