# DRMS Deep System Walkthrough — English Voice-over

**Video:** `DRMS_Deep_System_Walkthrough.mp4`  
**Runtime:** approximately 10 minutes 20 seconds  
**Recording style:** calm, clear narration; no need to read the visual callouts word for word.  
**Timing note:** timecodes are approximate navigation points. Leave short pauses when a page loads or an action completes; allow a few seconds of natural drift.

## 00:00–03:38 — System overview

### 00:00–00:28 — Opening

Welcome to the Disaster Mapping and Relief Management System, or DRMS. This application helps response teams organize incident information, affected families, shelters, warnings, field observations, response tasks, relief supplies, and distributions in one place.

The examples in this presentation are fictional training data. The system supports coordination and record keeping; its sample warnings and map information are not an official emergency alert service.

### 00:28–00:58 — Sign-in and access

On a new installation, an administrator creates the first account. After setup, staff sign in with their own username and password. Individual accounts help protect sensitive information and make actions traceable.

DRMS has three roles. Administrators manage users and all records. Editors can update response information, while viewers can browse and export records without changing them. The application checks permissions on the server, not just by hiding buttons.

### 00:58–01:30 — Dashboard

The dashboard gives the team a quick operational overview. Summary cards link to the underlying records, while the live status and attention areas highlight signals, shelter capacity, relief shortages, and information that may need follow-up.

Use the dashboard as a starting point, then open the linked incident or record to verify the details before acting.

### 01:30–02:05 — Incident and field information

An incident begins as Reported and progresses through Active, Under control, and Closed. The incident report brings related warnings, affected areas, families, shelters, damage, partner organizations, tasks, and field updates together.

Responders can add a timestamped field observation and identify its affected area. Updates are attributed to the signed-in user and retained for review. Avoid placing unnecessary sensitive personal details in shared operational notes.

### 02:05–02:38 — Live Report, map, and weather

The Live Report combines four operational views: warning signals, incident paths, shelter availability, and relief coverage. Its filters help focus on a locality, shelters with available space, or areas with shortages.

The map adds geographic context, and Live Weather is provided by an external service. Map tiles and weather require an internet connection; check their timestamps and limitations before relying on them.

### 02:38–03:10 — Records, inventory, and data quality

The record pages provide searchable, sortable information and CSV export. Authorized staff can add, edit, confirm, or delete records where permitted. Relationship rules help ensure that families, shelters, partners, and distributions are connected to the correct incident.

Inventory tracks item receipts, batches, balances, and movements. Data Health reports consistency checks so staff can investigate problems at their source rather than silently changing records.

### 03:10–03:38 — Transition to detailed demonstration

Next, we will go beyond the overview and exercise important workflows with a fictional, isolated training database. We will demonstrate role restrictions, validation, record changes, incident relationships, capacity-aware shelter assignments, response tasks, field updates, inventory, and the system’s record pages.

## 03:38–09:01 — Detailed workflow demonstration

### 03:38–04:10 — First-run account and role checks

The detailed walkthrough begins with first-run administrator setup. A strong password is entered into protected password fields; the example credentials are not displayed. After authentication, the dashboard opens.

The administrator creates separate Editor and Viewer accounts. We sign in as the Viewer and confirm that the Users page and record-editing controls are unavailable, while read-only browsing and CSV export remain available. We then check that the Editor cannot manage users or delete an incident.

### 04:10–04:45 — Validation and basic record management

Here we test server-side validation by attempting to save a family with an invalid size. The database rule rejects the value, and the form preserves the entered information for correction.

Next, we create a fictional response area and family. The family is linked to its home area, then confirmed as current. We edit the family record, review the audit trail, inspect the deletion warning, and cancel before deliberately deleting only the unlinked training family.

### 04:45–05:25 — Connect the incident and its response

We create a fictional incident and connect it to an affected area, a response shelter, a family, and a partner organization. These relationship records matter: the system checks that the linked records belong to the same incident before allowing dependent actions.

The family is assigned to an incident-linked shelter. The application checks family membership, open stays, and available people capacity before saving. We also create a response task, assign it to an Editor, and update its progress.

### 05:25–06:05 — Field update and incident lifecycle

A field observation is submitted with its category, area, observation time, and summary. DRMS attributes the update to the signed-in responder and displays it in the incident report.

The incident then moves through its supported status changes. Status timestamps are maintained automatically. Reopening a closed incident is restricted to an administrator.

### 06:05–06:45 — Live operations and inventory

The Live Report loads its current data from the authenticated application. We check the incident warning, path, shelter capacity, and relief coverage panels.

In Inventory and Stock, the administrator records a dated training batch for a relief item. The receipt is linked to the item and appears in the movement history, supporting reconciliation from stock received to stock distributed.

### 06:45–07:20 — User administration, alerts, and audit history

The administrator changes a user's role, restores the intended role, and demonstrates a password reset without revealing the previous password. Role changes are checked on subsequent requests.

An active alert is assigned to an Editor and acknowledged. The alert remains visible until its underlying condition is resolved. The audit log records actions and relevant changes for later review.

### 07:20–09:01 — All record pages and incident report

The walkthrough reviews the searchable record pages one by one, including the main records and their relationship tables. There are twenty-five database tables: twenty-four record pages, plus the separately managed Users table.

Each table serves a specific part of the workflow. Examples include affected families, areas, warnings, incident tracks, damage, tasks, field updates, shelters, assignments, inventory batches, distributions, and recipients. Relationship tables connect those records while enforcing incident consistency.

The incident report presents linked information in one place. Use its sections to review warnings, paths, area coverage, response tasks, field updates, shelter stays, and damage. Use the print action when a focused incident briefing is needed.

## 09:01–10:20 — Search, readiness, filters, and handover

### 09:01–09:24 — Search and shelter readiness

Global search finds related records across the system. Table search and suggestions help narrow a register, while CSV export provides a copy of the records available to the signed-in role.

On the shelter page, an authorized user confirms capacity and details. The verification is timestamped and attributed to the reviewer. Before deleting a related record, read the dependency warning and cancel if the linked information should remain.

### 09:24–09:52 — Live Report filters

The report’s area selector focuses the warning view on a selected locality. Shelter filters show only places with available capacity or sort from a chosen area. Relief filters prioritize reported shortages.

These controls change the displayed view, not the underlying records. Refresh now requests a new server snapshot, and the status indicator confirms whether the report is connected.

### 09:52–10:10 — Map, weather, and audit

The map shows geographic context for areas and shelters. External map tiles need internet access. Live Weather is also provided by an external service, so verify its availability and timestamp.

The audit log is read-only and helps reviewers see who performed an action and when. Use Data Health and the relevant source records to investigate consistency concerns.

### 10:10–10:20 — Closing

Before handover, review active warnings, assigned alerts, shelter capacity, inventory, and data health. Sign out when finished.

This demonstration used fictional data in an isolated training environment. For real operations, configure approved privacy controls, secure hosting, HTTPS, and least-privilege accounts.
