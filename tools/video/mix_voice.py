"""
Voice mix for the demo video (plan: docs/superpowers/plans/2026-11-13-voice-mix.md).

    py -3 tools/video/mix_voice.py clean      # Voice Clip/NN_X.* -> Voice Clip/_work/clean/NN_X.wav (mono, one shared room, -18 LUFS)

Originals in Voice Clip/ are never written.
"""
from __future__ import annotations

import json
import re
import subprocess
import sys
from pathlib import Path

import numpy as np
from scipy import signal
from scipy.io import wavfile

sys.path.insert(0, str(Path(__file__).parent))
from render import FFMPEG  # noqa: E402

ROOT = Path(__file__).resolve().parents[2]
VOICE = ROOT / "Voice Clip"
WORK = VOICE / "_work"
SR = 48000


def ff(*args: str) -> str:
    r = subprocess.run([FFMPEG, "-hide_banner", "-nostats", "-y", *args], capture_output=True, text=True)
    if r.returncode:
        raise RuntimeError(r.stderr[-2000:])
    return r.stderr


def read(path: Path) -> np.ndarray:
    sr, x = wavfile.read(path)
    assert sr == SR, path
    return x.astype(np.float32)


def write(path: Path, x: np.ndarray) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    wavfile.write(path, SR, x.astype(np.float32))


def frame_db(x: np.ndarray, win: float = 0.03) -> np.ndarray:
    w = int(SR * win)
    n = len(x) // w
    return 20 * np.log10(np.sqrt((x[: n * w].reshape(n, w) ** 2).mean(1)) + 1e-9)


def clips() -> list[Path]:
    files = sorted(p for p in VOICE.iterdir() if re.fullmatch(r"\d\d_[FU]\.(m4a|aac)", p.name))
    assert len(files) == 36, f"expected 36 voice clips, found {len(files)}"
    return files


def loudnorm(src: Path, dst: Path, target: float) -> None:
    """Two-pass EBU R128 normalisation (linear gain, so the voice is not pumped)."""
    out = ff("-i", str(src), "-af", f"loudnorm=I={target}:TP=-2:LRA=11:print_format=json", "-f", "null", "-")
    m = json.JSONDecoder().raw_decode(out[out.rindex("{"):])[0]
    ff("-i", str(src), "-af",
       f"loudnorm=I={target}:TP=-2:LRA=11:linear=true:measured_I={m['input_i']}:measured_TP={m['input_tp']}:"
       f"measured_LRA={m['input_lra']}:measured_thresh={m['input_thresh']}:offset={m['target_offset']}",
       "-ar", str(SR), "-c:a", "pcm_f32le", str(dst))


def room_ir() -> np.ndarray:
    """Small, soft room (~0.25 s decay): a few early reflections plus a darkened noise tail. Same for both speakers."""
    rng = np.random.default_rng(7)
    n = int(SR * 0.35)
    t = np.arange(n) / SR
    tail = rng.standard_normal(n) * np.exp(-6.9 * t / 0.25)
    tail = signal.sosfilt(signal.butter(2, 4500, "low", fs=SR, output="sos"), tail)
    tail[: int(SR * 0.012)] = 0
    for d, g in ((0.0041, 0.5), (0.0067, 0.38), (0.0093, 0.3), (0.0128, 0.22)):
        tail[int(SR * d)] += g * np.max(np.abs(tail))
    return tail / np.sqrt((tail ** 2).sum())


def ltas(x: np.ndarray) -> tuple[np.ndarray, np.ndarray]:
    """Long-term average spectrum of the speech frames only (dB)."""
    db = frame_db(x)
    w = int(SR * 0.03)
    keep = np.repeat(db > np.percentile(db, 10) + 15, w)
    f, p = signal.welch(x[: len(keep)][keep], SR, nperseg=4096)
    return f, 10 * np.log10(p + 1e-14)


