<?php
/** Live report: re-reads the MySQL database every few seconds and redraws itself (see assets/report.js). */
declare(strict_types=1);
require __DIR__ . '/inc/boot.php';
guard();
page_start('Live Report', 'report');
?>
<div class="live">
  <span class="dot" id="dot"></span>
  <b id="state">Connecting...</b>
  <span class="muted" id="time"></span>
  <button class="btn btn-sm btn-ghost" type="button" id="refreshNow">Refresh now</button>
  <button class="btn btn-sm btn-ghost" type="button" id="printNow">Print</button>
</div>
<section class="panel" id="signalPanel">
  <h2>Warning signals <span class="muted small">(active disasters)</span></h2>
  <div class="sig-legend small muted"><span class="sig sig-ok">1-3 Normal</span> <span class="sig sig-warn">4-7 Danger</span> <span class="sig sig-err">8-10 Severe Danger</span></div>
  <div class="sig-cards" id="sigCards"></div>
  <div class="sig-check">
    <label>Check my area:
      <select id="sigArea"><option value="">- choose an area -</option></select>
    </label>
    <span id="sigAreaResult" class="sig-result muted">Pick an area to see its warning signal.</span>
  </div>
  <div class="tbl-wrap"><table class="tbl" id="tSig"></table></div>
</section>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<section class="panel" id="pathPanel">
  <h2>Live disaster path <span class="muted small">(observed track, forecast and areas in the way)</span></h2>
  <div class="legend small">
    <span><i style="background:#c0392b"></i> Being crossed now</span><span><i style="background:#e67e22"></i> In the forecast path</span>
    <span><i style="background:#7f8c8d"></i> Already passed</span>
    <span class="muted">Solid line = observed path, dashed line = forecast, big dot = current position with its impact circle</span>
  </div>
  <div id="pathMap"></div>
  <div class="sig-cards" id="pathLists"></div>
</section>
<section class="panel" id="spacePanel">
  <h2>Shelter space <span class="muted small">(people checked in now vs capacity)</span></h2>
  <div class="sig-check">
    <label>Area: <select id="spArea"><option value="">All areas</option></select></label>
    <label><input type="checkbox" id="spOnly"> Only shelters with space</label>
    <label>Nearest to: <select id="spNear"><option value="">- not used -</option></select></label>
    <button class="btn btn-sm btn-ghost" type="button" id="spLocate">Use my location</button>
    <span class="muted small" id="spCount"></span>
  </div>
  <div class="sig-cards" id="spCards"></div>
</section>
<section class="panel" id="reliefPanel">
  <h2>Relief distribution <span class="muted small">(affected people who have received relief, by home area)</span></h2>
  <div class="sig-check">
    <label><input type="checkbox" id="rlOnly"> Only shortages</label>
    <span class="muted small">Shortage = under 80% of affected people reached, or flagged by a coordinator.</span>
  </div>
  <div id="rlBody"></div>
</section>
<div class="cards" id="cards"></div>
<div class="grid2">
  <section class="panel"><h2>Disaster-wise summary</h2><div class="tbl-wrap"><table class="tbl" id="tDis"></table></div></section>
  <section class="panel"><h2>Areas by risk level</h2><div id="risk"></div></section>
</div>
<div class="grid2">
  <section class="panel"><h2>Shelters</h2><div class="tbl-wrap"><table class="tbl" id="tSh"></table></div></section>
  <section class="panel"><h2>Relief items</h2><div class="tbl-wrap"><table class="tbl" id="tRel"></table></div></section>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/report.js"></script>
<?php page_end(); ?>
