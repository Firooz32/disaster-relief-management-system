"""
Screen-recording framework for the DRMS demo video (no on-screen text is ever added).

* Real Chromium pages driven by Playwright; frames captured with the Chrome DevTools screencast (JPEG q=92) together with
  wall-clock timestamps, then rebuilt into constant-30-fps 1080p H.264 by render.py. One clock for all pages, so split-screen
  halves recorded at the same time line up exactly.
* A drawn mouse cursor (white arrow + click ripple) follows real mouse events; moves follow eased, slightly curved paths at
  human speed; typing has a human rhythm.
* Beats: `async with rec.beat(seconds)` keeps a step on screen for at least `seconds` (the narration time from the script),
  and every beat is logged with its start/end so the voiceover notes can quote real timecodes.
"""
from __future__ import annotations

import asyncio
import base64
import contextlib
import json
import math
import random
import sys
import time
from pathlib import Path

from playwright.async_api import Browser, BrowserContext, Page, async_playwright

CURSOR_JS = r"""
(() => {
  if (window.__drmsCursor) return;
  window.__drmsCursor = true;
  const KEY = '__drms_cursor_pos';
  const css = `#__cur{position:fixed;left:0;top:0;width:26px;height:26px;z-index:2147483647;pointer-events:none;will-change:transform}
    #__cur.hidden{display:none}
    .__ripple{position:fixed;width:34px;height:34px;margin:-17px 0 0 -17px;border-radius:50%;border:3px solid rgba(37,99,235,.85);
      background:rgba(37,99,235,.18);z-index:2147483646;pointer-events:none;animation:__rp .45s ease-out forwards}
    @keyframes __rp{from{transform:scale(.35);opacity:1}to{transform:scale(1.25);opacity:0}}`;
  const svg = '<svg width="26" height="26" viewBox="0 0 26 26" xmlns="http://www.w3.org/2000/svg">'
    + '<path d="M2.5 1.8 L2.5 21 L7.6 16.2 L11.2 24.2 L14.6 22.7 L11.1 14.9 L18 14.9 Z" fill="#fff" stroke="#111" stroke-width="1.5" stroke-linejoin="round"/></svg>';
  const place = (x, y) => { const c = document.getElementById('__cur'); if (c) c.style.transform = `translate(${x - 2}px, ${y - 1}px)`; };
  const init = () => {
    if (document.getElementById('__cur')) return;
    const st = document.createElement('style'); st.textContent = css; document.documentElement.appendChild(st);
    const c = document.createElement('div'); c.id = '__cur'; c.innerHTML = svg; document.documentElement.appendChild(c);
    let p = null; try { p = JSON.parse(sessionStorage.getItem(KEY)); } catch (e) {}
    if (p) place(p[0], p[1]); else c.style.transform = 'translate(-60px,-60px)';
    if (window.__curHidden) c.classList.add('hidden');
  };
  if (document.documentElement) init(); document.addEventListener('DOMContentLoaded', init);
  addEventListener('mousemove', e => { init(); place(e.clientX, e.clientY);
    try { sessionStorage.setItem(KEY, JSON.stringify([e.clientX, e.clientY])); } catch (err) {} }, true);
  addEventListener('mousedown', e => { const r = document.createElement('div'); r.className = '__ripple';
    r.style.left = e.clientX + 'px'; r.style.top = e.clientY + 'px'; document.documentElement.appendChild(r);
    setTimeout(() => r.remove(), 500); }, true);
  window.__setCursorHidden = h => { window.__curHidden = h; const c = document.getElementById('__cur'); if (c) c.classList.toggle('hidden', h); };
})();
"""


def ease(t: float) -> float:
    return 0.5 - 0.5 * math.cos(math.pi * t)          # smooth start and stop