def expand_pauses(x: np.ndarray, depth_db: float = 9.0) -> np.ndarray:
    """Soft downward expander: frames near the noise floor are lowered by up to depth_db (fast open, slow close),
    so room noise between words drops without the choppy sound of a hard gate."""
    w = int(SR * 0.01)
    db = frame_db(x, 0.01)
    floor, speech = np.percentile(db, 10), np.percentile(db, 90)
    thr = floor + 0.35 * (speech - floor)
    tgt = np.clip((thr - db) / 6, 0, 1) * -depth_db               # 0 dB above thr, full depth 6 dB below it
    g = np.empty_like(tgt)
    cur = 0.0
    for i, t in enumerate(tgt):                                     # attack 10 ms, release 150 ms
        cur = t if t > cur else cur + (t - cur) * (1 - np.exp(-1 / (0.15 if t < cur else 0.01) * 0.01))
        g[i] = cur
    g = np.interp(np.arange(len(x)), np.arange(len(g)) * w + w / 2, g)   # per-sample ramp, no zipper clicks
    return x * 10 ** (g / 20)


def clean() -> None:
    files = clips()
    (WORK / "a").mkdir(parents=True, exist_ok=True)
    (WORK / "clean").mkdir(parents=True, exist_ok=True)
    report = {}
    # 1. decode, best channel, rumble/hum, light denoise, tone, de-ess, gentle compression
    for p in files:
        raw = WORK / "a" / f"{p.stem}_raw.wav"
        ff("-i", str(p), "-vn", "-ar", str(SR), "-c:a", "pcm_f32le", str(raw))
        x = read(raw)
        snr = []
        for c in range(2):
            d = frame_db(x[:, c])
            snr.append((np.percentile(d, 90) - np.percentile(d, 10), np.percentile(d, 10)))
        ch = int(np.argmax([s[0] for s in snr]))
        floor = float(np.clip(snr[ch][1], -80, -25))
        nr = 15 if p.stem.endswith("U") else 8                      # lighter on Firoz (cleaner) to keep air and breaths
        chain = (f"pan=mono|c0=c{ch},highpass=f=80:poles=2,afftdn=nr={nr}:nf={floor:.1f}:tn=1,"
                 "equalizer=f=250:t=q:w=1.0:g=-2,equalizer=f=3200:t=q:w=1.0:g=2,treble=g=1.5:f=10000,"
                 "deesser=i=0.35,acompressor=threshold=0.08:ratio=2.5:attack=12:release=180:makeup=1.5")
        out = WORK / "a" / f"{p.stem}.wav"
        ff("-i", str(raw), "-af", chain, "-ar", str(SR), "-c:a", "pcm_f32le", str(out))
        write(out, expand_pauses(read(out)))
        report[p.stem] = {"channel": ch, "snr_db": round(snr[ch][0], 1), "floor_db": round(floor, 1)}
    # 2. one shared tone: correct each speaker's average spectrum halfway to the mean of both (max +-4 dB)
    spec = {}
    for spk in "FU":
        xs = [read(WORK / "a" / f"{p.stem}.wav") for p in files if p.stem.endswith(spk)]
        f, s = ltas(np.concatenate(xs))
        spec[spk] = s
    target = (spec["F"] + spec["U"]) / 2
    ir = room_ir()
    for spk in "FU":
        corr = np.clip(signal.savgol_filter(target - spec[spk], 31, 2), -4, 4)
        corr[(f < 90) | (f > 14000)] = 0
        fir = signal.firwin2(2047, f / (SR / 2), 10 ** (corr / 20))
        for p in files:
            if not p.stem.endswith(spk):
                continue
            x = read(WORK / "a" / f"{p.stem}.wav")
            y = signal.fftconvolve(x, fir, "same")
            y = y + 0.2 * signal.fftconvolve(y, ir)[: len(y)] * np.sqrt((y ** 2).mean()) / (
                np.sqrt((signal.fftconvolve(y, ir)[: len(y)] ** 2).mean()) + 1e-9)   # reverb ~14 dB under the dry voice
            write(WORK / "a" / f"{p.stem}_room.wav", y / max(1.0, np.abs(y).max() / 0.95))
            loudnorm(WORK / "a" / f"{p.stem}_room.wav", WORK / "clean" / f"{p.stem}.wav", -18)
        report[f"eq_{spk}_max_db"] = round(float(np.abs(corr).max()), 1)
    (WORK / "clean_report.json").write_text(json.dumps(report, indent=1, default=float))
    print(json.dumps(report, default=float))


