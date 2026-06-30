<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];

// Get all donations made by this user (reuse the recent query with a big limit)
$donations = recent_donations($uid, 100);

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
          <h3 class="fb-mb-0">My Donations 📋</h3>
          <p class="fb-text-secondary fb-mb-0">All the food you have shared.</p>
        </div>
        <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="gift"></i> Donate Food</a>
      </div>

      <?= render_flash() ?>

      <?php if (!$donations): ?>
        <!-- Empty state -->
        <div class="fb-panel fb-text-center" style="padding:56px 24px;">
          <i data-lucide="package-open" style="width:56px;height:56px;color:var(--fb-text-muted);"></i>
          <h4 class="fb-mt-4 fb-mb-2">No donations yet</h4>
          <p class="fb-text-secondary fb-mb-6">Share your first meal and help someone today.</p>
          <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="plus-circle"></i> Donate Now</a>
        </div>
      <?php else: ?>
        <!-- Donations table -->
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