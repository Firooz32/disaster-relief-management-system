# TASKS - Disaster Mapping & Relief Management System

Legend: `[x]` done and verified, `[ ]` not done. Plan and decisions: `PLAN.md`.
Overall status: **all tasks complete** (last verification: 25 Oct 2026).

## Task 1 - Database changes and safe upgrade  `[x]`
- [x] `early_warning.Signal_Level` (1-10) with CHECK
- [x] `disaster.Status` (Active / Ended)
- [x] `affected_person.Area_ID` (home area, FK)
- [x] `disaster_area.Relief_Shortage` (No / Yes)
- [x] new tables `relief_recipient`, `disaster_track` (with CHECK on coordinates and radius)
- [x] realistic sample data: Active cyclone #13 (11 path points, signal 10), school shelter exactly full, relief shortages, flood #12 re-rising (signal 5)
- [x] `inc/upgrade.php`: in-place, repeatable upgrade of the 20-table database; sample rows only for an untouched sample
- [x] regenerated `disaster_and_relief_management.sql`
- [x] proof: upgraded database == fresh install == plain `mysql` client install (identical)

## Task 2 - Warning signal indicator  `[x]`
- [x] `signal_info()` (1-3 green Normal, 4-7 orange Danger, 8-10 red Severe Danger, none grey)
- [x] `signal_summary()` (latest signal per active disaster; highest per area)
- [x] panel with cards, area table and **Check my area**
- [x] tests: boundaries 3|4 and 7|8, ended disasters excluded, unaffected area has no signal

## Task 3 - Real-time shelter capacity  `[x]`
- [x] `space_info()`, `shelter_capacity()`: green with exact places left, red "No space available" at 0
- [x] filters: area, only shelters with space
- [x] tests: 1 left = green, 0 = red, over-full never negative, sample school shelter red

## Task 4 - Relief distribution tracker  `[x]`
- [x] `relief_info()`, `relief_for_disaster()`, `relief_status()`; threshold 80 % or manual flag
- [x] green: received count; red: received **and** not received
- [x] "Only shortages" filter
- [x] rules: a recipient must be affected by the distribution's disaster; a distribution cannot move to a disaster its recipients are not part of
- [x] tests: 80 % on track, 79 % short, manual flag, grey when nobody recorded, sample areas

## Task 5 - Live disaster path tracking  `[x]`
- [x] `km_between()` (haversine) and `path_status()`: now / next / passed per area
- [x] Leaflet map: solid observed line, dashed forecast, current marker + impact circle, highlighted areas
- [x] lists: currently crossing, in the forecast path, already passed (with km)
- [x] redraws in place; the viewer's zoom is kept; works without internet for the lists
- [x] tests: distance, current point = latest Actual, area states

## Task 6 - Integration, regression, release  `[x]`
- [x] all four panels on `report.php`, fed by `report_data.php`; original report unchanged
- [x] Dashboard "Live status" cards (signals, full shelters, relief shortages, data health)
- [x] regression: every page and all 21 tables (list, add form, CSV export)
- [x] smoke tests extended (81 checks)
- [x] README.md and README.txt rewritten; `create_db_user.sql`, `start_server.bat` updated

## Task 7 - Quality extras  `[x]`
- [x] Data Health page: 17 read-only checks (`health.php`, `inc/health.php`)
- [x] 8 database triggers (`inc/triggers.sql`) + `run_sql_script()` that understands `DELIMITER`
- [x] audit log details (added values, old -> new, deleted copy)
- [x] nearest-shelter finder (by area or "Use my location")
- [x] disaster report: signals, path points, relief coverage by area
- [x] security: Content-Security-Policy + headers, no inline scripts, admin-only deletion of disasters, 4-hour idle timeout, lockout reset, friendly error texts, `router.php` for `php -S`
- [x] fixes: mobile sideways scrolling, misleading dashboard labels ("Affected families", "Relief units left"), favicon
- [x] installer rewrites the database name to the configured one

## Task 8 - Security tests and database verification  `[x]`
- [x] not signed in: redirect / 401 on every page and endpoint
- [x] CSRF: missing or wrong token rejected
- [x] roles: viewer cannot add; editor cannot delete a disaster or open Users (server side)
- [x] SQL injection attempts (table, search, sort column, keys) harmless
- [x] stored XSS in names / messages shown as text on every page and the map
- [x] lockout and recovery; idle timeout (aged session refused, background refresh does not extend it)
- [x] private files not downloadable (`.htaccess`, `router.php`)
- [x] limited MySQL user: full suite passes; upgrade refused with a clear message and no partial change
- [x] real browser sweep of 16 pages with the CSP active: no console errors

## Task 9 - Documentation and packaging  `[x]`
- [x] `DRMS_Project_Overview_and_User_Guide.docx` - 45 pages, 47 screenshots, step by step for every feature
- [x] `README.md` (English), `README.txt` (Bangla + English)
- [x] `PLAN.md`, `TASKS.md` (these files)
- [x] `disaster_and_relief_management.zip` rebuilt and tested from an extracted copy (fresh install + full suite)

## Task 10 - Corrections after delivery  `[x]`
- [x] map tiles blocked by OpenStreetMap ("403 Access blocked"): `Referrer-Policy` header and the tile layers now send the site as Referer
- [x] saves are race-safe: one transaction per save with row locks on the family and the shelter / relief item (`lock_for_rules()`); proved by `tests/concurrency.php` (control without lock overbooks, with lock exactly one wins)
- [x] documentation corrected: known limits added to README.md and the Word guide; the guide no longer claims that every page was checked in a browser (16 sample pages were; the other table pages by script)
- [x] localhost made available: Apache fixed (newer `vcruntime140.dll` next to `httpd.exe`), project linked into `C:\wamp64\www\`, `start_localhost.bat` / `stop_localhost.bat`

## Final state of the user's database
- [x] 22 tables, 8 triggers, sample data of the final version
- [x] the existing administrator account is present (restored from backup after a test run replaced the database; same password hash)
- [x] `php tests/smoke.php` against it: 81 / 81 pass
- [x] temporary test databases, web servers and browser profiles removed

## Not done (optional future work)
- [ ] Push updates (WebSocket) instead of 5-second polling
- [ ] SMS / e-mail alerts when a signal reaches 8
- [ ] Match `*_emergency_item` needs against relief supply
- [ ] Import live cyclone tracks from a weather service
- [ ] Bangla screens