def voiced_runs(x: np.ndarray) -> list[list[float]]:
    """Speech runs [start, end] in seconds: 10 ms frames above an adaptive threshold, small gaps closed, blips dropped."""
    db = frame_db(x, 0.01)
    floor, speech = np.percentile(db, 10), np.percentile(db, 90)
    v = db > max(floor + 10, speech - 30)
    runs, i = [], 0
    while i < len(v):
        if v[i]:
            j = i
            while j < len(v) and v[j]:
                j += 1
            runs.append([i / 100, j / 100])
            i = j
        else:
            i += 1
    merged = []
    for r in runs:
        if merged and r[0] - merged[-1][1] < 0.15:
            merged[-1][1] = r[1]
        else:
            merged.append(r)
    return [r for r in merged if r[1] - r[0] >= 0.06]


def sentences(text: str) -> list[str]:
    return [s for s in re.split(r"(?<=[.!?])\s+", text.strip()) if s]


def segment() -> None:
    """Trim each clean line, shorten long reading pauses, and split it into the script's sentences at its pauses."""
    from narration import BEATS
    (WORK / "lines").mkdir(parents=True, exist_ok=True)
    out = {}
    fade = int(SR * 0.04)
    for p, beat in zip(clips(), BEATS):
        x = read(WORK / "clean" / f"{p.stem}.wav")
        runs = voiced_runs(x)
        head, tail = max(0.0, runs[0][0] - 0.18), min(len(x) / SR, runs[-1][1] + 0.30)
        cut_end = runs[-1][1] + 0.05 >= len(x) / SR                 # speech runs to the very end: maybe a clipped word
        # rebuild: speech runs with pauses capped (over 0.7 s -> 0.45 s), crossfaded so the cut is inaudible
        pieces, starts, t = [], [], 0.0
        for k, r in enumerate(runs):
            a = head if k == 0 else (runs[k - 1][1] + r[0]) / 2
            b = tail if k == len(runs) - 1 else (r[1] + runs[k + 1][0]) / 2
            if k > 0:
                gap = r[0] - runs[k - 1][1]
                if gap > 0.7:                                       # take 0.45 s of pause in total, half on each side
                    a = r[0] - 0.225
            if k < len(runs) - 1:
                gap = runs[k + 1][0] - r[1]
                if gap > 0.7:
                    b = r[1] + 0.225
            seg = x[int(a * SR): int(b * SR)].copy()
            starts.append((t + (r[0] - a), t + (r[1] - a), r[0], r[1]))
            pieces.append(seg)
            t += len(seg) / SR - (fade / SR if k < len(runs) - 1 else 0)
        y = pieces[0]
        for s in pieces[1:]:
            ramp = np.linspace(0, 1, fade)
            y = np.concatenate([y[:-fade], y[-fade:] * (1 - ramp) + s[:fade] * ramp, s[fade:]])
        n = int(SR * (0.06 if cut_end else 0.03))
        y[-n:] *= np.linspace(1, 0, n)
        y[: int(SR * 0.01)] *= np.linspace(0, 1, int(SR * 0.01))
        write(WORK / "lines" / f"{p.stem}.wav", y)
        # sentence boundaries: pause (in the new timeline) whose voiced-time fraction is closest to the word fraction
        sens = sentences(beat[5])
        words = np.cumsum([len(s.split()) for s in sens]) / sum(len(s.split()) for s in sens)
        voiced = np.cumsum([e - s for s, e, _, _ in starts])
        frac = voiced / voiced[-1]
        bounds, last = [], -1
        for wf in words[:-1]:
            cand = [k for k in range(last + 1, len(starts) - 1)]
            if not cand:
                break
            k = min(cand, key=lambda k: abs(frac[k] - wf))
            bounds.append(k)
            last = k
        segs, s0 = [], 0
        for k in bounds + [len(starts) - 1]:
            segs.append([round(max(0.0, starts[s0][0] - 0.12), 3), round(starts[k][1] + 0.2, 3)])
            s0 = k + 1
        if len(segs) < len(sens):                                   # fewer pauses than sentences: keep the rest together
            sens = sens[: len(segs) - 1] + [" ".join(sens[len(segs) - 1:])]
        segs[-1][1] = round(len(y) / SR, 3)
        out[p.stem] = {"key": beat[0], "dur": round(len(y) / SR, 3), "cut_end": cut_end,
                       "sentences": [{"text": s, "t": g} for s, g in zip(sens, segs)]}
    (WORK / "segments.json").write_text(json.dumps(out, indent=1))
    for k, v in out.items():
        print(k, v["key"], v["dur"], "CUT?" if v["cut_end"] else "", [g["t"] for g in v["sentences"]])


