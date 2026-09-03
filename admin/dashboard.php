<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

$u = current_user();

$totals     = admin_totals();
$categories = donation_categories();
$timeline   = donations_timeline(14);
$donations  = recent_donations(null, 4);
$requests   = recent_requests(null, 3);

$active = 'dashboard';
$page_title = 'Admin Dashboard';
require __DIR__ . '/../includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Dashboard</h3>
        <p class="fb-text-secondary fb-mb-0">Welcome back, <?= e(explode(' ', $u['name'])[0]) ?>! 👋</p>
      </div>

      <?= render_flash() ?>

      <!-- Top stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <?php
          $cards = [
            ['Total Donations', $totals['donations'],     'gift',         'green',  '+12.5%'],
            ['Total Requests',  $totals['requests'],       'hand-helping', 'orange', '+8.4%'],
            ['People Helped',   $totals['people_helped'],  'users',        'blue',   '+15.2%'],
            ['Active Donors',   $totals['active_donors'],  'heart',        'purple', '+10.2%'],
          ];
          foreach ($cards as $i => $c):
        ?>
        <div class="fb-stat fb-anim-slide fb-delay-<?= $i + 1 ?>">
          <div class="fb-flex fb-justify-between fb-items-center fb-mb-2">
            <span class="fb-stat-icon <?= $c[3] ?>"><i data-lucide="<?= $c[2] ?>"></i></span>
            <span class="fb-stat-trend"><i data-lucide="trending-up" style="width:14px;height:14px;"></i> <?= $c[4] ?></span>
          </div>
          <p class="fb-stat-value"><?= number_format($c[1]) ?></p>
          <p class="fb-stat-label fb-mb-0"><?= $c[0] ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Charts row -->
      <div class="row g-4 fb-mb-2">
        <div class="col-lg-8">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Donation Overview</h4>
              <span class="fb-badge fb-badge-success">This Month</span>
            </div>
            <canvas id="donationChart" height="110"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Donation Categories</h4>
            </div>
            <div style="max-width:220px;margin:0 auto;">
              <canvas id="categoryChart"></canvas>
            </div>
            <ul class="fb-legend">
              <?php
                $colors = ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#9CA3AF'];
                $i = 0;
                $totalCat = array_sum($categories) ?: 1;
                foreach ($categories as $label => $count):
                  $pct = round($count / $totalCat * 100);
              ?>
                <li>
                  <span class="fb-dotc" style="background:<?= $colors[$i % 5] ?>;"></span>
                  <span class="fb-flex-1"><?= e($label) ?></span>
                  <span class="fb-fw-600"><?= $pct ?>%</span>
                </li>
              <?php $i++; endforeach; ?>
            </ul>
          </div>
        </div>
      </div>

      <!-- Tables row -->
      <div class="row g-4">
        <!-- Recent Donations -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Recent Donations</h4>
              <a href="<?= url('admin/donations.php') ?>" class="fb-view-all">View All</a>
            </div>
            <?php if (!$donations): ?>
              <p class="fb-text-muted fb-text-center" style="padding:24px 0;">No donations yet.</p>
            <?php else: foreach ($donations as $d): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb"><i data-lucide="<?= food_type_icon($d['food_type']) ?>"></i></div>
                <div class="fb-list-body">
                  <p class="fb-list-title"><?= e($d['food_name']) ?></p>
                  <p class="fb-list-sub"><?= e($d['quantity']) ?> <?= e($d['unit']) ?> &middot; <?= e($d['donor']) ?></p>
                </div>
                <span class="fb-list-meta"><?= time_ago($d['created_at']) ?></span>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- Recent Food Requests -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head">
              <h4>Recent Food Requests</h4>
              <a href="<?= url('admin/requests.php') ?>" class="fb-view-all">View All</a>
            </div>
            <?php if (!$requests): ?>
              <p class="fb-text-muted fb-text-center" style="padding:24px 0;">No requests yet.</p>
            <?php else: foreach ($requests as $r): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb" style="background:var(--fb-orange-soft);color:var(--fb-warning);">
                  <i data-lucide="hand-helping"></i>
                </div>
                <div class="fb-list-body">
                  <p class="fb-list-title"><?= ucfirst($r['food_type']) ?></p>
                  <p class="fb-list-sub"><?= e($r['description']) ?> &middot; <?= e($r['requester']) ?></p>
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

<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script>
  // ---- Donation Overview (line) ----
  const lineCtx = document.getElementById('donationChart');
  if (lineCtx) {
    new Chart(lineCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode($timeline['labels']) ?>,
        datasets: [{
          label: 'Donations',
          data: <?= json_encode($timeline['data']) ?>,
          borderColor: '#10B981',
          backgroundColor: 'rgba(16,185,129,0.12)',
          fill: true,
          tension: 0.4,
          pointRadius: 3,
          pointBackgroundColor: '#10B981',
          borderWidth: 3
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false } },
          y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F0F0F0' } }
        }
      }
    });
  }

  // ---- Donation Categories (doughnut) ----
  const donutCtx = document.getElementById('categoryChart');
  if (donutCtx) {
    new Chart(donutCtx, {
      type: 'doughnut',
      data: {
        labels: <?= json_encode(array_keys($categories)) ?>,
        datasets: [{
          data: <?= json_encode(array_values($categories)) ?>,
          backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#9CA3AF'],
          borderWidth: 0
        }]
      },
      options: {
        cutout: '70%',
        plugins: { legend: { display: false } }
      }
    });
  }
</script>
</body>
</html>