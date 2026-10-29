<p align="center"><img src="disaster_and_relief_management/assets/logo-512.png" width="130" alt="Disaster Mapping & Relief Management System logo"></p>

# Disaster Mapping & Relief Management System

A PHP 8 + MySQL 8 web application (WampServer) that records disasters and their effects, shelters affected families,
tracks relief distribution, shows **Live Weather** for Khulna (powered by Ventusky), and presents the whole situation
on a **Live Report** that refreshes itself every 5 seconds. The interface includes an English/Bangla toggle that
remembers the choice in the current browser.

| Live Report panel | Rule |
|---|---|
| **Warning signals** | 1-3 green *Normal*, 4-7 orange *Danger*, 8-10 red *Severe Danger* |
| **Shelter space** | places left in green with the exact number; red *No space available* at 0; nearest-shelter finder |
| **Relief distribution** | green with the number of people reached; red *Shortage* with both *received* and *not received* |
| **Live disaster path** | Leaflet map: observed track, forecast, current position and impact circle, areas being crossed / in the path |

Also: dashboard summary, data-health report (17 checks), inventory batches, incident response tasks and field updates,
8 database triggers, audit log with before/after values, roles (admin / editor / viewer), CSRF protection and Content-Security-Policy.

## Run it

1. Copy the folder [`disaster_and_relief_management/`](disaster_and_relief_management) into `C:\wamp64\www\` and start WampServer.
2. Copy `config.sample.php` to `config.php` and enter your MySQL login (the WampServer default `root` with an empty password also works).
3. Open <http://localhost/disaster_and_relief_management/>. The database, 25 tables, 8 triggers and sample data are created on the first visit;
   then create the administrator account on the first-run screen.

`start_localhost.bat` starts MySQL + Apache and opens the link; `start_server.bat` runs without Apache.

## Documentation

* [`disaster_and_relief_management/README.md`](disaster_and_relief_management/README.md) - features, roles, database, security, tests, upgrade, troubleshooting
* [`DRMS_Project_Overview_and_User_Guide.docx`](DRMS_Project_Overview_and_User_Guide.docx) - 45-page step-by-step guide with screenshots
* [`DRMS_ER_Diagram.pdf`](DRMS_ER_Diagram.pdf) - entity-relationship diagram of the original 22-table schema; it does not yet include the incident and inventory additions
* [`PLAN.md`](PLAN.md) - goals, decisions, architecture, quality gates
* [`TASKS.md`](TASKS.md) - task checklist with what was verified

Tests: `php disaster_and_relief_management/tests/smoke.php` (MySQL must be running; it changes nothing permanent).
All sample data is fictional.
