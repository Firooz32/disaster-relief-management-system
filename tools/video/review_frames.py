"""Saves frames of every beat (at 45% and 95% of its real length) from the rendered unit clips, for checking by eye.
    py -3 tools/video/review_frames.py <recording dir> <assembled dir> <out dir> [beat key prefix]"""
import json
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from render import FFMPEG  # noqa: E402

rec, asm, out = Path(sys.argv[1]), Path(sys.argv[2]), Path(sys.argv[3])
only = sys.argv[4] if len(sys.argv) > 4 else ""
tl = json.loads((rec / "timeline.json").read_text())
out.mkdir(parents=True, exist_ok=True)
for b in tl["beats"]:
    if not b["key"].startswith(only):
        continue
    u = next(u for u in tl["units"] if u["start"] - 0.05 <= b["start"] and b["end"] <= u["end"] + 0.05)
    clip = asm / "units" / f"{u['name']}.mp4"
    if not clip.exists():
        continue
    for tag, frac in (("a", 0.45), ("b", 0.95)):
        t = b["start"] - u["start"] + (b["end"] - b["start"]) * frac
        subprocess.run([FFMPEG, "-loglevel", "error", "-y", "-ss", f"{t:.2f}", "-i", str(clip), "-frames:v", "1", "-vf", "scale=960:-1",
                        str(out / f"{b['key']}_{tag}.png")], check=True)
    print(f"{b['key']}: {b['end'] - b['start']:.1f} s")
