"""
Renders every recorded unit, joins them into the four parts and the full silent video, and writes video_map.json
(beat key -> [start, end] in seconds of the final video) for the voiceover script.
    py -3 tools/video/assemble.py <recording dir> <out dir> [unit names...]     (unit names = render only those, for checks)
"""
import json
import subprocess
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from render import FFMPEG, render  # noqa: E402

PART_WINDOWS = {1: (60, 120), 2: (180, 240), 3: (240, 300), 4: (60, 120)}      # brief: 1-2, 3-4, 4-5, 1-2 minutes


def concat(clips: list[Path], out: Path) -> None:
    lst = out.with_suffix(".txt")
    lst.write_text("".join(f"file '{c.as_posix()}'\n" for c in clips), encoding="utf-8")
    subprocess.run([FFMPEG, "-y", "-loglevel", "error", "-f", "concat", "-safe", "0", "-i", str(lst), "-c", "copy",
                    "-movflags", "+faststart", str(out)], check=True)
    lst.unlink()


def main() -> None:
    rec_dir, out_dir, only = Path(sys.argv[1]), Path(sys.argv[2]), sys.argv[3:]
    tl = json.loads((rec_dir / "timeline.json").read_text())
    units_dir = out_dir / "units"
    units_dir.mkdir(parents=True, exist_ok=True)
    clips, offsets = [], {}
    t = 0.0
    for u in tl["units"]:
        if only and u["name"] not in only:
            continue
        clip = units_dir / f"{u['name']}.mp4"
        render(rec_dir, clip, u["layout"], u["panes"], u["name"] + "_start", u["name"] + "_end")
        clips.append((u, clip))
        offsets[u["name"]] = t
        t += u["end"] - u["start"]
    if only:
        return
    # beat -> time in the final video
    vmap, part_of_unit = {}, {}
    for b in tl["beats"]:
        u = next(u for u in tl["units"] if u["start"] - 0.05 <= b["start"] and b["end"] <= u["end"] + 0.05)
        part_of_unit.setdefault(u["name"], int(b["key"][1]))
        vmap[b["key"]] = [round(offsets[u["name"]] + b["start"] - u["start"], 2), round(offsets[u["name"]] + b["end"] - u["start"], 2)]
    (out_dir / "video_map.json").write_text(json.dumps(vmap, indent=1))
    parts: dict[int, list[Path]] = {}
    for u, clip in clips:
        parts.setdefault(part_of_unit[u["name"]], []).append(clip)
    for n, cl in sorted(parts.items()):
        concat(cl, out_dir / f"DRMS_Group3_Part{n}.mp4")
    concat([c for _, c in clips], out_dir / "DRMS_Group3_Demo_Video_silent.mp4")
    lens = {n: sum(u["end"] - u["start"] for u, _ in clips if part_of_unit[u["name"]] == n) for n in parts}
    for n, s in sorted(lens.items()):
        lo, hi = PART_WINDOWS[n]
        print(f"Part {n}: {int(s // 60)}:{s % 60:04.1f}  (brief window {lo // 60}-{hi // 60} min) {'OK' if lo <= s <= hi else '<<< OUTSIDE'}")
    total = sum(lens.values())
    print(f"Total: {int(total // 60)}:{total % 60:04.1f}  {'OK' if 600 <= total <= 720 else '<<< OUTSIDE 10:00-12:00'}")


if __name__ == "__main__":
    main()
