# Disaster Mapping & Relief Management System
## Final submission and detailed operator guide

This guide covers a clean installation, the full operator workflow, every navigation page and record table, role-specific actions, and a practical acceptance checklist. It describes the current release in this package; all built-in example incidents and demo records are fictional.

## What is included

- `disaster_and_relief_management/` — PHP/MySQL application, installer, example data, and automated tests.
- `start_localhost.bat`, `stop_localhost.bat`, `start_server.bat` — Windows/WampServer startup helpers.
- `DRMS_Deep_System_Walkthrough.mp4` — silent, 1280 × 720, caption-free feature demonstration. Brief visual callouts explain each screen.
- `DRMS_Submission_Guide.pdf` — print-ready companion to this guide.
- `DRMS_ER_Diagram.pdf` — original-schema reference diagram. Its title page explains the later additions that it does **not** contain; do not treat it as a complete diagram of the present 25-table schema.
- `README.md` — short package overview and launch instructions.

## 1. Install and start

### Requirements

- Windows and WampServer with PHP 8.1+ (`mysqli`, `mbstring`) and MySQL 8.0.16+.
- A modern browser. Internet is required for OpenStreetMap tiles, Leaflet, and the embedded Ventusky forecast; operational tables and report lists still remain useful if mapping services are unavailable.
- A MySQL account with permission to create the application database, tables, and triggers during a fresh install. For an existing installation, make a backup before using any upgrade path.

### WampServer

1. Extract the submission package.
2. Copy `disaster_and_relief_management\` into `C:\wamp64\www\`.
3. If this installation does not already have a valid local `config.php`, copy `config.sample.php` to `config.php` in that application folder and configure the local MySQL host, user, password, database, and port. Never place real passwords in a public archive.
4. Start WampServer and wait for its icon to turn green. Alternatively, launch `start_localhost.bat` from the extracted package.
5. Open `http://localhost/disaster_and_relief_management/`.
6. On a new database, create the first administrator account. Use a unique user name and a strong password of at least eight characters.
7. Sign in, open **Users**, create accounts for staff, and choose a role for each.

### PHP's built-in local server

If the WampServer paths in `start_server.bat` match this computer, run the script. It starts MySQL and serves only on `127.0.0.1:8099`. The built-in `router.php` blocks configuration, SQL, documentation, tests, and source-only files from direct web requests. Do not expose this development server to the public internet.

### First-run data and safe reset

When the configured database has none of the application tables, the installer creates the current schema, installs eight triggers, and loads fictional example data. It then displays the administrator-creation form. It refuses to overwrite a partially populated or unrecognized database. Never drop or restore a database without verifying its name and creating a separate, verified backup first.

## 2. Sign-in, language, and personal account

- **First run:** create the first administrator using the account-creation form and matching password fields.
- **Sign in:** enter the account name and password. Five failed password attempts lock that account for five minutes. A fresh set of attempts begins after lockout expires.
- **English/Bangla:** select the language button on the sign-in page or top bar. The browser remembers its preference; record data and the external weather provider are not translated.
- **Change password:** choose your account name in the top bar, confirm your current password, enter and confirm the new password, and save.
- **Sign out:** use the top-bar button. The session expires after four hours without user activity; automatic Live Report refreshes do not count as activity.

## 3. Roles and what each can do

| Action | Administrator | Editor | Viewer |
|---|---:|---:|---:|
| View records, search all tables, use maps and reports | Yes | Yes | Yes |
| Add/edit normal records and confirm current details | Yes | Yes | No |
| Delete non-disaster records, subject to database relationships | Yes | Yes | No |
| Delete a disaster (and records that depend on it) | Yes | No | No |
| Acknowledge and assign current alerts | Yes | Yes | No |
| Receive stock through the Inventory & stock page | Yes | Yes | No |
| Use the Users page; add users; change roles; reset passwords; delete other users | Yes | No | No |
| Reopen a closed incident | Yes | No | No |
| View/export the audit log | Yes | Yes | Yes |

The server checks permissions on each request; hiding a button is not the security boundary. A user cannot change or delete their own administrator account through the user-management screen.

## 4. Dashboard: incident and response overview

The dashboard links counts to their source tables. Its cards summarize disasters, affected families, shelters, response organisations, warnings, total shelter capacity, people checked in now, relief units remaining, estimated loss, and Live Weather.

Below the cards:

- **Live status** shows active warning signals, shelters with no remaining space, relief shortages, and the Data Health result.
- **Needs attention** draws attention to active response issues, overdue tasks, and readiness follow-ups. Administrators and editors can acknowledge/assign current alerts to an administrator or editor. The owner and acknowledgement are audited; the alert remains until its underlying condition is fixed.
- Disaster summaries and risk/occupancy bars link back to source records.
- **Manage inventory batches** opens the stock workflow.

