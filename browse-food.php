<?php
/**
 * FoodBridge — browse-food.php
 * Shows all AVAILABLE donated food as cards. Click a card to open full details.
 * Optional filter:  browse-food.php?type=cooked
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u = current_user();

// Filter by food type
$type    = $_GET['type'] ?? 'all';
$allowed = ['all', 'cooked', 'groceries', 'vegetables', 'fruits', 'other'];
if (!in_array($type, $allowed, true)) {
    $type = 'all';
}

if ($type === 'all') {
    $res = $conn->query(
        "SELECT fp.*, us.name AS donor
         FROM food_posts fp JOIN users us ON us.id = fp.user_id
         WHERE fp.status = 'available'
         ORDER BY fp.created_at DESC"
    );
    $foods = $res->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare(
        "SELECT fp.*, us.name AS donor
         FROM food_posts fp JOIN users us ON us.id = fp.user_id
         WHERE fp.status = 'available' AND fp.food_type = ?
         ORDER BY fp.created_at DESC"
    );
    $stmt->bind_param('s', $type);
    $stmt->execute();
    $foods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Filter chips
$chips = [
    'all'        => 'All',
    'cooked'     => 'Cooked',
    'groceries'  => 'Groceries',
    'vegetables' => 'Vegetables',
    'fruits'     => 'Fruits',
    'other'      => 'Other',
];

$active     = 'browse';
$page_title = 'Browse Food';
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
        <h3 class="fb-mb-0">Available Food 🍱</h3>
        <p class="fb-text-secondary fb-mb-0">Browse food shared by donors near you. Click one to see details and claim it.</p>
      </div>

      <?= render_flash() ?>

      <!-- Filter chips -->
      <div class="fb-flex fb-flex-wrap fb-gap-2 fb-mb-6">
        <?php foreach ($chips as $key => $label):
          $isOn = ($type === $key);
        ?>
          <a href="<?= url('browse-food.php?type=' . $key) ?>"
             class="fb-btn <?= $isOn ? 'fb-btn-primary' : 'fb-btn-secondary' ?>"
             style="padding:8px 16px;">
            <?= e($label) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Food grid -->
      <?php if (!$foods): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:48px 0;">
          <i data-lucide="package-open" style="width:48px;height:48px;"></i>
          <h4 class="fb-mt-4 fb-mb-1">No food available right now</h4>
          <p class="fb-mb-0">Please check back later. New donations appear here.</p>
        </div>
      <?php else: ?>
        <div class="row g-4">
          <?php foreach ($foods as $f): ?>
            <div class="col-lg-4 col-md-6">
              <div class="fb-card" style="height:100%;display:flex;flex-direction:column;">

                <!-- Image / icon -->
                <?php if (!empty($f['photo'])): ?>
                  <img src="<?= url('assets/uploads/' . $f['photo']) ?>" alt="Food"
                       style="width:100%;height:160px;object-fit:cover;border-radius:14px;margin-bottom:16px;">
                <?php else: ?>
                  <div style="background:linear-gradient(135deg,#FEF3C7,#D1FAE5);border-radius:14px;height:160px;
                              display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
                    <i data-lucide="<?= food_type_icon($f['food_type']) ?>" style="width:56px;height:56px;color:var(--fb-primary);"></i>
                  </div>
                <?php endif; ?>

                <div class="fb-flex fb-justify-between fb-items-center fb-mb-2">
                  <span class="fb-badge fb-badge-success"><?= ucfirst($f['food_type']) ?></span>
                  <span class="fb-list-meta"><?= time_ago($f['created_at']) ?></span>
                </div>

                <h5 class="fb-mb-1"><?= e($f['food_name']) ?></h5>
                <p class="fb-text-secondary fb-small fb-mb-3">
                  <i data-lucide="package" style="width:14px;height:14px;"></i> <?= e($f['quantity']) ?> <?= e($f['unit']) ?>
                  &nbsp;·&nbsp;
                  <i data-lucide="map-pin" style="width:14px;height:14px;"></i> <?= e($f['location'] ?: 'Nearby') ?>
                </p>

                <a href="<?= url('food-details.php?id=' . (int) $f['id']) ?>"
                   class="fb-btn fb-btn-primary" style="width:100%;margin-top:auto;">
                  <i data-lucide="eye"></i> View Details
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>