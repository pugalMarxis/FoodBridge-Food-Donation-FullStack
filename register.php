<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/logo.php';

if (is_logged_in()) {
    redirect(dashboard_for(current_role()));
}

$old = ['name' => '', 'email' => '', 'phone' => '', 'location' => '', 'role' => 'giver'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean($_POST['name'] ?? '');
    $email    = clean($_POST['email'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');
    $location = clean($_POST['location'] ?? '');
    $role     = $_POST['role'] ?? 'giver';
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    $old = compact('name', 'email', 'phone', 'location', 'role');

    // Validation
    $errors = [];
    if ($name === '')                         $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($phone === '')                        $errors[] = 'Please enter your phone number.';
    if (strlen($password) < 6)                $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)               $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $newId = register_user($name, $email, $phone, $password, $role, $location);
        if ($newId === 0) {
            set_flash('error', 'An account with that email already exists.');
        } else {
            // Auto-login the new user
            attempt_login($email, $password);
            set_flash('success', 'Welcome to FoodBridge, ' . $name . '! Your account is ready.');
            redirect(dashboard_for($role));
        }
    } else {
        set_flash('error', implode(' ', $errors));
    }
}

$page_title = 'Register';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="container-fluid">
  <div class="row min-vh-100">

    <!-- Left: image / message panel -->
    <div class="col-lg-5 d-none d-lg-flex align-items-center justify-content-center position-relative"
         style="background:linear-gradient(135deg,#ECFDF5,#F8F9FA);overflow:hidden;">
      <div style="position:absolute;width:520px;height:520px;background:var(--fb-primary-light);
                  border-radius:55% 45% 40% 60% / 45% 55% 45% 55%;filter:blur(4px);opacity:.7;"></div>
      <div class="text-center position-relative fb-anim-scale" style="z-index:1;padding:48px;">
        <div class="fb-float" style="font-size:120px;line-height:1;">🤝</div>
        <h2 class="fb-mt-6" style="font-size:30px;">Join FoodBridge</h2>
        <p class="fb-text-secondary" style="max-width:340px;margin:0 auto;">
          Become a donor, a receiver, or a volunteer. Together we can end hunger.
        </p>
      </div>
    </div>

    <!-- Right: glass register card -->
    <div class="col-lg-7 d-flex align-items-center justify-content-center"
         style="background:var(--fb-bg);padding:32px;">
      <div class="fb-glass-card fb-anim-slide" style="width:100%;max-width:520px;">
        <div class="fb-flex fb-items-center fb-gap-2 fb-mb-4">
          <?= fb_logo(40) ?>
          <span style="font-family:var(--fb-font-heading);font-weight:800;font-size:22px;">FoodBridge</span>
        </div>

        <h3 style="font-size:26px;margin-bottom:4px;">Create Account ✨</h3>
        <p class="fb-text-secondary fb-mb-4">Choose your role and join the movement</p>

        <?= render_flash() ?>

        <form method="post" novalidate>

          <!-- Role selector cards -->
          <label class="fb-label">I want to join as</label>
          <div class="fb-grid fb-grid-3 fb-mb-4" style="gap:12px;">
            <?php
              $roles = [
                'giver'     => ['🍽️', 'Food Giver', 'I have food to share'],
                'receiver'  => ['🙏', 'Food Receiver', 'I need food'],
                'volunteer' => ['🚴', 'Volunteer', 'I can deliver food'],
              ];
              foreach ($roles as $key => $r):
                $checked = ($old['role'] === $key) ? 'checked' : '';
            ?>
            <label class="fb-role-card" style="cursor:pointer;">
              <input type="radio" name="role" value="<?= $key ?>" <?= $checked ?> class="fb-role-input" style="position:absolute;opacity:0;">
              <div class="fb-card" style="text-align:center;padding:16px 12px;border:2px solid var(--fb-border);">
                <div style="font-size:28px;"><?= $r[0] ?></div>
                <div class="fb-fw-600" style="font-size:14px;margin-top:6px;"><?= $r[1] ?></div>
                <div class="fb-caption"><?= $r[2] ?></div>
              </div>
            </label>
            <?php endforeach; ?>
          </div>

          <div class="row">
            <div class="col-md-6 fb-form-group">
              <label class="fb-label" for="name">Full Name</label>
              <input class="fb-input" type="text" id="name" name="name" value="<?= e($old['name']) ?>" placeholder="Your full name" required>
            </div>
            <div class="col-md-6 fb-form-group">
              <label class="fb-label" for="phone">Phone Number</label>
              <input class="fb-input" type="text" id="phone" name="phone" value="<?= e($old['phone']) ?>" placeholder="07X XXX XXXX" required>
            </div>
          </div>

          <div class="fb-form-group">
            <label class="fb-label" for="email">Email Address</label>
            <input class="fb-input" type="email" id="email" name="email" value="<?= e($old['email']) ?>" placeholder="you@example.com" required>
          </div>

          <div class="fb-form-group">
            <label class="fb-label" for="location">Location / Area</label>
            <input class="fb-input" type="text" id="location" name="location" value="<?= e($old['location']) ?>" placeholder="e.g. Jaffna, Colombo 03">
          </div>

          <div class="row">
            <div class="col-md-6 fb-form-group">
              <label class="fb-label" for="password">Password</label>
              <input class="fb-input" type="password" id="password" name="password" placeholder="At least 6 characters" required>
            </div>
            <div class="col-md-6 fb-form-group">
              <label class="fb-label" for="confirm">Confirm Password</label>
              <input class="fb-input" type="password" id="confirm" name="confirm" placeholder="Re-enter password" required>
            </div>
          </div>

          <button type="submit" class="fb-btn fb-btn-primary fb-btn-block fb-btn-lg fb-mt-2">Create Account</button>
        </form>

        <p class="fb-text-center fb-small fb-mt-6 fb-mb-0">
          Already have an account?
          <a href="<?= url('login.php') ?>" class="fb-text-primary fb-fw-600">Login</a>
        </p>
      </div>
    </div>

  </div>
</div>

<style>
  /* Highlight the selected role card */
  .fb-role-input:checked + .fb-card {
    border-color: var(--fb-primary) !important;
    background: var(--fb-primary-light);
    box-shadow: var(--fb-shadow-md);
  }
</style>

<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>