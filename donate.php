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
    'food_type' => 'cooked', 'food_name' => '', 'quantity' => '', 'unit' => '',
    'pickup_date' => '', 'pickup_time' => '', 'location' => '', 'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $foodType  = $_POST['food_type'] ?? 'other';
    $foodName  = clean($_POST['food_name'] ?? '');
    $quantity  = clean($_POST['quantity'] ?? '');
    $unit      = clean($_POST['unit'] ?? '');
    $pickDate  = clean($_POST['pickup_date'] ?? '');
    $pickTime  = clean($_POST['pickup_time'] ?? '');
    $location  = clean($_POST['location'] ?? '');
    $notes     = clean($_POST['notes'] ?? '');
    $latitude  = ($_POST['latitude'] ?? '')  !== '' ? (float) $_POST['latitude']  : null;
    $longitude = ($_POST['longitude'] ?? '') !== '' ? (float) $_POST['longitude'] : null;

    $old = compact('foodType', 'foodName', 'quantity', 'unit', 'pickDate', 'pickTime', 'location', 'notes');

    $errors = [];
    $allowedTypes = ['cooked', 'groceries', 'vegetables', 'fruits', 'other'];
    if (!in_array($foodType, $allowedTypes, true)) $errors[] = 'Please choose a valid food type.';
    if ($foodName === '') $errors[] = 'Please enter the food name.';
    if ($quantity === '') $errors[] = 'Please enter the quantity.';
    if ($location === '') $errors[] = 'Please enter a pick-up address.';

    if (!$errors) {
        $pickDate = $pickDate !== '' ? $pickDate : null;
        $pickTime = $pickTime !== '' ? $pickTime : null;

        $stmt = $conn->prepare(
            'INSERT INTO food_posts (user_id, food_type, food_name, quantity, unit, location, latitude, longitude, pickup_date, pickup_time, notes, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "available")'
        );
                $stmt->bind_param('isssssddsss', $uid, $foodType, $foodName, $quantity, $unit, $location, $latitude, $longitude, $pickDate, $pickTime, $notes);
        $stmt->execute();
        $foodPostId = (int) $stmt->insert_id;
        $stmt->close();

        $title = 'Donation posted: ' . $foodName;
        $body  = 'Your food donation is now visible to people in need.';
        $stmt = $conn->prepare('INSERT INTO notifications (user_id, title, body, icon) VALUES (?, ?, ?, "gift")');
        $stmt->bind_param('iss', $uid, $title, $body);
        $stmt->execute();
        $stmt->close();

        // Auto-send SMS alerts (demo) to people without smartphones
        $smsMsg = 'FoodBridge: Free food "' . $foodName . '" (' . trim($quantity . ' ' . $unit) . ') at '
                . ($location !== '' ? $location : 'see details') . '. Call ' . $u['phone'] . '. Reply YES to claim.';
        $subs = $conn->query("SELECT name, phone FROM sms_subscribers WHERE phone <> ''")->fetch_all(MYSQLI_ASSOC);
        $smsSent = 0;
        if ($subs) {
            $slog = $conn->prepare('INSERT INTO sms_logs (phone, recipient_name, message, food_post_id) VALUES (?, ?, ?, ?)');
            foreach ($subs as $s) {
                $slog->bind_param('sssi', $s['phone'], $s['name'], $smsMsg, $foodPostId);
                $slog->execute();
                $smsSent++;
            }
            $slog->close();
        }

        $msg = 'Thank you! Your donation has been posted. 🍱';
        if ($smsSent > 0) {
            $msg .= ' SMS alert sent to ' . $smsSent . ' people without smartphones. 📟';
        }
        set_flash('success', $msg);
        redirect('dashboard.php');

    } else {
        set_flash('error', implode(' ', $errors));
    }
}

$active = 'donate';
$page_title = 'Donate Food';
require __DIR__ . '/includes/head.php';