Use the fictional sample cyclone and flood to see the red severe-danger and orange danger states, a full shelter, and relief shortage cards without creating artificial emergencies.

## 5. Incident lifecycle, field updates, and disaster report

### Create and assign an incident

1. Open **Disaster** and choose **Add record**.
2. Enter the event type, date, and available incident details. New incidents begin as **Reported**.
3. Link affected areas, shelters, affected families, and response organisations using their corresponding relationship tables.
4. Assign an incident lead from the DRMS user list.

### Move through incident states

The valid progression is **Reported → Active → Under control → Closed**. An incident under control may return to Active or move to Closed. Only administrators may reopen a closed incident. Status-change and closure times are recorded by the application.

Active and Under control incidents contribute to live warning, path, shelter, and relief panels. Reported or Closed incidents are retained for review but do not appear in those active summaries.

### Track field work

- Record **Response tasks** in the incident-task records: title, details, assigned DRMS user, due date, and status. Keep each task tied to the correct incident; overdue or blocked work can appear in Needs attention.
- Open **Disaster Report** to select an incident and review its summary, warnings, affected areas, path, damage, linked shelters and their verified capacity, rescue organisations, relief, relief coverage by home area, and affected families.
- Use **Field updates** on the report to capture what an on-scene reporter observed. Select an incident area, update type, concise summary, details, and observation time. The reporter and submission time are recorded. Updates are append-only and appear in the report and audit trail.
- Use **Print report** for a concise, incident-specific printable situation report. Verify the selected disaster before printing or sharing.

## 6. Live Report: all four operational panels

The Live Report reads one JSON snapshot, redraws when data changes, and refreshes every five seconds while the browser tab is visible. **Refresh now** fetches immediately; **Print** prints the report. Try the filters and area selector with the fictional sample data.

### Warning signals

- Signals **1–3** are Normal, **4–7** Danger, and **8–10** Severe Danger.
- Each active incident shows its latest warning that has a numeric signal.
- An area shows the highest signal from active incidents affecting it.
- **Check my area** looks up one selected area and lists the incidents contributing to its status.
- Change warning values through **Early Warning**. Signals are descriptive system bands, not an official national alert feed.

### Live incident path

- Select an incident point or line to see the position, point time, observed/forecast type, impact radius, and wind speed where available.
- The solid line is the observed track; the dashed line is the forecast; the larger marker/ring identifies the current position and impact radius.
- Read the accompanying categories for areas being crossed, forecast-path areas, and areas already passed.
- Adjust incident path points through **Disaster Track**. Sample coordinates and radius are fictional. The geometry is deliberately simplified; an area's centre point, not its full polygon, is compared with impact radii.
- If online map tiles cannot load, continue using the incident-path lists.

### Shelter availability

- Inspect capacity, number of people currently checked in, and exact places left; a full shelter displays **No space available**.
- Choose an **Area**, enable **Only shelters with space**, and/or choose **Nearest to** a known area.
- **Use my location** requests the browser's explicit geolocation permission and sorts from the allowed location. If access is denied, use the area selector instead.
- Shelter assignments record check-in and optional check-out. Capacity is enforced during web saves, and a family cannot have two open stays. Shelter and disaster links must match.

### Relief coverage

- Inspect each active disaster and affected home area. This measures people who received a recorded distribution, not whether the amount met every family's needs.
- A shortage is shown when coverage is below **80%** or the relevant Disaster Area row has **Relief Shortage = Yes**.
- Enable **Only shortages** to prioritize affected areas.
- Record a shipment/distribution under **Relief Distribution**, record recipients under **Relief Recipient**, and flag a coordinator-confirmed shortage on **Disaster Area**.

The lower report retains the disaster summary, counts, risk bands, shelters, and relief-item tables.

## 7. Map and Live Weather

### Map

The **Map** displays risk-coloured circles for mapped areas, shelter symbols shaded by occupancy (green/orange/red), and available linked incident paths. Select a symbol for its location and related status. Leaflet and OpenStreetMap tiles require internet access; the accompanying tables and report lists remain the fallback.

### Live Weather

The **Live Weather** page embeds Ventusky around Khulna. Use the provider's search to change location and inspect its forecast/map controls, or choose **Open Live Weather in a new tab**. Weather data is supplied by the external provider; check its current timestamp and availability before making an operational decision.

## 8. Data Health and shelter readiness

### Data Health

The 17 read-only checks identify inconsistencies such as duplicate open shelter stays, over-capacity shelters, stays at a shelter not linked to the incident, unlinked or unaffected family/recipient records, relief issued beyond stock, damage in an unaffected area, missing information used by active reports, and unusual conditions that merit review.

Open each reported record at its source table and correct it through an authorized workflow. The report never silently modifies or deletes data. A note or warning is a prompt for an operator, not necessarily a data-integrity failure.

