<?php
/**
 * FoodBridge — sidebar.php
 * Role-aware dashboard sidebar. Set $active (string key) before including.
 * Requires: config.php, logo.php loaded; a logged-in user.
 */
require_once __DIR__ . '/logo.php';
$active = $active ?? '';
$role   = current_role();

/**
 * Menu definition per role.
 * Each item: [key, label, lucide-icon, href, badge(optional)]
 */
$menus = [
    'admin' => [
        ['dashboard', 'Dashboard',     'layout-dashboard', 'admin/dashboard.php'],
        ['donations', 'Donations',      'gift',             'admin/donations.php'],
        ['requests',  'Food Requests',  'hand-helping',     'admin/requests.php'],
        ['users',     'Users',          'users',            'admin/users.php'],
        ['messages',  'Messages',       'mail',             'admin/messages.php'],
        ['reports',   'Reports',        'bar-chart-3',      'admin/reports.php'],
        ['notifications', 'Notifications', 'bell',          'notifications.php'],
        ['profile',   'Profile',        'user',             'profile.php'],
        ['settings',  'Settings',       'settings',         'settings.php'],
    ],
    'giver' => [
        ['dashboard', 'Dashboard',    'layout-dashboard', 'dashboard.php'],
        ['donate',    'Donate Food',  'gift',             'donate.php'],
        ['mydonations','My Donations','package',          'my-donations.php'],
        ['map',       'Live Map',     'map',              'map.php'],
        ['notifications', 'Notifications', 'bell',        'notifications.php'],
        ['profile',   'Profile',      'user',             'profile.php'],
        ['settings',  'Settings',     'settings',         'settings.php'],
    ],
    'receiver' => [
        ['dashboard', 'Dashboard',    'layout-dashboard', 'dashboard.php'],
        ['request',   'Request Food', 'hand-helping',     'request.php'],
        ['browse',    'Browse Food',  'search',           'browse-food.php'],
        ['myrequests','My Requests',  'inbox',            'my-requests.php'],
        ['ratings',   'Ratings',      'star',             'ratings.php'],
        ['map',       'Live Map',     'map',              'map.php'],
        ['notifications', 'Notifications', 'bell',        'notifications.php'],
        ['profile',   'Profile',      'user',             'profile.php'],
        ['settings',  'Settings',     'settings',         'settings.php'],
    ],
    'volunteer' => [
        ['dashboard', 'Dashboard',       'layout-dashboard', 'dashboard.php'],
        ['tasks',     'Available Tasks', 'bike',             'tasks.php'],
        ['deliveries','My Deliveries',   'package-check',    'my-deliveries.php'],
        ['ratings',   'Ratings',       'star',             'ratings.php'],
        ['map',       'Live Map',        'map',              'map.php'],
        ['notifications', 'Notifications','bell',            'notifications.php'],
        ['profile',   'Profile',         'user',             'profile.php'],
        ['settings',  'Settings',        'settings',         'settings.php'],
    ],
];

$menu = $menus[$role] ?? $menus['giver'];
$unread = function_exists('unread_notifications') ? unread_notifications((int) $_SESSION['user_id']) : 0;
?>
<div class="fb-sidebar-backdrop" id="fbSidebarBackdrop"></div>
<aside class="fb-sidebar" id="fbSidebar">
  <a href="<?= url(dashboard_for($role)) ?>" class="fb-sidebar-brand">
    <?= fb_logo(34) ?>
    <span>FoodBridge</span>
  </a>

  <ul class="fb-menu">
    <?php foreach ($menu as $item): 
      [$key, $label, $icon, $href] = $item;
      $isActive = ($active === $key) ? 'active' : '';
      $showBadge = ($key === 'notifications' && $unread > 0);
    ?>
      <li>
        <a href="<?= url($href) ?>" class="<?= $isActive ?>">
          <i data-lucide="<?= $icon ?>"></i>
          <span><?= e($label) ?></span>
          <?php if ($showBadge): ?><span class="fb-dot"><?= $unread ?></span><?php endif; ?>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>

  <!-- Promo card -->
  <div class="fb-promo">
    <h5>🍱 Together we can<br>end hunger.</h5>
    <a href="<?= url($role === 'receiver' ? 'request.php' : 'donate.php') ?>">
      <?= $role === 'receiver' ? 'Request Now' : 'Donate Now' ?> &rarr;
    </a>
  </div>

  <a href="<?= url('php/logout.php') ?>" class="fb-menu" style="margin-top:16px;">
    <span style="display:flex;align-items:center;gap:12px;padding:12px;color:var(--fb-danger);font-weight:600;">
      <i data-lucide="log-out"></i> Logout
    </span>
  </a>
</aside>