$types = [
    'cooked'     => 'Cooked Food',
    'groceries'  => 'Groceries',
    'vegetables' => 'Vegetables',
    'fruits'     => 'Fruits',
    'other'      => 'Other',
];
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css"/>
<body class="fb-has-tabbar">
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <div class="fb-mb-6">
        <h3 class="fb-mb-0">Donate Food 🍱</h3>
        <p class="fb-text-secondary fb-mb-0">Fill the information below to donate food.</p>
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
                <label class="fb-label" for="food_name">Food Name</label>
                <input class="fb-input" type="text" id="food_name" name="food_name"
                       value="<?= e($old['food_name']) ?>" placeholder="e.g. Chicken Biryani, Rice & Dal" required>
              </div>

              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="quantity">Quantity</label>
                  <input class="fb-input" type="text" id="quantity" name="quantity"
                         value="<?= e($old['quantity']) ?>" placeholder="e.g. 10" required>
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="unit">Unit</label>
                  <input class="fb-input" type="text" id="unit" name="unit"
                         value="<?= e($old['unit']) ?>" placeholder="e.g. packs, kg, people">
                </div>
              </div>

              <div class="row">
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="pickup_date">Pick-up Date</label>
                  <input class="fb-input" type="date" id="pickup_date" name="pickup_date" value="<?= e($old['pickup_date']) ?>">
                </div>
                <div class="col-md-6 fb-form-group">
                  <label class="fb-label" for="pickup_time">Pick-up Time</label>
                  <input class="fb-input" type="time" id="pickup_time" name="pickup_time" value="<?= e($old['pickup_time']) ?>">
                </div>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="location">Address</label>
                <input class="fb-input" type="text" id="location" name="location"
                       value="<?= e($old['location']) ?>" placeholder="Enter full pick-up address" required>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="area">Quick Area (optional)</label>
                <select class="fb-select" id="area">
                  <option value="">-- Jump to an area --</option>
                  <option data-lat="6.9271" data-lng="79.8612">Colombo</option>
                  <option data-lat="6.8448" data-lng="79.9265">Sri Jayawardenepura Kotte</option>
                  <option data-lat="7.2083" data-lng="79.8358">Negombo</option>
                  <option data-lat="7.2906" data-lng="80.6337">Kandy</option>
                  <option data-lat="6.0535" data-lng="80.2210">Galle</option>
                  <option data-lat="5.9549" data-lng="80.5550">Matara</option>
                  <option data-lat="9.6615" data-lng="80.0255">Jaffna</option>
                  <option data-lat="8.7514" data-lng="80.4971">Vavuniya</option>
                  <option data-lat="8.5874" data-lng="81.2152">Trincomalee</option>
                  <option data-lat="7.7170" data-lng="81.7000">Batticaloa</option>
                  <option data-lat="8.3114" data-lng="80.4037">Anuradhapura</option>
                  <option data-lat="7.4863" data-lng="80.3623">Kurunegala</option>
                  <option data-lat="6.6828" data-lng="80.3992">Ratnapura</option>
                  <option data-lat="6.9934" data-lng="81.0550">Badulla</option>
                  <option data-lat="6.9497" data-lng="80.7891">Nuwara Eliya</option>
                </select>
              </div>

              <div class="fb-form-group">
                <label class="fb-label">Pin Your Exact Location</label>
                <p class="fb-small fb-text-muted fb-mb-2">Search a place, or click the map to drop a pin on your exact spot.</p>
                <div id="pickMap" style="height:320px;border-radius:12px;overflow:hidden;border:1px solid var(--fb-border);z-index:0;"></div>
                <input type="hidden" id="latitude"  name="latitude"  value="<?= e($_POST['latitude']  ?? '') ?>">
                <input type="hidden" id="longitude" name="longitude" value="<?= e($_POST['longitude'] ?? '') ?>">
                <div class="fb-mt-2">
                  <button type="button" id="useLocationBtn" class="fb-btn fb-btn-secondary" style="padding:8px 14px;">
                    <i data-lucide="crosshair"></i> Use My GPS
                  </button>
                  <span id="locStatus" class="fb-small fb-text-muted" style="margin-left:10px;"></span>
                </div>
              </div>

              <div class="fb-form-group">
                <label class="fb-label" for="notes">Additional Notes (Optional)</label>
                <textarea class="fb-textarea" id="notes" name="notes"
                          placeholder="Any additional information (vegetarian, spicy, etc.)"><?= e($old['notes']) ?></textarea>
              </div>

              <button type="submit" class="fb-btn fb-btn-primary fb-btn-block fb-btn-lg">
                <i data-lucide="send"></i> Submit Donation
              </button>
            </form>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="fb-neu-card">
            <div class="fb-feature-icon" style="width:52px;height:52px;border-radius:12px;background:var(--fb-primary-light);color:var(--fb-primary);display:flex;align-items:center;justify-content:center;">
              <i data-lucide="heart-handshake"></i>
            </div>
            <h4 class="fb-mt-4">Every meal counts</h4>
            <p class="fb-text-secondary fb-small">
              Your donation will be shown to nearby families and shelters in need.
              Please make sure the food is fresh and safe to eat.
            </p>
            <ul class="fb-small fb-text-secondary" style="padding-left:18px;margin:0;">
              <li class="fb-mb-2">Add a clear food name & quantity.</li>
              <li class="fb-mb-2">Give an accurate pick-up address.</li>
              <li>Click the map to pin your exact location.</li>
            </ul>
          </div>
        </div>
      </div>

    </main>
  </div>
