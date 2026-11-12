# Demo Video Production Plan (Group 3) - revised 2026-11-12

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax.

**Goal:** A 1080p video of the real system, 10:00-12:00 (planned 10:51), in the brief's 4-part order, with a moving and clicking
mouse and phpMyAdmin beside the app during the live demo. Picture is produced here; **Firoz and Urmy add voice only (no webcam)**
from `submission/presentation/Voiceover_Script.md`, and their voice files are mixed into the final MP4 here.

**Architecture:** `tools/video/recorder.py` (Playwright + Chromium, drawn cursor, DevTools screencast with timestamps),
`render.py` (constant 30 fps H.264), `narration.py` (single source of spoken text; each beat held >= words / 2.2 + 0.8 s),
`erd_camera.py` (pan/zoom over the vector ERD), `make_voice_notes.py` (voiceover script with line numbers and timecodes).

## Brief compliance (checked 2026-11-12)

| Brief requirement | How the video meets it |
|---|---|
| 10-12 min, Unlisted/Public, 1080p | Planned 10:51; 1920x1080 30 fps; build fails if total or any part is outside its window. Upload is done by the team. |
| Part 1 (1-2 min): members, roles, problem, stack | 1:36 + clicks. Both introduce themselves and their roles; Firoz frontend, Urmy DB/backend. |
| Part 2 (3-4 min): ERD **and relational schema**; keys, cardinality, cascade, indexing, 1NF-BCNF | 3:16 + clicks. ERD camera; phpMyAdmin table list of all 25 tables (relational schema); keys, open-stay functional index, FK delete rules, CHECKs, trigger body, EXPLAIN. |
| Part 3 (4-5 min): UI walk-through, live CRUD, multi-table reports, **DB tool beside UI** | 3:34 speaking + typing/clicks (expected 4:15-4:40). Split screen phpMyAdmin / app for every CRUD step, constraint and trigger refusals, RESTRICT + CASCADE delete; Live Report, path map, 6-table query beside the panel. |
| Part 4 (1-2 min): one hurdle + fix, future scope | 1:15 + clicks. Open-stay index bug and fix (duplicate-key demo), concurrency; future scope. |
| Each member presents own work, **explains code/schema they developed** | Urmy: schema, keys, triggers (shows her trigger SQL), CRUD rules. Firoz: ERD/normalization, report queries (runs his 6-table SQL), frontend. |
| Equal participation | Speaking time Firoz 4.95 min / Urmy 4.73 min. Voice only satisfies "camera/audio". |
| Readable screen text | Split-screen pages at 110 % zoom (898 + 1018 px), full-screen at 120 %; one frame per line checked by eye. |
| Clear audio | Team records in a quiet room; mix step applies noise reduction and loudness normalisation (-16 LUFS). |

Outside the video (Phase B of `2026-11-09-final-assignment-submission.md`, still open): printed report with cover, screenshots,
YouTube URL + QR; final zip `3_DisasterMappingReliefManagementSystem_FinalDB.zip` (a DRAFT zip exists; it needs the report soft copy).
Database scripts already comply: `schema.sql` (25 tables, PK/FK, CHECK, indexes, triggers) and `sample_data.sql` (3-5 rows in every table, FK-safe order).

## Global Constraints

- **No added text on screen** (no captions, titles, callouts). Only real pages, the ERD and phpMyAdmin.
- 1920x1080, 30 fps, H.264 yuv420p, CRF 16; one final MP4 + one MP4 per part. File names `DRMS_Group3_*`.
- Only `drms_demo` is used; `tools\reset_demo.ps1 -NoStart` before every take; the real database is never opened.
- Every scripted step asserts its result; a failed assertion aborts the take (never patch a broken take).

## What is needed for recording

**Done here (verified 2026-11-12):** Apache started directly (`httpd.exe -d C:\wamp64\bin\apache\apache2.4.58`), MySQL 8.2,
phpMyAdmin 5.2.1 (root, no password), app on http://127.0.0.1:8099/ on `drms_demo`; Playwright Chromium 1248; ffmpeg 9.0.2;
internet for map tiles, Leaflet, Mermaid; 27 GB free disk. Only `drms_demo` among drms_* databases. RAM is 3.9 GB: close other apps while recording.

**Needed from the team (after the silent video is delivered):**
1. A quiet room, a phone voice recorder or headset mic, the same device and place for every line.
2. Each person reads only their lines from `Voiceover_Script.md`, one file per line named `NN_F` / `NN_U` (m4a, mp3 or wav).
3. Send the 35 files back. Then: YouTube upload (Unlisted, embedding on, not made for kids), link into `submission/team.json`.

## Task 1: Scene scripts

**Files:** Create `tools/video/scenes.py` (one async function per scene), `tools/video/build_video.py`.

