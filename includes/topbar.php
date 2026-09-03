<?php
/**
 * FoodBridge — topbar.php
 * Dashboard top bar: mobile menu toggle, search, notifications, user chip.
 * Requires a logged-in user.
 */
$u = current_user();
$unread = function_exists('unread_notifications') ? unread_notifications((int) $_SESSION['user_id']) : 0;
$roleLabel = ucfirst($u['role'] ?? 'User');
?>
<header class="fb-topbar">
  <button class="fb-nav-toggle" id="fbSidebarToggle" aria-label="Open menu" style="display:inline-flex;">
    <i data-lucide="menu"></i>
  </button>

  <div class="fb-search fb-neu-search fb-flex fb-items-center fb-gap-2" style="padding:0 14px;height:44px;">
    <i data-lucide="search" style="width:18px;height:18px;color:var(--fb-text-muted);"></i>
    <input type="text" placeholder="Search anything..."
           style="border:none;background:transparent;outline:none;width:100%;font-size:14px;color:var(--fb-text);">
  </div>

  <div class="fb-flex fb-items-center fb-gap-4" style="margin-left:auto;">
    <a href="<?= url('notifications.php') ?>" class="fb-relative" aria-label="Notifications">
      <i data-lucide="bell" style="width:24px;height:24px;color:var(--fb-text-secondary);"></i>
      <?php if ($unread > 0): ?>
        <span class="fb-dot" style="position:absolute;top:-6px;right:-8px;"><?= $unread ?></span>
      <?php endif; ?>
    </a>

    <div class="fb-user-chip">
      <div class="fb-avatar fb-flex fb-items-center fb-justify-center" style="color:var(--fb-primary);font-weight:700;">
        <?= e(strtoupper(substr($u['name'] ?? 'U', 0, 1))) ?>
      </div>
      <div class="d-none d-sm-block">
        <div class="fb-fw-600" style="font-size:14px;line-height:1.1;"><?= e($u['name'] ?? 'User') ?></div>
        <div class="fb-caption"><?= e($roleLabel) ?></div>
      </div>
      <i data-lucide="chevron-down" style="width:18px;height:18px;color:var(--fb-text-muted);"></i>
    </div>
  </div>
</header>