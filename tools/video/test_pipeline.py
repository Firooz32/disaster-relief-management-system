"""25-second pipeline test: split screen, phpMyAdmin (left) and the app (right) both signing in, with the drawn cursor."""
import asyncio
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from recorder import Recorder  # noqa: E402

OUT = Path(sys.argv[1])
APP = "http://127.0.0.1:8099/"
PMA = "http://localhost/phpmyadmin/"


async def main():
    async with Recorder(OUT) as rec:
        left = await rec.pane("pma", 958, 1080)
        right = await rec.pane("app", 958, 1080)
        await left.goto(PMA)
        await right.goto(APP + "index.php")
        await left.start()
        await right.start()
        await left.hide_cursor(True)
        await asyncio.sleep(0.5)
        rec.mark("start")
        async with rec.beat(8, "app-login"):
            await right.type("input[name=username]", "demo_editor")
            await right.type("input[name=password]", "DemoEditor2026")
            await right.click_nav("button[type=submit]")
        async with rec.beat(8, "pma-login"):
            await right.hide_cursor(True)
            await left.hide_cursor(False)
            await left.type("#input_username", "root")
            await left.click_nav("#input_go")
            await left.settle(1.0)
            await left.click_ajax("#pma_navigation_tree a:text-is('drms_demo')")
        async with rec.beat(6, "pma-table"):
            await left.click_ajax("#pma_navigation_tree a:text-is('shelter')")
        rec.mark("end")


asyncio.run(main())
