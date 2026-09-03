<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];

// Get all requests made by this user
$requests = recent_requests($uid, 100);

// Small helper: emoji for urgency
function urgency_label(string $urgency): string
{
    return [
        'urgent'  => '🔴 Very Urgent',
        'today'   => '🟡 Today',
        'anytime' => '🟢 Anytime',
    ][$urgency] ?? $urgency;
}

$active = 'myrequests';
$page_title = 'My Requests';
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
          <h3 class="fb-mb-0">My Requests 📥</h3>
          <p class="fb-text-secondary fb-mb-0">All the food you have asked for.</p>
        </div>
        <a href="<?= url('request.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="hand-helping"></i> Request Food</a>
      </div>

      <?= render_flash() ?>

      <?php if (!$requests): ?>
        <!-- Empty state -->
        <div class="fb-panel fb-text-center" style="padding:56px 24px;">
          <i data-lucide="inbox" style="width:56px;height:56px;color:var(--fb-text-muted);"></i>
          <h4 class="fb-mt-4 fb-mb-2">No requests yet</h4>
          <p class="fb-text-secondary fb-mb-6">Need food? Send your first request.</p>
          <a href="<?= url('request.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="plus-circle"></i> Request Now</a>
        </div>
      <?php else: ?>
        <!-- Requests table -->
        <div class="fb-table-wrap">
          <table class="fb-table">
            <thead>
              <tr>
                <th>What you need</th>
                <th>Type</th>
                <th>People</th>
                <th>Urgency</th>
                <th>Status</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($requests as $r): ?>
              <tr>
                <td>
                  <div class="fb-flex fb-items-center fb-gap-3">
                    <span class="fb-list-thumb" style="width:36px;height:36px;background:var(--fb-orange-soft);color:var(--fb-warning);">
                      <i data-lucide="hand-helping" style="width:18px;height:18px;"></i>
                    </span>
                    <span class="fb-fw-600"><?= e($r['description']) ?></span>
                  </div>
                </td>
                <td><?= ucfirst($r['food_type']) ?></td>
                <td><?= (int) $r['people_count'] ?></td>
                <td class="fb-small"><?= urgency_label($r['urgency']) ?></td>
                <td><span class="fb-badge <?= status_badge_class($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                <td class="fb-text-muted fb-small"><?= time_ago($r['created_at']) ?></td>
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
  <a href="<?= url('my-requests.php') ?>" class="active"><i data-lucide="inbox"></i>Requests</a>
  <a href="<?= url('request.php') ?>"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>