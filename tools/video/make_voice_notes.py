"""
Writes submission/presentation/Voiceover_Script.md from narration.py.
    py -3 tools/video/make_voice_notes.py                 -> planned timecodes (before recording)
    py -3 tools/video/make_voice_notes.py <video_map.json> -> real timecodes of the final video (after rendering)
video_map.json = {"beat key": [start_seconds_in_final_video, end_seconds], ...}, written by the build script.
"""
import json
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from narration import BEATS, F, U, WPS, seconds, words  # noqa: E402

PARTS = {1: "Part 1 - Introduction and system scope", 2: "Part 2 - ER diagram and database design",
         3: "Part 3 - Live system demonstration and database operations", 4: "Part 4 - Challenges and future scope"}


def tc(s: float) -> str:
    return f"{int(s // 60)}:{s % 60:04.1f}"


def main():
    real = json.loads(Path(sys.argv[1]).read_text()) if len(sys.argv) > 1 else None
    rows, t = [], 0.0
    for b in BEATS:
        if real:
            start, end = real[b[0]]
        else:
            start, end = t, t + seconds(b) + 2.0          # plan: narration + ~2 s of clicking / loading
        t = end
        rows.append((b, start, end))
    total = rows[-1][2]
    out = Path(__file__).resolve().parents[2] / "submission" / "presentation" / "Voiceover_Script.md"
    md = [f"# Voiceover Script - Disaster Mapping & Relief Management System (Group 3)\n",
          f"Video length {tc(total)}. Timecodes are " + ("**measured from the final video**." if real else
          "**planned**; they are replaced by the real ones after recording.") + " Speak at a calm pace "
          f"(about {int(WPS * 60)} words per minute). Every line fits inside its time slot with a short pause; if you finish early, "
          "simply wait - the screen keeps showing the step.\n",
          "**Voice only (no webcam).** Each person records only their own lines: quiet room, phone voice recorder or headset mic "
          "about 20 cm from the mouth, same place and device for every line. **One file per line**, named with the line number "
          "shown below, e.g. `07_U.m4a` (any of m4a / mp3 / wav). Leave about 1 second of silence before and after you speak. "
          "If you stumble, record the whole line again. Send the files back; they are placed at their start times and "
          "levelled automatically, and if a line runs longer than its slot the picture is held a little longer, so nothing is cut.\n",
          "**Pronunciation:** phpMyAdmin = \"P-H-P my admin\"; BCNF = \"B-C-N-F\" (Boyce-Codd normal form); haversine = "
          "\"HAV-er-sine\"; SQL = \"S-Q-L\" or \"sequel\" (choose one and keep it); Paikgachha = \"PIKE-gah-chah\"; Dacope = \"DAH-cope\".\n"]
    part = None
    for n, (b, start, end) in enumerate(rows, 1):
        if b[1] != part:
            part = b[1]
            md.append(f"\n## {PARTS[part]}\n")
        md.append(f"### Line {n:02d} | {tc(start)} - {tc(end)} | **{b[2].upper()}** (file `{n:02d}_{b[2][0]}`)  \n"
                  f"*On screen:* {b[4]}\n\n> {b[5]}\n")
    for who, full in ((F, "Taohiduzzaman Firoz (210232)"), (U, "Archy Mondal Urmy (240203)")):
        mine = [(n, b, s) for n, (b, s, _) in enumerate(rows, 1) if b[2] == who]
        secs = sum(seconds(b) for _, b, _ in mine)
        md.append(f"\n---\n\n## Only {full}'s lines ({len(mine)} lines, about {secs / 60:.1f} min of speaking)\n")
        for n, b, s in mine:
            md.append(f"- **Line {n:02d}** (file `{n:02d}_{b[2][0]}`, starts {tc(s)}, {words(b[5])} words) - {b[5]}\n")
    out.write_text("\n".join(md), encoding="utf-8")
    print(f"Wrote {out} ({len(rows)} lines, video {tc(total)})")


if __name__ == "__main__":
    main()
