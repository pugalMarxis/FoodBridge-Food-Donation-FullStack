<?php
/**
 * FoodBridge — food-details.php
 * Full details of one donated food item. Receivers can claim it.
 * Open with:  food-details.php?id=123
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u    = current_user();
$uid  = (int) $u['id'];
$role = $u['role'];

/* ------------------------------------------------------------------ *
 *  Handle "Claim This Food" (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_food'])) {
    $postId = (int) ($_POST['post_id'] ?? 0);

    if ($role !== 'receiver') {
        set_flash('error', 'Only receivers can claim food.');
        redirect('food-details.php?id=' . $postId);
    }

    // Load the post
    $stmt = $conn->prepare('SELECT * FROM food_posts WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $post = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$post) {
        set_flash('error', 'That food was not found.');
        redirect('dashboard.php');
    }
    if ($post['user_id'] == $uid) {
        set_flash('error', 'You cannot claim your own donation.');
        redirect('food-details.php?id=' . $postId);
    }

    // Claim only if still available (safety lock)
    $stmt = $conn->prepare("UPDATE food_posts SET status = 'claimed' WHERE id = ? AND status = 'available'");
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $claimed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($claimed) {
        // Create a linked request so a volunteer can deliver it
        $desc = $post['food_name'];
        $loc  = $post['location'];
        $ftype = $post['food_type'];
        $stmt = $conn->prepare(
            "INSERT INTO requests (receiver_id, food_post_id, food_type, description, people_count, location, urgency, status)
             VALUES (?, ?, ?, ?, 1, ?, 'today', 'approved')"
        );
        $stmt->bind_param('iisss', $uid, $postId, $ftype, $desc, $loc);
        $stmt->execute();
        $stmt->close();

        // Notify the giver
        $title = 'Your donation was claimed 🎉';
        $body  = $u['name'] . ' claimed "' . $post['food_name'] . '". A volunteer can now deliver it.';
        $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'hand')");
        $stmt->bind_param('iss', $post['user_id'], $title, $body);
        $stmt->execute();
        $stmt->close();

        set_flash('success', 'You claimed this food! A volunteer will help deliver it. 🍱');
    } else {
        set_flash('error', 'Sorry, this food was already claimed.');
    }
    redirect('food-details.php?id=' . $postId);
}

/* ------------------------------------------------------------------ *
 *  Load the food item for viewing
 * ------------------------------------------------------------------ */
$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT fp.*, u.name AS donor, u.phone AS donor_phone
     FROM food_posts fp JOIN users u ON u.id = fp.user_id
     WHERE fp.id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$food = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$food) {
    set_flash('error', 'That food item was not found.');
    redirect('dashboard.php');
}

$isOwner    = ((int) $food['user_id'] === $uid);
$canClaim   = ($role === 'receiver' && $food['status'] === 'available' && !$isOwner);

$active     = '';
$page_title = 'Food Details';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Back -->
      <button onclick="history.back()" class="fb-btn fb-btn-secondary fb-mb-4" style="padding:8px 14px;">
        <i data-lucide="arrow-left"></i> Back
      </button>

      <?= render_flash() ?>

      <div class="row g-4">

        <!-- Left: photo / icon -->
        <div class="col-lg-5">
          <div class="fb-panel fb-text-center">
            <?php if (!empty($food['photo'])): ?>
              <img src="<?= url('assets/uploads/' . $food['photo']) ?>" alt="Food"
                   style="width:100%;border-radius:16px;object-fit:cover;max-height:320px;">
            <?php else: ?>
              <div style="background:linear-gradient(135deg,#FEF3C7,#D1FAE5);border-radius:16px;height:280px;
                          display:flex;align-items:center;justify-content:center;">
                <i data-lucide="<?= food_type_icon($food['food_type']) ?>" style="width:90px;height:90px;color:var(--fb-primary);"></i>
              </div>
            <?php endif; ?>

            <div class="fb-mt-4">
              <span class="fb-badge <?= status_badge_class($food['status']) ?>" style="font-size:14px;">
                <?= ucfirst($food['status']) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Right: details -->
        <div class="col-lg-7">
          <div class="fb-panel">
            <h3 class="fb-mb-1"><?= e($food['food_name']) ?></h3>
            <p class="fb-text-secondary fb-mb-4"><?= ucfirst($food['food_type']) ?> · posted <?= time_ago($food['created_at']) ?></p>

            <div class="fb-grid fb-grid-2 fb-mb-4" style="gap:16px;">
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Quantity</p>
                <p class="fb-fw-600 fb-mb-0"><i data-lucide="package" style="width:16px;height:16px;"></i> <?= e($food['quantity']) ?> <?= e($food['unit']) ?></p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Pick-up</p>
                <p class="fb-fw-600 fb-mb-0">
                  <i data-lucide="clock" style="width:16px;height:16px;"></i>
                  <?= $food['pickup_date'] ? e(date('d M', strtotime($food['pickup_date']))) : 'Anytime' ?>
                  <?= $food['pickup_time'] ? e(date('g:i A', strtotime($food['pickup_time']))) : '' ?>
                </p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Location</p>
                <p class="fb-fw-600 fb-mb-0"><i data-lucide="map-pin" style="width:16px;height:16px;"></i> <?= e($food['location'] ?: 'Not given') ?></p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Donor</p>
                <p class="fb-fw-600 fb-mb-0"><i data-lucide="user" style="width:16px;height:16px;"></i> <?= e($food['donor']) ?></p>
              </div>
            </div>

            <?php if (!empty($food['notes'])): ?>
              <div class="fb-mb-4">
                <p class="fb-caption fb-text-muted fb-mb-1">Notes</p>
                <p class="fb-mb-0"><?= e($food['notes']) ?></p>
              </div>
            <?php endif; ?>

            <!-- Actions -->
            <?php if ($canClaim): ?>
              <form method="post" onsubmit="return confirm('Claim this food?');">
                <input type="hidden" name="post_id" value="<?= (int) $food['id'] ?>">
                <button type="submit" name="claim_food" class="fb-btn fb-btn-primary fb-btn-lg" style="width:100%;">
                  <i data-lucide="hand-helping"></i> Claim This Food
                </button>
              </form>
            <?php elseif ($isOwner): ?>
              <div class="fb-panel" style="background:var(--fb-primary-light);border:none;">
                <p class="fb-mb-0 fb-small" style="color:var(--fb-primary-hover);">
                  <i data-lucide="info" style="width:16px;height:16px;"></i> This is your donation.
                </p>
              </div>
            <?php elseif ($food['status'] !== 'available'): ?>
              <div class="fb-panel fb-text-center fb-text-muted" style="padding:16px;">
                <i data-lucide="lock" style="width:20px;height:20px;"></i>
                This food is no longer available.
              </div>
            <?php else: ?>
              <p class="fb-text-muted fb-small fb-mb-0">Only receivers can claim food items.</p>
            <?php endif; ?>

          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>