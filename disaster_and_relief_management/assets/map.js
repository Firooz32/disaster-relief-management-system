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
  (data.paths || []).forEach(function (disaster) {
    var actual = disaster.points.filter(function (p) { return p.type === 'Actual'; });
    var forecast = disaster.points.filter(function (p) { return p.type === 'Forecast'; });
    var ll = function (p) { return [p.lat, p.lng]; };
    if (actual.length > 1) L.polyline(actual.map(ll), { color: '#c0392b', weight: 4 }).addTo(map);
    if (forecast.length) {
      var future = (actual.length ? [ll(actual[actual.length - 1])] : []).concat(forecast.map(ll));
      L.polyline(future, { color: '#e67e22', weight: 4, dashArray: '8 8' }).addTo(map);
    }
    disaster.points.forEach(function (point, index) {
      var current = index === disaster.current;
      var isForecast = point.type === 'Forecast';
      var detail = [disaster.type + ' #' + disaster.id, point.time, isForecast ? 'Forecast position' : 'Observed position',
        'Impact radius: ' + point.radius + ' km'];
      if (point.wind !== null) detail.push('Wind: ' + point.wind + ' km/h');
      if (current) L.circle(ll(point), { radius: point.radius * 1000, color: '#c0392b', weight: 2, fillColor: '#c0392b', fillOpacity: 0.18 }).addTo(map);
      L.circleMarker(ll(point), current ? { radius: 10, color: '#fff', weight: 3, fillColor: '#c0392b', fillOpacity: 1 }
                                        : { radius: 5, color: isForecast ? '#e67e22' : '#c0392b', weight: 2, fillColor: isForecast ? '#fff' : '#c0392b', fillOpacity: 1 })
        .addTo(map).bindPopup(popup(disaster.type + (current ? ' - current position' : ''), detail));
      pts.push(ll(point));
    });
  });
  if (pts.length) map.fitBounds(pts, { padding: [30, 30] }); else map.setView([22.6, 89.5], 8);
})();
