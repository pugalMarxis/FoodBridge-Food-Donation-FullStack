<?php
/**
 * FoodBridge — map.php
 * Live Map: green pins = available food, red pins = people needing help.
 *
 * SETUP: paste your free Google Maps API key below (replace YOUR_GOOGLE_MAPS_API_KEY).
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

// ---- Paste your Google Maps key here ----
$GOOGLE_MAPS_KEY = 'YOUR_GOOGLE_MAPS_API_KEY';
$keyReady = ($GOOGLE_MAPS_KEY !== 'YOUR_GOOGLE_MAPS_API_KEY' && $GOOGLE_MAPS_KEY !== '');

/* ------------------------------------------------------------------ *
 *  Build map markers from items that HAVE coordinates
 * ------------------------------------------------------------------ */
$markers = [];

// Available food (green)
$foodRows = $conn->query(
    "SELECT id, food_name, quantity, unit, location, latitude, longitude
     FROM food_posts
     WHERE status = 'available' AND latitude IS NOT NULL AND longitude IS NOT NULL"
)->fetch_all(MYSQLI_ASSOC);
foreach ($foodRows as $f) {
    $markers[] = [
        'lat'   => (float) $f['latitude'],
        'lng'   => (float) $f['longitude'],
        'type'  => 'food',
        'title' => $f['food_name'],
        'info'  => trim($f['quantity'] . ' ' . $f['unit']) . ' · ' . ($f['location'] ?: 'Nearby'),
        'id'    => (int) $f['id'],
    ];
}

// Active requests (red)
$reqRows = $conn->query(
    "SELECT id, description, people_count, location, latitude, longitude
     FROM requests
     WHERE status IN ('pending','approved') AND latitude IS NOT NULL AND longitude IS NOT NULL"
)->fetch_all(MYSQLI_ASSOC);
foreach ($reqRows as $r) {
    $markers[] = [
        'lat'   => (float) $r['latitude'],
        'lng'   => (float) $r['longitude'],
        'type'  => 'request',
        'title' => $r['description'],
        'info'  => $r['people_count'] . ' people · ' . ($r['location'] ?: 'Nearby'),
        'id'    => (int) $r['id'],
    ];
}

$active     = 'map';
$page_title = 'Live Map';
require __DIR__ . '/includes/head.php';
?>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

      <!-- Header -->
      <div class="fb-flex fb-justify-between fb-items-center fb-flex-wrap fb-gap-4 fb-mb-4">
        <div>
          <h3 class="fb-mb-0">Live Map 🗺️</h3>
          <p class="fb-text-secondary fb-mb-0">See available food and people who need help nearby.</p>
        </div>
        <div class="fb-flex fb-items-center fb-gap-4">
          <span class="fb-flex fb-items-center fb-gap-2 fb-small">
            <span style="width:12px;height:12px;border-radius:50%;background:var(--fb-success);display:inline-block;"></span> Food
          </span>
          <span class="fb-flex fb-items-center fb-gap-2 fb-small">
            <span style="width:12px;height:12px;border-radius:50%;background:var(--fb-danger);display:inline-block;"></span> Needs help
          </span>
        </div>
      </div>

      <?= render_flash() ?>

      <?php if (!$keyReady): ?>
        <!-- Setup instructions when no key yet -->
        <div class="fb-panel fb-mb-6" style="background:var(--fb-primary-light);border:none;">
          <h4 class="fb-mb-2"><i data-lucide="key-round"></i> Add your free Google Maps key</h4>
          <p class="fb-text-secondary fb-mb-2">The map needs a free Google key to load. Steps:</p>
          <ol class="fb-text-secondary fb-mb-0" style="padding-left:20px;line-height:1.9;">
            <li>Go to <b>console.cloud.google.com</b> and sign in</li>
            <li>Create a project → enable <b>Maps JavaScript API</b></li>
            <li>Create an <b>API key</b> and copy it</li>
            <li>Open <b>map.php</b> and paste it into <code>$GOOGLE_MAPS_KEY</code></li>
          </ol>
        </div>
      <?php endif; ?>

      <!-- Map area -->
      <div class="fb-panel" style="padding:0;overflow:hidden;">
        <div id="map" style="width:100%;height:520px;background:#EAEFEA;
             display:flex;align-items:center;justify-content:center;color:var(--fb-text-muted);">
          <?php if (!$keyReady): ?>
            <div class="fb-text-center">
              <i data-lucide="map" style="width:56px;height:56px;"></i>
              <p class="fb-mt-3 fb-mb-0">Map will appear here once your Google key is added.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Text fallback list (always useful) -->
      <div class="fb-mt-6">
        <h4 class="fb-mb-3">Locations (<?= count($markers) ?>)</h4>
        <?php if (!$markers): ?>
          <div class="fb-panel fb-text-center fb-text-muted" style="padding:28px 0;">
            <i data-lucide="map-pin-off" style="width:40px;height:40px;"></i>
            <p class="fb-mb-0 fb-mt-3">No locations with GPS yet. Add latitude/longitude when posting food or requests to see pins.</p>
          </div>
          
        <?php else: ?>
          <div class="fb-panel">
            <?php foreach ($markers as $m): ?>
              <div class="fb-list-item">
                <div class="fb-list-thumb"
                     style="background:<?= $m['type'] === 'food' ? 'var(--fb-primary-light)' : '#FEE2E2' ?>;
                            color:<?= $m['type'] === 'food' ? 'var(--fb-primary)' : 'var(--fb-danger)' ?>;">
                  <i data-lucide="<?= $m['type'] === 'food' ? 'utensils' : 'hand-helping' ?>"></i>
                </div>
                <div class="fb-list-body">
                  <p class="fb-list-title fb-mb-0"><?= e($m['title']) ?></p>
                  <p class="fb-list-sub fb-mb-0"><?= e($m['info']) ?></p>
                </div>
                <?php if ($m['type'] === 'food'): ?>
                  <a href="<?= url('food-details.php?id=' . $m['id']) ?>" class="fb-btn fb-btn-secondary" style="padding:8px 14px;">View</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </main>
  </div>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= url('assets/js/main.js') ?>"></script>

<?php if ($keyReady): ?>
<script>
  const FB_MARKERS = <?= json_encode($markers) ?>;

  function initMap() {
    // Center on first marker, or default to Colombo, Sri Lanka
    const center = FB_MARKERS.length
      ? { lat: FB_MARKERS[0].lat, lng: FB_MARKERS[0].lng }
      : { lat: 6.9271, lng: 79.8612 };

    const map = new google.maps.Map(document.getElementById('map'), {
      center: center,
      zoom: 12,
    });

    const info = new google.maps.InfoWindow();

    FB_MARKERS.forEach(m => {
      const marker = new google.maps.Marker({
        position: { lat: m.lat, lng: m.lng },
        map: map,
        title: m.title,
        icon: m.type === 'food'
          ? 'http://maps.google.com/mapfiles/ms/icons/green-dot.png'
          : 'http://maps.google.com/mapfiles/ms/icons/red-dot.png',
      });

      marker.addListener('click', () => {
        let html = '<b>' + m.title + '</b><br>' + m.info;
        if (m.type === 'food') {
          html += '<br><a href="<?= url('food-details.php?id=') ?>' + m.id + '">View details</a>';
        }
        info.setContent(html);
        info.open(map, marker);
      });
    });
  }
</script>
<script async defer
  src="https://maps.googleapis.com/maps/api/js?key=<?= e($GOOGLE_MAPS_KEY) ?>&callback=initMap">
</script>
<?php endif; ?>

</body>
</html>