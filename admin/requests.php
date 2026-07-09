<?php
/***
 * FoodBridge — admin/requests.php
 * Admin manages food requests: view all, approve, or reject.
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

/* ------------------------------------------------------------------ *
 *  Handle Approve / Reject (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $reqId  = (int) ($_POST['request_id'] ?? 0);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';

    if ($reqId > 0) {
        // Only a still-pending request can be approved or rejected
        $stmt = $conn->prepare("UPDATE requests SET status = ? WHERE id = ? AND status = 'pending'");
        $stmt->bind_param('si', $action, $reqId);
        $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        $stmt->close();

        if ($changed) {
            // Notify the receiver
            $stmt = $conn->prepare('SELECT receiver_id FROM requests WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $reqId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                if ($action === 'approved') {
                    $title = 'Your food request was approved ✅';
                    $body  = 'A volunteer can now pick it up for you. Please stay reachable.';
                    $icon  = 'check-circle';
                } else {
                    $title = 'Your food request was not approved';
                    $body  = 'Sorry, we could not approve this request. You may try again.';
                    $icon  = 'x-circle';
                }
                $stmt = $conn->prepare(
                    'INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, ?)'
                );
                $stmt->bind_param('isss', $row['receiver_id'], $title, $body, $icon);
                $stmt->execute();
                $stmt->close();
            }
            set_flash('success', 'Request ' . $action . ' successfully.');
        } else {
            set_flash('error', 'That request was already handled.');
        }
    }
    redirect('admin/requests.php');
}

/* ------------------------------------------------------------------ *
 *  Stats
 * ------------------------------------------------------------------ */
$pending   = scalar_count("SELECT COUNT(*) c FROM requests WHERE status = 'pending'");
$approved  = scalar_count("SELECT COUNT(*) c FROM requests WHERE status = 'approved'");
$delivered = scalar_count("SELECT COUNT(*) c FROM requests WHERE status = 'delivered'");
$total     = scalar_count("SELECT COUNT(*) c FROM requests");

/* ------------------------------------------------------------------ *
 *  All requests (newest first)
 * ------------------------------------------------------------------ */
$rows = $conn->query(
    "SELECT r.*, u.name AS requester, u.phone AS req_phone
     FROM requests r
     JOIN users u ON u.id = r.receiver_id
     ORDER BY FIELD(r.status,'pending','approved','assigned','delivered','rejected'), r.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$urgencyMeta = [
    'urgent'  => ['Urgent',  'var(--fb-danger)'],
    'today'   => ['Today',   'var(--fb-warning)'],
    'anytime' => ['Anytime', 'var(--fb-success)'],
];

$active     = 'requests';
$page_title = 'Manage Requests';
require __DIR__ . '/../includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/../includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Manage Food Requests 🙏</h3>
        <p class="fb-text-secondary fb-mb-0">Approve or reject requests from people in need.</p>
      </div>

      <?= render_flash() ?>

      <!-- Stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Pending</p><p class="fb-stat-value"><?= $pending ?></p></div>
            <span class="fb-stat-icon orange"><i data-lucide="clock"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Approved</p><p class="fb-stat-value"><?= $approved ?></p></div>
            <span class="fb-stat-icon blue"><i data-lucide="check-circle"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Delivered</p><p class="fb-stat-value"><?= $delivered ?></p></div>
            <span class="fb-stat-icon green"><i data-lucide="package-check"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Requests</p><p class="fb-stat-value"><?= $total ?></p></div>
            <span class="fb-stat-icon purple"><i data-lucide="inbox"></i></span>
          </div>
        </div>
      </div>

      <!-- Requests table -->
      <?php if (!$rows): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="inbox" style="width:44px;height:44px;"></i>
          <p class="fb-mb-0 fb-mt-3">No requests yet.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel" style="padding:0;overflow:hidden;">
          <div class="table-responsive">
            <table class="fb-table" style="width:100%;margin:0;">
              <thead>
                <tr>
                  <th>Requester</th>
                  <th>Need</th>
                  <th>People</th>
                  <th>Urgency</th>
                  <th>Status</th>
                  <th>Date</th>
                  <th class="fb-text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r):
                  [$uLabel, $uDot] = $urgencyMeta[$r['urgency']] ?? $urgencyMeta['today'];
                ?>
                  <tr>
                    <td>
                      <div class="fb-fw-600"><?= e($r['requester']) ?></div>
                      <div class="fb-caption fb-text-muted"><?= e($r['req_phone']) ?></div>
                    </td>
                    <td>
                      <div class="fb-flex fb-items-center fb-gap-2">
                        <i data-lucide="<?= food_type_icon($r['food_type']) ?>" style="width:18px;height:18px;"></i>
                        <span><?= e($r['description']) ?></span>
                      </div>
                      <div class="fb-caption fb-text-muted"><i data-lucide="map-pin" style="width:12px;height:12px;"></i> <?= e($r['location'] ?: 'No location') ?></div>
                    </td>
                    <td><?= (int) $r['people_count'] ?></td>
                    <td>
                      <span class="fb-flex fb-items-center fb-gap-2">
                        <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:<?= $uDot ?>;"></span>
                        <?= $uLabel ?>
                      </span>
                    </td>
                    <td><span class="fb-badge <?= status_badge_class($r['status']) ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="fb-text-muted"><?= time_ago($r['created_at']) ?></td>
                    <td class="fb-text-right">
                      <?php if ($r['status'] === 'pending'): ?>
                        <form method="post" style="display:inline-flex;gap:8px;">
                          <input type="hidden" name="request_id" value="<?= (int) $r['id'] ?>">
                          <button type="submit" name="action" value="approve"
                                  class="fb-btn fb-btn-success" style="padding:8px 12px;">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                          </button>
                          <button type="submit" name="action" value="reject"
                                  class="fb-btn fb-btn-danger" style="padding:8px 12px;"
                                  onclick="return confirm('Reject this request?');">
                            <i data-lucide="x" style="width:16px;height:16px;"></i>
                          </button>
                        </form>
                      <?php else: ?>
                        <span class="fb-text-muted fb-small">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>