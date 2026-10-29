<p align="center"><img src="assets/logo-512.png" width="120" alt="Disaster Mapping & Relief Management System logo"></p>

# Disaster Mapping & Relief Management System

A PHP + MySQL web application for recording disasters and their effects, sheltering affected families, handing out relief,
and watching the situation on one **Live Report** that refreshes itself every 5 seconds. The interface can be switched
between English and Bangla, with the choice remembered in the current browser.
Built for WampServer; no framework, no build step, no Composer.

* 25 database tables (24 shown in the app), 8 integrity triggers, CHECK constraints and foreign keys with deliberate `ON DELETE` rules
* Role-based login (admin / editor / viewer), audit log with before/after values and record confirmations, CSRF protection, Content-Security-Policy
* Automated smoke checks (`php tests/smoke.php`)

---

## 1. Quick start

**With WampServer**

1. Start WampServer (icon green).
2. Copy the whole `disaster_and_relief_management` folder into `C:\wamp64\www\`.
3. If your MySQL password is not empty, edit `config.php` (host, user, password, port). Keep `name` as `disaster_and_relief_management`.
4. Open <http://localhost/disaster_and_relief_management/>.
5. First visit: the database and all tables are created and filled with sample data automatically (no phpMyAdmin import needed). Then create the **administrator account** on the first-run screen (password: at least 8 characters).

**One click (`start_localhost.bat`, in the folder above this one)** - starts MySQL and Apache without administrator rights, links the project into `C:\wamp64\www\` if needed and opens <http://localhost/disaster_and_relief_management/>. `stop_localhost.bat` stops them again.

**Without Apache (`start_server.bat`)** - starts WampServer's MySQL and PHP's built-in server on <http://127.0.0.1:8099/> and opens the browser.
Edit the three paths at the top of the file if your WampServer versions differ. `router.php` keeps private files (config, SQL dump, PowerShell tools, `inc/`, `tests/`) unreachable on this server.

Requirements: PHP 8.1 or newer with `mysqli` and `mbstring` (tested on PHP 8.2), MySQL 8.0.16 or newer (CHECK constraints and triggers; tested on 8.2), internet access for the map tiles (Leaflet / OpenStreetMap) and Ventusky Live Weather.

### Database backup and restore

Run a manual backup from this folder:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\backup_database.ps1 -Mode Backup
```

The script reads the database name, host, user and port from `config.php`, prompts for the MySQL password without echoing it,
and creates a timestamped SQL backup under `Documents\DRMS-Backups` by default. Pass `-BackupDirectory "E:\DRMS-Backups"` to
choose another destination. It includes table data, triggers, routines,
events and binary columns. The temporary MySQL credential file is restricted to the current Windows user and removed after
the operation. Store backup files on a separate, access-controlled drive and protect them as sensitive data; they contain
personal records. The script refuses to overwrite an existing backup.

For unattended backups, configure a MySQL login path once from the same Windows account that will own the scheduled task
(use that MySQL installation's `mysql_config_editor.exe`; it stores credentials in the Windows user's MySQL login-path file):

```powershell
& "C:\wamp64\bin\mysql\mysql8.2.0\bin\mysql_config_editor.exe" set --login-path=drms-backup --host=127.0.0.1 --port=3306 --user=drms_backup --password
```

Use an authorized backup account with read access plus the privileges needed to dump views, triggers and events. Then create a
Windows Task Scheduler task that runs under the same Windows account, starts after MySQL, and executes:

```text
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "C:\wamp64\www\disaster_and_relief_management\backup_database.ps1" -Mode Backup -LoginPath drms-backup -BackupDirectory "E:\DRMS-Backups"
```

Set a schedule appropriate to operations and alert on task failures. MySQL login paths are obfuscated by the MySQL tool, not
a substitute for protecting the Windows account. Backups are plain SQL files; keep the target on encrypted, access-controlled
storage and retain copies separately from the PC.

