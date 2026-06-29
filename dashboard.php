<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
// Admins use the dedicated admin dashboard
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];
$role = $u['role'];

// Greeting based on time of day
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

// Stat cards (numbers)
$myDonations = count_user_donations($uid);
$myRequests  = count_user_requests($uid);
$approved    = count_user_approved($uid);
$helped      = count_people_helped($uid);

$donations = recent_donations($uid, 4);
$requests  = recent_requests($uid, 4);

$active = 'dashboard';
$page_title = 'Dashboard';
require __DIR__ . '/includes/head.php';
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Greeting header -->
      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-6">
        <div>
          <h3 class="fb-mb-0"><?= e($greeting) ?>, <?= e(explode(' ', $u['name'])[0]) ?>! 👋</h3>
          <p class="fb-text-secondary fb-mb-0">Thank you for being a part of FoodBridge.</p>
        </div>
        <?php if ($role === 'receiver'): ?>
          <a href="<?= url('request.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="hand-helping"></i> Request Food</a>
        <?php else: ?>
          <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="gift"></i> Donate Food</a>
        <?php endif; ?>
      </div>

      <?= render_flash() ?>

      <!-- Stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat fb-anim-slide fb-delay-1">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">My Donations</p>
              <p class="fb-stat-value"><?= $myDonations ?></p>
            </div>
            <span class="fb-stat-icon green"><i data-lucide="gift"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-2">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">My Requests</p>
              <p class="fb-stat-value"><?= $myRequests ?></p>
            </div>
            <span class="fb-stat-icon orange"><i data-lucide="inbox"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-3">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">Requests Approved</p>
              <p class="fb-stat-value"><?= $approved ?></p>
            </div>
            <span class="fb-stat-icon blue"><i data-lucide="check-circle"></i></span>
          </div>
        </div>
        <div class="fb-stat fb-anim-slide fb-delay-4">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div>
              <p class="fb-stat-label">People Helped</p>
              <p class="fb-stat-value"><?= $helped ?></p>
            </div>
            <span class="fb-stat-icon purple"><i data-lucide="users"></i></span>
          </div>
        </div>
      </div>

      <!-- Recent activity -->
      <div class="row g-4">
        <!-- Recent Donations -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Recent Donations</h4>
              <a href="<?= url('my-donations.php') ?>" class="fb-view-all">View All</a>
            </div>
            <?php if (!$donations): ?>
              <div class="fb-text-center fb-text-muted" style="padding:32px 0;">
                <i data-lucide="package-open" style="width:40px;height:40px;"></i>
                <p class="fb-mb-0 fb-mt-3">No donations yet. Share your first meal!</p>
              </div>
            <?php else: foreach ($donations as $d): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb"><i data-lucide="<?= food_type_icon($d['food_type']) ?>"></i></div>
                <div class="fb-list-body">
                  <p class="fb-list-title"><?= e($d['food_name']) ?></p>
                  <p class="fb-list-sub"><?= e($d['quantity']) ?> <?= e($d['unit']) ?></p>
                </div>
                <div class="fb-text-right">
                  <span class="fb-badge <?= status_badge_class($d['status']) ?>"><?= ucfirst($d['status']) ?></span>
                  <p class="fb-list-meta fb-mt-2 fb-mb-0"><?= time_ago($d['created_at']) ?></p>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Recent Requests -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Recent Requests</h4>
              <a href="<?= url('my-requests.php') ?>" class="fb-view-all">View All</a>
            </div>
            <?php if (!$requests): ?>
              <div class="fb-text-center fb-text-muted" style="padding:32px 0;">
                <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                <p class="fb-mb-0 fb-mt-3">No requests yet.</p>
              </div>
            <?php else: foreach ($requests as $r): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb" style="background:var(--fb-orange-soft);color:var(--fb-warning);">
                  <i data-lucide="hand-helping"></i>
                </div>
                <div class="fb-list-body">
                  <p class="fb-list-title"><?= ucfirst($r['food_type']) ?></p>
                  <p class="fb-list-sub"><?= e($r['description']) ?></p>
                </div>
                <div class="fb-text-right">
                  <span class="fb-badge <?= status_badge_class($r['status']) ?>"><?= ucfirst($r['status']) ?></span>
                  <p class="fb-list-meta fb-mt-2 fb-mb-0"><?= time_ago($r['created_at']) ?></p>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<!-- Mobile bottom tab bar -->
<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>" class="active"><i data-lucide="home"></i>Home</a>
  <a href="<?= url($role === 'receiver' ? 'my-requests.php' : 'my-donations.php') ?>"><i data-lucide="gift"></i>Donations</a>
  <a href="<?= url($role === 'receiver' ? 'request.php' : 'donate.php') ?>"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>