class Pane:
    """One browser page (full screen or one half of a split screen) with its own frame log and mouse position."""

    def __init__(self, rec: "Recorder", name: str, page: Page, w: int, h: int, scale: float = 1.0):
        # w, h = size of the pane in OUTPUT pixels; the page itself lays out in CSS px = output px / scale (browser zoom)
        self.rec, self.name, self.page, self.w, self.h, self.scale = rec, name, page, w, h, scale
        cw, ch = w / scale, h / scale
        self.x, self.y = cw * 0.62, ch * 0.55
        self.frames: list[tuple[float, str]] = []
        self.dir = rec.out / "frames" / name
        self.dir.mkdir(parents=True, exist_ok=True)
        self._n = 0
        self._cdp = None

    async def start(self) -> None:
        self._cdp = await self.page.context.new_cdp_session(self.page)

        def on_frame(ev):
            self._n += 1
            path = self.dir / f"{self._n:06d}.jpg"
            path.write_bytes(base64.b64decode(ev["data"]))
            self.frames.append((float(ev["metadata"]["timestamp"]), str(path)))
            asyncio.ensure_future(self._ack(ev["sessionId"]))

        self._cdp.on("Page.screencastFrame", on_frame)
        await self._cdp.send("Page.startScreencast", {"format": "jpeg", "quality": 92, "maxWidth": self.w,
                                                       "maxHeight": self.h, "everyNthFrame": 1})

    async def _ack(self, session_id: int) -> None:
        with contextlib.suppress(Exception):            # a late ack after the page closed is harmless
            await self._cdp.send("Page.screencastFrameAck", {"sessionId": session_id})

    async def stop(self) -> None:
        with contextlib.suppress(Exception):
            await self._cdp.send("Page.stopScreencast")

    # ------------------------------------------------------------------ mouse
    async def hide_cursor(self, hidden: bool = True) -> None:
        await self.page.evaluate("h => window.__setCursorHidden && window.__setCursorHidden(h)", hidden)

    async def move(self, x: float, y: float, speed: float = 1.0) -> None:
        """Glide to (x, y) on a gently curved, eased path; time grows with distance like a real hand."""
        x0, y0 = self.x, self.y
        dist = math.hypot(x - x0, y - y0)
        if dist < 2:
            return
        dur = (0.28 + min(dist, 1600) / 1500) / speed
        steps = max(8, int(dur * 60))
        bend = random.uniform(-0.12, 0.12) * dist        # control point pushed sideways
        nx, ny = (-(y - y0) / dist, (x - x0) / dist)
        cx, cy = (x0 + x) / 2 + nx * bend, (y0 + y) / 2 + ny * bend
        for i in range(1, steps + 1):
            t = ease(i / steps)
            px = (1 - t) ** 2 * x0 + 2 * (1 - t) * t * cx + t * t * x
            py = (1 - t) ** 2 * y0 + 2 * (1 - t) * t * cy + t * t * y
            await self.page.mouse.move(px, py)
            await asyncio.sleep(dur / steps)
        self.x, self.y = x, y

    async def point_of(self, selector: str, dx: float = 0.0, dy: float = 0.0, frac_x: float | None = None):
        box = None
        for _ in range(20):                              # AJAX pages (phpMyAdmin) may replace the element: look again
            try:
                loc = self.page.locator(selector).first
                await loc.wait_for(state="visible", timeout=5000)
                await loc.scroll_into_view_if_needed(timeout=3000)
                box = await loc.bounding_box()
                if box:
                    break
            except Exception:
                await asyncio.sleep(0.3)
        if box is None:
            raise RuntimeError(f"{self.name}: {selector} is not visible")
        fx = frac_x if frac_x is not None else random.uniform(0.38, 0.62)
        return box["x"] + box["width"] * fx + dx, box["y"] + box["height"] * random.uniform(0.4, 0.6) + dy

    async def hover(self, selector: str, **kw) -> None:
        x, y = await self.point_of(selector, **kw)
        await self.move(x, y)

    async def click(self, selector: str, settle: float = 0.25, **kw) -> None:
        await self.hover(selector, **kw)
        await asyncio.sleep(random.uniform(0.15, 0.3))
        await self.page.mouse.down()
        await asyncio.sleep(0.09)
        await self.page.mouse.up()
        await asyncio.sleep(settle)

    async def click_nav(self, selector: str, **kw) -> None:
        """Click something that loads a new page and wait for it, then redraw the cursor where the hand is."""
        async with self.page.expect_navigation(wait_until="load"):
            await self.click(selector, settle=0.0, **kw)
        await self.page.mouse.move(self.x + 0.5, self.y + 0.5)
        await asyncio.sleep(0.3)

    async def settle(self, extra: float = 0.4) -> None:
        """Wait until the page has finished loading and its AJAX calls are done."""
        with contextlib.suppress(Exception):
            await self.page.wait_for_load_state("networkidle", timeout=15000)
        await asyncio.sleep(extra)

    async def click_ajax(self, selector: str, **kw) -> None:
        """Click a link that updates the page in place (phpMyAdmin) and wait for the update to finish."""
        await self.click(selector, settle=0.2, **kw)
        await self.settle()

    async def type(self, selector: str, text: str, clear: bool = True, wpm: int = 230) -> None:
        await self.click(selector, settle=0.15)
        if clear:
            await self.page.keyboard.press("Control+A")
            await self.page.keyboard.press("Delete")
        base = 60 / (wpm * 5)
        for ch in text:
            await self.page.keyboard.type(ch)
            await asyncio.sleep(base * random.uniform(0.6, 1.5) + (0.12 if ch in " .,-_@" else 0))

    async def select(self, selector: str, label_contains: str) -> None:
        """Real dropdown use: click the select (the menu itself is native, so it is chosen by value) then settle."""
        await self.click(selector, settle=0.35)
        loc = self.page.locator(selector).first
        value = await loc.evaluate("(s, t) => [...s.options].find(o => o.text.includes(t))?.value", label_contains)
        if value is None:
            raise RuntimeError(f"{selector}: no option containing {label_contains!r}")
        await loc.select_option(value)
        await self.page.keyboard.press("Escape")
        await asyncio.sleep(0.25)

    async def scroll(self, dy: float, duration: float = 1.2) -> None:
        steps = max(6, int(duration * 30))
        for i in range(steps):
            await self.page.mouse.wheel(0, dy / steps)
            await asyncio.sleep(duration / steps)

    async def goto(self, url: str) -> None:
        await self.page.goto(url, wait_until="load")
        await self.page.mouse.move(self.x + 0.5, self.y + 0.5)