RNG = np.random.default_rng(2026)


def _burst(dur: float, lo: float, hi: float, decay: float) -> np.ndarray:
    n = int(SR * dur)
    t = np.arange(n) / SR
    s = signal.sosfilt(signal.butter(2, [lo, hi], "band", fs=SR, output="sos"), RNG.standard_normal(n))
    return s * np.exp(-t / decay)


def mouse_click() -> np.ndarray:
    """Press + release of a quiet office mouse, slightly different every time."""
    def tick(gain):
        b = _burst(0.02, 1800 * RNG.uniform(0.85, 1.15), 6500, 0.0018) * gain
        b[: int(SR * 0.008)] += np.sin(2 * np.pi * 180 * np.arange(int(SR * 0.008)) / SR) * np.exp(
            -np.arange(int(SR * 0.008)) / (SR * 0.002)) * gain * 0.6
        return b
    rel = int(SR * RNG.uniform(0.07, 0.1))
    out = np.zeros(rel + int(SR * 0.03))
    out[: int(SR * 0.02)] += tick(1.0)
    out[rel: rel + int(SR * 0.02)] += tick(RNG.uniform(0.45, 0.6))
    return out / np.abs(out).max()


def key_stroke(space: bool = False) -> np.ndarray:
    b = _burst(0.035, 900 if space else 1300, 5000, 0.004 if space else 0.0028) * RNG.uniform(0.6, 1.0)
    n = int(SR * 0.012)
    b[:n] += np.sin(2 * np.pi * (140 if space else 230) * np.arange(n) / SR) * np.exp(-np.arange(n) / (SR * 0.003)) * 0.5
    return b / np.abs(b).max() * RNG.uniform(0.6, 1.0)


def typing(dur: float) -> np.ndarray:
    out = np.zeros(int(SR * (dur + 0.1)))
    t = 0.0
    while t < dur:
        k = key_stroke(space=RNG.random() < 0.15)
        i = int(t * SR)
        out[i: i + len(k)] += k[: len(out) - i]
        t += RNG.uniform(0.055, 0.13)
    return out


def music_bed(length: float) -> np.ndarray:
    """White Moon looped to `length` s: silent tail cut, 5 s crossfades into the calm intro, leveled and softened."""
    raw = WORK / "music_raw.wav"
    if not raw.exists():
        ff("-i", str(VOICE / "background music.mpeg"), "-vn", "-ar", str(SR), "-c:a", "pcm_f32le", str(raw))
    soft = WORK / "music_soft.wav"
    ff("-i", str(raw), "-t", "248", "-af",
       "lowpass=f=6000,equalizer=f=2200:t=o:w=1.5:g=-4,acompressor=threshold=0.05:ratio=3:attack=200:release=1500,"
       "dynaudnorm=f=1000:g=31:m=3", "-c:a", "pcm_f32le", str(soft))
    m = read(soft)
    xf = int(SR * 5)
    body = m[: int(SR * 246)]
    out = body.copy()
    while len(out) < length * SR:
        ramp = np.linspace(0, 1, xf)[:, None]
        out = np.concatenate([out[:-xf], out[-xf:] * np.sqrt(1 - ramp) + body[:xf] * np.sqrt(ramp), body[xf:]])
    out = out[: int(length * SR)]
    return out / np.sqrt((out ** 2).mean())                         # unit RMS; level is set in the mix


