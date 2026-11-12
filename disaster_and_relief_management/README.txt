Disaster Mapping & Relief Management System
==========================================

Full feature, installation, security, and operator instructions: README.md.
Submission and detailed walkthrough guide: see DRMS_Submission_Guide.pdf in the package root.

Windows quick start
1. Start WampServer and wait for its icon to turn green.
2. Copy this folder into C:\wamp64\www\.
3. If needed, copy config.sample.php to config.php and configure the local MySQL connection.
4. Open http://localhost/disaster_and_relief_management/.
5. On a fresh database, the installer creates the current schema and fictional examples.
   Create the first administrator using the first-run form; use a strong unique password.

The current schema has 25 database tables: 24 record pages and the separately managed Users
table. It includes eight integrity triggers. Use MySQL 8.0.16+ and PHP 8.1+ with mysqli
and mbstring.

Roles: administrator has full access; editor manages response records but cannot manage users,
delete disasters, or reopen closed incidents; viewer is read-only and can export CSV.

The Live Report displays warning signals, incident paths, shelter capacity, and relief coverage.
Its refresh period is configured in assets/report.js. Maps and external weather need internet.
All bundled example incidents, alerts, families, and contact details are fictional.

For an existing database, make a verified backup before any upgrade. Never drop or reset a
database to troubleshoot an installation. Use a disposable database for tests:
  php tests/smoke.php
  php tests/concurrency.php

Do not expose a development server to the public internet. Use HTTPS, least-privilege
database access, and approved privacy controls before any real operational use.
