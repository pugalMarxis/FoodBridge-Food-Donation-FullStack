<?php
/**
 * FoodBridge — food-details.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u    = current_user();
$uid  = (int) $u['id'];
$role = $u['role'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_food'])) {
    $postId = (int) ($_POST['post_id'] ?? 0);
    // 'delivery' = needs a volunteer, 'self' = will collect it themselves
    $mode   = ($_POST['claim_food'] === 'delivery') ? 'delivery' : 'self';

    if ($role !== 'receiver') {
        set_flash('error', 'Only receivers can claim food.');
        redirect('food-details.php?id=' . $postId);
    }

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

    $stmt = $conn->prepare("UPDATE food_posts SET status = 'claimed' WHERE id = ? AND status = 'available'");
    $stmt->bind_param('i', $postId);
    $stmt->execute();
    $claimed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($claimed) {
        // "delivery" -> 'approved' (volunteers can pick up) | "self" -> 'assigned' (no volunteer needed)
        $desc  = $post['food_name'];
        $loc   = $post['location'];
        $ftype = $post['food_type'];
        $reqStatus = ($mode === 'delivery') ? 'approved' : 'assigned';
        $stmt = $conn->prepare(
            "INSERT INTO requests (receiver_id, food_post_id, food_type, description, people_count, location, urgency, status)
             VALUES (?, ?, ?, ?, 1, ?, 'today', ?)"
        );
        $stmt->bind_param('iissss', $uid, $postId, $ftype, $desc, $loc, $reqStatus);
        $stmt->execute();
        $stmt->close();

        $title = 'Your donation was claimed 🎉';
        $body  = $u['name'] . ' claimed "' . $post['food_name'] . '". Call them: ' . $u['phone'];
        $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'hand')");
        $stmt->bind_param('iss', $post['user_id'], $title, $body);
        $stmt->execute();
        $stmt->close();

        if ($mode === 'delivery') {
            set_flash('success', 'You claimed this food! A volunteer can now deliver it to you. 🚴');
        } else {
            set_flash('success', 'You claimed this food! Please call the donor and collect it yourself. 🍱');
        }
    } else {
        set_flash('error', 'Sorry, this food was already claimed.');
    }
    redirect('food-details.php?id=' . $postId);
}

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

$claimedByMe = false;
if ($role === 'receiver') {
    $stmt = $conn->prepare('SELECT id FROM requests WHERE food_post_id = ? AND receiver_id = ? LIMIT 1');
    $stmt->bind_param('ii', $id, $uid);
    $stmt->execute();
    $claimedByMe = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

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

      <button onclick="history.back()" class="fb-btn fb-btn-secondary fb-mb-4" style="padding:8px 14px;">
        <i data-lucide="arrow-left"></i> Back
      </button>

      <?= render_flash() ?>

      <div class="row g-4">

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

            <?php if ($canClaim): ?>
              <p class="fb-fw-600 fb-mb-1">Claim this food 🍱</p>
              <p class="fb-small fb-text-secondary fb-mb-3">How will you get it?</p>
              <form method="post">
                <input type="hidden" name="post_id" value="<?= (int) $food['id'] ?>">
                <div class="fb-grid fb-grid-2" style="gap:12px;">
                  <button type="submit" name="claim_food" value="self" class="fb-btn fb-btn-primary fb-btn-lg"
                          onclick="return confirm('Claim and collect it yourself? No volunteer will be requested.');">
                    <i data-lucide="hand-helping"></i> I'll Collect Myself
                  </button>
                  <button type="submit" name="claim_food" value="delivery" class="fb-btn fb-btn-secondary fb-btn-lg"
                          onclick="return confirm('Claim and request a volunteer to deliver it?');">
                    <i data-lucide="bike"></i> I Need Delivery
                  </button>
                </div>
              </form>
            <?php elseif ($isOwner): ?>
              <div class="fb-panel" style="background:var(--fb-primary-light);border:none;">
                <p class="fb-mb-0 fb-small" style="color:var(--fb-primary-hover);">
                  <i data-lucide="info" style="width:16px;height:16px;"></i> This is your donation.
                </p>
              </div>
            <?php elseif ($claimedByMe): ?>
              <div class="fb-panel" style="background:var(--fb-primary-light);border:none;">
                <p class="fb-fw-600 fb-mb-2" style="color:var(--fb-primary-hover);">
                  <i data-lucide="check-circle" style="width:18px;height:18px;"></i> You claimed this food!
                </p>
                <p class="fb-small fb-text-secondary fb-mb-3">Contact the donor to arrange pick-up:</p>

                  <div class="fb-flex fb-items-center fb-gap-3">
                  <a href="tel:<?= e($food['donor_phone']) ?>" class="fb-btn fb-btn-primary">
                    <i data-lucide="phone"></i> Call <?= e($food['donor']) ?>
                  </a>
                  <span class="fb-fw-600"><?= e($food['donor_phone']) ?></span>
                </div>
                <?php
                  $gdest = ($food['latitude'] !== null && $food['longitude'] !== null)
                         ? $food['latitude'] . ',' . $food['longitude']
                         : urlencode($food['location'] ?? '');
                ?>
                <a href="https://www.google.com/maps/dir/?api=1&destination=<?= $gdest ?>" target="_blank"
                   class="fb-btn fb-btn-secondary fb-mt-3" style="width:100%;">
                  <i data-lucide="navigation"></i> Get Directions to Donor
                </a>
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
