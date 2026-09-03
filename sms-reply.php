<?php
/**
 * FoodBridge — sms-reply.php  (DEMO: simulate a person texting "YES" to claim food)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();
if (!in_array(current_role(), ['admin', 'volunteer'], true)) {
    set_flash('error', 'Only admins and volunteers can use the SMS reply demo.');
    redirect(dashboard_for(current_role()));
}

$u = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_yes'])) {
    $logId = (int) ($_POST['log_id'] ?? 0);

    $stmt = $conn->prepare(
        'SELECT sl.phone, sl.recipient_name, sl.food_post_id,
                fp.food_name, fp.location, fp.user_id AS giver_id, fp.status,
                g.phone AS giver_phone
         FROM sms_logs sl
         JOIN food_posts fp ON fp.id = sl.food_post_id
         JOIN users g ON g.id = fp.user_id
         WHERE sl.id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $logId);
    $stmt->execute();
    $a = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$a) {
        set_flash('error', 'That SMS alert was not found.');
        redirect('sms-reply.php');
    }

    $stmt = $conn->prepare("UPDATE food_posts SET status = 'claimed' WHERE id = ? AND status = 'available'");
    $stmt->bind_param('i', $a['food_post_id']);
    $stmt->execute();
    $claimed = $stmt->affected_rows > 0;
    $stmt->close();

    if ($claimed) {
        $title = 'Your food was claimed by SMS 📟';
        $body  = $a['recipient_name'] . ' (SMS user) claimed "' . $a['food_name'] . '". Call them: ' . $a['phone'];
        $stmt  = $conn->prepare("INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, 'message-circle')");
        $stmt->bind_param('iss', $a['giver_id'], $title, $body);
        $stmt->execute();
        $stmt->close();

        $confMsg = 'You claimed "' . $a['food_name'] . '". Call the donor at ' . $a['giver_phone']
                 . ' and collect at ' . ($a['location'] ?: 'the given location') . '.';
        $stmt = $conn->prepare('INSERT INTO sms_logs (phone, recipient_name, message, food_post_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $a['phone'], $a['recipient_name'], $confMsg, $a['food_post_id']);
        $stmt->execute();
        $stmt->close();

        set_flash('success', $a['recipient_name'] . ' claimed "' . $a['food_name'] . '"! A confirmation SMS was sent with the donor\'s number. 🎉');
    } else {
        set_flash('error', 'Sorry, that food was already claimed by someone else.');
    }
    redirect('sms-reply.php');
}

$alerts = $conn->query(
    "SELECT sl.id, sl.phone, sl.recipient_name, sl.created_at,
            fp.food_name, fp.location
     FROM sms_logs sl
     JOIN food_posts fp ON fp.id = sl.food_post_id
     WHERE fp.status = 'available' AND sl.recipient_name IS NOT NULL
     ORDER BY sl.created_at DESC
     LIMIT 40"
)->fetch_all(MYSQLI_ASSOC);

$active     = 'smsreply';
$page_title = 'SMS Replies (Demo)';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">SMS Replies 📟</h3>
        <p class="fb-text-secondary fb-mb-0">Demo: simulate a person texting "YES" to claim food. (Real SMS uses a Twilio/Dialog webhook.)</p>
      </div>

      <?= render_flash() ?>

      <?php if (!$alerts): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="message-square-off" style="width:44px;height:44px;"></i>
          <p class="fb-mb-1 fb-mt-3">No pending SMS alerts.</p>
          <p class="fb-mb-0 fb-small">When a giver posts food, alerts go to SMS receivers and appear here.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel" style="padding:0;overflow:hidden;">
          <div class="table-responsive">
            <table class="fb-table" style="width:100%;margin:0;">
              <thead>
                <tr>
                  <th>Person</th>
                  <th>Phone</th>
                  <th>Food alert</th>
                  <th>Sent</th>
                  <th class="fb-text-right">Their reply</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($alerts as $al): ?>
                  <tr>
                    <td class="fb-fw-600"><?= e($al['recipient_name']) ?></td>
                    <td><?= e($al['phone']) ?></td>
                    <td>
                      <span class="fb-flex fb-items-center fb-gap-2">
                        <i data-lucide="utensils" style="width:16px;height:16px;"></i>
                        <?= e($al['food_name']) ?>
                      </span>
                      <span class="fb-caption fb-text-muted"><i data-lucide="map-pin" style="width:12px;height:12px;"></i> <?= e($al['location'] ?: '—') ?></span>
                    </td>
                    <td class="fb-text-muted fb-small"><?= time_ago($al['created_at']) ?></td>
                    <td class="fb-text-right">
                      <form method="post" onsubmit="return confirm('Simulate this person replying YES?');">
                        <input type="hidden" name="log_id" value="<?= (int) $al['id'] ?>">
                        <button type="submit" name="confirm_yes" class="fb-btn fb-btn-success" style="padding:8px 14px;">
                          <i data-lucide="check" style="width:16px;height:16px;"></i> Reply YES
                        </button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

      <div class="fb-panel fb-mt-4" style="background:var(--fb-blue-soft);border:none;">
        <p class="fb-mb-0 fb-small" style="color:#1D4ED8;">
          <i data-lucide="info" style="width:16px;height:16px;"></i>
          In real life, the person just texts <b>YES</b> from their basic phone. The SMS gateway calls this page automatically (webhook). This demo shows the same result.
        </p>
      </div>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
