<?php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $msgId = (int) ($_POST['msg_id'] ?? 0);
    if ($msgId > 0) {
        $stmt = $conn->prepare('DELETE FROM contact_messages WHERE id = ?');
        $stmt->bind_param('i', $msgId);
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Message deleted.');
    }
    redirect('admin/messages.php');
}

$rows = $conn->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);

$total  = count($rows);
$unread = 0;
foreach ($rows as $r) {
    if ((int) $r['is_read'] === 0) $unread++;
}
if ($unread > 0) {
    $conn->query('UPDATE contact_messages SET is_read = 1 WHERE is_read = 0');
}

$active     = 'messages';
$page_title = 'Messages';
require __DIR__ . '/../includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Messages 📬</h3>
        <p class="fb-text-secondary fb-mb-0">Messages sent from the Contact Us page.</p>
      </div>

      <?= render_flash() ?>

      <div class="fb-grid fb-grid-2 fb-mb-8" style="max-width:520px;">
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Messages</p><p class="fb-stat-value"><?= $total ?></p></div>
            <span class="fb-stat-icon blue"><i data-lucide="mail"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">New (unread)</p><p class="fb-stat-value"><?= $unread ?></p></div>
            <span class="fb-stat-icon orange"><i data-lucide="mail-open"></i></span>
          </div>
        </div>
      </div>

      <?php if (!$rows): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="inbox" style="width:44px;height:44px;"></i>
          <p class="fb-mb-0 fb-mt-3">No messages yet.</p>
        </div>
      <?php else: ?>
        <?php foreach ($rows as $m): ?>
          <div class="fb-panel fb-mb-3"
               style="<?= (int) $m['is_read'] === 0 ? 'border-left:4px solid var(--fb-primary);' : '' ?>">
            <div class="fb-flex fb-justify-between fb-items-start fb-gap-4">
              <div>
                <div class="fb-flex fb-items-center fb-gap-2 fb-mb-1">
                  <span class="fb-fw-600"><?= e($m['name']) ?></span>
                  <?php if ((int) $m['is_read'] === 0): ?>
                    <span class="fb-badge fb-badge-success">New</span>
                  <?php endif; ?>
                </div>
                <p class="fb-caption fb-text-muted fb-mb-2">
                  <i data-lucide="mail" style="width:12px;height:12px;"></i> <?= e($m['email']) ?>
                  &nbsp;·&nbsp; <?= time_ago($m['created_at']) ?>
                </p>
                <p class="fb-mb-0"><?= e($m['message']) ?></p>
              </div>
              <div class="fb-flex fb-items-center fb-gap-2">
                <a href="mailto:<?= e($m['email']) ?>" class="fb-btn fb-btn-secondary" style="padding:8px 12px;">
                  <i data-lucide="reply" style="width:16px;height:16px;"></i>
                </a>
                <form method="post" onsubmit="return confirm('Delete this message?');">
                  <input type="hidden" name="msg_id" value="<?= (int) $m['id'] ?>">
                  <button type="submit" name="action" value="delete" class="fb-btn fb-btn-danger" style="padding:8px 12px;">
                    <i data-lucide="trash-2" style="width:16px;height:16px;"></i>
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
