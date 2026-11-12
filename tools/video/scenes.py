"""
Scene scripts S1-S8 of the demo video. Every step ends with an assertion: if the page does not show what the narration says,
the take aborts (a wrong video is never produced). Beat keys come from narration.py; a beat is held at least as long as its
narration, the real actions usually take longer.

Units: one unit = one contiguous recording with fixed panes ('full' = one pane, 'split' = phpMyAdmin | app). A beat never
spans two units. Zoom (browser-zoom look): split panes 1.1, full-screen panes 1.2, ERD 1.0 (vector camera instead).
"""
from __future__ import annotations

import asyncio
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
import erd_camera as erd  # noqa: E402
from narration import BEATS, seconds  # noqa: E402
from recorder import Pane, Recorder  # noqa: E402

APP = "http://127.0.0.1:8099/"
PMA = "http://localhost/phpmyadmin/"
TREE = "#pma_navigation_tree"
FULL, LEFT, RIGHT = (1920, 1080, 1.2), (898, 1080, 1.1), (1018, 1080, 1.1)
ADMIN, EDITOR = ("demo_admin", "DemoAdmin2026"), ("demo_editor", "DemoEditor2026")
SEC = {b[0]: seconds(b) for b in BEATS}


def beat(rec: Recorder, key: str):
    return rec.beat(SEC[key], key)


# ------------------------------------------------------------------------------------------------ helpers
async def see(p: Pane, selector: str, within: str = "body", timeout: int = 20000) -> None:
    """Assert that something is visible on the page (selector or text=...)."""
    await found(p.page.locator(within), selector).wait_for(state="visible", timeout=timeout)


def found(root, spec: str):
    """Locator for an expectation: 'cell=11' = a table cell whose whole text is 11 (works with nested spans); else a selector."""
    if spec.startswith("cell="):
        return root.get_by_role("cell", name=spec[5:], exact=True).first
    return root.locator(spec).first


async def dwell(p: Pane, selector: str, secs: float = 1.0, **kw) -> None:
    await p.hover(selector, **kw)
    await asyncio.sleep(secs)


async def drag(p: Pane, x0: float, y0: float, x1: float, y1: float) -> None:
    await p.move(x0, y0)
    await p.page.mouse.down()
    await p.move(x1, y1, speed=0.45)
    await p.page.mouse.up()


async def scroll_to(p: Pane, selector: str, duration: float = 1.6, offset: float = 100) -> None:
    """Eased page scroll so that the element's top is `offset` px below the top. No wheel events: a Leaflet map under the
    pointer would zoom instead of letting the page scroll."""
    await p.page.evaluate("""([sel, off, ms]) => new Promise(res => {
        const y0 = scrollY, y1 = document.querySelector(sel).getBoundingClientRect().top + scrollY - off, t0 = performance.now();
        const step = t => { const k = Math.min(1, (t - t0) / ms), e = .5 - .5 * Math.cos(Math.PI * k); scrollTo(0, y0 + (y1 - y0) * e);
                            k < 1 ? requestAnimationFrame(step) : res(); };
        requestAnimationFrame(step); })""", [selector, offset, duration * 1000])


async def hscroll(p: Pane, dx: float, duration: float = 1.4) -> None:
    """Scroll a wide phpMyAdmin result sideways (the narrow split pane cuts off the right-hand columns)."""
    await p.hover("table.table_results tbody tr >> nth=-1", frac_x=0.5)
    steps = max(6, int(duration * 30))
    for _ in range(steps):
        await p.page.mouse.wheel(dx / steps, 0)
        await asyncio.sleep(duration / steps)


async def focus(active: Pane, other: Pane) -> None:
    await other.hide_cursor(True)
    await active.hide_cursor(False)


async def app_session(rec: Recorder, name: str, user, size, landing: str = "dashboard.php") -> Pane:
    """App pane signed in off-camera (a recording starts on the page we want to show)."""
    p = await rec.pane(name, *size)
    await p.goto(APP + "index.php")
    await p.page.fill("input[name=username]", user[0])
    await p.page.fill("input[name=password]", user[1])
    await p.page.click("button[type=submit]")
    await p.page.wait_for_url("**/dashboard.php")
    if landing != "dashboard.php":
        await p.goto(APP + landing)
    await p.settle(0.6)
    return p


