# DRMS Final Assignment - Completion Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax. Tasks marked **[TEAM]** need the students themselves (voices, faces, YouTube account, printer).

**Goal:** Submit everything the "Database Course - Final Assignment" brief asks for - a 10-12 min YouTube demo, a tape-bound printed report, and `[GroupID]_[ProjectTitle]_FinalDB.zip` - with full marks on the 10-mark video rubric.

**Architecture:** The application is finished and tested. Phase A (done, 2026-11-09/10) fixed two bugs and produced every database, diagram, source-code and presentation artifact from the live schema by script, so they cannot drift. Phase B (remaining) records the video, builds the report around real screenshots and the YouTube link, packages, prints and submits.

**Tech Stack:** PHP 8.2, MySQL 8.2 (WampServer), phpMyAdmin, Mermaid 11.6 + ELK (diagrams), headless Edge (rendering, screenshots), OBS Studio (recording), Word / `anthropic-skills:docx` (report).

**Spec:** the assignment brief (memory note `drms-final-assignment.md`) and `submission/team.json` (course, members, roles, video split).

## Global Constraints

- Zip name exactly `[GroupID]_DisasterMappingReliefManagementSystem_FinalDB.zip`; contents: soft-copy report, `database/schema.sql`, `database/sample_data.sql`, `source_code/` with a short `README.md` (environment variables + run steps). `tools/build_submission.php` enforces all of it.
- Video: 10:00-12:00 (script targets 11:30), at least 1080p, YouTube **Unlisted or Public** (never Private), embedding allowed, both members on camera and audio presenting their own sections, phpMyAdmin visible beside the UI during the live demo.
- Report cover: course title and code, project title, Group ID, both members with IDs and contributions, YouTube URL and QR code.
- Members and roles (equal 50 / 50, from `team.json`): **Archy Mondal Urmy (240203)** - database implementation, backend, testing. **Taohiduzzaman Firoz (210232)** - requirements, reporting queries, frontend. ER design is joint.
- Never run installers, imports or tests against the real database `disaster_and_relief_management` (it holds the accounts `firoz322011` and `Archy240203`). Scratch databases only: `drms_demo` (video), `drms_sample_check`, `drms_submit_test`, `drms_clean`. A backup of the real database from 2026-11-09 is in the session scratchpad (`real_db_backup_2026-11-09.sql`).
- Deadline: not announced. Aim to finish Phase B within 7 days so a re-take is possible.

## Review Focus

1. **Empty Group ID or YouTube link in the final files** - `build_submission.php` refuses to build the real zip while `group_id`, `youtube_url` or the report PDF is missing.
2. **Demo data drifting between takes** - run `tools\reset_demo.ps1` before every take; the run-sheet numbers (11 of 11 places, 73% relief) are only true on a fresh reset.
3. **Video over 12:00 or speaking time unbalanced** - each part has a time stamp in `Video_Script.md`; cut words, not demo steps; both members 5:45.
4. **Unreadable screen text at 1080p** - browser and phpMyAdmin at 110% zoom; check one frame full-screen before the full take.
5. **Private YouTube link** - test the link in a private window while signed out before it goes into the report and QR code.

---

## Phase A - Done and verified

