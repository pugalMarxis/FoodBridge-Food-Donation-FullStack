<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u   = current_user();
$uid = (int) $u['id'];

// ---- Handle form submits ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // (1) Update profile info
    if (isset($_POST['save_profile'])) {
        $name     = clean($_POST['name'] ?? '');
        $phone    = clean($_POST['phone'] ?? '');
        $location = clean($_POST['location'] ?? '');

        if ($name === '' || $phone === '') {
            set_flash('error', 'Name and phone cannot be empty.');
        } else {
            $stmt = $conn->prepare('UPDATE users SET name = ?, phone = ?, location = ? WHERE id = ?');
            $stmt->bind_param('sssi', $name, $phone, $location, $uid);
            $stmt->execute();
            $stmt->close();
            $_SESSION['name'] = $name;
            set_flash('success', 'Your profile has been updated. ✅');
        }
        redirect('profile.php');
    }

    // (2) Change password
    if (isset($_POST['save_password'])) {
        $current = $_POST['current'] ?? '';
        $new     = $_POST['new'] ?? '';
        $confirm = $_POST['confirm'] ?? '';

        if (!password_verify($current, $u['password_hash'])) {
            set_flash('error', 'Your current password is wrong.');
        } elseif (strlen($new) < 6) {
            set_flash('error', 'New password must be at least 6 characters.');
        } elseif ($new !== $confirm) {
            set_flash('error', 'New passwords do not match.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $stmt->bind_param('si', $hash, $uid);
            $stmt->execute();
            $stmt->close();
            set_flash('success', 'Your password has been changed. 🔐');
        }
        redirect('profile.php');
    }
}

$active = 'profile';
$page_title = 'Profile';
require __DIR__ . '/includes/head.php';
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">My Profile 👤</h3>
        <p class="fb-text-secondary fb-mb-0">See and update your information.</p>
      </div>

      <?= render_flash() ?>

      <div class="row g-4">

        <!-- Profile summary card (neumorphism) -->
        <div class="col-lg-4">
          <div class="fb-neu-card fb-text-center">
            <div class="fb-avatar fb-flex fb-items-center fb-justify-center"
                 style="width:90px;height:90px;font-size:34px;font-weight:700;color:var(--fb-primary);margin:0 auto 16px;">
              <?= e(strtoupper(substr($u['name'], 0, 1))) ?>
            </div>
            <h4 class="fb-mb-0"><?= e($u['name']) ?></h4>
            <p class="fb-text-secondary fb-mb-3"><?= e($u['email']) ?></p>
            <span class="fb-badge fb-badge-success"><?= ucfirst($u['role']) ?></span>

            <ul class="fb-small fb-text-secondary" style="list-style:none;padding:0;margin:20px 0 0;text-align:left;">
              <li class="fb-flex fb-items-center fb-gap-2 fb-mb-2"><i data-lucide="phone" style="width:16px;height:16px;"></i> <?= e($u['phone']) ?></li>
              <li class="fb-flex fb-items-center fb-gap-2 fb-mb-2"><i data-lucide="map-pin" style="width:16px;height:16px;"></i> <?= e($u['location'] ?: 'Not set') ?></li>
              <li class="fb-flex fb-items-center fb-gap-2"><i data-lucide="calendar" style="width:16px;height:16px;"></i> Joined <?= e(date('d M Y', strtotime($u['created_at']))) ?></li>
            </ul>
          </div>
        </div>

        <!-- Edit forms -->
        <div class="col-lg-8">

          <!-- Edit profile info -->
          <div class="fb-panel fb-mb-6">
            <h4 class="fb-mb-4">Edit Information</h4>
            <form method="post">
              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="name">Full Name</label>
                  <input class="fb-input" type="text" id="name" name="name" value="<?= e($u['name']) ?>" required>
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="phone">Phone Number</label>
                  <input class="fb-input" type="text" id="phone" name="phone" value="<?= e($u['phone']) ?>" required>
                </div>
              </div>
              <div class="fb-form-group">
                <label class="fb-label" for="email">Email (cannot change)</label>
                <input class="fb-input" type="email" id="email" value="<?= e($u['email']) ?>" disabled style="background:var(--fb-bg);">
              </div>
              <div class="fb-form-group">
                <label class="fb-label" for="location">Location / Area</label>
                <input class="fb-input" type="text" id="location" name="location" value="<?= e($u['location']) ?>" placeholder="e.g. Jaffna">
              </div>
              <button type="submit" name="save_profile" class="fb-btn fb-btn-primary"><i data-lucide="save"></i> Save Changes</button>
            </form>
          </div>

          <!-- Change password -->
          <div class="fb-panel">
            <h4 class="fb-mb-4">Change Password</h4>
            <form method="post">
              <div class="fb-form-group">
                <label class="fb-label" for="current">Current Password</label>
                <input class="fb-input" type="password" id="current" name="current" placeholder="Enter current password" required>
              </div>
              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="new">New Password</label>
                  <input class="fb-input" type="password" id="new" name="new" placeholder="At least 6 characters" required>
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="confirm">Confirm New Password</label>
                  <input class="fb-input" type="password" id="confirm" name="confirm" placeholder="Re-enter new password" required>
                </div>
              </div>
              <button type="submit" name="save_password" class="fb-btn fb-btn-secondary"><i data-lucide="lock"></i> Update Password</button>
            </form>
          </div>

        </div>
      </div>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('notifications.php') ?>"><i data-lucide="bell"></i>Alerts</a>
  <a href="<?= url('profile.php') ?>" class="active"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>