| Scene | Layout / zoom | Beats | Content |
|---|---|---|---|
| S1 Intro | full app, 1.2 | p1_hello, p1_problem, p1_scope | sign-in page, type demo_admin / password, dashboard cards, scroll to live status |
| S2 Stack | full phpMyAdmin then app, 1.2 | p1_stack_db, p1_stack_fe | phpMyAdmin home (Database server / Web server boxes); app Map page, slow pan |
| S3 ERD | full ERD | p2_erd ... p2_exceptions | fit view, disaster + children, disaster_area, shelter_assignment, relief chain, PK/FK/UK markers |
| S4 Schema | full phpMyAdmin, 1.2 | p2_keys ... p2_explain | drms_demo table list, shelter_assignment Structure > Indexes, Relation view, Triggers list + trg_assignment_ins body, SQL tab EXPLAIN |
| S5 CRUD | split phpMyAdmin 898 / app 1018, 1.1 | p3_create ... p3_delete | run sheet rows 5:45-8:00 of `Video_Script.md` |
| S6 Reports | full app 1.2, then split | p3_signals ... p3_disaster | Live Report panels, path map, Q3 in phpMyAdmin beside the panel, disaster report, Data Health |
| S7 Challenge | full phpMyAdmin then app, 1.2 | p4_hurdle, p4_fix | shelter_assignment indexes, duplicate-key error for a second open stay, Rahim Uddin's two stays |
| S8 Close | full app, 1.2 | p4_future | dashboard, map with the cyclone path |

- [x] **Step 1:** Each beat: `async with rec.beat(seconds(beat), key)` around its actions, then an assertion of the expected result.
- [x] **Step 2:** `build_video.py`: reset `drms_demo`, record S1-S8 in order (data changes carry forward), write `timeline.json`.
- [x] **Step 3:** Dry run each scene alone; fix selectors until every assertion passes twice in a row.

## Task 2: Record

- [ ] **Step 1:** `tools\reset_demo.ps1 -NoStart`, then `py -3 tools\video\build_video.py <out dir>`. Expected: all assertions pass; 35 beats.
- [ ] **Step 2:** If an assertion fails, fix and re-record from the reset.

## Task 3: Render and assemble (silent)

- [ ] **Step 1:** Render each scene (full or split) from its first beat start to its last beat end.
- [ ] **Step 2:** Concatenate per part -> `DRMS_Group3_Part1.mp4` ... `Part4.mp4`, then `DRMS_Group3_Demo_Video_silent.mp4`.
- [ ] **Step 3:** `video_map.json` (beat -> start/end) and `make_voice_notes.py video_map.json` -> real timecodes in the voiceover script.
- [ ] **Step 4:** Check total 10:00-12:00, each part inside its window (Part 3 >= 4:00, else lengthen holds), ffprobe 1920x1080 30 fps h264.

## Task 4: Quality review

- [ ] **Step 1:** One frame per beat (35 PNGs): right page, cursor visible, no error page except intended refusals, text readable.
- [ ] **Step 2:** Watch S1, S5, S6 at normal speed for natural cursor motion.
- [ ] **Step 3:** Deliver silent MP4 + part MP4s + `Voiceover_Script.md` with real timecodes.

## Task 5: Voice mix (after the team sends the files)

- [ ] **Step 1:** Check 35 files present; measure each line; where a line is longer than its slot, hold that beat's last frame longer and shift later lines.
- [ ] **Step 2:** ffmpeg: denoise (afftdn), high-pass 80 Hz, `loudnorm` to -16 LUFS, place each line at its start (`adelay`), mix, AAC 192 kb/s.
- [ ] **Step 3:** Final `DRMS_Group3_Demo_Video.mp4`; spot-check 5 lines for sync; confirm length 10:00-12:00.

## Status 2026-11-12: silent video DELIVERED (Tasks 1-4 done)

- Take 2 recorded with all 36 beats asserted; assembled 11:21 (Part 1 1:37, Part 2 3:43, Part 3 4:40, Part 4 1:21), 1920x1080 30 fps H.264.
- Files in `submission/presentation/`: `DRMS_Group3_Demo_Video_silent.mp4`, `video_parts/DRMS_Group3_Part1-4.mp4`, `video_map.json`, `Voiceover_Script.md`, `DRMS_Voiceover_Script_Final.docx` (real timecodes).
- Narration changes vs the first plan: p3_delete split into p3_restrict + p3_cascade; p4_hurdle stays in phpMyAdmin; 36 beats.
- Open: Task 5 (voice mix) after the team sends `NN_F` / `NN_U` files; YouTube upload; Phase B of the submission plan.
- Re-record: `tools\reset_demo.ps1 -NoStart`, `py -3 tools\video\build_video.py <dir>`, `py -3 tools\video\assemble.py <dir> <out>`, `py -3 tools\video\voice_docx.py <out.docx> video_map.json` (set DOCX_NODE_PATH to a folder containing node_modules\docx).