### Confirm capacity and readiness

Open **Shelter**, review capacity, operating services, accessibility notes, and area. Editors and administrators use **Confirm current** to record that the details were reviewed. Confirmation stores the reviewer and timestamp and is included in the audit log. Shelters serving active incidents without a recent readiness check are flagged for follow-up.

## 9. Inventory and stock batches

Use **Inventory & stock** to reconcile item-level stock and receipt history:

1. Inspect total received, dispatched, available, and batch balance for each relief item.
2. Under **Receive a stock batch**, select the correct relief item; enter a unique batch code and positive integer quantity; enter the receipt date; optionally enter expiry, supplier/source, storage location, and notes.
3. Select **Record receipt**. The batch, relief-item received quantity, current user, and audit entry are saved together.
4. Review the batch ledger, expiry, location, and stock movements. Dispatches are recorded through incident-linked **Relief Distribution** and must use a batch for the same item.

The generic relief-batch table is deliberately read-only: receive a new batch through the inventory form. The available stock shown at a glance combines different units; compare the individual relief item and batch for a real deployment decision.

## 10. Global search and all 24 record tables

There are **25 database tables total**: 24 main/relationship record pages and one protected user table managed through the Users screen. Database metadata drives the generic record lists and forms.

### Common table-page options

- **Search:** choose a column or search all columns. Start typing for suggestions; referenced records include readable names where available.
- **Sort:** select a column heading to sort. Sort and search state remain in the page links.
- **Pagination:** navigate long result sets without loading every row at once.
- **Export CSV:** download the current searchable table rows. Values beginning with spreadsheet formula characters are neutralized before export.
- **Last confirmed / Confirm current:** authorized users can mark editable information as checked; the application records its reviewer and time. Shelter has its dedicated readiness confirmation.
- **+ Add record / Edit:** editors and administrators use column-aware forms with enums, date controls, linked-record dropdowns, and server-side validation.
- **Delete:** editors and administrators may delete normal records when permitted. A confirmation explains dependent records or a foreign-key restriction. Disaster deletion is administrator-only because it may remove the incident's related records. Read the confirmation carefully.
- **Read-only records:** the audit log, append-only field updates, and relief batch records cannot be edited or deleted via generic table forms. Use the purpose-built workflows where appropriate.

### Main/reference tables

| Page | What it stores and where to maintain it |
|---|---|
| **Affected Person** | One affected family, its size/contact, and home area. Link it to incidents using Disaster Affected Person. |
| **Area** | Community/locality, district, map coordinates, and risk rating. |
| **Audit Log** | Append-only record of who performed an action and when. Read-only. |
| **Damage** | Incident-linked impact type, area, quantities, estimated loss, and notes. |
| **Disaster** | Event description, date, state, lead, and status timestamps. Deleting a disaster is administrator-only. |
| **Disaster Track** | Time-stamped observed and forecast positions, coordinates, radius, and optional wind. |
| **Early Warning** | Incident warnings, message, time, type, and optional 1–10 signal. |
| **Emergency Item** | Requested supplies/needs for an affected family. |
| **Incident Task** | Incident response action, assignment, due date, and progress. Change its status through authorized record editing. |
| **Incident Update** | Attributed, time-stamped field update. Append via the disaster report; generic table is read-only. |
| **Relief Batch** | Receipt batch, supplier, expiry, location, and remaining quantity. Review here; add a batch via Inventory & stock. |
| **Relief Distribution** | Incident-linked item quantity, date, shelter, partner, and source batch. Only use linked entities and available stock. |
| **Relief Item** | Relief-item description, unit/category, and maintained on-hand received total. Do not treat different item units as interchangeable. |
| **Rescue Org** | Partner organisation, type, and contact. Link to incidents and to the relief items it supplies. |
| **Shelter** | Name, area, capacity, services, accessibility, and capacity-verification fields. Use readiness confirmation. |
| **Shelter Assignment** | Family stay at an incident-linked shelter, with check-in and check-out timestamps. An empty check-out means still staying. |

### Relationship tables

| Page | Connects |
|---|---|
| **Affected Person Emergency Item** | One affected family to an emergency-need item. |
| **Disaster Affected Person** | Incidents to the families affected by them. |
| **Disaster Area** | Incidents to affected areas, including the coordinator's relief-shortage flag. |
| **Disaster Rescue Org** | Incidents to responding partner organisations. |
| **Disaster Shelter** | Incidents to shelters participating in the response. |
| **Relief Recipient** | An individual/family to a relief distribution. The recipient must be affected by that distribution's incident. |
| **Rescue Org Relief Item** | A partner to the relief items it can supply. |
| **Shelter Emergency Item** | A shelter to an emergency-supply item it needs. |

