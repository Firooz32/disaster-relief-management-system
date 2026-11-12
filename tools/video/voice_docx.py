"""
Writes the voiceover script as a Word file.
    py -3 tools/video/voice_docx.py <out.docx> [video_map.json]     (without the map: planned timecodes)
"""
import json
import subprocess
import sys
import tempfile
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from narration import BEATS, F, U, seconds  # noqa: E402

PARTS = {1: "Part 1 - Introduction and system scope", 2: "Part 2 - ER diagram and database design",
         3: "Part 3 - Live system demonstration and database operations", 4: "Part 4 - Challenges and future scope"}
BRIEF = {1: "1-2 minutes", 2: "3-4 minutes", 3: "4-5 minutes", 4: "1-2 minutes"}
PRON = [["phpMyAdmin", "P-H-P my admin"], ["BCNF", "B-C-N-F (Boyce-Codd normal form)"], ["haversine", "HAV-er-sine"],
        ["SQL", "S-Q-L (or 'sequel': choose one and keep it)"], ["MySQL", "my S-Q-L"], ["Paikgachha", "PIKE-gah-chah"],
        ["Dacope", "DAH-cope"], ["Koyra", "KOY-rah"], ["Sonadanga", "SHO-nah-dahn-gah"], ["CASCADE / RESTRICT", "say them in English"]]


def tc(s: float) -> str:
    return f"{int(s // 60)}:{s % 60:04.1f}"


def main() -> None:
    out = Path(sys.argv[1])
    vmap = json.loads(Path(sys.argv[2]).read_text()) if len(sys.argv) > 2 else None
    lines, t = [], 0.0
    for n, b in enumerate(BEATS, 1):
        if vmap:
            start, end = vmap[b[0]]
        else:
            start, end = t, t + seconds(b) + 2.0
        t = end
        lines.append({"n": f"{n:02d}", "part": b[1], "speaker": b[2], "start": tc(start), "dur": round(seconds(b)), "s": start, "e": end,
                      "file": f"{n:02d}_{b[2][0]}", "screen": b[4], "text": b[5]})
    total = lines[-1]["e"]
    part_len = {p: max(l["e"] for l in lines if l["part"] == p) - min(l["s"] for l in lines if l["part"] == p) for p in PARTS}
    mins = {w: sum(seconds(b) for b in BEATS if b[2] == w) / 60 for w in (F, U)}
    data = {"total": tc(total), "fm": f"{mins[F]:.1f}", "um": f"{mins[U]:.1f}", "pron": PRON, "partNames": PARTS, "lines": lines,
            "parts": [[PARTS[p], tc(part_len[p]), BRIEF[p]] for p in PARTS],
            "full": {F: "Taohiduzzaman Firoz (ID 210232)", U: "Archy Mondal Urmy (ID 240203)"}}
    tmp = Path(tempfile.mkdtemp()) / "voice.json"
    tmp.write_text(json.dumps(data), encoding="utf-8")
    import os
    env = {**os.environ, "NODE_PATH": os.environ.get("DOCX_NODE_PATH", "")}          # folder that contains the docx npm package
    subprocess.run(["node", str(Path(__file__).with_name("voice_docx.js")), str(tmp), str(out)], check=True, env=env)


if __name__ == "__main__":
    main()