async def pma_session(rec: Recorder, name: str, size, db: bool = True, table: str | None = None) -> Pane:
    p = await rec.pane(name, *size)
    await p.goto(PMA)
    await p.page.fill("#input_username", "root")
    await p.page.click("#input_go")
    await p.settle(1.2)
    if db:
        await p.page.click(f"{TREE} li a:text-is('drms_demo')")
        await p.settle(1.2)
    if table:
        await p.page.click(f"{TREE} li a.hover_show_full:text-is('{table}')")
        await p.settle(1.2)
    return p


async def pma_table(p: Pane, name: str, expect: str | None = None, rows: int | None = None) -> None:
    """Open a table (Browse) from the navigation tree and wait until the expected content is shown."""
    await p.click_ajax(f"{TREE} li a.hover_show_full:text-is('{name}')")
    content = p.page.locator("#page_content")
    if expect:
        await found(content, expect).wait_for(state="visible", timeout=20000)
    if rows is not None:
        await p.page.wait_for_function(
            "n => document.querySelectorAll('#page_content table.table_results tbody tr').length === n", arg=rows, timeout=20000)


async def pma_sql(p: Pane, query: str, expect: str, wpm: int = 330, paste: bool = False) -> None:
    """Database > SQL tab: type (or paste) the query, press Go, wait for the expected result (a locator inside #page_content)."""
    await p.click_ajax(f"{TREE} li a:text-is('drms_demo')")
    if not await p.page.locator("#topmenu").is_visible():              # narrow (split) pane: the tab bar is a hamburger menu
        await p.click("button.navbar-toggler", settle=0.5)
    await p.click_ajax("#topmenu a[href*='route=/database/sql']")
    box = "#queryfieldscontainer .CodeMirror"
    await p.page.locator(box).wait_for(state="visible", timeout=15000)
    if paste:
        await p.click(box, settle=0.4)
        await p.page.evaluate("q => document.querySelector('#queryfieldscontainer .CodeMirror').CodeMirror.setValue(q)", query)
        await asyncio.sleep(1.0)
    else:
        await p.type(box, query, clear=False, wpm=wpm)
        await asyncio.sleep(0.5)
    await p.click_ajax("#button_submit_query")
    await found(p.page.locator("#page_content"), expect).wait_for(state="visible", timeout=20000)


# ------------------------------------------------------------------------------------------------ S1 intro
async def s1(rec: Recorder) -> None:
    app = await rec.pane("s1_app", *FULL)
    await app.goto(APP + "index.php")
    await app.settle(0.6)
    async with rec.unit("s1", "full", [app]):
        async with beat(rec, "p1_hello"):
            await dwell(app, "img", 2.0)
            await dwell(app, "h1", 1.5)
        async with beat(rec, "p1_problem"):
            await app.type("input[name=username]", ADMIN[0])
            await app.type("input[name=password]", ADMIN[1])
            await app.click_nav("button[type=submit]")
            await see(app, "text=Disasters on record")
            for card in ("disaster", "families", "shelter"):
                await dwell(app, f".dashboard-card-{card}", 1.0)
        async with beat(rec, "p1_scope"):
            for card in ("capacity", "relief"):
                await dwell(app, f".dashboard-card-{card}", 1.0)
            await app.scroll(380, 1.6)
            await see(app, "text=Live status")
            for t in ("text=Warning signals", "text=Shelter space", "text=Relief distribution", "text=Data health"):
                await dwell(app, t, 1.1)


