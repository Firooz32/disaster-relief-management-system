"""Camera over the live (vector) Mermaid ERD: hides the page title/caption, then pans/zooms smoothly to entities."""
from __future__ import annotations

import asyncio
from pathlib import Path

ERD = (Path(__file__).resolve().parents[2] / "submission" / "diagrams" / "ERD_Full.html").as_uri()

SETUP_JS = r"""
() => {
  document.querySelectorAll('body > h1, body > p').forEach(e => e.remove());   // page title only: labels inside the SVG are <p> too
  document.body.style.cssText = 'margin:0;overflow:hidden;background:#fff;width:100vw;height:100vh';
  const pre = document.querySelector('.mermaid'); pre.style.cssText = 'margin:0;padding:0;width:auto';
  const svg = pre.querySelector('svg');
  svg.style.cssText = 'position:absolute;left:0;top:0;transform-origin:0 0;transition:transform 1.6s cubic-bezier(.45,.05,.25,1);max-width:none';
  const vb = svg.viewBox.baseVal; svg.setAttribute('width', vb.width); svg.setAttribute('height', vb.height);
  window.__erd = { svg, vb };
  window.__entity = name => [...svg.querySelectorAll('g[id^="entity-' + name + '-"]')][0];
  window.__frame = (names, pad, maxScale) => {           // camera so that the given entities fill the screen
    let x1 = 1e9, y1 = 1e9, x2 = -1e9, y2 = -1e9;
    const vb = window.__erd.vb;
    for (const n of names) {
      // entity box in SVG user units: both screen CTMs carry the same CSS transform, so it cancels (safe mid-animation)
      const g = window.__entity(n); const b = g.getBBox();
      const m = window.__erd.svg.getScreenCTM().inverse().multiply(g.getScreenCTM());
      const p1 = new DOMPoint(b.x, b.y).matrixTransform(m), p2 = new DOMPoint(b.x + b.width, b.y + b.height).matrixTransform(m);
      x1 = Math.min(x1, p1.x - vb.x); y1 = Math.min(y1, p1.y - vb.y); x2 = Math.max(x2, p2.x - vb.x); y2 = Math.max(y2, p2.y - vb.y); }
    x1 -= pad; y1 -= pad; x2 += pad; y2 += pad;
    const W = innerWidth, H = innerHeight;
    const s = Math.min(maxScale, W / (x2 - x1), H / (y2 - y1));
    const x = (W - (x2 - x1) * s) / 2 - x1 * s, y = (H - (y2 - y1) * s) / 2 - y1 * s;
    window.__erd.state = { s, x, y };
    window.__erd.svg.style.transform = `translate(${x}px, ${y}px) scale(${s})`;
    return { s, x, y };
  };
  window.__fit = () => window.__frame([...svg.querySelectorAll('g[id^="entity-"]')].map(g => g.id.replace(/^entity-(.*)-\d+$/, '$1')), 30, 5);
  window.__fit();
  return { w: vb.width, h: vb.height };
}
"""


async def open_erd(pane) -> None:
    await pane.page.goto(ERD, wait_until="load")
    await pane.page.wait_for_selector(".mermaid svg g[id^='entity-']", timeout=60000)
    await asyncio.sleep(1.0)
    await pane.page.evaluate(SETUP_JS)
    await asyncio.sleep(1.8)


async def frame(pane, names: list[str], pad: int = 40, max_scale: float = 1.6, wait: float = 1.8) -> None:
    await pane.page.evaluate("([n, p, m]) => window.__frame(n, p, m)", [names, pad, max_scale])
    await asyncio.sleep(wait)


async def fit(pane, wait: float = 1.8) -> None:
    await pane.page.evaluate("() => window.__fit()")
    await asyncio.sleep(wait)


async def point_entity(pane, name: str, row: str | None = None) -> None:
    """Move the cursor to an entity's title, or to one of its attribute rows (by attribute name)."""
    box = await pane.page.evaluate("""([n, row]) => {
        const g = window.__entity(n);
        let el = g.querySelector('.label, text') || g;
        if (row) { el = [...g.querySelectorAll('text, span, .nodeLabel')].find(t => t.textContent.trim() === row) || el; }
        const r = el.getBoundingClientRect(); return { x: r.left + r.width / 2, y: r.top + r.height / 2 };
    }""", [name, row])
    await pane.move(box["x"], box["y"])