PRES = ROOT / "submission" / "presentation"
SILENT = PRES / "DRMS_Group3_Demo_Video_silent.mp4"
# sentence -> click it describes: {beat: [(sentence index, click index inside the beat, seconds the sentence starts before it)]}
# (negative = the sentence starts just after the click). Click indices come from Voice Clip/_work/clicks.json.
ANCHORS: dict[str, list[tuple[int, int, float]]] = {
    "p2_keys": [(2, 2, 0.3)],                     # "Now shelter_assignment." as it is opened
    "p2_cascade": [(0, 0, -0.3)],                 # "Here are the foreign keys" once the relation view opens
    "p2_triggers": [(2, 3, 0.5)],                 # "This is one I wrote" as the trigger opens
    "p2_explain": [(0, 2, 0.5)],                  # EXPLAIN explained while it is typed and run
    "p3_create": [(1, 1, 0.8), (2, 5, -0.2)],     # name typed while it is said; "After saving" on Save
    "p3_link": [(0, 1, 1.0), (1, 4, -0.3)],       # "Next I link..." at + Add; "This is a row..." after Save
    "p3_capacity": [(0, 1, 0.8), (1, 6, -0.2)],   # "I try to place them..." at + Add; "It is refused" after Save
    "p3_update": [(1, 4, 0.3)],                   # "The table on the left shows twenty" as phpMyAdmin refreshes
    "p3_check": [(1, 3, -0.2)],                   # "A negative family size is refused" after Save
    "p3_trigger": [(0, 4, 7.5)],                  # ends as the trigger error appears
    "p3_restrict": [(1, 0, 1.5), (2, 4, 2.0)],    # "so I sign in" at the login; "cannot be deleted" at Delete
    "p3_cascade": [(1, 3, -0.3), (2, 5, 0.3)],    # "has one warning" after Go; "The page lists..." at Delete
    "p3_query": [(1, 3, 0.0)],                    # "It joins six tables" as the query runs
    "p3_disaster": [(1, 1, -0.8)],                # "All seventeen checks pass" when Data Health opens
    "p4_fix": [(1, 3, -0.5)],                     # "Here, MySQL refuses..." after Go
}
LEVELS = {"music_speech": -23.0, "music_gap": -13.0, "music_intro": -10.0, "sfx": -10.0, "room": -45.0}  # dB vs speech


def place() -> dict:
    """Lay every sentence on the final timeline; returns voice events, holds (picture freezes) and the new beat map."""
    seg = json.loads((WORK / "segments.json").read_text())
    vmap = json.loads((PRES / "video_map.json").read_text())
    clicks = json.loads((WORK / "clicks.json").read_text())
    rng = np.random.default_rng(11)
    events, holds, new_map = [], [], {}
    H, prev_end, prev_spk, prev_part = 0.0, 0.0, None, None
    for (stem, s), (key, (a, b)) in zip(seg.items(), vmap.items()):
        assert s["key"] == key, (stem, key)
        spk, part = stem[-1], key[:2]
        if prev_spk is None:                                        # music intro on the first frame before the first word
            holds.append([0.0, 2.5])
            H += 2.5
        A, Bf = a + H, b + H
        if prev_spk is None:
            start = A + 0.4
        elif spk != prev_spk:
            start = max(A + rng.uniform(0.3, 0.5), prev_end + rng.uniform(0.55, 0.8))
        elif part == prev_part and prev_end < A - 0.9 and rng.random() < 0.35:
            start = max(A - rng.uniform(0.25, 0.45), prev_end + 0.5)  # starts talking while the previous action ends
        else:
            start = max(A + rng.uniform(0.15, 0.5), prev_end + rng.uniform(0.3, 0.7))
        sens = s["sentences"]
        cuts = [0.0] + [(sens[j]["t"][1] + sens[j + 1]["t"][0]) / 2 for j in range(len(sens) - 1)] + [s["dur"]]
        beat_clicks = [c for c in clicks if a <= c["t"] < b]
        extra = 0.0
        for j in range(len(sens)):
            nat = start + cuts[j] + extra
            for sj, ci, lead in ANCHORS.get(key, []):
                if sj == j and ci < len(beat_clicks):
                    target = beat_clicks[ci]["t"] + H - lead - (sens[j]["t"][0] - cuts[j] + 0.12)
                    if 0 < target - nat <= 10:
                        extra += target - nat
                        nat = target
            events.append({"stem": stem, "from": cuts[j], "to": cuts[j + 1], "at": nat})
        end = events[-1]["at"] + cuts[-1] - cuts[-2]
        if end > Bf - 0.15:                                         # line longer than the picture: freeze the last frame
            hold = round(end - Bf + 0.4, 2)
            holds.append([b, hold])
            H += hold
        new_map[key] = [round(A if prev_spk else 0.0, 2), round(b + H, 2)]
        prev_end, prev_spk, prev_part = end, spk, part
    return {"events": events, "holds": holds, "map": new_map, "length": round(list(vmap.values())[-1][1] + H + 2.5, 2)}