class Recorder:
    def __init__(self, out: Path):
        self.out = out
        self.out.mkdir(parents=True, exist_ok=True)
        self.panes: dict[str, Pane] = {}
        self.beats: list[dict] = []
        self.units: list[dict] = []
        self.marks: dict[str, float] = {}
        self._pw = None
        self.browser: Browser | None = None

    async def __aenter__(self):
        self._pw = await async_playwright().start()
        self.browser = await self._pw.chromium.launch(headless=True, args=["--hide-scrollbars", "--force-color-profile=srgb"])
        return self

    async def __aexit__(self, *exc):
        if exc[0] is not None:                          # failed take: keep a picture of every open page for the diagnosis
            for p in self.panes.values():
                with contextlib.suppress(Exception):
                    await p.page.screenshot(path=str(self.out / f"FAIL_{p.name}.png"), timeout=5000)
        for p in self.panes.values():
            await p.stop()
        await self.browser.close()
        await self._pw.stop()
        (self.out / "timeline.json").write_text(json.dumps({
            "marks": self.marks, "beats": self.beats, "units": self.units,
            "panes": {n: {"w": p.w, "h": p.h, "frames": p.frames} for n, p in self.panes.items()},
        }, indent=1))

    async def pane(self, name: str, w: int, h: int, scale: float = 1.0) -> Pane:
        """scale > 1 enlarges text and controls like browser zoom (1.15 = 115%) while the pane stays w x h output pixels."""
        ctx: BrowserContext = await self.browser.new_context(
            viewport={"width": round(w / scale), "height": round(h / scale)}, device_scale_factor=scale,
            locale="en-GB", timezone_id="Asia/Dhaka")
        await ctx.add_init_script(CURSOR_JS)
        page = await ctx.new_page()
        p = Pane(self, name, page, w, h, scale)
        self.panes[name] = p
        return p

    def now(self) -> float:
        return time.time()                                  # same clock as the screencast timestamps

    def mark(self, name: str) -> None:
        self.marks[name] = self.now()

    @contextlib.asynccontextmanager
    async def beat(self, seconds: float, key: str):
        """Hold the step on screen for at least `seconds` (narration length)."""
        t0 = self.now()
        yield
        left = seconds - (self.now() - t0)
        if left > 0:
            await asyncio.sleep(left)
        self.beats.append({"key": key, "start": t0, "end": self.now()})

    @contextlib.asynccontextmanager
    async def unit(self, name: str, layout: str, panes: list[Pane]):
        """One contiguous stretch of picture with fixed panes (layout 'full' = 1 pane, 'split' = left, right).
        Beats must lie inside one unit. The panes are closed afterwards, which frees memory."""
        for p in panes:
            await p.start()
        await asyncio.sleep(0.7)                            # the first screencast frame arrives
        t0 = self.now()
        self.marks[name + "_start"] = t0
        try:
            yield
        finally:
            if sys.exc_info()[0] is not None:               # failed take: a picture of every pane for the diagnosis
                for p in panes:
                    with contextlib.suppress(Exception):
                        await p.page.screenshot(path=str(self.out / f"FAIL_{p.name}.png"), timeout=5000)
            await asyncio.sleep(0.4)
            t1 = self.now()
            self.marks[name + "_end"] = t1
            self.units.append({"name": name, "layout": layout, "panes": [p.name for p in panes], "start": t0, "end": t1})
            for p in panes:
                await p.stop()
                with contextlib.suppress(Exception):
                    await p.page.context.close()
