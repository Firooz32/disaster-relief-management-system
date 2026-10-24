(function () {
  // small behaviours that used to be inline handlers (the Content-Security-Policy allows no inline scripts)
  document.addEventListener('change', function (e) { if (e.target.hasAttribute && e.target.hasAttribute('data-autosubmit')) e.target.form.submit(); });
  document.addEventListener('click', function (e) { var b = e.target.closest && e.target.closest('[data-print]'); if (b) window.print(); });
  document.addEventListener('submit', function (e) { var m = e.target.getAttribute && e.target.getAttribute('data-confirm'); if (m && !confirm(m)) e.preventDefault(); });

  // mobile menu
  var mb = document.getElementById('menuBtn');
  if (mb) mb.addEventListener('click', function () { document.body.classList.toggle('nav-open'); });

  // dictionary-style search suggestions
  var box = document.getElementById('q');
  if (!box) return;
  var form = box.form, colSel = document.getElementById('col'), list = document.getElementById('sugg');
  var table = box.dataset.table, timer = null, items = [], idx = -1, ctrl = null;

  function hide() { list.innerHTML = ''; list.hidden = true; items = []; idx = -1; }
  function render() {
    list.innerHTML = '';
    if (!items.length) { hide(); return; }
    items.forEach(function (it, i) {
      var li = document.createElement('li');
      li.className = 'sg' + (i === idx ? ' on' : '');
      var b = document.createElement('span'); b.textContent = it.v;
      var s = document.createElement('small'); s.textContent = it.c.replace(/_/g, ' ');
      li.appendChild(b); li.appendChild(s);
      li.addEventListener('mousedown', function (e) { e.preventDefault(); pick(i); });
      list.appendChild(li);
    });
    list.hidden = false;
  }
  function pick(i) { box.value = items[i].v; hide(); form.submit(); }
  function fetchSug() {
    var v = box.value.trim();
    if (!v) { hide(); return; }
    if (ctrl && window.AbortController) ctrl.abort();
    ctrl = window.AbortController ? new AbortController() : null;
    fetch('api.php?t=' + encodeURIComponent(table) + '&col=' + encodeURIComponent(colSel.value) + '&q=' + encodeURIComponent(v),
          { credentials: 'same-origin', signal: ctrl ? ctrl.signal : undefined })
      .then(function (r) { return r.json(); })
      .then(function (d) { items = d; idx = -1; render(); })
      .catch(function () {});
  }
  box.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(fetchSug, 120); });
  box.addEventListener('keydown', function (e) {
    if (list.hidden) return;
    if (e.key === 'ArrowDown') { e.preventDefault(); idx = (idx + 1) % items.length; render(); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); idx = (idx - 1 + items.length) % items.length; render(); }
    else if (e.key === 'Enter' && idx >= 0) { e.preventDefault(); pick(idx); }
    else if (e.key === 'Escape') { hide(); }
  });
  box.addEventListener('blur', function () { setTimeout(hide, 150); });
  colSel.addEventListener('change', function () { hide(); fetchSug(); });
})();
