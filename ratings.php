<?php
/**
 * FoodBridge — ratings.php
 * After a delivery, users rate each other (1–5 stars).
 *  - Receiver rates the volunteer who delivered.
 *  - Volunteer rates the receiver.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u    = current_user();
$uid  = (int) $u['id'];
$role = $u['role'];

/* ------------------------------------------------------------------ *
 *  Handle a new rating (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rating'])) {
    $requestId = (int) ($_POST['request_id'] ?? 0);
    $toUser    = (int) ($_POST['to_user'] ?? 0);
    $stars     = (int) ($_POST['stars'] ?? 0);
    $comment   = clean($_POST['comment'] ?? '');

    if ($stars < 1 || $stars > 5) {
        set_flash('error', 'Please choose 1 to 5 stars.');
        redirect('ratings.php');
    }

    // Stop double-rating the same delivery
    $stmt = $conn->prepare('SELECT id FROM ratings WHERE request_id = ? AND from_user = ? LIMIT 1');
    $stmt->bind_param('ii', $requestId, $uid);
    $stmt->execute();
    $already = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($already) {
        set_flash('error', 'You already rated this delivery.');
        redirect('ratings.php');
    }

    // Save the rating
    $stmt = $conn->prepare(
        'INSERT INTO ratings (from_user, to_user, request_id, stars, comment) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('iiiis', $uid, $toUser, $requestId, $stars, $comment);
    $stmt->execute();
    $stmt->close();

    // Update the rated person's average (only affects volunteers table if they are a volunteer)
    $stmt = $conn->prepare('SELECT ROUND(AVG(stars),1) a FROM ratings WHERE to_user = ?');
    $stmt->bind_param('i', $toUser);
    $stmt->execute();
    $avg = (float) ($stmt->get_result()->fetch_assoc()['a'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare('UPDATE volunteers SET rating = ? WHERE user_id = ?');
    $stmt->bind_param('di', $avg, $toUser);
    $stmt->execute();
    $stmt->close();

    // Notify the rated person
    $title = 'You received a ' . $stars . '-star rating ⭐';
    $body  = $u['name'] . ' rated your recent food exchange.';
    $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'star')");
    $stmt->bind_param('iss', $toUser, $title, $body);
    $stmt->execute();
    $stmt->close();

    set_flash('success', 'Thank you! Your rating was saved. ⭐');
    redirect('ratings.php');
}

/* ------------------------------------------------------------------ *
 *  Find deliveries this user can still rate
 * ------------------------------------------------------------------ */
$toRate = [];
if ($role === 'receiver') {
    $stmt = $conn->prepare(
        "SELECT r.id AS request_id, r.description, r.food_type, r.created_at,
                r.volunteer_id AS other_id, v.name AS other_name
         FROM requests r
         JOIN users v ON v.id = r.volunteer_id
         WHERE r.receiver_id = ? AND r.status = 'delivered' AND r.volunteer_id IS NOT NULL
           AND NOT EXISTS (SELECT 1 FROM ratings rt WHERE rt.request_id = r.id AND rt.from_user = ?)
         ORDER BY r.created_at DESC"
    );
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $toRate = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $rateWhoLabel = 'the volunteer who helped you';
} elseif ($role === 'volunteer') {
    $stmt = $conn->prepare(
        "SELECT r.id AS request_id, r.description, r.food_type, r.created_at,
                r.receiver_id AS other_id, rc.name AS other_name
         FROM requests r
         JOIN users rc ON rc.id = r.receiver_id
         WHERE r.volunteer_id = ? AND r.status = 'delivered'
           AND NOT EXISTS (SELECT 1 FROM ratings rt WHERE rt.request_id = r.id AND rt.from_user = ?)
         ORDER BY r.created_at DESC"
    );
    $stmt->bind_param('ii', $uid, $uid);
    $stmt->execute();
    $toRate = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $rateWhoLabel = 'the person you delivered to';
} else {
    $rateWhoLabel = '';
}

/* ------------------------------------------------------------------ *
 *  Ratings this user has already given
 * ------------------------------------------------------------------ */
