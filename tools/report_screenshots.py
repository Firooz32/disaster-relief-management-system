"""Screenshots for the report (chapter 3). Needs the demo app (tools/reset_demo.ps1 -> http://127.0.0.1:8099/) and Apache with phpMyAdmin.
    py -3 tools/report_screenshots.py
Writes submission/report/screenshots/shot_*.png. Only the scratch database drms_demo is used."""
import asyncio
from pathlib import Path
from playwright.async_api import async_playwright

APP, PMA = "http://127.0.0.1:8099/", "http://localhost/phpmyadmin/"
OUT = Path(__file__).resolve().parents[1] / "submission" / "report" / "screenshots"
W, H, S = 1440, 900, 1.5


async def main() -> None:
    OUT.mkdir(parents=True, exist_ok=True)
    async with async_playwright() as pw:
        b = await pw.chromium.launch(headless=True, args=["--hide-scrollbars", "--force-color-profile=srgb"])
        ctx = await b.new_context(viewport={"width": W, "height": H}, device_scale_factor=S, locale="en-GB", timezone_id="Asia/Dhaka")
        p = await ctx.new_page()
        shot = lambda n, **kw: p.screenshot(path=str(OUT / f"shot_{n}.png"), **kw)
        await p.goto(APP + "index.php"); await p.wait_for_load_state("networkidle"); await shot("signin")
        await p.fill("input[name=username]", "demo_admin"); await p.fill("input[name=password]", "DemoAdmin2026")
        await p.click("button[type=submit]"); await p.wait_for_url("**/dashboard.php"); await p.wait_for_load_state("networkidle")
        await asyncio.sleep(1); await shot("dashboard")
        await p.goto(APP + "report.php"); await p.wait_for_selector("text=Warning signals"); await asyncio.sleep(4); await shot("livereport")
        await p.goto(APP + "map.php"); await p.wait_for_load_state("networkidle"); await asyncio.sleep(4); await shot("map")
        await p.goto(APP + "table.php?t=shelter"); await p.wait_for_load_state("networkidle"); await shot("table")
        await p.goto(APP + "table.php?t=affected_person"); await p.click("a:text-is('+ Add record')"); await p.wait_for_load_state("networkidle")
        await p.fill("input[name='f[Name]']", "Test Family"); await p.fill("input[name='f[Family_Size]']", "-2")
        await p.evaluate("document.querySelector('main form[method=post]')?.setAttribute('novalidate','')")
        await p.click("button:has-text('Save record')"); await p.wait_for_selector(".alert-err"); await shot("form_error")
        await p.goto(APP + "disaster.php?id=1"); await p.wait_for_load_state("networkidle"); await asyncio.sleep(3); await shot("disaster")
        # phpMyAdmin beside the application, after a real change: capacity of shelter 5 edited in the app (11 -> 20)
        await p.set_viewport_size({"width": 860, "height": 760})
        await p.goto(APP + "table.php?t=shelter"); await p.click("a[href*='a=edit'][href*='Shelter_ID%5D=5']")
        await p.fill("input[name='f[Capacity]']", "20"); await p.click("button:has-text('Update record')"); await p.wait_for_load_state("networkidle")
        await p.goto(APP + "table.php?t=shelter"); await p.wait_for_load_state("networkidle")
        await p.screenshot(path=str(OUT / "pma_right.png"))
        left = await b.new_context(viewport={"width": 816, "height": 760}, device_scale_factor=S, locale="en-GB")
        lp = await left.new_page()
        await lp.goto(PMA); await lp.fill("#input_username", "root"); await lp.click("#input_go"); await lp.wait_for_load_state("networkidle")
        await lp.goto(PMA + "index.php?route=/sql&db=drms_demo&table=shelter&pos=0"); await lp.wait_for_load_state("networkidle")
        await asyncio.sleep(2); await lp.screenshot(path=str(OUT / "pma_left.png"))
        import cv2, numpy as np
        l, r = cv2.imread(str(OUT / "pma_left.png")), cv2.imread(str(OUT / "pma_right.png"))
        r = cv2.resize(r, (round(r.shape[1] * l.shape[0] / r.shape[0]), l.shape[0]), interpolation=cv2.INTER_AREA)
        cv2.imwrite(str(OUT / "shot_pma_beside_ui.png"), np.hstack([l, np.full((l.shape[0], 6, 3), 90, np.uint8), r]))
        await b.close()

asyncio.run(main())
