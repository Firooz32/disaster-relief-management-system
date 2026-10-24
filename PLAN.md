# PLAN - Disaster Mapping & Relief Management System

Final plan for the live-tracking upgrade of the existing PHP + MySQL system (WampServer).
Status: **complete** (see `TASKS.md` for the checklist and evidence).

## 1. Goal

Add four live disaster-tracking features to the existing **Live Report** without breaking anything that already worked,
show a short summary of them on the **Dashboard**, and deliver a finished, tested, documented system.

| # | Feature | Required behaviour |
|---|---|---|
| 1 | Warning signal indicator | Signal 1-3 = green "Normal"; 4-7 = orange "Danger"; 8-10 = red "Severe Danger" |
| 2 | Real-time shelter capacity | Places left = capacity - people staying now. > 0 = green with the exact number; 0 = red "No space available" |
| 3 | Relief distribution tracker | Normal = green with the count of people who received relief. Shortage = red with **both** counts (received and not received) |
| 4 | Live disaster path tracking | Map with the observed path, the forecast, the current position and impact circle, highlighted areas, and lists of areas being crossed now / in the path |
| 5 | Dashboard integration | Existing Live Report keeps working; the four new panels are in the same view, fed by `report_data.php`; compact summary on the Dashboard |

## 2. Decisions taken

| Question | Decision | Why |
|---|---|---|
| What is a "shortage"? | **Automatic + manual**: fewer than 80 % of the affected people have received relief, **or** a coordinator sets `disaster_area.Relief_Shortage = 'Yes'` | The requirement says "if a shortage is flagged" without saying how; this covers both a data-driven and a human judgement |
| Counts in people or families? | People (sum of `Family_Size`), families shown in small text | "number of people who have received relief" |
| Which disasters are shown? | Only `disaster.Status = 'Active'` | A finished disaster must leave the live panels |
| Current signal of a disaster | Latest early warning **with a signal number**, ordered by warning time | Independent of the computer's clock |
| Signal of an area | Highest signal among the active disasters that affect it | Safest reading for the public |
| "Currently crossing" | Area centre within the **current (latest Actual) position's** impact radius (haversine distance) | Simple, explainable, testable |
| "In the forecast path" / "passed" | Within a Forecast point's radius / within an earlier Actual point's radius | Same geometry, three states |
| Map technology | Leaflet 1.9.4 + OpenStreetMap, pinned with Subresource Integrity | Already used by `map.php`; no API key |
| Calculated or stored? | Places left, stock left, coverage are **calculated** | They can never drift from the records |
| Upgrade strategy | In-place, idempotent upgrade of the 20-table database; fresh installs use the regenerated `.sql` | Never lose the user's data or accounts |

## 3. Architecture

```
Browser (report.php)
  -> assets/report.js  --every 5 s-->  report_data.php  (one JSON answer)
                                          -> inc/stats.php  (signal_summary, path_status, shelter_capacity,
                                                             relief_status, area_points, ...)  -> MySQL (read only)
Editors (table.php forms) -> inc/rules.php (friendly rules) -> MySQL triggers + CHECK + FK -> audit_log
```

* One endpoint feeds every panel; the page redraws only what changed (map view and filters are kept).
* The same functions feed the Dashboard, the Live Report and the Map, so numbers cannot disagree.
* Rules are layered: foreign keys / CHECK (always) -> 8 triggers (links between records) -> PHP rules (messages, capacity, stock) -> Data Health (finds anything that slipped through).

## 4. Database changes

| Change | Purpose |
|---|---|
| `early_warning.Signal_Level` (1-10, CHECK) | Feature 1 |
| `disaster.Status` (`Active` / `Ended`) | Which disasters are live |
| `affected_person.Area_ID` (FK, home area) | Relief status per area |
| `disaster_area.Relief_Shortage` (`No` / `Yes`) | Manual shortage flag |
| new table `relief_recipient` | Which family received which distribution |
| new table `disaster_track` (time, Actual/Forecast, lat, lng, radius, wind) | Feature 4 |
| `audit_log.Details` | Old / new values of every change |
| 8 triggers (`inc/triggers.sql`) | Links between disaster, shelter, family, organisation, area |

Delivery: `inc/upgrade.php` (in place) and `disaster_and_relief_management.sql` (fresh install) give **identical** results (verified byte for byte).

## 5. Phases

1. **Database** - schema, constraints, sample data (active cyclone with an 11-point path, a full shelter, relief shortages), upgrade path, tests.
2. **Warning signals** - `signal_info()`, `signal_summary()`, panel with "Check my area".
3. **Shelter space** - `space_info()`, `shelter_capacity()`, filters, nearest shelter.
4. **Relief tracker** - `relief_info()`, `relief_for_disaster()`, rules for recipients.
5. **Path map** - `path_status()`, Leaflet layers, three area lists.
6. **Integration and release** - all panels on `report.php`, Dashboard summary, regression and security tests, README, Word guide, zip.
7. **Quality extras** - Data Health, triggers, audit details, security hardening, mobile fix, nearest-shelter finder, extra sample data.

## 6. Quality gates (all must pass)

* `php tests/smoke.php` - 81 checks, on an upgraded database, a fresh install, and with the limited MySQL user.
* Upgraded database == fresh install == plain `mysql` client install.
* Every page and all 21 table pages load in a real browser with no console error and no Content-Security-Policy violation; no sideways scrolling on a phone.
* Security: no access without sign-in, CSRF, roles enforced on the server, SQL-injection and stored-XSS attempts harmless, lockout and idle timeout, private files not downloadable.
* The packaged zip installs itself from an empty location and passes the suite.

## 7. Risks and how they were handled

| Risk | Handling |
|---|---|
| Upgrading the user's real database | Backup first; additive upgrade; sample rows only if the data is still the untouched sample; **installer rewrites the database name to the configured one** (a test once hit the real database - account restored from backup, root cause fixed) |
| `information_schema` row counts are estimates | Always count with `COUNT(*)` before deciding anything about user data |
| Limited MySQL user cannot ALTER | Clear message "needs a one-time upgrade ... put root in config.php"; nothing is changed |
| Map needs internet | Page says so; the three area lists still work |
| A browser that sends no Referer gets blocked map tiles | Header `Referrer-Policy: strict-origin-when-cross-origin` and a `referrerPolicy` on the tile layers; documented as a known limit |
| Two people saving into the last places at once | One transaction per save with `SELECT ... FOR UPDATE` on the family and the shelter / relief item; `tests/concurrency.php` proves it |
| Mixed units in "relief left" | Dashboard label changed to "Relief units left"; per-item figures stay in the Relief Item table |

## 8. Out of scope (possible later work)

Real-time push (WebSocket) instead of 5-second polling; SMS / e-mail alerts; matching needs (`*_emergency_item`) against relief supply; importing live cyclone data from a weather service; multi-language screens.