def shift(t: float, holds: list) -> float:
    return t + sum(h for at, h in holds if at <= t)


def mix() -> None:
    plan = place()
    L = plan["length"]
    n = int(L * SR)
    voice = np.zeros(n, np.float32)
    lines = {}
    for e in plan["events"]:
        y = lines.setdefault(e["stem"], read(WORK / "lines" / f"{e['stem']}.wav"))
        c = y[int(e["from"] * SR): int(e["to"] * SR)].copy()
        f = int(SR * 0.015)
        c[:f] *= np.linspace(0, 1, f)
        c[-f:] *= np.linspace(1, 0, f)
        i = int(e["at"] * SR)
        voice[i: i + len(c)] += c[: n - i]
    w = int(SR * 0.05)
    vdb = frame_db(voice, 0.05)
    speech_db = np.percentile(vdb[vdb > -60], 80)
    # desk sounds
    sfx = np.zeros(n, np.float32)
    for c in json.loads((WORK / "clicks.json").read_text()):
        t = shift(c["t"], plan["holds"]) - 0.01
        k = mouse_click() * RNG.uniform(0.75, 1.0)
        i = int(t * SR)
        sfx[i: i + len(k)] += k[: n - i]
        if c.get("typing"):
            k = typing(c["typing"]) * 0.7
            i = int(shift(c.get("typing_at", c["t"] + 0.25), plan["holds"]) * SR)
            sfx[i: i + len(k)] += k[: n - i]
    sfx *= 10 ** ((speech_db + LEVELS["sfx"]) / 20) / 0.3
    # room tone: quiet, darkened noise so the background never drops to digital silence
    room = signal.sosfilt(signal.butter(1, [120, 2500], "band", fs=SR, output="sos"), RNG.standard_normal(n)).astype(np.float32)
    room *= 10 ** ((speech_db + LEVELS["room"]) / 20) / np.sqrt((room ** 2).mean())
    # music with ducking: lowered while anyone speaks (look-ahead 0.3 s, hold 0.6 s), higher in gaps and the intro
    act = vdb > speech_db - 25
    k1, k2 = int(0.3 / 0.05), int(0.6 / 0.05)
    act = np.array([act[max(0, i - k2): i + k1 + 1].any() for i in range(len(act))])
    gdb = np.where(act, LEVELS["music_speech"], LEVELS["music_gap"]).astype(float)
    first = int(plan["events"][0]["at"] / 0.05)
    gdb[:first] = LEVELS["music_intro"]
    gdb = np.convolve(np.pad(gdb, 10, mode="edge"), np.ones(21) / 21, "same")[10:-10]   # 1 s smooth ramps
    g = 10 ** ((speech_db + gdb) / 20)
    g = np.interp(np.arange(n), np.arange(len(g)) * w + w / 2, g)
    music = music_bed(L) * g[:, None]
    fo = int(SR * 4)
    music[-fo:] *= (np.linspace(1, 0, fo) ** 2)[:, None]
    music[: int(SR * 0.5)] *= np.linspace(0, 1, int(SR * 0.5))[:, None]
    full = (voice + sfx + room)[:, None] + music               # voice centred, music keeps its stereo
    write(WORK / "mix_pre.wav", full)
    loudnorm(WORK / "mix_pre.wav", WORK / "mix.wav", -14)
    (WORK / "placement.json").write_text(json.dumps(plan, indent=1))
    print("length", L, "holds", plan["holds"], "speech_db", round(float(speech_db), 1))