# ------------------------------------------------------------------------------------------------ S2 technology stack
async def s2(rec: Recorder) -> None:
    pma = await pma_session(rec, "s2_pma", FULL, db=False)
    await see(pma, "text=Database server")
    async with rec.unit("s2a", "full", [pma]):
        async with beat(rec, "p1_stack_db"):
            await dwell(pma, "text=Database server >> nth=0", 1.8)
            await dwell(pma, "text=Server type: MySQL", 1.2)
            await dwell(pma, "text=Server version: 8.2.0", 1.2)
            await dwell(pma, "text=Web server >> nth=0", 1.5)
            await dwell(pma, "text=PHP version: 8.2.13", 1.2)
    app = await app_session(rec, "s2_app", ADMIN, FULL, "map.php")
    await see(app, ".leaflet-container")
    await asyncio.sleep(2.5)                                             # tiles load
    async with rec.unit("s2b", "full", [app]):
        async with beat(rec, "p1_stack_fe"):
            box = await app.page.locator(".leaflet-container").first.bounding_box()
            cx, cy = box["x"] + box["width"] * 0.55, box["y"] + box["height"] * 0.5
            await drag(app, cx, cy, cx - 220, cy + 90)
            await asyncio.sleep(1.2)
            await drag(app, cx - 220, cy + 90, cx + 60, cy - 70)
            await asyncio.sleep(1.2)
            await app.page.mouse.wheel(0, -300)
            await asyncio.sleep(2.0)


# ------------------------------------------------------------------------------------------------ S3 ER diagram
async def s3(rec: Recorder) -> None:
    pane = await rec.pane("s3_erd", 1920, 1080, 1.0)
    await erd.open_erd(pane)
    await pane.page.mouse.move(pane.x, pane.y)
    async with rec.unit("s3", "full", [pane]):
        async with beat(rec, "p2_erd"):
            await erd.fit(pane, wait=2.2)
            for n in ("disaster", "area", "shelter", "affected_person", "rescue_org", "relief_item", "users"):
                await erd.point_entity(pane, n)
                await asyncio.sleep(0.6)
        async with beat(rec, "p2_cardinality"):
            await erd.frame(pane, ["disaster", "early_warning", "disaster_track", "damage", "incident_task"], max_scale=1.6, wait=2.2)
            for n in ("disaster", "early_warning", "disaster_track", "damage", "incident_task"):
                await erd.point_entity(pane, n)
                await asyncio.sleep(0.7)
        async with beat(rec, "p2_junction"):
            await erd.frame(pane, ["disaster", "disaster_area", "area"], max_scale=1.5, wait=2.2)
            await erd.point_entity(pane, "disaster_area")
            await asyncio.sleep(0.8)
            for row in ("Disaster_ID", "Area_ID", "Relief_Shortage"):
                await erd.point_entity(pane, "disaster_area", row)
                await asyncio.sleep(1.4)
        async with beat(rec, "p2_events"):
            await erd.frame(pane, ["shelter_assignment"], pad=60, max_scale=2.2, wait=2.2)
            for row in ("Disaster_ID", "Person_ID", "Shelter_ID"):
                await erd.point_entity(pane, "shelter_assignment", row)
                await asyncio.sleep(0.7)
            await erd.frame(pane, ["relief_item", "relief_batch", "relief_distribution", "relief_recipient"], max_scale=1.2, wait=2.4)
            for n in ("relief_item", "relief_batch", "relief_distribution", "relief_recipient"):
                await erd.point_entity(pane, n)
                await asyncio.sleep(0.6)
        async with beat(rec, "p2_nf"):
            await erd.fit(pane, wait=2.2)
            await erd.frame(pane, ["shelter"], pad=50, max_scale=2.0, wait=2.2)
            for row in ("Shelter_ID", "Area_ID", "Shelter_Name"):
                await erd.point_entity(pane, "shelter", row)
                await asyncio.sleep(1.2)
            await erd.frame(pane, ["relief_batch"], pad=50, max_scale=2.0, wait=2.2)
            await erd.point_entity(pane, "relief_batch", "Batch_Code")
            await asyncio.sleep(1.2)
            await erd.frame(pane, ["disaster_affected_person"], pad=80, max_scale=2.4, wait=2.2)
            for row in ("Disaster_ID", "Person_ID"):
                await erd.point_entity(pane, "disaster_affected_person", row)
                await asyncio.sleep(1.2)
        async with beat(rec, "p2_exceptions"):
            await erd.frame(pane, ["relief_item"], pad=50, max_scale=2.0, wait=2.2)
            await erd.point_entity(pane, "relief_item", "Quantity")
            await asyncio.sleep(1.8)
            await erd.frame(pane, ["relief_distribution"], pad=50, max_scale=2.0, wait=2.2)
            for row in ("Relief_ID", "Batch_ID"):
                await erd.point_entity(pane, "relief_distribution", row)
                await asyncio.sleep(1.6)
            await erd.fit(pane, wait=2.0)


