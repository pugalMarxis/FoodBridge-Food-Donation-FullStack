<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];
$role = $u['role'];

$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

/** Quick count helper (one integer parameter). */
function fb_count(string $sql, int $id): int
{
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $n = (int) $stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    return $n;
}

$helpingCount = fb_count("SELECT COUNT(*) c FROM requests WHERE volunteer_id = ? AND status IN ('assigned','delivered')", $uid);

// Role-smart stat cards: [label, value, lucide-icon, color]
$cards = [];
if ($role === 'giver') {
    $available = fb_count("SELECT COUNT(*) c FROM food_posts WHERE user_id = ? AND status = 'available'", $uid);
    $completed = fb_count("SELECT COUNT(*) c FROM food_posts WHERE user_id = ? AND status = 'completed'", $uid);
    $cards = [
        ['My Donations',       count_user_donations($uid), 'gift',            'green'],
        ["People I'm Helping", $helpingCount,               'heart-handshake', 'purple'],
        ['Available Now',      $available,                  'package',         'blue'],
        ['Completed',          $completed,                  'check-circle',    'orange'],
    ];
} elseif ($role === 'receiver') {
    $delivered = fb_count("SELECT COUNT(*) c FROM requests WHERE receiver_id = ? AND status = 'delivered'", $uid);
    $pending   = fb_count("SELECT COUNT(*) c FROM requests WHERE receiver_id = ? AND status = 'pending'", $uid);
    $cards = [
        ['My Requests', count_user_requests($uid), 'inbox',         'orange'],
        ['Approved',    count_user_approved($uid), 'check-circle',  'blue'],
        ['Delivered',   $delivered,                'package-check', 'green'],
        ['Pending',     $pending,                  'clock',         'purple'],
    ];
} else { // volunteer
    $activeDel = fb_count("SELECT COUNT(*) c FROM requests WHERE volunteer_id = ? AND status = 'assigned'", $uid);
    $doneDel   = fb_count("SELECT COUNT(*) c FROM requests WHERE volunteer_id = ? AND status = 'delivered'", $uid);
    $vp = ['points' => 0, 'rating' => 0];
    $stmt = $conn->prepare('SELECT points, rating FROM volunteers WHERE user_id = ? LIMIT 1');
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $vp = $stmt->get_result()->fetch_assoc() ?: ['points' => 0, 'rating' => 0];
    $stmt->close();
    $cards = [
        ['Active Deliveries', $activeDel,                              'bike',          'orange'],
        ['Completed',         $doneDel,                                'package-check', 'green'],
        ['My Points',         (int) $vp['points'],                     'award',         'purple'],
        ['My Rating',         number_format((float) $vp['rating'], 1), 'star',          'blue'],
    ];
}

$donations = recent_donations($uid, 4);
$requests  = recent_requests($uid, 4);

$active = 'dashboard';
$page_title = 'Dashboard';
require __DIR__ . '/includes/head.php';
?>
<style>
  .fb-help-banner{
    position:relative; overflow:hidden;
    background:linear-gradient(135deg, var(--fb-primary-light) 0%, #ffffff 72%);
    border:1px solid #D1FAE5; border-radius:18px; padding:24px 28px;
    box-shadow:0 12px 34px rgba(16,185,129,.12);
  }
  .fb-help-glow{
    position:absolute; top:-70px; right:-50px; width:220px; height:220px;
    background:radial-gradient(circle, rgba(16,185,129,.20), transparent 70%);
    border-radius:50%;
  }
  .fb-help-heart{
    width:62px; height:62px; border-radius:16px; flex:0 0 auto;
    display:flex; align-items:center; justify-content:center;
    background:#fff; color:var(--fb-primary);
    box-shadow:0 8px 20px rgba(16,185,129,.28);
    animation:fbHeartPulse 1.6s ease-in-out infinite;
  }
  .fb-help-heart i{ width:30px; height:30px; }
  .fb-count-big{ font-size:34px; font-weight:800; color:var(--fb-primary); }
  @keyframes fbHeartPulse{ 0%,100%{ transform:scale(1); } 50%{ transform:scale(1.12); } }
</style>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-6">
        <div>
          <h3 class="fb-mb-0"><?= e($greeting) ?>, <?= e(explode(' ', $u['name'])[0]) ?>! 👋</h3>
          <p class="fb-text-secondary fb-mb-0">Thank you for being a part of FoodBridge.</p>
        </div>
        <?php if ($role === 'receiver'): ?>
          <a href="<?= url('request.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="hand-helping"></i> Request Food</a>
        <?php elseif ($role === 'volunteer'): ?>
          <a href="<?= url('tasks.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="list-todo"></i> Find Tasks</a>
        <?php else: ?>
          <a href="<?= url('donate.php') ?>" class="fb-btn fb-btn-primary"><i data-lucide="gift"></i> Donate Food</a>
        <?php endif; ?>
      </div>

      <?= render_flash() ?>

      <div class="fb-grid fb-grid-4 fb-mb-8">
        <?php foreach ($cards as $i => $c): ?>
          <div class="fb-stat fb-anim-slide fb-delay-<?= $i + 1 ?>">
            <div class="fb-flex fb-justify-between fb-items-center">
              <div>
                <p class="fb-stat-label"><?= e($c[0]) ?></p>
                <p class="fb-stat-value"><?= e($c[1]) ?></p>
              </div>
              <span class="fb-stat-icon <?= e($c[3]) ?>"><i data-lucide="<?= e($c[2]) ?>"></i></span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($role === 'giver'): ?>
      <div class="fb-help-banner fb-anim-slide fb-mb-8">
        <div class="fb-help-glow"></div>
        <div class="fb-flex fb-items-center fb-justify-between fb-flex-wrap fb-gap-4" style="position:relative;z-index:1;">
          <div class="fb-flex fb-items-center fb-gap-4">
            <span class="fb-help-heart"><i data-lucide="heart-handshake"></i></span>
            <div>
              <p class="fb-mb-1" style="color:var(--fb-primary-hover);font-weight:600;letter-spacing:.4px;font-size:12px;">
                YOU ARE MAKING A DIFFERENCE
              </p>
              <h3 class="fb-mb-1">
                You are personally helping
                <span class="fb-count-big fb-count" data-target="<?= $helpingCount ?>">0</span>
                <?= $helpingCount === 1 ? 'person' : 'people' ?> 💚
              </h3>
              <p class="fb-text-secondary fb-mb-0">Thank you for your kindness. Every meal changes a life.</p>
            </div>
          </div>
          <a href="<?= url('map.php') ?>" class="fb-btn fb-btn-primary fb-btn-lg"><i data-lucide="map"></i> Help More</a>
        </div>
      </div>
      <?php endif; ?>

      <div class="row g-4">
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

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>" class="active"><i data-lucide="home"></i>Home</a>
  <a href="<?= url($role === 'receiver' ? 'my-requests.php' : 'my-donations.php') ?>"><i data-lucide="gift"></i>Donations</a>
  <a href="<?= url($role === 'receiver' ? 'request.php' : 'donate.php') ?>"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script>
  document.querySelectorAll('.fb-count').forEach(function (el) {
    const target = parseInt(el.dataset.target, 10) || 0;
    if (target === 0) { el.textContent = '0'; return; }
    let n = 0;
    const step = Math.max(1, Math.ceil(target / 30));
    const timer = setInterval(function () {
      n += step;
      if (n >= target) { n = target; clearInterval(timer); }
      el.textContent = n;
    }, 30);
  });
</script>
</body>
</html>
