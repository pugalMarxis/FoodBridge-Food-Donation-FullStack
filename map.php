<?php
/**
 * FoodBridge — map.php  (FREE OpenStreetMap, no API key)
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/queries.php';

require_login();

$markers = [];

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
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<body>
<div class="fb-layout">

  <?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="fb-main">
    <?php require __DIR__ . '/includes/topbar.php'; ?>

    <main class="fb-content fb-anim-fade">

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

      <div class="fb-panel" style="padding:0;overflow:hidden;">
        <div id="map" style="width:100%;height:520px;z-index:0;"></div>
      </div>

      <div class="fb-mt-6">
        <h4 class="fb-mb-3">Locations (<?= count($markers) ?>)</h4>
        <?php if (!$markers): ?>
          <div class="fb-panel fb-text-center fb-text-muted" style="padding:28px 0;">
            <i data-lucide="map-pin-off" style="width:40px;height:40px;"></i>
            <p class="fb-mb-0 fb-mt-3">No locations yet. Pin a location when posting food or a request to see it here.</p>
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
                <?php else: ?>
                  <a href="<?= url('help-request.php?id=' . $m['id']) ?>" class="fb-btn fb-btn-primary" style="padding:8px 14px;">Help</a>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  const FB_MARKERS = <?= json_encode($markers) ?>;
  const DETAILS_URL = '<?= url('food-details.php?id=') ?>';
  const HELP_URL    = '<?= url('help-request.php?id=') ?>';


  const center = FB_MARKERS.length ? [FB_MARKERS[0].lat, FB_MARKERS[0].lng] : [7.8731, 80.7718];
  const zoom   = FB_MARKERS.length ? 12 : 7;

  const map = L.map('map').setView(center, zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap', maxZoom: 19
  }).addTo(map);

  FB_MARKERS.forEach(m => {
    const color = m.type === 'food' ? '#10B981' : '#EF4444';
    const dot = L.circleMarker([m.lat, m.lng], {
      radius: 10, color: '#fff', weight: 2, fillColor: color, fillOpacity: 1
    }).addTo(map);

        let html = '<b>' + m.title + '</b><br>' + m.info;
    if (m.type === 'food') {
      html += '<br><a href="' + DETAILS_URL + m.id + '">View details</a>';
    } else {
      html += '<br><a href="' + HELP_URL + m.id + '">🙏 Help this person</a>';
    }
    dot.bindPopup(html);

  });
</script>
</body>
</html>