| # | Deliverable | Evidence |
|---|---|---|
| A1 | Baseline on scratch DB: smoke + concurrency tests | 113 / 113 smoke, concurrency pass (before the fixes) |
| A2 | **Bug fix:** a family could not move to a second shelter in the same disaster (`UNIQUE(Disaster_ID, Person_ID)`). Replaced by functional unique index `uq_assignment_open_stay` = at most one *open* stay; in-place upgrade in `inc/upgrade.php`; 2 new tests | 115 / 115 on fresh install and on upgraded DB; MySQL itself refuses a second open stay (error 1062) |
| A3 | **Bug fix:** upgrade step double-counted stock (added a `LEGACY-n` batch to items already in batches) | `check_submission_db.php` runs the first-sign-in upgrade twice; batches stay 4; real DB checked clean |
| A4 | `submission/database/schema.sql` (25 tables parents-first, keys, 40 FKs, 18 CHECKs, indexes, 8 triggers) via `tools/make_schema.php` | loads with FK checks on, by app loader and by plain `mysql` client |
| A5 | `submission/database/sample_data.sql`: 3-5 realistic rows per table, parents-first, Khulna cyclone scenario, demo logins | `tools/check_submission_db.php`: 25/25 tables in range, stock / capacity / org rules hold, live panels show full shelter, signal 10, shortage, path states |
| A6 | ERD: `submission/diagrams/ERD_Full.(png,pdf)` (all columns, PK/FK/UK, NOT NULL, cardinality, FK + ON DELETE label on each line), `ERD_Overview.(png,pdf)`; `Relational_Schema.md` - all generated from `information_schema` (`tools/make_erd.php`, `tools/render_erd.ps1`) | visually checked; A3 landscape PDF |
| A7 | `Normalization_and_Design.md`: 1NF-BCNF argument, candidate keys, 2 documented controlled exceptions (incl. one real transitive dependency), cascade rationale, indexes with `EXPLAIN` | claims cross-checked against code and tests |
| A8 | `submission/report/Query_Log.md`: 7 multi-table queries with real output (`tools/make_query_log.php`) | generated from the sample DB |
| A9 | `/source_code` README (`submission/source_code_README.md`) + `config.sample.php` now reads `DRMS_DB_*` environment variables | clean-room: extracted zip, followed README only, signed in, added a record, audit row written, private files 404 |
| A10 | `tools/build_submission.php` (allow-list from git, forbidden-file check, required-field check, zip) | draft zip 53 files / 0.8 MB; real build refuses until Group ID, link, report exist |
| A11 | `tools/reset_demo.ps1` + `submission/presentation/Video_Script.md` (timed script + live run sheet with exact clicks, SQL and expected results) | every run-sheet action rehearsed on `drms_demo`; resulting numbers written into the script |
| A12 | `submission/team.json`: course, teacher, members, IDs, equal roles, video split | - |
| A13 | Docs drift fixed in `PLAN.md`, `TASKS.md` | - |

---

## Phase B - Remaining

### Task B1: Fill the missing facts **[TEAM]** (10 min)

**Files:** Modify `submission/team.json`

- [ ] **Step 1:** Ask the course teacher / class representative for the **Group ID**; write it into `group_id` (letters, digits, dash only).
- [ ] **Step 2:** Confirm both student IDs once more (240203 and 210232 are from different batches).
- [ ] **Step 3:** Read the `contributions` lists aloud together; edit any line that does not match what each of you actually did, so you can explain it in the viva.

### Task B2: Commit the finished Phase A work (agent, 5 min)

- [ ] **Step 1:** `git add` the changed app files (`inc/upgrade.php`, `disaster_and_relief_management.sql`, `tests/smoke.php`, `config.sample.php`), `tools/`, `submission/` (not `submission/build/`), `docs/`, `PLAN.md`, `TASKS.md`. Add `submission/build/` to `.gitignore`.
- [ ] **Step 2:** Commit `feat: final-assignment database scripts, diagrams, demo tools; fix shelter move and stock double count`.
- [ ] **Step 3:** Decide with the user whether to delete the stale untracked copy `disaster_and_relief_management/disaster_and_relief_management/` (it contains an old `config.php`; it is never packaged).

### Task B3: Rehearse **[TEAM]** (1-2 hours)

- [ ] **Step 1:** Install OBS Studio; canvas and output 1920x1080, 30 fps; scene = full screen + webcam overlay; record a 20-second test and check text is readable and both voices are clear.
- [ ] **Step 2:** Start WampServer (for phpMyAdmin), then `powershell -ExecutionPolicy Bypass -File tools\reset_demo.ps1`. Arrange phpMyAdmin left, app right.
- [ ] **Step 3:** Prepare 4 slides (title + members, tech stack, challenge: old vs new index, future scope) and open `ERD_Overview.png`, `ERD_Full.png`.
- [ ] **Step 4:** Full run-through with a stopwatch following `Video_Script.md`. Expected: 11:00-11:45, every run-sheet result appears as written. Reset the demo afterwards.

### Task B4: Record **[TEAM]** (1 hour)

