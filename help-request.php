<?php
/**
 * FoodBridge — help-request.php
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$u   = current_user();
$uid = (int) $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['offer_help'])) {
    $reqId = (int) ($_POST['request_id'] ?? 0);

    // Take the request ONLY if still open -> status 'assigned' removes it from the map
    $stmt = $conn->prepare(
        "UPDATE requests
         SET status = 'assigned', volunteer_id = ?
         WHERE id = ? AND status IN ('pending','approved')"
    );
    $stmt->bind_param('ii', $uid, $reqId);
    $stmt->execute();
    $claimed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($claimed) {
        $stmt = $conn->prepare('SELECT receiver_id FROM requests WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $reqId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($row) {
            $title = 'A donor is helping you! 💚';
            $body  = $u['name'] . ' will give you food. Call: ' . $u['phone'];
            $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'heart-handshake')");
            $stmt->bind_param('iss', $row['receiver_id'], $title, $body);
            $stmt->execute();
            $stmt->close();
        }
        set_flash('success', 'Thank you! You are now helping this person. They are removed from the map so no one else takes it. Please call them. 💚');
    } else {
        set_flash('error', 'Sorry, someone is already helping this person.');
    }
    redirect('help-request.php?id=' . $reqId);
}

$id = (int) ($_GET['id'] ?? 0);
$stmt = $conn->prepare(
    'SELECT r.*, u.name AS requester, u.phone AS req_phone
     FROM requests r JOIN users u ON u.id = r.receiver_id
     WHERE r.id = ? LIMIT 1'
);
$stmt->bind_param('i', $id);
$stmt->execute();
$req = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$req) {
    set_flash('error', 'That request was not found.');
    redirect('map.php');
}

$isOpen    = in_array($req['status'], ['pending', 'approved'], true);
$iAmHelper = ((int) $req['volunteer_id'] === $uid);

$urgencyMeta = [
    'urgent'  => ['Very Urgent', 'var(--fb-danger)'],
    'today'   => ['Today',       'var(--fb-warning)'],
    'anytime' => ['Anytime',     'var(--fb-success)'],
];
[$uLabel, $uDot] = $urgencyMeta[$req['urgency']] ?? $urgencyMeta['today'];

$active     = 'map';
$page_title = 'Help a Person';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <button onclick="history.back()" class="fb-btn fb-btn-secondary fb-mb-4" style="padding:8px 14px;">
        <i data-lucide="arrow-left"></i> Back to Map
      </button>

      <?= render_flash() ?>

      <div class="row g-4">
        <div class="col-lg-7">
          <div class="fb-panel">
            <div class="fb-flex fb-items-center fb-gap-3 fb-mb-4">
              <div class="fb-list-thumb" style="background:#FEE2E2;color:var(--fb-danger);width:56px;height:56px;">
                <i data-lucide="hand-helping" style="width:28px;height:28px;"></i>
              </div>
              <div>
                <h3 class="fb-mb-0">Someone needs food 🙏</h3>
                <p class="fb-text-secondary fb-mb-0">Posted <?= time_ago($req['created_at']) ?></p>
              </div>
            </div>

            <div class="fb-grid fb-grid-2" style="gap:16px;">
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">What they need</p>
                <p class="fb-fw-600 fb-mb-0"><?= e($req['description']) ?></p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Food type</p>
                <p class="fb-fw-600 fb-mb-0"><?= ucfirst($req['food_type']) ?></p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">People</p>
                <p class="fb-fw-600 fb-mb-0"><i data-lucide="users" style="width:16px;height:16px;"></i> <?= (int) $req['people_count'] ?></p>
              </div>
              <div class="fb-neu-card" style="padding:16px;">
                <p class="fb-caption fb-text-muted fb-mb-1">Urgency</p>
                <p class="fb-fw-600 fb-mb-0">
                  <span style="display:inline-block;width:9px;height:9px;border-radius:50%;background:<?= $uDot ?>;"></span>
                  <?= $uLabel ?>
                </p>
              </div>
            </div>

            <div class="fb-mt-4">
              <p class="fb-caption fb-text-muted fb-mb-1">Location</p>
              <p class="fb-mb-0"><i data-lucide="map-pin" style="width:16px;height:16px;"></i> <?= e($req['location'] ?: 'Not given') ?></p>
            </div>
          </div>
        </div>

        <div class="col-lg-5">
          <div class="fb-neu-card">

            <?php if ($isOpen): ?>
              <h4 class="fb-mb-2">Can you help? 💚</h4>
              <p class="fb-text-secondary fb-small fb-mb-4">
                You can give food to <?= e($req['requester']) ?>. Call them, then tap the button —
                this removes them from the map so no one else takes it.
              </p>

              <a href="tel:<?= e($req['req_phone']) ?>" class="fb-btn fb-btn-secondary fb-mb-3" style="width:100%;">
                <i data-lucide="phone"></i> Call <?= e($req['requester']) ?> — <?= e($req['req_phone']) ?>
              </a>

              <form method="post">
                <input type="hidden" name="request_id" value="<?= (int) $req['id'] ?>">
                <button type="submit" name="offer_help" class="fb-btn fb-btn-primary fb-btn-lg" style="width:100%;">
                  <i data-lucide="heart-handshake"></i> I Want to Help
                </button>
              </form>

            <?php elseif ($iAmHelper): ?>
              <div class="fb-text-center">
                <div class="fb-feature-icon" style="width:56px;height:56px;border-radius:14px;background:var(--fb-primary-light);color:var(--fb-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                  <i data-lucide="check-check"></i>
                </div>
                <h4 class="fb-mb-2">You are helping this person 💚</h4>
                <p class="fb-text-secondary fb-small fb-mb-4">Please call them to arrange the food.</p>
                <a href="tel:<?= e($req['req_phone']) ?>" class="fb-btn fb-btn-primary" style="width:100%;">
                  <i data-lucide="phone"></i> Call <?= e($req['requester']) ?> — <?= e($req['req_phone']) ?>
                </a>
              </div>

            <?php else: ?>
              <div class="fb-text-center fb-text-muted" style="padding:16px 0;">
                <i data-lucide="lock" style="width:40px;height:40px;"></i>
                <h4 class="fb-mt-3 fb-mb-1">Already being helped</h4>
                <p class="fb-mb-0">Someone is already helping this person. Thank you for caring! 💚</p>
              </div>
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
