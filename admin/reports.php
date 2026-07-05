<?php
/**
 * FoodBridge — admin/reports.php
 * Admin reports: summary numbers, charts, and a print/download-PDF button.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

/* ------------------------------------------------------------------ *
 *  Data
 * ------------------------------------------------------------------ */
$t = admin_totals();

// Donations per day (last 14 days) — line chart
$timeline = donations_timeline(14);

// Donation categories — doughnut chart
$cats       = donation_categories();
$catLabels  = array_keys($cats);
$catValues  = array_values($cats);

// Requests by status — bar chart
$statusList = ['pending', 'approved', 'assigned', 'delivered', 'rejected'];
$reqByStatus = [];
foreach ($statusList as $s) {
    $stmt = $conn->prepare('SELECT COUNT(*) c FROM requests WHERE status = ?');
    $stmt->bind_param('s', $s);
    $stmt->execute();
    $reqByStatus[] = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
}

// Top donors (most donations)
$topDonors = $conn->query(
    "SELECT u.name, COUNT(*) c
     FROM food_posts fp JOIN users u ON u.id = fp.user_id
     GROUP BY fp.user_id ORDER BY c DESC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$active     = 'reports';
$page_title = 'Reports';
require __DIR__ . '/../includes/head.php';
?>
<body>
<!-- Print rules: hide sidebar + top bar when printing / saving as PDF -->
<style>
@media print {
  .fb-sidebar, .fb-topbar, .fb-tabbar, .fb-no-print { display: none !important; }
  .fb-main { margin: 0 !important; }
  .fb-content { padding: 0 !important; }
  body { background: #fff !important; }
}
</style>
<div class="fb-layout">

  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-6">
        <div>
          <h3 class="fb-mb-0">Reports &amp; Analytics 📊</h3>
          <p class="fb-text-secondary fb-mb-0">Overview of FoodBridge activity. Generated on <?= date('d M Y') ?>.</p>
        </div>
        <button onclick="window.print()" class="fb-btn fb-btn-primary fb-no-print">
          <i data-lucide="download"></i> Download / Print PDF
        </button>
      </div>

      <!-- Summary cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Donations</p><p class="fb-stat-value"><?= $t['donations'] ?></p></div>
            <span class="fb-stat-icon green"><i data-lucide="gift"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Requests</p><p class="fb-stat-value"><?= $t['requests'] ?></p></div>
            <span class="fb-stat-icon orange"><i data-lucide="hand-helping"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">People Helped</p><p class="fb-stat-value"><?= $t['people_helped'] ?></p></div>
            <span class="fb-stat-icon blue"><i data-lucide="users"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Volunteers</p><p class="fb-stat-value"><?= $t['volunteers'] ?></p></div>
            <span class="fb-stat-icon purple"><i data-lucide="bike"></i></span>
          </div>
        </div>
      </div>

      <!-- Charts row 1 -->
      <div class="row g-4 fb-mb-8">
        <div class="col-lg-8">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4>Donations — Last 14 Days</h4></div>
            <canvas id="lineChart" height="110"></canvas>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4>Donation Categories</h4></div>
            <canvas id="donutChart" height="180"></canvas>
          </div>
        </div>
      </div>

      <!-- Charts row 2 -->
      <div class="row g-4">
        <div class="col-lg-7">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4>Requests by Status</h4></div>
            <canvas id="barChart" height="130"></canvas>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4>Top Donors 🏆</h4></div>
            <?php if (!$topDonors): ?>
              <p class="fb-text-muted fb-text-center" style="padding:24px 0;">No donations yet.</p>
            <?php else: foreach ($topDonors as $i => $d): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb"><?= $i + 1 ?></div>
                <div class="fb-list-body">
                  <p class="fb-list-title fb-mb-0"><?= e($d['name']) ?></p>
                </div>
                <div class="fb-text-right">
                  <span class="fb-badge fb-badge-success"><?= (int) $d['c'] ?> donations</span>
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
<script>
  const GREEN = '#10B981';

  // Line chart — donations over time
  new Chart(document.getElementById('lineChart'), {
    type: 'line',
    data: {
      labels: <?= json_encode($timeline['labels']) ?>,
      datasets: [{
        label: 'Donations',
        data: <?= json_encode($timeline['data']) ?>,
        borderColor: GREEN,
        backgroundColor: 'rgba(16,185,129,.12)',
        fill: true, tension: .4, pointRadius: 3
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });

  // Doughnut — categories
  new Chart(document.getElementById('donutChart'), {
    type: 'doughnut',
    data: {
      labels: <?= json_encode($catLabels) ?>,
      datasets: [{
        data: <?= json_encode($catValues) ?>,
        backgroundColor: ['#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#9CA3AF']
      }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
  });

  // Bar — requests by status
  new Chart(document.getElementById('barChart'), {
    type: 'bar',
    data: {
      labels: ['Pending', 'Approved', 'Assigned', 'Delivered', 'Rejected'],
      datasets: [{
        label: 'Requests',
        data: <?= json_encode($reqByStatus) ?>,
        backgroundColor: ['#F59E0B', '#3B82F6', '#8B5CF6', '#22C55E', '#EF4444'],
        borderRadius: 8
      }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
  });

  lucide.createIcons();
</script>
</body>
</html>