def video() -> None:
    """Silent video with the picture holds (cloned frames), a fade from black and a fade to black: WORK/video.mp4."""
    plan = json.loads((WORK / "placement.json").read_text())
    L = plan["length"]
    cuts = sorted(plan["holds"])
    parts, t0, chains = [], 0.0, []
    bounds = [h[0] for h in cuts if h[0] > 0] + [None]
    first_hold = sum(h for at, h in cuts if at == 0)
    for k, t1 in enumerate(bounds):
        trim = f"trim=start={t0}" + (f":end={t1}" if t1 is not None else "")
        pad = []
        if k == 0 and first_hold:
            pad.append(f"start_mode=clone:start_duration={first_hold}")
        hold = next((h for at, h in cuts if at == t1), 0) if t1 is not None else 3.0   # tail: hold the last frame
        pad.append(f"stop_mode=clone:stop_duration={hold}")
        chains.append(f"[0:v]{trim},setpts=PTS-STARTPTS,tpad={':'.join(pad)}[s{k}]")
        parts.append(f"[s{k}]")
        t0 = t1
    fc = ";".join(chains) + f";{''.join(parts)}concat=n={len(parts)}:v=1:a=0,fps=30,trim=duration={L}," \
         f"fade=t=in:st=0:d=0.8,fade=t=out:st={L - 1.5}:d=1.5[v]"
    ff("-i", str(SILENT), "-filter_complex", fc, "-map", "[v]", "-c:v", "libx264", "-preset", "medium", "-crf", "18",
       "-pix_fmt", "yuv420p", "-an", str(WORK / "video.mp4"))
    print("video done", L)


def mux(out: Path) -> None:
    ff("-i", str(WORK / "video.mp4"), "-i", str(WORK / "mix.wav"), "-map", "0:v", "-map", "1:a", "-c:v", "copy",
       "-c:a", "aac", "-b:a", "192k", "-ar", "48000", "-shortest", "-movflags", "+faststart", str(out))


def sample() -> None:
    """60 s check: the opening, a CRUD stretch with clicks and typing, and the Urmy -> Firoz hand-over."""
    plan = json.loads((WORK / "placement.json").read_text())
    m = plan["map"]
    full = WORK / "full_tmp.mp4"
    mux(full)
    ranges = [(0, 20), (m["p3_create"][0], m["p3_create"][0] + 22), (m["p3_signals"][0] - 9, m["p3_signals"][0] + 9)]
    fc = "".join(f"[0:v]trim={a}:{b},setpts=PTS-STARTPTS[v{i}];[0:a]atrim={a}:{b},asetpts=PTS-STARTPTS,"
                 f"afade=t=in:d=0.3,afade=t=out:st={b - a - 0.4}:d=0.4[a{i}];" for i, (a, b) in enumerate(ranges))
    fc += "".join(f"[v{i}][a{i}]" for i in range(len(ranges))) + f"concat=n={len(ranges)}:v=1:a=1[v][a]"
    ff("-i", str(full), "-filter_complex", fc, "-map", "[v]", "-map", "[a]", "-c:v", "libx264", "-crf", "18",
       "-c:a", "aac", "-b:a", "192k", "-movflags", "+faststart", str(PRES / "Sample_Voice_Mix_60s.mp4"))
    print("sample:", PRES / "Sample_Voice_Mix_60s.mp4")


if __name__ == "__main__":
    {"clean": clean, "segment": segment, "mix": mix, "video": video, "sample": sample,
     "final": lambda: mux(PRES / "DRMS_Group3_Demo_Video.mp4")}[sys.argv[1]]()