$stmt = $conn->prepare(
    "SELECT rt.stars, rt.comment, rt.created_at, u.name AS to_name
     FROM ratings rt JOIN users u ON u.id = rt.to_user
     WHERE rt.from_user = ? ORDER BY rt.created_at DESC LIMIT 20"
);
$stmt->bind_param('i', $uid);
$stmt->execute();
$given = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$active     = 'ratings';
$page_title = 'Ratings';
require __DIR__ . '/includes/head.php';
?>
<body>
<!-- Star selector styles -->
<style>
  .fb-stars { display:inline-flex; flex-direction:row-reverse; }
  .fb-stars input { display:none; }
  .fb-stars label { font-size:30px; line-height:1; color:var(--fb-border); cursor:pointer; padding:0 3px; transition:color .15s; }
  .fb-stars label:hover,
  .fb-stars label:hover ~ label,
  .fb-stars input:checked ~ label { color:var(--fb-warning); }
</style>

<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Ratings ⭐</h3>
        <p class="fb-text-secondary fb-mb-0">Rate your food exchanges to build trust in the community.</p>
      </div>

      <?= render_flash() ?>

      <?php if ($role !== 'receiver' && $role !== 'volunteer'): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="star" style="width:44px;height:44px;"></i>
          <p class="fb-mb-0 fb-mt-3">Ratings are for receivers and volunteers after a delivery.</p>
        </div>
      <?php else: ?>

        <!-- Waiting to be rated -->
        <h4 class="fb-mb-4">Rate Your Deliveries</h4>
        <?php if (!$toRate): ?>
          <div class="fb-panel fb-text-center fb-text-muted fb-mb-8" style="padding:36px 0;">
            <i data-lucide="check-circle" style="width:40px;height:40px;"></i>
            <p class="fb-mb-0 fb-mt-3">Nothing to rate right now. Ratings appear here after a delivery is complete.</p>
          </div>
        <?php else: ?>
          <div class="row g-4 fb-mb-8">
            <?php foreach ($toRate as $item):
              $rid = (int) $item['request_id'];
            ?>
              <div class="col-lg-6">
                <div class="fb-card">
                  <div class="fb-flex fb-items-center fb-gap-3 fb-mb-3">
                    <div class="fb-list-thumb"><i data-lucide="<?= food_type_icon($item['food_type']) ?>"></i></div>
                    <div>
                      <p class="fb-list-title fb-mb-0"><?= e($item['other_name']) ?></p>
                      <p class="fb-list-sub fb-mb-0"><?= e($item['description']) ?></p>
                    </div>
                  </div>
                  <p class="fb-small fb-text-secondary fb-mb-2">How was <?= e($rateWhoLabel) ?>?</p>

                  <form method="post">
                    <input type="hidden" name="request_id" value="<?= $rid ?>">
                    <input type="hidden" name="to_user" value="<?= (int) $item['other_id'] ?>">

                    <!-- Star picker (5 → 1 for the CSS trick) -->
                    <div class="fb-stars fb-mb-3">
                      <?php for ($s = 5; $s >= 1; $s--): ?>
                        <input type="radio" id="s<?= $s ?>_<?= $rid ?>" name="stars" value="<?= $s ?>" required>
                        <label for="s<?= $s ?>_<?= $rid ?>">★</label>
                      <?php endfor; ?>
                    </div>

                    <input type="text" name="comment" class="fb-input fb-mb-3" maxlength="255"
                           placeholder="Add a short comment (optional)"
                           style="width:100%;height:44px;border:1px solid var(--fb-border);border-radius:12px;padding:0 12px;">

                    <button type="submit" name="submit_rating" class="fb-btn fb-btn-primary" style="width:100%;">
                      <i data-lucide="send"></i> Submit Rating
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>

      <!-- Ratings given -->
      <h4 class="fb-mb-4">Ratings You Gave</h4>
      <?php if (!$given): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:32px 0;">
          <i data-lucide="message-square" style="width:40px;height:40px;"></i>
          <p class="fb-mb-0 fb-mt-3">You haven't given any ratings yet.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel">
          <?php foreach ($given as $g): ?>
            <div class="fb-list-item">
              <div class="fb-list-body">
                <p class="fb-list-title fb-mb-0"><?= e($g['to_name']) ?></p>
                <p class="fb-list-sub fb-mb-0"><?= $g['comment'] ? e($g['comment']) : 'No comment' ?></p>
              </div>
              <div class="fb-text-right">
                <div style="color:var(--fb-warning);font-size:16px;letter-spacing:1px;">
                  <?= str_repeat('★', (int) $g['stars']) . str_repeat('☆', 5 - (int) $g['stars']) ?>
                </div>
                <p class="fb-list-meta fb-mb-0"><?= time_ago($g['created_at']) ?></p>
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