# ------------------------------------------------------------------------------------------------ S4 schema in phpMyAdmin
async def s4(rec: Recorder) -> None:
    pma = await pma_session(rec, "s4_pma", FULL)
    await see(pma, "text=affected_person", "#page_content")
    async with rec.unit("s4", "full", [pma]):
        async with beat(rec, "p2_keys"):
            await dwell(pma, "#page_content a[href*='table=affected_person&']", 1.0)
            await dwell(pma, "#page_content a[href*='table=incident_task&']", 1.0)
            await dwell(pma, "#page_content a[href*='table=shelter_assignment&']", 1.0)
            await dwell(pma, "#page_content a[href*='table=users&']", 1.0)
            await pma_table(pma, "shelter_assignment")
            await pma.click_ajax("#topmenu a[href*='route=/table/structure']")
            await see(pma, "#index_div")
            await pma.scroll(420, 1.4)
            await dwell(pma, "#index_div tr:has-text('PRIMARY')", 1.0)
        async with beat(rec, "p2_openstay"):
            await dwell(pma, "#index_div tr:has-text('uq_assignment_open_stay') >> nth=0", 1.6)
            await dwell(pma, "#index_div tr:has-text('uq_assignment_open_stay') td:has-text('Check_Out')", 2.4)
        async with beat(rec, "p2_cascade"):
            await pma.click_ajax("#table_relation_id")
            await see(pma, "text=Foreign key constraints")
            for i in range(3):
                await dwell(pma, f"text=ON DELETE >> nth={i}", 1.8)
        async with beat(rec, "p2_triggers"):
            await pma.click_ajax(f"{TREE} li a:text-is('drms_demo')")
            await pma.click_ajax("#topmenu a[href*='route=/database/triggers']")
            await see(pma, "tr:has-text('trg_assignment_ins') a.edit_anchor")
            await dwell(pma, "tr:has-text('trg_assignment_ins') td >> nth=1", 1.6)
            await dwell(pma, "tr:has-text('trg_damage_ins') td >> nth=1", 0.8)
            await dwell(pma, "tr:has-text('trg_recipient_upd') td >> nth=1", 0.8)
            await pma.click_ajax("tr:has-text('trg_assignment_ins') a.edit_anchor")
            await see(pma, ".CodeMirror-line", "#rteDialog")
            await dwell(pma, "#rteDialog .CodeMirror", 4.5)
            await pma.click_ajax(".ui-dialog button:text-is('Close')")
        async with beat(rec, "p2_explain"):
            await pma_sql(pma, "EXPLAIN SELECT Task_ID FROM incident_task WHERE Status <> 'Done' AND Due_At < NOW();",
                          "td:has-text('Using index')", wpm=450)
            await dwell(pma, "td:has-text('ix_task_due_status') >> nth=0", 1.0)
            await dwell(pma, "td:has-text('Using index')", 1.2)