To restore a backup, first copy it to a safe location and verify that it is the intended file. Restore replaces tables in the
configured database and requires a MySQL account with the necessary `DROP`, `CREATE`, `ALTER`, `INSERT` and trigger privileges:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\backup_database.ps1 -Mode Restore -BackupPath "C:\path\to\backup.sql" -MysqlUser root
```

The restore asks for the exact database name before proceeding. Take a fresh backup before restoring, and test recovery against
a separate database before relying on backups for real operations. Keep the application database account least-privileged;
use an authorized administrative account only for backup/restore work when needed.

## Share your local server

On Windows, install the `cloudflared` CLI (`winget install --id Cloudflare.cloudflared`) and start WampServer's Apache and MySQL:
if Windows does not yet recognize `cloudflared`, open a new PowerShell terminal.

```powershell
..\start_localhost.bat /nobrowser
```

In a second PowerShell terminal, from this project folder, run:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\share.ps1
```

The script forwards to port 80 by default; pass a different local port as an optional argument, for example
`powershell.exe -NoProfile -ExecutionPolicy Bypass -File .\share.ps1 8080`.
Copy the `https://<random-words>.trycloudflare.com` URL printed by `cloudflared` and append `/disaster_and_relief_management/`
to open this app (Apache serves the project under that path). Keep both the local server and tunnel terminals running.
Stop the tunnel with **Ctrl+C** in its terminal; stop WampServer with `..\stop_localhost.bat`.

### Important notes

* The URL is temporary and random. It changes whenever the tunnel restarts and works only while both the local server and tunnel are running.
* Anyone with the link can access the app. Do not expose sensitive data, admin panels, or real credentials.
* Quick Tunnels are for testing and demos, not production. They do not support Server-Sent Events (SSE) and have a limit of about 200 concurrent requests.
* No Cloudflare account or auth token is needed. Never commit secrets.

---

## 2. Roles

| Role | Can do |
|---|---|
| **admin** | Everything: all tables, delete disasters, the *Users* page (add users, change roles, reset passwords, delete users), reopen closed incidents, and acknowledge/assign alerts |
| **editor** | View everything, add / edit / delete records, confirm records are current, and acknowledge/assign alerts (but not delete a **disaster**, which removes everything recorded under it) |
| **viewer** | Read-only, search, CSV export |

Five wrong passwords lock an account for 5 minutes (a fresh set of attempts starts after the lock). Every user can change their own password by clicking their name (top right).
A signed-in session ends after 4 hours without a click; the Live Report's automatic refresh does not keep it alive.

---

## 3. What is in the system

### Pages

| Page | Purpose |
|---|---|
| **Dashboard** | Totals, **Live status** cards (warning signals, full shelters, relief shortages, data health), *Needs attention* list, disasters at a glance, risk and shelter-occupancy bars, table index |
| **Live Report** | The control room view, refreshed every 5 s (see 3.1) |
| **Live Weather** | Khulna current weather, forecasts and interactive Ventusky map; use its search to change location |
| **Data Health** | 17 database checks that find records that contradict each other or are incomplete (see 3.4) |
| **Disaster Report** | One printable situation report per disaster: signals, areas, warnings, path, damage, shelters, organisations, relief given, **relief coverage by area**, affected families |
| **Response tasks** | Incident-linked tasks with an assigned DRMS user, due date, progress status and change history; overdue work appears in Needs attention |
| **Field updates** | Editors record online situation, shelter, medical, supplies or safety observations against an incident and affected area; each entry is time-stamped and attributed |
| **Shelter readiness** | Record services and accessibility notes; editors can confirm capacity/readiness, with the verification time and reviewer recorded. Active-incident shelters not checked in the last 7 days are flagged |
| **Inventory & stock** | Record receipts as append-only batches, including source, location and expiry; dispatches select the correct batch and available stock is reconciled against the item totals |
| **Map** | Risk-coloured area circles and shelter-capacity markers, overlaid with observed and forecast paths for live incidents |
| **Search** | One box that searches every table, including readable names from linked records; autocomplete suggestions on each table page |
| **Table pages** (24) | Sort, search linked records, paginate, **CSV export**, review record confirmation status, add / edit / delete. Shelter rows have a dedicated **Confirm capacity** action. Relief item totals are managed through Inventory & stock; batch records and field updates are append-only. Delete shows what else would disappear or what blocks it |
| **Users** (admin) | Accounts and roles |
| **Audit Log** | Who added / edited / deleted what, with the old and new values (read-only) |

