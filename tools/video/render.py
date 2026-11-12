"""
Turns a recorder timeline (timeline.json + JPEG frames) into constant-frame-rate 1080p H.264 clips.

    py -3 tools/video/render.py <recording dir> <out.mp4> full  <pane> <start mark> <end mark>
    py -3 tools/video/render.py <recording dir> <out.mp4> split <left pane> <right pane> <start mark> <end mark>

Each output frame shows the newest screencast frame taken at or before that instant (the screencast only sends a frame
when something changes), so pauses in the recording are real pauses in the video.
"""
from __future__ import annotations

import json
import subprocess
import sys
import tempfile
from pathlib import Path

FFMPEG = r"C:\Users\hp\AppData\Local\Microsoft\WinGet\Packages\Gyan.FFmpeg_Microsoft.Winget.Source_8wekyb3d8bbwe\ffmpeg-9.0.2-full_build\bin\ffmpeg.exe"
FPS = 30


def concat_list(frames: list[list], t0: float, t1: float, path: Path) -> None:
    """ffconcat file: every frame that is visible inside [t0, t1] with how long it stays visible."""
    frames = sorted(frames)
    first = max((i for i, f in enumerate(frames) if f[0] <= t0), default=0)
    shown = frames[first:]
    lines = ["ffconcat version 1.0"]
    for i, (ts, file) in enumerate(shown):
        if ts > t1:
            break
        start = max(ts, t0)
        end = min(shown[i + 1][0] if i + 1 < len(shown) else t1, t1)
        if end <= start:
            continue
        lines += [f"file '{Path(file).as_posix()}'", f"duration {end - start:.4f}"]
    lines.append(lines[-2])                                      # concat demuxer quirk: repeat the last file
    path.write_text("\n".join(lines) + "\n", encoding="utf-8")


def render(rec_dir: Path, out: Path, layout: str, panes: list[str], start: str, end: str) -> None:
    tl = json.loads((rec_dir / "timeline.json").read_text())
    t0, t1 = tl["marks"][start], tl["marks"][end]
    tmp = Path(tempfile.mkdtemp(prefix="drms_render_"))
    inputs = []
    for name in panes:
        lst = tmp / f"{name}.ffconcat"
        concat_list(tl["panes"][name]["frames"], t0, t1, lst)
        inputs += ["-f", "concat", "-safe", "0", "-i", str(lst)]
    if layout == "full":
        vf = f"[0:v]fps={FPS},scale=1920:1080:flags=lanczos,format=yuv420p[v]"
    else:                                                        # two panes at their own widths (sum 1916) + a 4-px dark divider
        wl, wr = (tl["panes"][n]["w"] for n in panes)
        assert wl + wr + 4 == 1920, f"split widths must add up to 1916, got {wl}+{wr}"
        vf = (f"[0:v]fps={FPS},scale={wl}:1080:flags=lanczos[l];[1:v]fps={FPS},scale={wr}:1080:flags=lanczos[r];"
              f"[l]pad={wl + 4}:1080:0:0:color=0x1f2937[lp];[lp][r]hstack=inputs=2,format=yuv420p[v]")
    out.parent.mkdir(parents=True, exist_ok=True)
    cmd = [FFMPEG, "-y", "-loglevel", "error", *inputs, "-filter_complex", vf, "-map", "[v]", "-t", f"{t1 - t0:.3f}",
           "-c:v", "libx264", "-preset", "medium", "-crf", "16", "-tune", "stillimage", "-r", str(FPS), "-movflags", "+faststart", str(out)]
    subprocess.run(cmd, check=True)
    print(f"{out}  {t1 - t0:.1f} s")


if __name__ == "__main__":
    rec, out, layout, *rest = sys.argv[1:]
    if layout == "full":
        render(Path(rec), Path(out), layout, [rest[0]], rest[1], rest[2])
    else:
        render(Path(rec), Path(out), layout, [rest[0], rest[1]], rest[2], rest[3])
