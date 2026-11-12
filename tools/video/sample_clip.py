"""~30 s sample: split screen, create a family in the app (right) and see the new row in phpMyAdmin (left). Every step is asserted."""
import asyncio
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from recorder import Recorder  # noqa: E402

OUT = Path(sys.argv[1])
APP = "http://127.0.0.1:8099/"
PMA = "http://localhost/phpmyadmin/"
TREE = "#pma_navigation_tree"


async def pma_open_table(left, name, expect):
    """Open a table from the navigation tree and wait until a row we expect is shown (proves it is the right, fresh page)."""
    await left.click_ajax(f"{TREE} li a:text-is('{name}')")
    await left.page.locator("#page_content").get_by_text(expect).first.wait_for(timeout=15000)


async def main():
    async with Recorder(OUT) as rec:
        left = await rec.pane("pma", 898, 1080, 1.1)
        right = await rec.pane("app", 1018, 1080, 1.1)
        # ---- setup before the clip starts: both sides signed in, phpMyAdmin showing affected_person
        await right.goto(APP + "index.php")
        await right.page.fill("input[name=username]", "demo_editor")
        await right.page.fill("input[name=password]", "DemoEditor2026")
        await right.page.click("button[type=submit]")
        await right.page.wait_for_url("**/dashboard.php")
        await left.goto(PMA)
        await left.page.fill("#input_username", "root")
        await left.page.click("#input_go")
        await left.settle(1.0)
        await left.page.click(f"{TREE} li a:text-is('drms_demo')")
        await left.settle(1.0)
        await left.page.click(f"{TREE} li a:text-is('affected_person')")
        await left.settle(1.0)
        assert await left.page.locator("#page_content").get_by_text("Rahim Uddin").count() >= 1
        await left.start()
        await right.start()
        await left.hide_cursor(True)
        await asyncio.sleep(0.6)
        rec.mark("start")

        async with rec.beat(19, "p3_create"):
            await right.click_nav("nav a[href='table.php?t=affected_person']")
            await right.click_nav("a:text-is('+ Add record')")
            await right.type("input[name='f[Name]']", "Shirin Akter")
            await right.type("input[name='f[Family_Size]']", "4")
            await right.type("input[name='f[Contact]']", "+880-1712-000206")
            await right.select("select[name='f[Area_ID]']", "Dacope")
            await right.click_nav("button:has-text('Save record')")
            assert await right.page.locator(".alert-ok").count() == 1, "record was not saved"
            await asyncio.sleep(0.8)
            await right.hide_cursor(True)
            await left.hide_cursor(False)
            await pma_open_table(left, "affected_person", "Shirin Akter")
        async with rec.beat(7, "p3_audit"):
            await pma_open_table(left, "audit_log", "Shirin Akter")
        await asyncio.sleep(1.0)
        rec.mark("end")


asyncio.run(main())