</div>

<nav class="fb-tabbar">
  <a href="<?= url('dashboard.php') ?>"><i data-lucide="home"></i>Home</a>
  <a href="<?= url('my-donations.php') ?>"><i data-lucide="gift"></i>Donations</a>
  <a href="<?= url('donate.php') ?>" class="active"><i data-lucide="plus-circle"></i>Add</a>
  <a href="<?= url('profile.php') ?>"><i data-lucide="user"></i>Profile</a>
</nav>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>
<script>
  const pickMap = L.map('pickMap').setView([7.8731, 80.7718], 7);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap', maxZoom: 19
  }).addTo(pickMap);

  let marker = null;
  function setPin(lat, lng, zoom) {
    document.getElementById('latitude').value  = (+lat).toFixed(6);
    document.getElementById('longitude').value = (+lng).toFixed(6);
    if (marker) { marker.setLatLng([lat, lng]); }
    else {
      marker = L.marker([lat, lng], { draggable: true }).addTo(pickMap);
      marker.on('dragend', e => { const p = e.target.getLatLng(); setPin(p.lat, p.lng); });
    }
    pickMap.setView([lat, lng], zoom || pickMap.getZoom());
    const s = document.getElementById('locStatus');
    if (s) { s.textContent = '📍 Location pinned!'; s.style.color = 'var(--fb-success)'; }
  }

  pickMap.on('click', e => setPin(e.latlng.lat, e.latlng.lng));

    L.Control.geocoder({
    defaultMarkGeocode: false,
    collapsed: false,
    placeholder: 'Search your town or area...',
    geocoder: L.Control.Geocoder.photon()   // free, gives suggestions as you type
  })
    .on('markgeocode', e => { const c = e.geocode.center; setPin(c.lat, c.lng, 16); })
    .addTo(pickMap);


  document.getElementById('area')?.addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.lat) setPin(+opt.dataset.lat, +opt.dataset.lng, 13);
  });

  document.getElementById('useLocationBtn')?.addEventListener('click', function () {
    const status = document.getElementById('locStatus');
    if (!navigator.geolocation) { status.textContent = 'GPS not supported.'; return; }
    status.textContent = 'Getting your location...';
    navigator.geolocation.getCurrentPosition(
      pos => setPin(pos.coords.latitude, pos.coords.longitude, 16),
      ()  => { status.textContent = 'GPS not available. Please click the map instead.'; status.style.color = 'var(--fb-danger)'; },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });

  (function () {
    const la = document.getElementById('latitude').value;
    const lo = document.getElementById('longitude').value;
    if (la && lo) setPin(+la, +lo, 15);
  })();
</script>
</body>
</html>
