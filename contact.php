<?php
/**
 * FoodBridge — contact.php
 * Public "Contact Us" page with contact info and a message form.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/logo.php';

// Handle the contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name    = clean($_POST['name'] ?? '');
    $email   = clean($_POST['email'] ?? '');
    $message = clean($_POST['message'] ?? '');

    $errors = [];
    if ($name === '')    $errors[] = 'Please enter your name.';
    if ($email === '')   $errors[] = 'Please enter your email.';
    if ($message === '') $errors[] = 'Please write a message.';

    if (!$errors) {
        set_flash('success', 'Thank you, ' . $name . '! Your message has been sent. We will reply soon. 💚');
    } else {
        set_flash('error', implode(' ', $errors));
    }
    redirect('contact.php');
}

$nav_active = 'contact';
$page_title = 'Contact Us';
require __DIR__ . '/includes/head.php';
?>
<body>

<?php require __DIR__ . '/includes/navbar.php'; ?>

<!-- Header -->
<section class="fb-container fb-section-sm fb-text-center">
  <span class="fb-glass-pill fb-mb-4"><i data-lucide="mail" style="width:16px;height:16px;"></i> We'd love to hear from you</span>
  <h1>Contact <span class="fb-text-primary">Us</span></h1>
  <p class="fb-hero-sub" style="margin:0 auto;">
    Questions, ideas, or want to help? Send us a message and we'll get back to you.
  </p>
</section>

<section class="fb-container fb-section-sm">
  <?= render_flash() ?>

  <div class="row g-4">

    <!-- Contact info -->
    <div class="col-lg-5">
      <div class="fb-neu-card" style="height:100%;">
        <h4 class="fb-mb-4">Get in touch</h4>

        <div class="fb-list-item">
          <div class="fb-list-thumb"><i data-lucide="mail"></i></div>
          <div class="fb-list-body">
            <p class="fb-list-title fb-mb-0">Email</p>
            <p class="fb-list-sub fb-mb-0">help@foodbridge.lk</p>
          </div>
        </div>

        <div class="fb-list-item">
          <div class="fb-list-thumb" style="background:var(--fb-blue-soft);color:var(--fb-info);"><i data-lucide="phone"></i></div>
          <div class="fb-list-body">
            <p class="fb-list-title fb-mb-0">Phone</p>
            <p class="fb-list-sub fb-mb-0">077 000 0000</p>
          </div>
        </div>

        <div class="fb-list-item">
          <div class="fb-list-thumb" style="background:var(--fb-orange-soft);color:var(--fb-warning);"><i data-lucide="map-pin"></i></div>
          <div class="fb-list-body">
            <p class="fb-list-title fb-mb-0">Address</p>
            <p class="fb-list-sub fb-mb-0">Colombo, Sri Lanka</p>
          </div>
        </div>

        <div class="fb-list-item">
          <div class="fb-list-thumb" style="background:var(--fb-primary-light);color:var(--fb-primary);"><i data-lucide="clock"></i></div>
          <div class="fb-list-body">
            <p class="fb-list-title fb-mb-0">Hours</p>
            <p class="fb-list-sub fb-mb-0">Every day, 8:00 AM – 8:00 PM</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Contact form -->
    <div class="col-lg-7">
      <div class="fb-panel">
        <h4 class="fb-mb-4">Send a message</h4>
        <form method="post">
          <div class="fb-form-group">
            <label class="fb-label" for="name">Your Name</label>
            <input class="fb-input" type="text" id="name" name="name" placeholder="Enter your name" required>
          </div>

          <div class="fb-form-group">
            <label class="fb-label" for="email">Your Email</label>
            <input class="fb-input" type="email" id="email" name="email" placeholder="Enter your email" required>
          </div>

          <div class="fb-form-group">
            <label class="fb-label" for="message">Message</label>
            <textarea class="fb-textarea" id="message" name="message" rows="5"
                      placeholder="Write your message here..." required></textarea>
          </div>

          <button type="submit" name="send_message" class="fb-btn fb-btn-primary fb-btn-lg">
            <i data-lucide="send"></i> Send Message
          </button>
        </form>
      </div>
    </div>

  </div>
</section>

<!-- Footer -->
<footer class="fb-footer">
  <div class="fb-container">
    <div class="fb-footer-bottom">
      &copy; <?= date('Y') ?> FoodBridge. Made with 💚 to end hunger.
    </div>
  </div>
</footer>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>