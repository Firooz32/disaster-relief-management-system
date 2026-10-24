// Live report: re-reads report_data.php every REFRESH_MS and redraws only when something changed.
const REFRESH_MS = 5000;           // change this number to update faster / slower
let last = '', busy = false;
const esc = s => String(s ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const num = v => Number(v || 0).toLocaleString();
function tbl(el, head, body, right = []) {
  el.innerHTML = '<thead><tr>' + head.map((h, i) => '<th' + (right.includes(i) ? ' class="r"' : '') + '>' + h + '</th>').join('') + '</tr></thead><tbody>' +
    (body.length ? body.map(r => '<tr>' + r.map((c, i) => '<td' + (right.includes(i) ? ' class="r"' : '') + '>' + esc(c) + '</td>').join('') + '</tr>').join('')
                 : '<tr><td colspan="' + head.length + '" class="muted">No records.</td></tr>') + '</tbody>';
}
// ---- warning signals (labels and colour classes are decided by signal_info() in inc/stats.php)
let sigAreas = [];
const sigBadge = s => '<span class="sig sig-' + esc(s.cls) + '">' + (s.level === null ? '' : 'Signal ' + esc(s.level) + ' - ') + esc(s.label) + '</span>';
function showMyArea() {
  const a = sigAreas.find(x => String(x.id) === document.getElementById('sigArea').value);
  const out = document.getElementById('sigAreaResult');
  if (!a) { out.className = 'sig-result muted'; out.textContent = 'Pick an area to see its warning signal.'; return; }
  out.className = 'sig-result';
  out.innerHTML = '<b>' + esc(a.name) + '</b> ' + sigBadge(a.sig) + ' <span class="muted small">' +
    (a.hit ? esc(a.by.join(', ')) : 'No active disaster is affecting this area.') + '</span>';
}
function drawSignals(g) {
  document.getElementById('sigCards').innerHTML = g.disasters.length ? g.disasters.map(x =>
    '<div class="sig-card sig-' + esc(x.sig.cls) + '"><div class="sig-head"><div><b>' + esc(x.type) + '</b> <span class="muted small">' + esc(x.date) + ' (#' + esc(x.id) + ')</span></div>' +
    '<span class="sig-num">' + (x.sig.level === null ? '-' : esc(x.sig.level)) + '</span></div>' + sigBadge(x.sig) +
    (x.alert ? '<p class="small"><b>' + esc(x.alert) + '</b> <span class="muted">' + esc(x.time) + '</span><br>' + esc(x.message) + '</p>' : '<p class="small muted">No warning with a signal number has been recorded.</p>') +
    '<div class="small muted">Areas: ' + esc(x.areas.join(', ') || '-') + '</div></div>').join('')
    : '<p class="muted">No disaster is marked Active right now.</p>';
  sigAreas = g.areas;
  const sel = document.getElementById('sigArea'), keep = sel.value;
  if (sel.options.length !== g.areas.length + 1 || [...sel.options].slice(1).some((o, i) => o.value !== String(g.areas[i].id))) {   // rebuild only when the area list changed
    sel.innerHTML = '<option value="">- choose an area -</option>' + g.areas.map(a => '<option value="' + esc(a.id) + '">' + esc(a.name) + ' (' + esc(a.loc) + ')</option>').join('');
    sel.value = keep;
  }
  showMyArea();
  tbl(document.getElementById('tSig'), ['Area', 'District', 'Signal', 'Active disasters'],
      g.areas.filter(a => a.hit).map(a => [a.name, a.loc, a.sig.level === null ? a.sig.label : 'Signal ' + a.sig.level + ' - ' + a.sig.label, a.by.join(', ')]));
}
document.getElementById('sigArea').addEventListener('change', showMyArea);

// ---- shelter space (free places and the green / red status come from space_info() in inc/stats.php)
let spaceList = [], areaPts = [], spFrom = null;      // spFrom = {lat, lng, label}: where the distances are measured from
function kmBetween(a, b, c, d) {                       // great-circle distance in km (same formula as km_between() in inc/stats.php)
  const r = Math.PI / 180, h = Math.sin((c - a) * r / 2) ** 2 + Math.cos(a * r) * Math.cos(c * r) * Math.sin((d - b) * r / 2) ** 2;
  return 12742 * Math.asin(Math.min(1, Math.sqrt(h)));
}
function showSpace() {
  const area = document.getElementById('spArea').value, only = document.getElementById('spOnly').checked;
  spaceList.forEach(s => { s.dist = spFrom && s.lat !== null ? kmBetween(spFrom.lat, spFrom.lng, s.lat, s.lng) : null; });
  const list = spaceList.filter(s => (area === '' || String(s.area_id) === area) && (!only || s.space.left > 0))
    .sort((a, b) => (b.space.left > 0) - (a.space.left > 0) || (spFrom ? (a.dist ?? 1e9) - (b.dist ?? 1e9) : b.space.left - a.space.left) || a.name.localeCompare(b.name));
  document.getElementById('spCount').textContent = list.length + ' of ' + spaceList.length + ' shelters shown' + (spFrom ? ' - nearest to ' + spFrom.label + ' first (with space before full)' : '');
  document.getElementById('spCards').innerHTML = list.length ? list.map(s => {
    const pct = s.capacity > 0 ? Math.min(100, Math.round(s.occupied / s.capacity * 100)) : 100;
    return '<div class="sp-card sp-' + esc(s.space.cls) + '"><div class="sig-head"><div><b>' + esc(s.name) + '</b><br><span class="muted small">' + esc(s.area) + '</span></div>' +
      (s.space.left > 0 ? '<span class="sig-num">' + num(s.space.left) + '</span>' : '') + '</div>' +
      '<span class="sig sig-' + esc(s.space.cls) + '">' + esc(s.space.label) + '</span>' + (s.dist === null ? '' : ' <span class="small muted">' + s.dist.toFixed(1) + ' km away</span>') +
      '<div class="bar"><i style="width:' + pct + '%"></i></div><div class="small muted">' + num(s.occupied) + ' staying now of ' + num(s.capacity) + ' places</div></div>';
  }).join('') : '<p class="muted">No shelter matches.</p>';
}
function drawSpace(list, pts) {
  spaceList = list;
  areaPts = pts;
  const near = document.getElementById('spNear'), keepNear = near.value;
  if (near.options.length !== pts.length + 1 || [...near.options].slice(1).some((o, i) => o.value !== String(pts[i].id))) {   // rebuild only when the area list changed
    near.innerHTML = '<option value="">- not used -</option>' + pts.map(a => '<option value="' + esc(a.id) + '">' + esc(a.name) + '</option>').join('');
    near.value = keepNear;
  }
  if (near.value) {                                     // an area is chosen: measure from its (possibly edited) coordinates
    const a = pts.find(x => String(x.id) === near.value);
    spFrom = a ? { lat: a.lat, lng: a.lng, label: a.name } : null;
  }
  const sel = document.getElementById('spArea'), keep = sel.value;
  const areas = [...new Map(list.filter(s => s.area_id !== null).map(s => [s.area_id, s.area])).entries()].sort((a, b) => String(a[1]).localeCompare(String(b[1])));
  if (sel.options.length !== areas.length + 1 || [...sel.options].slice(1).some((o, i) => o.value !== String(areas[i][0]))) {   // rebuild only when the area list changed
    sel.innerHTML = '<option value="">All areas</option>' + areas.map(a => '<option value="' + esc(a[0]) + '">' + esc(a[1]) + '</option>').join('');
    sel.value = keep;
  }
  showSpace();
}
document.getElementById('spArea').addEventListener('change', showSpace);
document.getElementById('spNear').addEventListener('change', function () {
  const a = areaPts.find(x => String(x.id) === this.value);
  spFrom = a ? { lat: a.lat, lng: a.lng, label: a.name } : null;
  showSpace();
});
document.getElementById('spLocate').addEventListener('click', function () {
  if (!navigator.geolocation) { alert('This browser cannot tell where you are. Choose an area instead.'); return; }
  navigator.geolocation.getCurrentPosition(function (p) {
    document.getElementById('spNear').value = '';
    spFrom = { lat: p.coords.latitude, lng: p.coords.longitude, label: 'your location' };
    showSpace();
  }, function () { alert('Your location could not be read (permission denied?). Choose an area instead.'); }, { timeout: 10000 });
});
document.getElementById('spOnly').addEventListener('change', showSpace);

// ---- live disaster path (which area is "now" / "next" / "passed" is decided by path_status() in inc/stats.php)
const PATH_COLOR = { now: '#c0392b', next: '#e67e22', passed: '#7f8c8d', none: '#2a9d8f' };
let pathMap = null, pathLayer = null, pathFitted = false;
function popupEl(title, lines) {      // built with textContent, so database text can never inject HTML
  const d = document.createElement('div'), b = document.createElement('b');
  b.textContent = title; d.appendChild(b);
  lines.forEach(t => { d.appendChild(document.createElement('br')); d.appendChild(document.createTextNode(t)); });
  return d;
}
function initPathMap() {
  const el = document.getElementById('pathMap');
  if (pathMap) return true;
  if (typeof L === 'undefined') {     // no internet: the lists below the map still work
    el.innerHTML = '<p class="muted" style="padding:16px">The map library could not be loaded (internet needed). The lists below show the areas in the path.</p>';
    return false;
  }
  pathMap = L.map('pathMap');
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap contributors' }).addTo(pathMap);
  pathLayer = L.layerGroup().addTo(pathMap);
  pathMap.setView([22.6, 89.5], 8);
  return true;
}
function drawPathMap(list) {
  if (!initPathMap()) return;
  pathLayer.clearLayers();           // redraw in place; the viewer's zoom and position are kept
  const all = [];
  list.forEach(ds => {
    ds.areas.forEach(a => {
      const c = PATH_COLOR[a.state], hit = a.state !== 'none';
      const circle = L.circle([a.lat, a.lng], { radius: 7000, color: c, weight: hit ? 3 : 1, fillColor: c, fillOpacity: hit ? 0.4 : 0.06 }).addTo(pathLayer)
        .bindPopup(popupEl(a.name, [a.loc, 'Risk: ' + (a.risk || 'unknown'), a.state === 'now' ? 'Being crossed now' : a.state === 'next' ? 'In the forecast path' : a.state === 'passed' ? 'Already passed' : 'Not in the path']));
      if (hit) circle.bindTooltip(a.name, { permanent: a.state === 'now', direction: 'right', offset: [8, 0], className: 'path-tip' });   // others show their name on hover
      if (hit) all.push([a.lat, a.lng]);
    });
    const actual = ds.points.filter(p => p.type === 'Actual'), fc = ds.points.filter(p => p.type === 'Forecast');
    const ll = p => [p.lat, p.lng];
    if (actual.length > 1) L.polyline(actual.map(ll), { color: '#c0392b', weight: 4 }).addTo(pathLayer);
    if (fc.length) L.polyline((actual.length ? [ll(actual[actual.length - 1])] : []).concat(fc.map(ll)), { color: '#e67e22', weight: 4, dashArray: '8 8' }).addTo(pathLayer);
    ds.points.forEach((p, i) => {
      const isCur = i === ds.current, f = p.type === 'Forecast';
      const tip = [p.time, (f ? 'Forecast' : 'Observed') + ' position', 'Impact radius: ' + p.radius + ' km'].concat(p.wind === null ? [] : ['Wind: ' + p.wind + ' km/h']);
      if (f) L.circle(ll(p), { radius: p.radius * 1000, color: '#e67e22', weight: 1, dashArray: '4 6', fillOpacity: 0.04 }).addTo(pathLayer);
      if (isCur) L.circle(ll(p), { radius: p.radius * 1000, color: '#c0392b', weight: 2, fillColor: '#c0392b', fillOpacity: 0.18 }).addTo(pathLayer);
      L.circleMarker(ll(p), isCur ? { radius: 11, color: '#fff', weight: 3, fillColor: '#c0392b', fillOpacity: 1 }
                                  : { radius: 5, color: f ? '#e67e22' : '#c0392b', weight: 2, fillColor: f ? '#fff' : '#c0392b', fillOpacity: 1 })
        .addTo(pathLayer).bindPopup(popupEl(ds.type + (isCur ? ' - current position' : ''), tip));
      if (isCur || f) all.push(ll(p));      // the older part of the track does not decide the first view
    });
  });
  if (all.length && !pathFitted) { pathMap.fitBounds(all, { padding: [40, 40], maxZoom: 10 }); pathFitted = true; }
}
function drawPathLists(list) {
  const tags = (arr, cls) => arr.length ? arr.map(a => '<span class="tag tag-' + cls + '">' + esc(a.name) + (a.km !== null && cls !== 'passed' ? ' <span style="font-weight:400">(' + esc(a.km) + ' km)</span>' : '') + '</span>').join('') : '<span class="muted small">none</span>';
  document.getElementById('pathLists').innerHTML = list.length ? list.map(ds => {
    const cur = ds.current === null ? null : ds.points[ds.current], by = s => ds.areas.filter(a => a.state === s).sort((x, y) => (x.km ?? 0) - (y.km ?? 0) || x.name.localeCompare(y.name));
    return '<div class="path-card"><b>' + esc(ds.type) + '</b> <span class="muted small">' + esc(ds.date) + ' (#' + esc(ds.id) + ')</span>' +
      (cur ? '<div class="small muted">Position at ' + esc(cur.time) + (cur.wind === null ? '' : ', wind ' + esc(cur.wind) + ' km/h') + ', impact radius ' + esc(cur.radius) + ' km</div>'
           : '<div class="small muted">No observed position has been recorded yet - only a forecast.</div>') +
      '<h4>Currently crossing</h4>' + (cur ? tags(by('now'), 'now') : '<span class="muted small">unknown</span>') +
      '<h4>In the forecast path</h4>' + tags(by('next'), 'next') +
      '<h4>Already passed</h4>' + tags(by('passed'), 'passed') + '</div>';
  }).join('') : '<p class="muted">No active disaster has a recorded path.</p>';
}
function drawPaths(list) { drawPathMap(list); drawPathLists(list); }

// ---- relief tracker (Shortage / on-track decision comes from relief_info() in inc/stats.php)
let reliefList = [];
const fam = n => num(n) + (Number(n) === 1 ? ' family' : ' families');
function rlCard(title, sub, r, x) {
  const short = r.cls === 'err';
  return '<div class="rl-card rl-' + esc(r.cls) + '"><div class="sig-head"><div><b>' + esc(title) + '</b>' + (sub ? '<br><span class="muted small">' + esc(sub) + '</span>' : '') + '</div>' +
    '<span class="sig sig-' + esc(r.cls) + '">' + esc(r.label) + '</span></div>' +
    (short   // shortage: both numbers; normal: how many people have received relief
      ? '<div class="rl-two"><div class="yes"><b>' + num(x.received) + '</b><span class="small muted">people received relief</span></div><div class="no"><b>' + num(x.not_received) + '</b><span class="small muted">people have not received</span></div></div>' +
        (r.reason ? '<div class="small" style="margin-top:6px">' + esc(r.reason) + '</div>' : '')
      : '<div class="sig-num">' + num(x.received) + '</div><div class="small muted">people have received relief' + (x.people > 0 ? ' (' + num(x.fam_received) + ' of ' + fam(x.families) + ')' : '') + '</div>') +
    (x.people > 0 ? '<div class="bar"><i style="width:' + Math.round(x.received / x.people * 100) + '%"></i></div>' : '') + '</div>';
}
function showRelief() {
  const only = document.getElementById('rlOnly').checked;
  const html = reliefList.map(ds => {
    const areas = ds.areas.filter(a => !only || a.rel.cls === 'err');
    if (only && !areas.length) return '';
    return '<div class="rl-dis"><h3>' + esc(ds.type) + ' <span class="muted small">' + esc(ds.date) + ' (#' + esc(ds.id) + ')</span> <span class="sig sig-' + esc(ds.rel.cls) + '">' + esc(ds.rel.label) + '</span></h3>' +
      '<div class="sig-cards">' + rlCard('All affected areas', fam(ds.families), ds.rel, ds) +
      areas.map(a => rlCard(a.name, (a.flagged ? 'Flagged by coordinator - ' : '') + fam(a.families), a.rel, a)).join('') + '</div></div>';
  }).join('');
  document.getElementById('rlBody').innerHTML = html || '<p class="muted">' + (reliefList.length ? 'No area is short of relief.' : 'No disaster is marked Active right now.') + '</p>';
}
document.getElementById('rlOnly').addEventListener('change', showRelief);

function draw(d) {
  drawSignals(d.signals);
  drawPaths(d.paths);
  reliefList = d.relief_by; showRelief();
  drawSpace(d.space, d.areas);
  const s = d.stats;
  const cards = [['Disasters', s.disasters], ['Affected families', s.people], ['People in those families', s.family], ['Areas', s.areas],
    ['Shelters', s.shelters], ['Shelter capacity', s.capacity], ['People in shelters now', s.occupied], ['Rescue organisations', s.orgs],
    ['Early warnings', s.warnings], ['Relief received (qty)', s.relief], ['Relief given out (qty)', s.distributed], ['Estimated loss', 'Tk ' + num(s.loss)]];
  document.getElementById('cards').innerHTML = cards.map(c => '<div class="card stat"><div class="num">' + (typeof c[1] === 'number' ? num(c[1]) : esc(c[1])) + '</div><div class="lbl">' + c[0] + '</div></div>').join('');
  tbl(document.getElementById('tDis'), ['ID','Type','Date','Areas','People','Shelters','Orgs','Loss (Tk)'],
      d.disasters.map(x => [x.id, x.type, x.date, x.areas, x.people, x.shelters, x.orgs, num(x.loss)]), [4,5,6,7]);
  tbl(document.getElementById('tSh'), ['ID','Shelter','Area','Capacity','In now'],
      d.shelters.map(x => [x.id, x.name, x.area, num(x.capacity), num(x.occupied)]), [3,4]);
  tbl(document.getElementById('tRel'), ['ID','Item','Category','Received','Given','Left'],
      d.relief.map(x => [x.id, x.name, x.cat, num(x.received), num(x.distributed), num(x.remaining)]), [3,4,5]);
  const mx = Math.max(1, ...d.risk.map(r => r.n));
  document.getElementById('risk').innerHTML = d.risk.map(r => '<div class="bar-row"><span class="bar-l">' + esc(r.lvl) + '</span><span class="bar"><i style="width:' + Math.round(r.n / mx * 100) + '%"></i></span><span class="bar-n">' + esc(r.n) + '</span></div>').join('');
}
async function loadReport() {
  if (busy) return;                // a slow server must not pile up requests
  busy = true;
  const dot = document.getElementById('dot'), st = document.getElementById('state');
  try {
    const r = await fetch('report_data.php', {cache: 'no-store'});
    if (r.status === 401) { location.href = 'index.php'; return; }
    const d = await r.json();
    const sig = JSON.stringify([d.stats, d.signals, d.paths, d.space, d.areas, d.relief_by, d.disasters, d.risk, d.shelters, d.relief]);
    if (sig !== last) {
      draw(d);
      if (last !== '') document.querySelectorAll('.card.stat,.tbl').forEach(e => { e.classList.remove('flash'); void e.offsetWidth; e.classList.add('flash'); });
      last = sig;
    }
    dot.className = 'dot'; st.textContent = 'Live';
    document.getElementById('time').textContent = 'Last checked: ' + d.time;
  } catch (e) {
    dot.className = 'dot off'; st.textContent = 'Connection lost - retrying...';
  } finally {
    busy = false;
  }
}
loadReport();
setInterval(() => { if (!document.hidden) loadReport(); }, REFRESH_MS);   // no polling while the tab is in the background
document.addEventListener('visibilitychange', () => { if (!document.hidden) loadReport(); });
document.getElementById('refreshNow').addEventListener('click', loadReport);
document.getElementById('printNow').addEventListener('click', () => window.print());
