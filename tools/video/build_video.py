"""
Records the scenes in one browser session (data changes carry forward from scene to scene).
    py -3 tools/video/build_video.py <out dir> [s1 s2 ...]      default: all scenes in order
Reset the demo database first:  powershell -File tools\reset_demo.ps1 -NoStart   (the app server must already run)
"""
import asyncio
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from recorder import Recorder  # noqa: E402
from scenes import SCENES  # noqa: E402


async def main(out: Path, names: list[str]) -> None:
    async with Recorder(out) as rec:
        for n in names:
            print(f"== {n}", flush=True)
            await SCENES[n](rec)
    print(f"recorded {len(rec.beats)} beats in {len(rec.units)} units -> {out / 'timeline.json'}")


if __name__ == "__main__":
    out = Path(sys.argv[1])
    asyncio.run(main(out, sys.argv[2:] or list(SCENES)))