Use the **বাংলা / English** button in the header or sign-in screen to switch the interface language. The preference is
stored in that browser. User-entered record contents and the embedded third-party Live Weather experience are not translated.

### 3.1 Live Report

All panels are fed by one JSON call, `report_data.php`, and redraw only when the data changed.

1. **Warning signals** - for every *Active* disaster the signal of its latest warning that has a signal number.
   Signals 1-3 = green **Normal**, 4-7 = orange **Danger**, 8-10 = red **Severe Danger**, none = grey *No signal*.
   An area takes the highest signal of the active disasters affecting it. *Check my area* shows any area's signal.
2. **Live disaster path** - Leaflet map: solid red line = observed path, dashed orange line = forecast, big dot with circle = current position and impact radius.
   Areas are highlighted and listed as **Currently crossing** (centre within the current position's radius), **In the forecast path** (within a forecast point's radius) and **Already passed**.
3. **Shelter space** - places left = capacity - family members currently checked in. Green with the exact number while any place is left; red **"No space available"** at 0.
   Filter by area, *only shelters with space*, and **nearest to** an area or to *your location* (browser geolocation).
4. **Relief distribution** - per Active disaster and home area: people who have received relief.
   Green **Relief on track** shows the received count. Red **Shortage** shows both *received* and *not received*.
   Shortage = fewer than 80 % of the affected people reached **or** a coordinator set *Relief Shortage = Yes* on that disaster's area.
5. The original cards, disaster-wise summary, risk bars, shelter and relief-item tables are unchanged below.

To change what the panels show, edit data in the table pages: *Early Warning* (Signal Level), *Disaster* (incident lifecycle), *Disaster Track* (path points), *Shelter Assignment* (check in / out), *Relief Distribution* + *Relief Recipient*, *Disaster Area* (Relief Shortage).

On the Dashboard, editors and administrators can acknowledge and assign each current alert to an administrator or editor. The
acknowledgement and owner are retained in the audit log; the alert remains visible until its underlying condition is fixed.
Live incidents with a latest signal of 8 or higher also generate an actionable severe-warning alert. Alerts are in-app only; no
email or SMS service is contacted.

### 3.5 Incident lifecycle

Disasters move through **Reported → Active → Under control → Closed**. A reported incident can be activated; active incidents can
be marked under control or closed; under-control incidents can return to active or be closed. Reopening a closed incident is
restricted to administrators. Only Active and Under control incidents appear in live signals, paths, and relief status. Existing
`Ended` records are migrated to `Closed` without deleting incident data. New records start as Reported. Assign an incident lead
from the DRMS user list in the Disaster table. Status-change and close timestamps are recorded by the application; historical
records retain null timestamps when the exact time was not available.

### 3.2 Live Weather

Open **Live Weather** from the Dashboard or navigation to view the weather page, initially centered on Khulna. The
embedded map, conditions, search and forecasts are provided by Ventusky, whose branding remains visible in the embedded
experience. Use the **Open Live Weather in a new tab** link if the embedded page
is unavailable or inconvenient on your device. This feature requires an internet connection and relies on Ventusky's
availability and embedding behavior.

### 3.3 Database (25 tables)

Main tables: `disaster`, `area`, `affected_person` (a family; `Family_Size` people; home `Area_ID`), `shelter`, `rescue_org`, `relief_item`, `emergency_item`, `early_warning` (with `Signal_Level` 1-10), `damage`,
`shelter_assignment` (who stayed where; empty `Check_Out` = staying now), `relief_distribution` (what was given where), `disaster_track` (path points), `incident_task`, `incident_update`, `relief_batch`, `audit_log`, `users`.
Relationship tables: `disaster_area`, `disaster_shelter`, `disaster_affected_person`, `disaster_rescue_org`, `rescue_org_relief_item`, `affected_person_emergency_item`, `shelter_emergency_item`, `relief_recipient`.