- [ ] **Step 1:** `reset_demo.ps1`, then record. Either one continuous take, or one clip per part (reset before Part 3) joined in any editor (Clipchamp / OBS remux).
- [ ] **Step 2:** Watch the whole video at 1080p: length 10:00-12:00, no error pages except the intended refusals, audio clear.

### Task B5: Upload **[TEAM]** (30 min)

- [ ] **Step 1:** YouTube > Create > Upload. Title: "Disaster Mapping & Relief Management System - CSE 2206 Database Project". Visibility **Unlisted**. Audience: not made for kids. "Allow embedding" on.
- [ ] **Step 2:** After HD processing, open the link in a private window, signed out: it plays at 1080p.
- [ ] **Step 3:** Paste the link into `team.json` `youtube_url`.

### Task B6: Screenshots for the report (agent, 30 min)

**Files:** Create `submission/report/screenshots/*.png`

- [ ] **Step 1:** `reset_demo.ps1`, then headless Edge (1600x900, signed in as demo_admin) captures: sign-in, dashboard, Live Report (all 4 panels), map, disaster report #1, Affected Person list, add form, capacity-rule refusal, CHECK refusal, delete-confirmation with RESTRICT/CASCADE, Data Health, Inventory & stock, Users, Audit Log.
- [ ] **Step 2:** Look at every image; no error page, no real names or passwords. Expected: 14 PNGs.

### Task B7: Build the report (agent, 1-2 hours)

**Files:** Create `tools/build_report.py`, `submission/report/DRMS_Final_Report.docx` + `DRMS_Final_Report.pdf`; QR code PNG from `youtube_url`

- [ ] **Step 1:** Use the `anthropic-skills:docx` skill. Cover laid out like the team's earlier ER assignment cover (KU logo, course code 071402 CSE 2206, "Submitted To: Aminul Islam, Assistant Professor ...", "Submitted By: ..." with IDs), plus Group ID, YouTube URL and its QR code.
- [ ] **Step 2:** Sections in this order: 1 Problem statement and scope; 2 Team and contributions (table from `team.json`); 3 System architecture and technology stack; 4 User interface (B6 screenshots with captions); 5 ER diagram (overview + full, A3 fold-out or two pages); 6 Relational schema (`Relational_Schema.md`); 7 Normalization, keys, cascades, indexes (`Normalization_and_Design.md`); 8 Constraints and triggers; 9 Query log (`Query_Log.md`); 10 Operation log (the run-sheet CRUD actions with the SQL each one executes and the matching `audit_log` rows); 11 Testing (115 smoke checks, concurrency, clean-room install); 12 Challenges and future scope; Appendix: how to run.
- [ ] **Step 3:** Check: page numbers, table of contents, every figure readable when printed, `grep` the text for empty Group ID / link -> none.

### Task B8: Build and verify the zip (agent, 15 min)

- [ ] **Step 1:** `php tools/build_submission.php` (no `--draft`). Expected: `submission/build/<GroupID>_DisasterMappingReliefManagementSystem_FinalDB.zip`.
- [ ] **Step 2:** Clean-room: extract to an empty folder, load `database/*.sql` into a scratch DB, follow `source_code/README.md` only, sign in, add one record. Expected: works without edits.

### Task B9: Print and submit **[TEAM]**

- [ ] **Step 1:** Print the report PDF (ERD page on A3 if the shop allows), tape-bind it; scan the cover QR code with a phone -> the video opens.
- [ ] **Step 2:** Portal: paste the YouTube URL, upload the zip.
- [ ] **Step 3:** Hand in the printed report at the lab desk.

## Final acceptance checklist (tick before submitting)

- [ ] Video 10-12 min, 1080p, Unlisted/Public, both members on camera, phpMyAdmin beside the UI, CRUD + multi-table query + constraint refusals shown.
- [ ] Zip name correct; `database/schema.sql`, `database/sample_data.sql`, `source_code/` + `README.md`, report PDF inside; no `config.php`.
- [ ] Printed report: cover (course, project, Group ID, members, IDs, contributions, URL, QR), architecture, screenshots, ERD + normalized schema, query/operation logs.
- [ ] YouTube link works signed out; QR code scans.
