<?php
/** Map of risk areas and shelters (Leaflet + OpenStreetMap). The tables underneath work without internet. */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
require __DIR__ . '/inc/stats.php';
guard();

$num   = fn($v) => $v === null ? null : (float) $v;
$areas = array_map(fn($a) => ['lat' => $num($a['lat']), 'lng' => $num($a['lng']), 'loss' => (float) $a['loss']] + $a, rows(
    'SELECT a.Area_ID AS id, a.Area_Name AS name, a.Location AS loc, a.Risk_Level AS risk, a.Latitude AS lat, a.Longitude AS lng,
            (SELECT COUNT(*) FROM disaster_area da WHERE da.Area_ID = a.Area_ID) AS disasters,
            (SELECT COALESCE(SUM(m.Estimated_Loss), 0) FROM damage m WHERE m.Area_ID = a.Area_ID) AS loss
       FROM area a ORDER BY a.Area_ID'
));
$shelters = array_map(fn($s) => ['lat' => $num($s['lat']), 'lng' => $num($s['lng'])] + $s, shelter_summary());

page_start('Map', 'map');
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<section class="panel">
  <div class="legend">
    <span><i style="background:#c0392b"></i> Very High risk</span><span><i style="background:#e67e22"></i> High</span>
    <span><i style="background:#f1c40f"></i> Medium</span><span><i style="background:#2a9d8f"></i> Low</span>
    <span class="muted">Circles = risk areas &nbsp; Dots = shelters (green &lt; 70% full, orange &lt; 90%, red 90%+)</span>
  </div>
  <div id="map"></div>
</section>
<div class="grid2 eq">
  <section class="panel"><h2>Areas</h2><div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Area</th><th>Risk</th><th>Latitude</th><th>Longitude</th></tr></thead><tbody>
    <?php foreach ($areas as $a): ?><tr><td><?= h($a['name']) ?></td><td><?= h($a['risk'] ?? '-') ?></td><td><?= h($a['lat'] ?? '-') ?></td><td><?= h($a['lng'] ?? '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
  <section class="panel"><h2>Shelters</h2><div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>Shelter</th><th class="r">In now</th><th class="r">Capacity</th></tr></thead><tbody>
    <?php foreach ($shelters as $s): ?><tr><td><?= h($s['name']) ?></td><td class="r"><?= number_format($s['occupied']) ?></td><td class="r"><?= number_format($s['capacity']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
</div>
<p class="muted small">Coordinates are stored in the <a href="table.php?t=area">Area</a> and <a href="table.php?t=shelter">Shelter</a> tables (Latitude / Longitude) - edit them there and refresh this page.</p>
<script type="application/json" id="mapdata"><?= json_encode(['areas' => $areas, 'shelters' => $shelters], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/map.js"></script>
<?php page_end(); ?>
