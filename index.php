<?php
/**
 * FoodBridge — index.php
 * Public Home / Landing page: "Share Food. Share Hope."
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';
require_once __DIR__ . '/includes/logo.php';

$loggedIn = is_logged_in();
$donateLink  = $loggedIn ? 'donate.php'  : 'login.php';
$requestLink = $loggedIn ? 'request.php' : 'login.php';

// Live numbers for the stats bar
$t = admin_totals();
$donationsCount = max($t['donations'], 0);
$helpedCount    = max($t['people_helped'], 0);
$donorsCount    = max($t['active_donors'], 0);
$volunteerCount = max($t['volunteers'], 0);

$nav_active = 'home';
$page_title = 'Home';
require __DIR__ . '/includes/head.php';
?>
<body>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<!-- ===================== HERO ===================== -->
<section class="fb-container">
  <div class="fb-hero">

    <!-- Left text -->
    <div class="fb-anim-right">
      <span class="fb-glass-pill fb-mb-4"><i data-lucide="sparkles" style="width:16px;height:16px;"></i> Together, We Can Make a Difference</span>
      <h1>Share Food.<br><span class="fb-text-primary">Share Hope.</span></h1>
      <p class="fb-hero-sub">
        FoodBridge connects surplus food with people in need.
        Your small action can bring a big change.
      </p>
      <div class="fb-hero-actions">
        <a href="<?= url($donateLink) ?>" class="fb-btn fb-btn-primary fb-btn-lg"><i data-lucide="gift"></i> Donate Food</a>
        <a href="<?= url($requestLink) ?>" class="fb-btn fb-btn-secondary fb-btn-lg"><i data-lucide="hand-helping"></i> Request Food</a>
      </div>
    </div>

    <!-- Right visual: food box + floating badges -->
    <div class="fb-hero-visual fb-anim-scale">
      <div class="fb-hero-blob"></div>

      <div class="fb-float-badge top-left fb-float"><i data-lucide="salad" style="width:26px;height:26px;"></i></div>
      <div class="fb-float-badge bottom-left fb-float" style="animation-delay:1s;"><span style="font-size:22px;"><?= fb_logo(26) ?></span></div>
      <div class="fb-float-badge right fb-float" style="animation-delay:2s;color:var(--fb-info);"><i data-lucide="users" style="width:26px;height:26px;"></i></div>

      <div class="fb-hero-box-frame">
        <div style="background:linear-gradient(135deg,#FEF3C7,#D1FAE5);border-radius:14px;
                    height:280px;display:flex;align-items:center;justify-content:center;flex-direction:column;">
          <div style="font-size:110px;line-height:1;">📦</div>
          <div style="font-size:34px;margin-top:-10px;">🥦🍎🥖🥫</div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ===================== FEATURES ===================== -->
<section class="fb-container fb-section-sm" id="how">
  <div class="fb-grid fb-grid-3">
    <div class="fb-feature fb-card">
      <div class="fb-feature-icon"><i data-lucide="badge-check"></i></div>
      <h5>100% Free</h5>
      <p>It's completely free for everyone.</p>
    </div>
    <div class="fb-feature fb-card">
      <div class="fb-feature-icon"><i data-lucide="shield-check"></i></div>
      <h5>Safe & Secure</h5>
      <p>Your data and donations are always safe.</p>
    </div>
    <div class="fb-feature fb-card">
      <div class="fb-feature-icon"><i data-lucide="heart-handshake"></i></div>
      <h5>Help People</h5>
      <p>Make a real difference in someone's life.</p>
    </div>
  </div>
</section>

<!-- ===================== STATS BAR ===================== -->
<section class="fb-container" id="donations">
  <div class="fb-stats-bar">
    <div class="fb-statbar-item">
      <span class="fb-stat-icon green"><i data-lucide="gift"></i></span>
      <div><p class="fb-statbar-num"><?= number_format($donationsCount) ?>+</p><p class="fb-statbar-label">Donations</p></div>
    </div>
    <div class="fb-statbar-item">
      <span class="fb-stat-icon blue"><i data-lucide="users"></i></span>
      <div><p class="fb-statbar-num"><?= number_format($helpedCount) ?>+</p><p class="fb-statbar-label">People Helped</p></div>
    </div>
    <div class="fb-statbar-item">
      <span class="fb-stat-icon purple"><i data-lucide="heart"></i></span>
      <div><p class="fb-statbar-num"><?= number_format($donorsCount) ?>+</p><p class="fb-statbar-label">Donors</p></div>
    </div>
    <div class="fb-statbar-item">
      <span class="fb-stat-icon orange"><i data-lucide="bike"></i></span>
      <div><p class="fb-statbar-num"><?= number_format($volunteerCount) ?>+</p><p class="fb-statbar-label">Volunteers</p></div>
    </div>
  </div>
</section>

<!-- ===================== ABOUT ===================== -->
<section class="fb-container fb-section" id="about">
  <div class="row align-items-center g-5">
    <div class="col-lg-6">
      <h2>Why FoodBridge?</h2>
      <p class="fb-text-secondary">
        Every day good food is thrown away while many families sleep hungry.
        FoodBridge is a bridge between people who have extra food and people who need it.
      </p>
      <p class="fb-text-secondary">
        Restaurants, homes, and shops can share food in one minute. Families and shelters
        can ask for food easily. Volunteers help deliver it. Simple, fast, and kind.
      </p>
      <a href="<?= url('register.php') ?>" class="fb-btn fb-btn-primary fb-mt-2"><i data-lucide="user-plus"></i> Join FoodBridge</a>
    </div>
    <div class="col-lg-6">
      <div class="fb-neu-card fb-text-center" style="padding:48px;">
        <div style="font-size:80px;">🤝</div>
        <h4 class="fb-mt-4 fb-mb-0">"Relieving the hunger of the needy is the greatest virtue."</h4>
        <p class="fb-text-muted fb-mt-3 fb-mb-0">— Thirukkural 226</p>
      </div>
    </div>
  </div>
</section>

<!-- ===================== FOOTER ===================== -->
<footer class="fb-footer" id="contact">
  <div class="fb-container">
    <div class="fb-footer-grid">
      <div>
        <div class="fb-brand fb-mb-3"><span class="fb-logo-mark"><?= fb_logo(30) ?></span> FoodBridge</div>
        <p class="fb-text-secondary fb-small" style="max-width:280px;">
          Connecting surplus food with those who need it. Together, we can end hunger.
        </p>
      </div>
      <div>
        <h6>Quick Links</h6>
        <ul>
          <li><a href="<?= url('index.php') ?>">Home</a></li>
          <li><a href="<?= url('index.php#about') ?>">About Us</a></li>
          <li><a href="<?= url('index.php#how') ?>">How It Works</a></li>
        </ul>
      </div>
      <div>
        <h6>Get Started</h6>
        <ul>
          <li><a href="<?= url('register.php') ?>">Register</a></li>
          <li><a href="<?= url('login.php') ?>">Login</a></li>
          <li><a href="<?= url($donateLink) ?>">Donate Food</a></li>
        </ul>
      </div>
      <div>
        <h6>Contact</h6>
        <ul>
          <li class="fb-text-secondary fb-small">📧 help@foodbridge.lk</li>
          <li class="fb-text-secondary fb-small">📞 077 000 0000</li>
        </ul>
      </div>
    </div>
    <div class="fb-footer-bottom">
      &copy; <?= date('Y') ?> FoodBridge. Made with 💚 to end hunger.
    </div>
  </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