* Occupancy and remaining relief stock are **calculated**, never stored: stock left = `relief_item.Quantity` (received) - sum of distributions.
* **Foreign keys** with chosen rules (CASCADE / SET NULL / RESTRICT), **CHECK constraints** (capacity > 0, coordinates in range, signal 1-10, check-out not before check-in, quantities >= 0 ...).
* **Triggers** (`inc/triggers.sql`, 8): a stay or a distribution must use a shelter that belongs to that disaster, a distribution's organisation must belong to it, a relief recipient and a stay's person must be affected by that disaster, damage must be in an affected area. They also protect data changed in phpMyAdmin.
* **Business rules** (`inc/rules.php`) with friendly messages: shelter capacity, one open stay per person, relief stock, organisation supplies the item.
* **Field updates** are available on each disaster report. Updates are online-only, attributed to the signed-in editor, area-scoped to the incident, and retained as append-only records.

### 3.4 Data Health

`health.php` runs 17 read-only checks: stays at unlinked shelters, people in two shelters, over-capacity shelters, relief given beyond stock, recipients of the wrong disaster, damage in unaffected areas,
active disasters without a signal or area, families without a home area, missing coordinates, paths with no observed point, plus notes (long stays, low stock, families still sheltered after a disaster ended).
The dashboard shows the number of problems.

---

## 4. Security

* Prepared statements everywhere; table and column names are whitelisted against the real schema; output is HTML-escaped; the map and the live report build popups from text only.
* CSRF token on every state-changing request (including sign-out); `session.use_strict_mode`, HttpOnly + SameSite cookies, session id renewed at sign-in and password change.
* bcrypt password hashes, account lockout, idle timeout, roles checked on the server.
* Headers: `Content-Security-Policy` (scripts only from this site and `unpkg.com` for Leaflet), `X-Frame-Options: DENY`, `X-Content-Type-Options`, `Referrer-Policy: strict-origin-when-cross-origin` (OpenStreetMap blocks map tiles when no Referer is sent). Leaflet is loaded with Subresource Integrity hashes.
* Database errors are written to the PHP error log, never shown. CHECK / trigger messages shown to users are plain language.
* `.htaccess` (Apache / WampServer) and `router.php` (PHP built-in server) hide `config.php`, `*.sql`, `*.txt`, `*.md`, `inc/`, `tests/`.
* CSV export neutralises spreadsheet formulas (`=`, `+`, `-`, `@`).
* For anything beyond a lab PC: run `create_db_user.sql` and put the limited `drms_app` user in `config.php` instead of `root`, and serve over HTTPS.
  The limited user cannot create or alter tables: do the one-time upgrade (section 6) as `root` first.

---

## 5. Tests

```
php tests/smoke.php        # MySQL must be running; changes nothing permanent (writes are rolled back)
```

Checks cover installation, foreign-key map, CSRF helper, CHECK constraints, trigger refusals, data consistency of the sample data, business rules,
signal / space / relief / path calculations (including boundaries: signal 3|4|7|8, 80 % relief, radius edges), data-health detection, record verification, and role permissions.
Use `DRMS_DB_NAME=<name>` to run against a copy.

```
php tests/concurrency.php  # two PHP processes save into the last places of a shelter at the same moment; exactly one must win
```

Saving through the web pages is **race-safe**: each save runs in one transaction and first locks the family and the shelter (or the relief item), so two people cannot both pass the capacity / stock check. The test also runs a control without the lock to show that the race is real. It adds two temporary families and removes them again.

The project was also checked by hand and with scripts for: access without a session, CSRF, viewer / editor limits, SQL-injection and stored-XSS attempts, lockout and idle timeout,
every page and table in a real headless browser with no console or CSP errors, and byte-for-byte equality of an upgraded database and a fresh install.

---

## 6. Upgrading and resetting

* **From the previous 20-table version**: just open any page. A database that only lacks the new tables is upgraded **in place** (new columns, tables, triggers, plus sample rows only if the data is still the untouched sample). Existing rows and users are kept. The MySQL user in `config.php` needs ALTER / CREATE / TRIGGER rights for this one time (root is fine).
* **Incident workflow update**: back up the database, then sign in once with a MySQL account that can ALTER the database. Existing `Ended` statuses are mapped to `Closed`; the schema is then ready for the four-stage workflow. Switch back to the least-privileged app account afterwards.
* **Any other incomplete database** is never overwritten: you get a message instead. Back up what you need, drop the database in phpMyAdmin and reload.
* **Start again from scratch**: drop `disaster_and_relief_management` in phpMyAdmin and reload the page (the admin account must then be created again).

---

