<?php
/**
 * FoodBridge — settings.php
 * User settings: change language and view account options. Works for all roles.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u   = current_user();
$uid = (int) $u['id'];

$languages = [
    'en' => 'English',
    'ta' => 'தமிழ் (Tamil)',
    'si' => 'සිංහල (Sinhala)',
];

/* ------------------------------------------------------------------ *
 *  Handle language change (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_language'])) {
    $lang = $_POST['language'] ?? 'en';
    if (!array_key_exists($lang, $languages)) {
        $lang = 'en';
    }
    $stmt = $conn->prepare('UPDATE users SET language = ? WHERE id = ?');
    $stmt->bind_param('si', $lang, $uid);
    $stmt->execute();
    $stmt->close();

    $_SESSION['language'] = $lang;
    set_flash('success', 'Your language was updated.');
    redirect('settings.php');
}

$currentLang = $u['language'] ?? 'en';

$active     = 'settings';
$page_title = 'Settings';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Settings ⚙️</h3>
        <p class="fb-text-secondary fb-mb-0">Manage your language and account options.</p>
      </div>

      <?= render_flash() ?>

      <div class="row g-4">

        <!-- Language -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4><i data-lucide="languages"></i> Language</h4></div>
            <p class="fb-text-secondary fb-small">Choose the language you are most comfortable with.</p>

            <form method="post">
              <?php foreach ($languages as $code => $label): ?>
                <label class="fb-flex fb-items-center fb-gap-3 fb-mb-3"
                       style="padding:14px 16px;border:2px solid <?= $currentLang === $code ? 'var(--fb-primary)' : 'var(--fb-border)' ?>;
                              border-radius:12px;cursor:pointer;">
                  <input type="radio" name="language" value="<?= $code ?>" <?= $currentLang === $code ? 'checked' : '' ?>>
                  <span class="fb-fw-600"><?= e($label) ?></span>
                </label>
              <?php endforeach; ?>

              <button type="submit" name="save_language" class="fb-btn fb-btn-primary fb-mt-2">
                <i data-lucide="check"></i> Save Language
              </button>
            </form>
          </div>
        </div>

        <!-- Account options -->
        <div class="col-lg-6">
          <div class="fb-panel">
            <div class="fb-panel-head"><h4><i data-lucide="user-cog"></i> Account</h4></div>

            <div class="fb-list-item">
              <div class="fb-list-thumb"><i data-lucide="user"></i></div>
              <div class="fb-list-body">
                <p class="fb-list-title fb-mb-0">Edit Profile</p>
                <p class="fb-list-sub fb-mb-0">Change your name, phone, and location</p>
              </div>
              <a href="<?= url('profile.php') ?>" class="fb-btn fb-btn-secondary" style="padding:8px 14px;">Open</a>
            </div>

            <div class="fb-list-item">
              <div class="fb-list-thumb" style="background:var(--fb-blue-soft);color:var(--fb-info);"><i data-lucide="lock"></i></div>
              <div class="fb-list-body">
                <p class="fb-list-title fb-mb-0">Change Password</p>
                <p class="fb-list-sub fb-mb-0">Update your login password</p>
              </div>
              <a href="<?= url('profile.php') ?>" class="fb-btn fb-btn-secondary" style="padding:8px 14px;">Open</a>
            </div>

            <div class="fb-list-item">
              <div class="fb-list-thumb" style="background:#FEE2E2;color:var(--fb-danger);"><i data-lucide="log-out"></i></div>
              <div class="fb-list-body">
                <p class="fb-list-title fb-mb-0">Log Out</p>
                <p class="fb-list-sub fb-mb-0">Sign out of your account</p>
              </div>
              <a href="<?= url('php/logout.php') ?>" class="fb-btn fb-btn-danger" style="padding:8px 14px;">Log Out</a>
            </div>
          </div>
        </div>

      </div>

      <!-- Note -->
      <div class="fb-panel fb-mt-4" style="background:var(--fb-primary-light);border:none;">
        <p class="fb-mb-0 fb-small" style="color:var(--fb-primary-hover);">
          <i data-lucide="info" style="width:16px;height:16px;"></i>
          Your language choice is saved to your account. Full Tamil and Sinhala page translation can be added later.
        </p>
      </div>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>