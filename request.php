<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (current_role() === 'admin') {
    redirect('admin/dashboard.php');
}

$u   = current_user();
$uid = (int) $u['id'];

$old = [
    'food_type' => 'cooked', 'description' => '', 'people' => '1',
    'location' => $u['location'] ?? '', 'urgency' => 'today',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foodType = $_POST['food_type'] ?? 'other';
    $desc     = clean($_POST['description'] ?? '');
    $people   = (int) ($_POST['people'] ?? 1);
    $location = clean($_POST['location'] ?? '');
    $urgency  = $_POST['urgency'] ?? 'today';

    $old = compact('foodType', 'desc', 'people', 'location', 'urgency');

    // Check the inputs
    $errors = [];
    $allowedType = ['cooked', 'groceries', 'vegetables', 'fruits', 'other'];
    $allowedUrg  = ['urgent', 'today', 'anytime'];
    if (!in_array($foodType, $allowedType, true)) $errors[] = 'Please choose a valid food type.';
    if ($desc === '')      $errors[] = 'Please tell us what food you need.';
    if ($people < 1)       $errors[] = 'People count must be at least 1.';
    if ($location === '')  $errors[] = 'Please enter your location.';
    if (!in_array($urgency, $allowedUrg, true)) $urgency = 'today';

        if (!$errors) {
        $stmt = $conn->prepare(
            'INSERT INTO requests (receiver_id, food_type, description, people_count, location, urgency, status)
             VALUES (?, ?, ?, ?, ?, ?, "pending")'
        );
        $stmt->bind_param('ississ', $uid, $foodType, $desc, $people, $location, $urgency);

        $stmt->execute();
        $stmt->close();

        // Confirmation notification
        $title = 'Food request sent';
        $body  = 'Your request is now pending. We will notify you when it is approved.';
        $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, "hand-helping")');
        $stmt->bind_param('iss', $uid, $title, $body);
        $stmt->execute();
        $stmt->close();

        set_flash('success', 'Your food request has been sent. 🙏');
        redirect('dashboard.php');
    } else {
        set_flash('error', implode(' ', $errors));
    }
}

$active = 'request';
$page_title = 'Request Food';
require __DIR__ . '/includes/head.php';

$types = [
    'cooked' => 'Cooked Food', 'groceries' => 'Groceries',
    'vegetables' => 'Vegetables', 'fruits' => 'Fruits', 'other' => 'Other',
];
?>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Request Food 🙏</h3>
        <p class="fb-text-secondary fb-mb-0">Tell us what you need. Nearby donors and volunteers will help.</p>
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
                <label class="fb-label" for="description">What do you need?</label>
                <input class="fb-input" type="text" id="description" name="description"
                       value="<?= e($old['description']) ?>" placeholder="e.g. Need cooked food for 5 people" required>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="people">How many people?</label>
                <input class="fb-input" type="number" id="people" name="people" min="1"
                       value="<?= e($old['people']) ?>" placeholder="e.g. 5" required>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="location">Your Location</label>
                <input class="fb-input" type="text" id="location" name="location"
                       value="<?= e($old['location']) ?>" placeholder="Enter your area / address" required>
              </div>

              <div class="fb-form-group">
                <label class="fb-label">How urgent?</label>
                <div class="fb-grid fb-grid-3" style="gap:12px;">
                  <?php
                    $urgs = [
                      'urgent'  => ['🔴', 'Very Urgent'],
                      'today'   => ['🟡', 'Today'],
                      'anytime' => ['🟢', 'Anytime'],
                    ];
                    foreach ($urgs as $val => $info):
                      $checked = ($old['urgency'] === $val) ? 'checked' : '';
                  ?>
                  <label style="cursor:pointer;">
                    <input type="radio" name="urgency" value="<?= $val ?>" <?= $checked ?> class="fb-urg-input" style="position:absolute;opacity:0;">
                    <div class="fb-card" style="text-align:center;padding:14px 8px;border:2px solid var(--fb-border);">
                      <div style="font-size:22px;"><?= $info[0] ?></div>
                      <div class="fb-fw-600" style="font-size:13px;margin-top:4px;"><?= $info[1] ?></div>
                    </div>
                  </label>
                  <?php endforeach; ?>
                </div>
              </div>

              <button type="submit" class="fb-btn fb-btn-primary fb-btn-block fb-btn-lg">
                <i data-lucide="send"></i> Send Request
              </button>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="fb-neu-card">
            <div class="fb-feature-icon" style="width:52px;height:52px;border-radius:12px;background:var(--fb-orange-soft);color:var(--fb-warning);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="info"></i>
            </div>
            <h4 class="fb-mt-4">How it works</h4>
            <ul class="fb-small fb-text-secondary" style="padding-left:18px;margin:0;">
              <li class="fb-mb-2">Fill this simple form.</li>
              <li class="fb-mb-2">Your request goes to the admin.</li>
              <li class="fb-mb-2">When approved, a donor or volunteer helps you.</li>
              <li>You get a notification at every step.</li>
            </ul>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('my-requests.php') ?>"><i data-lucide="inbox"></i>Requests</a>
  <a href="<?= url('request.php') ?>" class="active"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<style>
  .fb-urg-input:checked + .fb-card {
    border-color: var(--fb-primary) !important;
    background: var(--fb-primary-light);
  }
</style>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>