## 6a. Known limits

* **Path geometry is simplified.** An area counts as "being crossed" when its centre point lies inside the disaster's impact circle; real areas are shapes. Radii and the sample path are invented.
* **Relief status measures reach, not sufficiency.** A family counts as "received" once it is a recipient of any distribution of that disaster. "Relief units left" on the Dashboard adds different units (kilograms, bottles, blankets); per-item numbers are in the Relief Item table.
* **Capacity and stock limits apply to saves made in the web pages.** Changes made directly in SQL (phpMyAdmin) bypass them; the *link* rules (a shelter must belong to the disaster, etc.) are triggers and always apply, and Data Health reports any over-capacity shelter.
* **Signals** follow the 1-3 / 4-7 / 8-10 bands of the requirements, not any official warning scheme; the wording of the meanings is descriptive.
* **Map tiles need internet** and a browser that sends a Referer (OpenStreetMap blocks requests without one). The page header `Referrer-Policy: strict-origin-when-cross-origin` and the tile layer both ask for that; a privacy setting that blocks Referers (for example Brave Shields) can still cause "Access blocked" tiles.
* **Plain HTTP on localhost.** Use HTTPS if the system is ever opened to a network. Apache and MySQL must be started again after the PC restarts (`start_localhost.bat`).
* All sample data is fictional.

## 7. Files

```
index.php            sign-in / first-run          dashboard.php   dashboard             report.php + report_data.php + assets/report.js   Live Report
weather.php          Ventusky live weather for Khulna        backup_database.ps1  manual backup and restore
table.php            generic table pages          disaster.php    disaster report       health.php  data health
assets/logo.png, logo.svg, logo-512.png   the logo (see below)
map.php + assets/map.js   map                      search.php + api.php   search         users.php, account.php, logout.php
db.php               connection, installer, upgrade, SQL script runner      router.php   private-file guard for php -S
inc/boot.php         session, headers, roles, CSRF, audit, layout          inc/meta.php   schema reader, permissions
inc/stats.php        all dashboard / live-report calculations              inc/rules.php  business rules
inc/health.php       the 17 data checks                                    inc/upgrade.php + inc/triggers.sql   in-place upgrade, triggers
disaster_and_relief_management.sql    fresh-install file (schema, sample data, triggers)
tests/smoke.php      automated checks              create_db_user.sql   limited MySQL user          config.sample.php   settings template
```

**Logo.** `assets/logo.png` is the one image used for the sign-in page, the menu and the browser tab; `logo.svg` is its vector source and `logo-512.png` a large copy for documents. To use your own logo, replace `assets/logo.png` with a square image (192 x 192 px or larger); nothing else needs to change.

## 8. Sample data

13 disasters (floods, cyclones, tsunami, erosion, tidal surge, waterlogging) in the Khulna - Satkhira - Bagerhat region, 21 shelters, 60 families, 12 organisations.
Two disasters are **Active**: a flood (signal 5, orange) and a **cyclone** (signal 10, red) with an 11-point path, a school shelter that is exactly full, and a relief shortage in several areas - so every state of the Live Report can be seen immediately.
The data is fictional.

## 9. Troubleshooting

| Message | What to do |
|---|---|
| Apache stops at start-up; `C:\wamp64\logs\apache_error.log` says *VCRUNTIME140.dll 14.0 is not compatible with this PHP build linked with 14.29* | The old C++ runtime in `C:\Windows\System32` is used. Copy `C:\wamp64\bin\php\php8.2.13\vcruntime140.dll` into `C:\wamp64\bin\apache\apache2.4.58\bin\` and start Apache again (or install the current "Microsoft Visual C++ 2015-2022 Redistributable x64") |
| *Cannot reach MySQL* | Start WampServer / run `start_server.bat`; check host and port in `config.php` |
| *MySQL refused the user name / password* | Fix `user` / `pass` in `config.php` |
| *database is incomplete or from an older version* | See section 6 |
| *needs a one-time upgrade, but the MySQL user may not change tables* | Put `root` in `config.php`, open any page once, switch back |
| Map is grey / "map library could not be loaded" | No internet; the tables and lists under the map still work |
| Technical details of any error | PHP error log (`C:\wamp64\logs\php_error.log`) |
