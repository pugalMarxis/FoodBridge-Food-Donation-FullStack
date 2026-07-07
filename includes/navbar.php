<?php
/**
 * FoodBridge — navbar.php
 * Public top navigation (glassmorphism, sticky). Used on the Home page.
 * Set $nav_active (string) before including to highlight a link.
 */
require_once __DIR__ . '/logo.php';
$nav_active = $nav_active ?? 'home';
$loggedIn = is_logged_in();
?>
<nav class="fb-navbar">
  <div class="fb-container">
    <a href="<?= url('index.php') ?>" class="fb-brand">
      <span class="fb-logo-mark"><?= fb_logo(34) ?></span>
      FoodBridge
    </a>

    <ul class="fb-nav-links" id="fbNavLinks">
      <li><a href="<?= url('index.php') ?>" class="<?= $nav_active === 'home' ? 'active' : '' ?>">Home</a></li>
      <li><a href="<?= url('index.php#about') ?>">About Us</a></li>
      <li><a href="<?= url('index.php#how') ?>">How It Works</a></li>
      <li><a href="<?= url('index.php#donations') ?>">Donations</a></li>
      <li><a href="<?= url('index.phpcontact') ?>">Contact</a></li>
    </ul>

    <div class="fb-nav-actions">
      <?php if ($loggedIn): ?>
        <a href="<?= url(dashboard_for(current_role())) ?>" class="fb-btn fb-btn-primary fb-btn-sm">My Dashboard</a>
      <?php else: ?>
        <a href="<?= url('login.php') ?>" class="fb-btn fb-btn-secondary fb-btn-sm">Login</a>
        <a href="<?= url('register.php') ?>" class="fb-btn fb-btn-primary fb-btn-sm">Register</a>
      <?php endif; ?>
      <button class="fb-nav-toggle" id="fbNavToggle" aria-label="Open menu">
        <i data-lucide="menu"></i>
      </button>
    </div>
  </div>
</nav>