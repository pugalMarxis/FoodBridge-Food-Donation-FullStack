<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/logo.php';

// Already logged in? Go to the right dashboard.
if (is_logged_in()) {
    redirect(dashboard_for(current_role()));
}

// Handle login submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        set_flash('error', 'Please enter both email and password.');
    } else {
        $user = attempt_login($email, $password);
        if ($user) {
            set_flash('success', 'Welcome back, ' . $user['name'] . '!');
            redirect(dashboard_for($user['role']));
        } else {
            set_flash('error', 'Invalid email or password.');
        }
    }
}

$page_title = 'Login';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="container-fluid">
  <div class="row min-vh-100">

    <!-- Left: image panel with green blob -->
    <div class="col-lg-6 d-none d-lg-flex align-items-center justify-content-center position-relative"
         style="background:linear-gradient(135deg,#ECFDF5,#F8F9FA);overflow:hidden;">
      <div style="position:absolute;width:520px;height:520px;background:var(--fb-primary-light);
                  border-radius:45% 55% 60% 40% / 50% 45% 55% 50%;filter:blur(4px);opacity:.7;"></div>
      <div class="text-center position-relative fb-anim-scale" style="z-index:1;padding:48px;">
        <div class="fb-float" style="font-size:120px;line-height:1;">🥗</div>
        <h2 class="fb-mt-6" style="font-size:30px;">Share Food. Share Hope.</h2>
        <p class="fb-text-secondary" style="max-width:360px;margin:0 auto;">
          Every meal you share brings a smile to someone in need.
        </p>
      </div>
    </div>

    <!-- Right: glass login card -->
    <div class="col-lg-6 d-flex align-items-center justify-content-center"
         style="background:var(--fb-bg);padding:32px;">
      <div class="fb-glass-card fb-anim-slide" style="width:100%;max-width:420px;">
        <div class="fb-flex fb-items-center fb-gap-2 fb-mb-6">
          <?= fb_logo(40) ?>
          <span style="font-family:var(--fb-font-heading);font-weight:800;font-size:22px;">FoodBridge</span>
        </div>

        <h3 style="font-size:26px;margin-bottom:4px;">Welcome Back! 👋</h3>
        <p class="fb-text-secondary fb-mb-6">Login to continue</p>

        <?= render_flash() ?>

        <form method="post" novalidate>
          <div class="fb-form-group">
            <label class="fb-label" for="email">Email Address</label>
            <input class="fb-input" type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="fb-form-group">
            <div class="fb-flex fb-justify-between fb-items-center">
              <label class="fb-label" for="password">Password</label>
              <a href="#" class="fb-small fb-text-primary fb-fw-600">Forgot Password?</a>
            </div>
            <input class="fb-input" type="password" id="password" name="password" placeholder="Enter your password" required>
          </div>

          <div class="fb-form-group fb-flex fb-items-center fb-gap-2">
            <input type="checkbox" id="remember" name="remember" style="width:16px;height:16px;">
            <label for="remember" class="fb-small" style="margin:0;">Remember me</label>
          </div>

          <button type="submit" class="fb-btn fb-btn-primary fb-btn-block fb-btn-lg fb-mt-2">Login</button>
        </form>

        <p class="fb-text-center fb-small fb-mt-6 fb-mb-0">
          Don't have an account?
          <a href="<?= url('register.php') ?>" class="fb-text-primary fb-fw-600">Register Now</a>
        </p>
      </div>
    </div>

  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>