Add relationship rows only after their parent records exist. Foreign keys and database triggers enforce these incident links even if someone bypasses the website in a SQL tool.

## 11. Important data rules

- All sample data is fictional. Replace demonstration/contact values under your organization's approved data-handling procedure.
- An affected-person row represents a **family**; `Family_Size` represents its number of people.
- Shelter occupancy is calculated from open stays and family sizes. The application locks involved rows during a save, so concurrent check-ins cannot exceed capacity.
- Available relief stock is calculated from receipts less distributions, with each distribution tied to its source batch. Concurrent dispatches lock the affected stock record.
- Signal bands are project-defined descriptions, not an official warning standard.
- Relief reach counts a person/family as reached once a related distribution-recipient row exists; it does not measure quantity adequacy.
- Area impact uses simplified point/radius geometry, not polygon boundaries. Example paths/radii are fictional.
- The service needs the internet for external basemaps and weather; neither should be represented as an official emergency feed.

## 12. Security, backup, and data handling

- Use unique accounts, strong passwords, and the least-powerful role needed for each staff member. A Viewer cannot create records; an Editor cannot delete an incident or manage users.
- For any non-disposable installation, use HTTPS and a dedicated least-privilege database account. `create_db_user.sql` describes the application account. An administrator performs the one-time schema installation/upgrade before switching to a restricted runtime account.
- Never share a public tunnel or repository with real family/contact data, credentials, database backups, or unredacted incident screenshots. Quick tunnels are for demos, not production.
- Use `backup_database.ps1 -Mode Backup` to make an authorized, protected backup. Verify the target database and backup before a restore; restore replaces the selected database.
- Review retention, privacy, consent, data residency, incident escalation, and offline fallback with the organization responsible for actual operations before live use.
- Browser defenses include prepared queries, role enforcement, CSRF, secure session settings, output escaping, a Content Security Policy, and restricted CSV output. Continue to patch and configure the underlying PHP/MySQL/WampServer host.

## 13. Clean verification and submission acceptance

Run on a **disposable test database**, not a database with live users or real response data. Confirm `config.php` points to the intended test database before each command.

```powershell
& "C:\wamp64\bin\php\php8.2.13\php.exe" .\tests\smoke.php
& "C:\wamp64\bin\php\php8.2.13\php.exe" .\tests\concurrency.php
```

Acceptance checklist:

- [ ] Fresh first-run install initializes the current schema and displays first-administrator setup.
- [ ] Administrator, Editor, and Viewer authentication; self-service password change; sign-out; and role-specific navigation work.
- [ ] Dashboard cards and active incident/alert summaries match the fictional sample database.
- [ ] All four Live Report panels load, their area and shortage/capacity filters work, and refresh does not sign out an idle user.
- [ ] The incident report can display signal, affected area, track, damage, shelters/readiness, partner, tasks, field updates, recipients/coverage, and families.
- [ ] Map markers and lists work; weather has an outbound link; outage behavior is shown clearly.
- [ ] Data Health is read-only and reports sample data correctly.
- [ ] Inventory can accept a valid batch on the disposable test database and correctly rejects invalid quantities/dates/duplicate batch codes.
- [ ] Each of the 24 record pages opens. Search, sort, pagination, CSV export, and record confirmation appear only where permitted.
- [ ] A test-only record can be created, edited, and deleted without changing a sample incident or a live database.
- [ ] Authorization, CSRF, relational integrity, check constraints, stock/capacity boundaries, and audit behavior pass the CLI tests.
- [ ] The final MP4 plays at 16:9, has no audio/subtitles, includes explanatory visual callouts, and contains no live contact details or saved passwords.
- [ ] The final ZIP opens, contains the application SQL schema, installation instructions, documented limitations, tests, and the matching final video, and excludes `config.php`, credentials, temporary captures, and test databases.

## Troubleshooting

| Symptom | First check |
|---|---|
| "Cannot reach MySQL" | Start the MySQL service and confirm host/port/user/database in `config.php`. |
| Database access or installer failure | Confirm the database name, user grants, MySQL version, and permissions for the first install or one-time upgrade. Never drop a database to clear an unexplained error. |
| Map is blank | Verify internet access, the Leaflet library request, OpenStreetMap requests, and browser Referer/privacy settings; use the lists below the map. |
| Live Weather is blank | Use **Open Live Weather in a new tab**; Ventusky requires internet and may change its embedding behavior. |
| A record is blocked on save | Read the validation message and review the referenced incident-area/shelter/organisation links, available capacity, stock, and duplicate open assignment. |
| User cannot open a screen or sees no write button | Review their assigned role. Only an administrator can use Users, delete incidents, or reopen a closed incident. |
| PHP details needed | Check the PHP/WampServer error log. Database exception details are logged rather than displayed to the visitor. |
