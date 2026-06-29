<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
// Givers and volunteers can donate; receivers are redirected to request page
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];

$old = [
    'food_type' => 'cooked', 'food_name' => '', 'quantity' => '', 'unit' => '',
    'pickup_date' => '', 'pickup_time' => '', 'location' => '', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foodType  = $_POST['food_type'] ?? 'other';
    $foodName  = clean($_POST['food_name'] ?? '');
    $quantity  = clean($_POST['quantity'] ?? '');
    $unit      = clean($_POST['unit'] ?? '');
    $pickDate  = clean($_POST['pickup_date'] ?? '');
    $pickTime  = clean($_POST['pickup_time'] ?? '');
    $location  = clean($_POST['location'] ?? '');
    $notes     = clean($_POST['notes'] ?? '');

    $old = compact('foodType', 'foodName', 'quantity', 'unit', 'pickDate', 'pickTime', 'location', 'notes');

    // Validation
    $errors = [];
    $allowedTypes = ['cooked', 'groceries', 'vegetables', 'fruits', 'other'];
    if (!in_array($foodType, $allowedTypes, true)) $errors[] = 'Please choose a valid food type.';
    if ($foodName === '') $errors[] = 'Please enter the food name.';
    if ($quantity === '') $errors[] = 'Please enter the quantity.';
    if ($location === '') $errors[] = 'Please enter a pick-up address.';

    if (!$errors) {
        $pickDate = $pickDate !== '' ? $pickDate : null;
        $pickTime = $pickTime !== '' ? $pickTime : null;

        $stmt = $conn->prepare(
            'INSERT INTO food_posts (user_id, food_type, food_name, quantity, unit, location, pickup_date, pickup_time, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "available")'
        );
        $stmt->bind_param('issssssss', $uid, $foodType, $foodName, $quantity, $unit, $location, $pickDate, $pickTime, $notes);
        $stmt->execute();
        $stmt->close();

        // Notify the donor (confirmation)
        $title = 'Donation posted: ' . $foodName;
        $body  = 'Your food donation is now visible to people in need.';
        $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, "gift")');
        $stmt->bind_param('iss', $uid, $title, $body);
        $stmt->execute();
        $stmt->close();

        set_flash('success', 'Thank you! Your donation has been posted successfully. 🍱');
        redirect('dashboard.php');
    } else {
        set_flash('error', implode(' ', $errors));
    }
}

$active = 'donate';
$page_title = 'Donate Food';
require __DIR__ . '/includes/head.php';

$types = [
    'cooked'     => 'Cooked Food',
    'groceries'  => 'Groceries',
    'vegetables' => 'Vegetables',
    'fruits'     => 'Fruits',
    'other'      => 'Other',
];
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Donate Food 🍱</h3>
        <p class="fb-text-secondary fb-mb-0">Fill the information below to donate food.</p>
      </div>

      <?= render_flash() ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="fb-panel">
            <form method="post" novalidate>

              <div class="fb-form-group">
                <label class="fb-label" for="food_type">Food Type</label>
                <select class="fb-select" id="food_type" name="food_type">
                  <?php foreach ($types as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($old['food_type'] === $val) ? 'selected' : '' ?>><?= $label ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="food_name">Food Name</label>
                <input class="fb-input" type="text" id="food_name" name="food_name"
                       value="<?= e($old['food_name']) ?>" placeholder="e.g. Chicken Biryani, Rice & Dal" required>
              </div>

              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="quantity">Quantity</label>
                  <input class="fb-input" type="text" id="quantity" name="quantity"
                         value="<?= e($old['quantity']) ?>" placeholder="e.g. 10" required>
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="unit">Unit</label>
                  <input class="fb-input" type="text" id="unit" name="unit"
                         value="<?= e($old['unit']) ?>" placeholder="e.g. packs, kg, people">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="pickup_date">Pick-up Date</label>
                  <input class="fb-input" type="date" id="pickup_date" name="pickup_date" value="<?= e($old['pickup_date']) ?>">
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="pickup_time">Pick-up Time</label>
                  <input class="fb-input" type="time" id="pickup_time" name="pickup_time" value="<?= e($old['pickup_time']) ?>">
                </div>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="location">Address</label>
                <input class="fb-input" type="text" id="location" name="location"
                       value="<?= e($old['location']) ?>" placeholder="Enter full pick-up address" required>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="notes">Additional Notes (Optional)</label>
                <textarea class="fb-textarea" id="notes" name="notes"
                          placeholder="Any additional information (vegetarian, spicy, etc.)"><?= e($old['notes']) ?></textarea>
              </div>

              <button type="submit" class="fb-btn fb-btn-primary fb-btn-block fb-btn-lg">
                <i data-lucide="send"></i> Submit Donation
              </button>
            </form>
          </div>
        </div>

        <!-- Helper / tips card -->
        <div class="col-lg-4">
          <div class="fb-neu-card">
            <div class="fb-feature-icon" style="width:52px;height:52px;border-radius:12px;background:var(--fb-primary-light);color:var(--fb-primary);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="heart-handshake"></i>
            </div>
            <h4 class="fb-mt-4">Every meal counts</h4>
            <p class="fb-text-secondary fb-small">
              Your donation will be shown to nearby families and shelters in need.
              Please make sure the food is fresh and safe to eat.
            </p>
            <ul class="fb-small fb-text-secondary" style="padding-left:18px;margin:0;">
              <li class="fb-mb-2">Add a clear food name & quantity.</li>
              <li class="fb-mb-2">Give an accurate pick-up address.</li>
              <li>Set a realistic pick-up time window.</li>
            </ul>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('my-donations.php') ?>"><i data-lucide="gift"></i>Donations</a>
  <a href="<?= url('donate.php') ?>" class="active"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>