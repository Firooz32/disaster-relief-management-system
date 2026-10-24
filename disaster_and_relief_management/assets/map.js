(function () {
  var data = JSON.parse(document.getElementById('mapdata').textContent);
  var el = document.getElementById('map');
  if (typeof L === 'undefined') {      // no internet: the tables below the map still show every location
    el.innerHTML = '<p class="muted" style="padding:16px">The map library could not be loaded (internet needed). The tables below list every location.</p>';
    return;
  }
  var map = L.map('map');
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, referrerPolicy: 'strict-origin-when-cross-origin', attribution: '&copy; OpenStreetMap contributors' }).addTo(map);

  // popups are built with textContent, so database text can never inject HTML
  function popup(title, lines) {
    var d = document.createElement('div');
    var b = document.createElement('b'); b.textContent = title; d.appendChild(b);
    lines.forEach(function (t) { d.appendChild(document.createElement('br')); d.appendChild(document.createTextNode(t)); });
    return d;
  }
  var riskColor = { 'Very High': '#c0392b', 'High': '#e67e22', 'Medium': '#f1c40f', 'Low': '#2a9d8f' };
  var pts = [];
  data.areas.forEach(function (a) {
    if (a.lat === null || a.lng === null) return;
    var c = riskColor[a.risk] || '#7f8c8d';
    L.circle([a.lat, a.lng], { radius: 7000, color: c, fillColor: c, fillOpacity: 0.25 }).addTo(map)
      .bindPopup(popup(a.name, [a.loc, 'Risk: ' + (a.risk || 'unknown'), 'Disasters: ' + a.disasters, 'Estimated loss: Tk ' + Number(a.loss).toLocaleString()]));
    pts.push([a.lat, a.lng]);
  });
  data.shelters.forEach(function (s) {
    if (s.lat === null || s.lng === null) return;
    var pct = s.capacity > 0 ? s.occupied / s.capacity : 0;
    var c = pct >= 0.9 ? '#c0392b' : pct >= 0.7 ? '#e67e22' : '#1e8449';
    L.circleMarker([s.lat, s.lng], { radius: 8, color: '#fff', weight: 2, fillColor: c, fillOpacity: 1 }).addTo(map)
      .bindPopup(popup(s.name, [s.area || '', 'Staying now: ' + s.occupied + ' / ' + s.capacity]));
    pts.push([s.lat, s.lng]);
  });
  if (pts.length) map.fitBounds(pts, { padding: [30, 30] }); else map.setView([22.6, 89.5], 8);
})();
