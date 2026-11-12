"""Checks the ERD camera: fit view, zoom on disaster_area, point at a column. Saves three screenshots."""
import asyncio
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from erd_camera import fit, frame, open_erd, point_entity  # noqa: E402
from recorder import Recorder  # noqa: E402

OUT = Path(sys.argv[1])


async def main():
    async with Recorder(OUT) as rec:
        p = await rec.pane("erd", 1920, 1080)
        await open_erd(p)
        await p.page.screenshot(path=str(OUT / "erd_fit.png"))
        await frame(p, ["disaster", "disaster_area", "area"], max_scale=1.5)
        await point_entity(p, "disaster_area", "Relief_Shortage")
        await asyncio.sleep(0.5)
        await p.page.screenshot(path=str(OUT / "erd_zoom.png"))
        await frame(p, ["relief_item", "relief_batch", "relief_distribution", "relief_recipient"], max_scale=1.2)
        await p.page.screenshot(path=str(OUT / "erd_relief.png"))
        await fit(p)
        rec.mark("start")
        rec.mark("end")


asyncio.run(main())
