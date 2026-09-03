<?php
/**
 * FoodBridge — admin/users.php
 * Admin views all users and can block / unblock them (watchdog).
 * Admin accounts cannot be blocked (safety).
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/queries.php';

require_role('admin');

$me = (int) $_SESSION['user_id'];

/* ------------------------------------------------------------------ *
 *  Handle Block / Unblock (POST)
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $userId    = (int) ($_POST['user_id'] ?? 0);
    $newStatus = $_POST['action'] === 'block' ? 'blocked' : 'active';

    if ($userId > 0 && $userId !== $me) {
        // Never allow blocking an admin account
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ? AND role <> 'admin'");
        $stmt->bind_param('si', $newStatus, $userId);
        $stmt->execute();
        $changed = $stmt->affected_rows > 0;
        $stmt->close();

        if ($changed) {
            set_flash('success', 'User ' . ($newStatus === 'blocked' ? 'blocked' : 'unblocked') . ' successfully.');
        } else {
            set_flash('error', 'Could not update that user.');
        }
    } else {
        set_flash('error', 'You cannot block your own account.');
    }
    redirect('admin/users.php');
}

/* ------------------------------------------------------------------ *
 *  Stats
 * ------------------------------------------------------------------ */
$totalUsers = scalar_count('SELECT COUNT(*) c FROM users');
$givers     = scalar_count("SELECT COUNT(*) c FROM users WHERE role = 'giver'");
$receivers  = scalar_count("SELECT COUNT(*) c FROM users WHERE role = 'receiver'");
$volunteers = scalar_count("SELECT COUNT(*) c FROM users WHERE role = 'volunteer'");

/* ------------------------------------------------------------------ *
 *  All users (newest first)
 * ------------------------------------------------------------------ */
$rows = $conn->query(
    "SELECT id, name, email, phone, role, location, status, created_at
     FROM users ORDER BY created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

// Colored badge per user status
$statusBadge = [
    'active'  => 'fb-badge-success',
    'pending' => 'fb-badge-pending',
    'blocked' => 'fb-badge-danger',
];
// Icon per role
$roleIcon = [
    'admin'     => 'shield',
    'giver'     => 'gift',
    'receiver'  => 'hand-helping',
    'volunteer' => 'bike',
];

$active     = 'users';
$page_title = 'Manage Users';
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
        <h3 class="fb-mb-0">Manage Users 👥</h3>
        <p class="fb-text-secondary fb-mb-0">See everyone on FoodBridge. Block or unblock accounts.</p>
      </div>

      <?= render_flash() ?>

      <!-- Stat cards -->
      <div class="fb-grid fb-grid-4 fb-mb-8">
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Total Users</p><p class="fb-stat-value"><?= $totalUsers ?></p></div>
            <span class="fb-stat-icon purple"><i data-lucide="users"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Givers</p><p class="fb-stat-value"><?= $givers ?></p></div>
            <span class="fb-stat-icon green"><i data-lucide="gift"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Receivers</p><p class="fb-stat-value"><?= $receivers ?></p></div>
            <span class="fb-stat-icon orange"><i data-lucide="hand-helping"></i></span>
          </div>
        </div>
        <div class="fb-stat">
          <div class="fb-flex fb-justify-between fb-items-center">
            <div><p class="fb-stat-label">Volunteers</p><p class="fb-stat-value"><?= $volunteers ?></p></div>
            <span class="fb-stat-icon blue"><i data-lucide="bike"></i></span>
          </div>
        </div>
      </div>

      <!-- Users table -->
      <?php if (!$rows): ?>
        <div class="fb-panel fb-text-center fb-text-muted" style="padding:40px 0;">
          <i data-lucide="users" style="width:44px;height:44px;"></i>
          <p class="fb-mb-0 fb-mt-3">No users yet.</p>
        </div>
      <?php else: ?>
        <div class="fb-panel" style="padding:0;overflow:hidden;">
          <div class="table-responsive">
            <table class="fb-table" style="width:100%;margin:0;">
              <thead>
                <tr>
                  <th>User</th>
                  <th>Role</th>
                  <th>Phone</th>
                  <th>Location</th>
                  <th>Status</th>
                  <th>Joined</th>
                  <th class="fb-text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $r): ?>
                  <tr>
                    <td>
                      <div class="fb-fw-600"><?= e($r['name']) ?></div>
                      <div class="fb-caption fb-text-muted"><?= e($r['email']) ?></div>
                    </td>
                    <td>
                      <span class="fb-flex fb-items-center fb-gap-2">
                        <i data-lucide="<?= $roleIcon[$r['role']] ?? 'user' ?>" style="width:16px;height:16px;"></i>
                        <?= ucfirst($r['role']) ?>
                      </span>
                    </td>
                    <td class="fb-text-muted"><?= e($r['phone']) ?></td>
                    <td class="fb-text-muted"><?= e($r['location'] ?: '—') ?></td>
                    <td><span class="fb-badge <?= $statusBadge[$r['status']] ?? 'fb-badge-pending' ?>"><?= ucfirst($r['status']) ?></span></td>
                    <td class="fb-text-muted"><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
                    <td class="fb-text-right">
                      <?php if ($r['role'] === 'admin' || (int) $r['id'] === $me): ?>
                        <span class="fb-text-muted fb-small">—</span>
                      <?php elseif ($r['status'] === 'blocked'): ?>
                        <form method="post">
                          <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                          <button type="submit" name="action" value="unblock"
                                  class="fb-btn fb-btn-success" style="padding:8px 14px;">
                            <i data-lucide="check" style="width:16px;height:16px;"></i> Unblock
                          </button>
                        </form>
                      <?php else: ?>
                        <form method="post" onsubmit="return confirm('Block this user? They cannot log in until unblocked.');">
                          <input type="hidden" name="user_id" value="<?= (int) $r['id'] ?>">
                          <button type="submit" name="action" value="block"
                                  class="fb-btn fb-btn-danger" style="padding:8px 14px;">
                            <i data-lucide="ban" style="width:16px;height:16px;"></i> Block
                          </button>
                        </form>
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