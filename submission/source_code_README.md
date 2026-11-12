# Disaster Mapping & Relief Management System - source code

PHP 8 + MySQL 8 web application for disaster response: incidents, affected areas and families, warning signals, shelters and
occupancy, relief stock and distribution, response tasks, a live cyclone-path map and an audit log.

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, vanilla JavaScript (`assets/`), Leaflet 1.9.4 + OpenStreetMap for maps |
| Backend | PHP 8.1+ (no framework), `mysqli` prepared statements |
| Database | MySQL 8.0.16+ (InnoDB, utf8mb4, CHECK constraints, triggers, functional index) |

## 1. Requirements

- PHP 8.1 or newer with the `mysqli` and `mbstring` extensions (WampServer 3.3 includes PHP 8.2 and MySQL 8.2).
- MySQL 8.0.16 or newer.
- Internet access only for map tiles and the embedded weather page.

## 2. Create the database

From the submission's `database` folder, either in phpMyAdmin (Import, first `schema.sql`, then `sample_data.sql`) or with the MySQL client:

```
mysql -u root -p < database/schema.sql
mysql -u root -p < database/sample_data.sql
```

This creates the database `disaster_and_relief_management` with 25 tables, 8 triggers and 3-5 sample rows per table.
Demo sign-in accounts: `demo_admin / DemoAdmin2026` (admin), `demo_editor / DemoEditor2026` (editor), `demo_viewer / DemoViewer2026` (viewer).

(Alternative: skip this step and point the application at an empty database. On the first visit it installs a larger
bundled data set from `disaster_and_relief_management.sql` and asks you to create the first administrator.)

## 3. Configure the database connection (environment variables)

The settings are read from environment variables first, then from `config.php`:

| Variable | Meaning | Default |
|---|---|---|
| `DRMS_DB_HOST` | MySQL host | `127.0.0.1` |
| `DRMS_DB_PORT` | MySQL port | `3306` |
| `DRMS_DB_USER` | MySQL user | `root` |
| `DRMS_DB_PASS` | MySQL password | empty |
| `DRMS_DB_NAME` | database name | `disaster_and_relief_management` |

Create the settings file once: copy `config.sample.php` to `config.php` (it reads the variables above; you may also type
your values after the `?:` instead). `config.php` is kept out of git and is blocked from the web. For anything beyond a lab PC, run `create_db_user.sql` and use that restricted user instead of root.

## 4. Run

**WampServer (Apache):** copy the `source_code` folder to `C:\wamp64\www\disaster_and_relief_management`, start WampServer and
open `http://localhost/disaster_and_relief_management/`.

**PHP built-in server (any OS):**

```
cd source_code
# Windows PowerShell:  $env:DRMS_DB_USER='root'; $env:DRMS_DB_PASS=''
# Linux / macOS:       export DRMS_DB_USER=root DRMS_DB_PASS=
php -S 127.0.0.1:8099 router.php
```

Then open `http://127.0.0.1:8099/` and sign in with a demo account. `router.php` keeps `config.php`, `inc/`, `tests/` and the
SQL files private (the built-in server ignores `.htaccess`). Use it for local development only.

## 5. Tests (use a disposable database only)

```
DRMS_DB_NAME=drms_test php tests/smoke.php        # 115 checks: schema, rules, triggers, reports, security helpers
DRMS_DB_NAME=drms_test php tests/concurrency.php  # two users saving into the last shelter places at once
```

## 6. Folder layout

| Path | Contents |
|---|---|
| `index.php`, `logout.php`, `account.php`, `users.php` | sign-in, password change, user administration (admin) |
| `dashboard.php`, `report.php` + `report_data.php`, `map.php`, `disaster.php`, `health.php`, `stock.php`, `search.php`, `weather.php` | screens and the Live Report JSON feed |
| `table.php` | generic list / search / sort / CSV export / add / edit / delete for every table |
| `db.php` | connection, installer, query helpers |
| `inc/boot.php` | session, roles, CSRF, audit log, page layout, security headers |
| `inc/meta.php` | reads tables, keys and foreign keys from `information_schema` |
| `inc/rules.php` | business rules (capacity, stock, lifecycle) checked inside transactions |
| `inc/stats.php` | multi-table report queries shared by the dashboard, report and map |
| `inc/triggers.sql`, `inc/upgrade.php`, `inc/health.php` | triggers, in-place schema upgrade, data-health checks |
| `assets/` | CSS, JavaScript (Live Report, map, English/Bangla toggle), logo |
| `tests/` | smoke and concurrency tests |

All sample data is fictional.
