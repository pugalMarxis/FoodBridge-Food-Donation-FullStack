<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u   = current_user();
$uid = (int) $u['id'];

// Get all notifications for this user (newest first)
$stmt = $conn->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$stmt->bind_param('i', $uid);
$stmt->execute();
$notifications = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark all as read now that the user opened the page
$stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
$stmt->bind_param('i', $uid);
$stmt->execute();
$stmt->close();

$active = 'notifications';
$page_title = 'Notifications';
require __DIR__ . '/includes/head.php';
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Notifications 🔔</h3>
        <p class="fb-text-secondary fb-mb-0">All your latest alerts in one place.</p>
      </div>

      <?php if (!$notifications): ?>
        <div class="fb-panel fb-text-center" style="padding:56px 24px;">
          <i data-lucide="bell-off" style="width:56px;height:56px;color:var(--fb-text-muted);"></i>
          <h4 class="fb-mt-4 fb-mb-2">No notifications</h4>
          <p class="fb-text-secondary fb-mb-0">When something happens, you will see it here.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel">
          <?php foreach ($notifications as $n):
            $unread = ((int) $n['is_read'] === 0);
          ?>
          <div class="fb-list-item" style="<?= $unread ? 'background:var(--fb-primary-light);border-radius:12px;padding-left:12px;padding-right:12px;' : '' ?>">
            <div class="fb-list-thumb">
              <i data-lucide="<?= e($n['icon'] ?: 'bell') ?>"></i>
            </div>
            <div class="fb-list-body">
              <p class="fb-list-title">
                <?= e($n['title']) ?>
                <?php if ($unread): ?><span class="fb-dot" style="background:var(--fb-primary);width:8px;height:8px;min-width:8px;padding:0;margin-left:6px;vertical-align:middle;"></span><?php endif; ?>
              </p>
              <?php if (!empty($n['body'])): ?>
                <p class="fb-list-sub"><?= e($n['body']) ?></p>
              <?php endif; ?>
            </div>
            <span class="fb-list-meta"><?= time_ago($n['created_at']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('notifications.php') ?>" class="active"><i data-lucide="bell"></i>Alerts</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>