# ------------------------------------------------------------------------------------------------ S5 live CRUD, split screen
async def s5(rec: Recorder) -> None:
    right = await app_session(rec, "s5_app", EDITOR, RIGHT)
    left = await pma_session(rec, "s5_pma", LEFT, table="affected_person")
    await see(left, "text=Rahim Uddin", "#page_content")
    await left.hide_cursor(True)
    async with rec.unit("s5", "split", [left, right]):
        # -- Create
        async with beat(rec, "p3_create"):
            await right.click_nav("nav a[href='table.php?t=affected_person']")
            await right.click_nav("a:text-is('+ Add record')")
            await right.type("input[name='f[Name]']", "Shirin Akter")
            await right.type("input[name='f[Family_Size]']", "4")
            await right.type("input[name='f[Contact]']", "+880-1712-000206")
            await right.select("select[name='f[Area_ID]']", "Dacope")
            await right.click_nav("button:has-text('Save record')")
            await see(right, ".alert-ok")
            await asyncio.sleep(0.8)
            await focus(left, right)
            await pma_table(left, "affected_person", "text=Shirin Akter", rows=6)
        async with beat(rec, "p3_audit"):
            await pma_table(left, "audit_log", "text=Shirin Akter")
            await hscroll(left, 900)
            await asyncio.sleep(1.0)
        # -- Link to the cyclone (junction row)
        async with beat(rec, "p3_link"):
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=disaster_affected_person']")
            await right.click_nav("a:text-is('+ Add record')")
            await right.select("select[name='f[Disaster_ID]']", "1 - Cyclone")
            await right.select("select[name='f[Person_ID]']", "6 - Shirin")
            await right.click_nav("button:has-text('Save record')")
            await see(right, ".alert-ok")
            await asyncio.sleep(0.6)
            await focus(left, right)
            await pma_table(left, "disaster_affected_person", rows=6)
        # -- Refused: over capacity
        async with beat(rec, "p3_capacity"):
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=shelter_assignment']")
            await right.click_nav("a:text-is('+ Add record')")
            await right.select("select[name='f[Disaster_ID]']", "1 - Cyclone")
            await right.select("select[name='f[Person_ID]']", "6 - Shirin")
            await right.select("select[name='f[Shelter_ID]']", "5 - Dacope Union")
            await right.click("input[name='f[Check_In]']", settle=0.2, frac_x=0.06)      # first segment = month (mm/dd/yyyy)
            await right.page.keyboard.type("10242026", delay=110)
            assert await right.page.input_value("input[name='f[Check_In]']") == "2026-10-24", "check-in date not entered"
            await right.click_nav("button:has-text('Save record')")
            await see(right, ".alert-err")
            assert "capacity" in (await right.page.locator(".alert-err").first.inner_text()).lower(), "expected the over-capacity refusal"
            await asyncio.sleep(0.8)
            await focus(left, right)
            await pma_sql(left, "SELECT SUM(p.Family_Size) AS staying_now FROM shelter_assignment a JOIN affected_person p USING (Person_ID) "
                                "WHERE a.Shelter_ID = 5 AND a.Check_Out IS NULL;", "cell=11", paste=True)
        # -- Accepted: Koyra shelter
        async with beat(rec, "p3_assign"):
            await focus(right, left)
            await right.select("select[name='f[Shelter_ID]']", "1 - Koyra")
            await right.click_nav("button:has-text('Save record')")
            await see(right, ".alert-ok")
            await asyncio.sleep(0.6)
            await focus(left, right)
            await pma_table(left, "shelter_assignment", rows=6)
        # -- Read: sort and search
        async with beat(rec, "p3_read"):
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=shelter_assignment']")
            await right.click_nav("table.tbl th a:has-text('Check In')")
            await right.type("#q", "2026-10-24")
            await right.click_nav("form.search button:has-text('Search')")
            await see(right, "text=matching")
            await asyncio.sleep(1.0)
        # -- Update: capacity 11 -> 20
        async with beat(rec, "p3_update"):
            await right.click_nav("nav a[href='table.php?t=shelter']")
            await right.click_nav("a[href*='a=edit'][href*='Shelter_ID%5D=5']")
            await right.type("input[name='f[Capacity]']", "20")
            await right.click_nav("button:has-text('Update record')")
            await see(right, ".alert-ok")
            await asyncio.sleep(0.6)
            await focus(left, right)
            await pma_table(left, "shelter", "cell=20")
            await pma_table(left, "audit_log", "text=Capacity: 11 -> 20")
            await hscroll(left, 900)
            await asyncio.sleep(1.0)
        # -- Constraints
        async with beat(rec, "p3_check"):
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=affected_person']")
            await right.click_nav("a:text-is('+ Add record')")
            await right.type("input[name='f[Name]']", "Test Family")
            await right.type("input[name='f[Family_Size]']", "-2")
            await right.page.evaluate("document.querySelector('form.form, main form[method=post]')?.setAttribute('novalidate','')")
            await right.click_nav("button:has-text('Save record')")
            await see(right, ".alert-err")
            await asyncio.sleep(0.3)
            await focus(left, right)
            await pma_sql(left, "UPDATE shelter SET Capacity = 0 WHERE Shelter_ID = 5;", "text=chk_shelter_capacity", wpm=420)
        async with beat(rec, "p3_trigger"):
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=shelter_assignment']")
            await focus(left, right)
            await pma_sql(left, "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (2, 5, 1, '2026-10-05');",
                          "text=That shelter is not used for this disaster", paste=True)
        # -- Delete (admin): RESTRICT
        async with beat(rec, "p3_restrict"):
            await focus(right, left)
            await right.click_nav("button:has-text('Sign out')")
            await right.type("input[name=username]", ADMIN[0])
            await right.type("input[name=password]", ADMIN[1])
            await right.click_nav("button[type=submit]")
            await right.click_nav("nav a[href='table.php?t=shelter']")
            await right.click_nav("a[href*='a=delete'][href*='Shelter_ID%5D=5']")
            await see(right, "text=blocks the deletion")
            await asyncio.sleep(1.8)
            await right.click_nav("a:text-is('Cancel')")
        # -- Delete (admin): CASCADE
        async with beat(rec, "p3_cascade"):
            await focus(left, right)
            await pma_sql(left, "SELECT COUNT(*) AS warnings FROM early_warning WHERE Disaster_ID = 3;", "cell=1", paste=True)
            await focus(right, left)
            await right.click_nav("nav a[href='table.php?t=disaster']")
            await right.click_nav("a[href*='a=delete'][href*='Disaster_ID%5D=3']")
            await see(right, "text=will be deleted too")
            await asyncio.sleep(1.6)
            await right.click_nav("button:has-text('Yes, delete')")
            await see(right, ".alert-ok")
            await asyncio.sleep(0.5)
            await focus(left, right)
            await pma_sql(left, "SELECT COUNT(*) AS warnings FROM early_warning WHERE Disaster_ID = 3;", "cell=0", paste=True)


