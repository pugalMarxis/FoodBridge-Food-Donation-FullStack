<?php
/**
 * FoodBridge — sms-register.php
 * Register people WITHOUT a smartphone (SMS receivers). Admin/volunteer adds them.
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

if (!in_array(current_role(), ['admin', 'volunteer'], true)) {
    set_flash('error', 'Only admins and volunteers can register SMS receivers.');
    redirect(dashboard_for(current_role()));
}

$u   = current_user();
$uid = (int) $u['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_receiver'])) {
    $name     = clean($_POST['name'] ?? '');
    $phone    = clean($_POST['phone'] ?? '');
    $location = clean($_POST['location'] ?? '');

    $errors = [];
    if ($name === '')  $errors[] = 'Please enter the name.';
    if ($phone === '') $errors[] = 'Please enter the phone number.';

    if (!$errors) {
        $stmt = $conn->prepare('INSERT INTO sms_subscribers (name, phone, location, added_by) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $name, $phone, $location, $uid);
        $stmt->execute();
        $stmt->close();
        set_flash('success', $name . ' was registered for SMS alerts. 📟');
    } else {
        set_flash('error', implode(' ', $errors));
    }
    redirect('sms-register.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_receiver'])) {
    $rid = (int) ($_POST['receiver_id'] ?? 0);
    $stmt = $conn->prepare('DELETE FROM sms_subscribers WHERE id = ?');
    $stmt->bind_param('i', $rid);
    $stmt->execute();
    $stmt->close();
    set_flash('success', 'SMS receiver removed.');
    redirect('sms-register.php');
}

$rows  = $conn->query('SELECT * FROM sms_subscribers ORDER BY created_at DESC')->fetch_all(MYSQLI_ASSOC);
$total = count($rows);

$active     = 'smsreg';
$page_title = 'Register SMS Receiver';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Register SMS Receiver 📟</h3>
        <p class="fb-text-secondary fb-mb-0">Add people who have no smartphone. They will get food alerts by SMS.</p>
      </div>

      <?= render_flash() ?>

      <div class="row g-4">
        <div class="col-lg-5">
          <div class="fb-panel">
            <h4 class="fb-mb-4">Add a person</h4>
            <form method="post">
              <div class="fb-form-group">
                <label class="fb-label" for="name">Full Name</label>
                <input class="fb-input" type="text" id="name" name="name" placeholder="e.g. Kamala Devi" required>
              </div>
              <div class="fb-form-group">
                <label class="fb-label" for="phone">Phone Number</label>
                <input class="fb-input" type="text" id="phone" name="phone" placeholder="e.g. 0771234567" required>
              </div>
              <div class="fb-form-group">
                <label class="fb-label" for="location">Area / Village</label>
                <input class="fb-input" type="text" id="location" name="location" placeholder="e.g. Nallur, Jaffna">
              </div>
              <button type="submit" name="add_receiver" class="fb-btn fb-btn-primary fb-btn-lg">
                <i data-lucide="user-plus"></i> Register Person
              </button>
            </form>

            <div class="fb-panel fb-mt-4" style="background:var(--fb-primary-light);border:none;">
              <p class="fb-mb-0 fb-small" style="color:var(--fb-primary-hover);">
                <i data-lucide="info" style="width:16px;height:16px;"></i>
                These people do not use the app. A volunteer, admin, or village officer registers them once.
              </p>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="fb-panel" style="padding:0;overflow:hidden;">
            <div style="padding:20px 24px;"><h4 class="fb-mb-0">Registered SMS Receivers (<?= $total ?>)</h4></div>
            <?php if (!$rows): ?>
              <div class="fb-text-center fb-text-muted" style="padding:36px 0;">
                <i data-lucide="users" style="width:40px;height:40px;"></i>
                <p class="fb-mb-0 fb-mt-3">No SMS receivers yet. Add the first one on the left.</p>
              </div>
            <?php else: ?>
              <div class="table-responsive">
                <table class="fb-table" style="width:100%;margin:0;">
                  <thead>
                    <tr><th>Name</th><th>Phone</th><th>Area</th><th>Added</th><th class="fb-text-right">Action</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($rows as $r): ?>
                      <tr>
                        <td class="fb-fw-600"><?= e($r['name']) ?></td>
                        <td><?= e($r['phone']) ?></td>
                        <td class="fb-text-muted"><?= e($r['location'] ?: '—') ?></td>
                        <td class="fb-text-muted fb-small"><?= time_ago($r['created_at']) ?></td>
                        <td class="fb-text-right">
                          <form method="post" onsubmit="return confirm('Remove this person?');">
                            <input type="hidden" name="receiver_id" value="<?= (int) $r['id'] ?>">
                            <button type="submit" name="delete_receiver" class="fb-btn fb-btn-danger" style="padding:6px 12px;">
                              <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
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