# ------------------------------------------------------------------------------------------------ S6 reports
Q3 = """SELECT a.Area_Name, da.Relief_Shortage AS Flagged,
       COALESCE(SUM(p.Family_Size), 0) AS People,
       COALESCE(SUM(CASE WHEN EXISTS (SELECT 1 FROM relief_recipient r JOIN relief_distribution x ON x.Distribution_ID = r.Distribution_ID
                                       WHERE r.Person_ID = p.Person_ID AND x.Disaster_ID = da.Disaster_ID)
                         THEN p.Family_Size END), 0) AS Received
  FROM disaster_area da
  JOIN area a ON a.Area_ID = da.Area_ID
  LEFT JOIN disaster_affected_person dp ON dp.Disaster_ID = da.Disaster_ID
  LEFT JOIN affected_person p ON p.Person_ID = dp.Person_ID AND p.Area_ID = da.Area_ID
 WHERE da.Disaster_ID = 1
 GROUP BY a.Area_Name, da.Relief_Shortage
 ORDER BY a.Area_Name;"""


async def s6(rec: Recorder) -> None:
    app = await app_session(rec, "s6_app", ADMIN, FULL, "report.php")
    await see(app, "text=Warning signals")
    await asyncio.sleep(3.0)                                             # map and panels draw
    async with rec.unit("s6a", "full", [app]):
        async with beat(rec, "p3_signals"):
            await dwell(app, "#signalPanel .sig-card >> nth=0", 1.4)
            await see(app, "text=Signal 10 - Severe Danger", "#signalPanel")
            await dwell(app, "#signalPanel table tbody tr >> nth=0", 1.2)
            await dwell(app, "#signalPanel table tbody tr >> nth=3", 1.4)
        async with beat(rec, "p3_shelters"):
            await scroll_to(app, "#spacePanel", 1.8)
            await see(app, "text=9 places left", "#spacePanel")
            await dwell(app, "#spacePanel .sp-card:has-text('Dacope Union')", 2.0)
            await dwell(app, "#spacePanel .sp-card:has-text('Koyra Government')", 1.2)
        async with beat(rec, "p3_relief"):
            await scroll_to(app, "#reliefPanel", 1.8)
            await see(app, ".sig:has-text('Shortage')", "#reliefPanel")
            await dwell(app, "#reliefPanel .rl-card:has-text('Paikgachha')", 2.0)
            await dwell(app, "#reliefPanel .rl-card:has-text('Dacope')", 2.4)
            await dwell(app, "#reliefPanel .rl-card:has-text('Koyra')", 1.2)
        async with beat(rec, "p3_path"):
            await scroll_to(app, "#pathPanel", 1.8)
            await asyncio.sleep(1.5)                                     # tiles
            await dwell(app, "#pathPanel .leaflet-container", 1.6)
            await dwell(app, "#pathPanel h4:has-text('Currently crossing')", 1.3)
            await dwell(app, "#pathPanel h4:has-text('In the forecast path')", 1.3)
            await dwell(app, "#pathPanel h4:has-text('Already passed')", 1.3)
    # -- Q3 beside the relief panel
    left = await pma_session(rec, "s6_pma", LEFT)
    right = await app_session(rec, "s6_app2", ADMIN, RIGHT, "report.php")
    await see(right, "text=Warning signals")
    await asyncio.sleep(2.0)
    await right.page.evaluate("document.querySelector('#reliefPanel').scrollIntoView()")
    await left.hide_cursor(False)
    await right.hide_cursor(True)
    async with rec.unit("s6b", "split", [left, right]):
        async with beat(rec, "p3_query"):
            await pma_sql(left, Q3, "cell=Dacope", paste=True)
            await dwell(left, "table.table_results tbody tr:has-text('Dacope')", 1.5)
            await dwell(left, "table.table_results tbody tr:has-text('Paikgachha')", 1.2)
            await focus(right, left)
            await dwell(right, "#reliefPanel .rl-card:has-text('Dacope')", 1.6)
    app = await app_session(rec, "s6_app3", ADMIN, FULL, "disaster.php?id=1")
    await see(app, "button:has-text('Print report')")
    async with rec.unit("s6c", "full", [app]):
        async with beat(rec, "p3_disaster"):
            await app.scroll(500, 1.6)
            await asyncio.sleep(1.6)
            await app.scroll(500, 1.6)
            await asyncio.sleep(1.4)
            await app.click_nav("nav a[href='health.php']")
            await see(app, "text=checks passed")
            await dwell(app, ".alert-ok", 2.5)


# ------------------------------------------------------------------------------------------------ S7 challenge
async def s7(rec: Recorder) -> None:
    pma = await pma_session(rec, "s7_pma", FULL, table="shelter_assignment")
    async with rec.unit("s7", "full", [pma]):
        async with beat(rec, "p4_hurdle"):
            await pma.click_ajax("#topmenu a[href*='route=/table/structure']")
            await see(pma, "#index_div")
            await pma.scroll(420, 1.4)
            await dwell(pma, "#index_div tr:has-text('uq_assignment_open_stay') >> nth=0", 1.8)
            await dwell(pma, "#index_div tr:has-text('uq_assignment_open_stay') td:has-text('Check_Out')", 1.6)
            await pma.click_ajax("#topmenu a[href*='route=/sql']")
            await see(pma, "cell=2026-10-23", "#page_content")
            await dwell(pma, "table.table_results tbody tr >> nth=0", 1.4)
            await dwell(pma, "table.table_results tbody tr >> nth=1", 1.8)
        async with beat(rec, "p4_fix"):
            await pma_sql(pma, "INSERT INTO shelter_assignment (Disaster_ID, Person_ID, Shelter_ID, Check_In) VALUES (1, 1, 1, '2026-10-30');",
                          "text=uq_assignment_open_stay", wpm=420)
            await asyncio.sleep(2.5)


# ------------------------------------------------------------------------------------------------ S8 close
async def s8(rec: Recorder) -> None:
    app = await app_session(rec, "s8_app", ADMIN, FULL, "dashboard.php")
    async with rec.unit("s8", "full", [app]):
        async with beat(rec, "p4_future"):
            for card in ("disaster", "shelter", "relief"):
                await dwell(app, f".dashboard-card-{card}", 1.2)
            await app.click_nav("nav a[href='map.php']")
            await see(app, ".leaflet-container")
            await asyncio.sleep(2.0)
            box = await app.page.locator(".leaflet-container").first.bounding_box()
            cx, cy = box["x"] + box["width"] * 0.5, box["y"] + box["height"] * 0.5
            await drag(app, cx, cy, cx - 140, cy + 60)
            await asyncio.sleep(2.0)


SCENES = {"s1": s1, "s2": s2, "s3": s3, "s4": s4, "s5": s5, "s6": s6, "s7": s7, "s8": s8}
