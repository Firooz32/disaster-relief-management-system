"""
Single source of truth for the narration. The recorder holds every beat on screen for at least `seconds(beat)`, and the
voiceover notes are generated from this text plus the real recorded timecodes, so voice and video cannot drift apart.

Pace: 2.2 words per second (about 132 words per minute, calm presentation speed) + 0.8 s breathing room per beat.
"""
WPS = 2.2
PAUSE = 0.8

F, U = "Firoz", "Urmy"

# (key, part, speaker, layout, what is on screen, narration)
BEATS = [
    # ---------------------------------------------------------------- Part 1: introduction and scope
    ("p1_hello", 1, F, "full: app", "Sign-in page of the application; the cursor rests near the logo.",
     "Assalamu alaikum. I am Taohiduzzaman Firoz, student ID 210232, and my teammate is Archy Mondal Urmy, student ID 240203. "
     "We are group 3, and this is our database project: the Disaster Mapping and Relief Management System."),
    ("p1_problem", 1, F, "full: app", "The cursor types the user name and password and signs in.",
     "Khulna is hit by cyclones and floods almost every year. When that happens, coordinators need answers fast: which areas are "
     "under a danger signal, which shelters still have space, which families have not received relief, and where the storm is moving."),
    ("p1_scope", 1, F, "full: app", "Dashboard: the cursor moves across the summary cards, then scrolls to the live status panels.",
     "Our system keeps all of this in one relational database: disasters, areas, families, shelters, warnings, relief stock and "
     "distribution, response tasks and the cyclone path, with three roles: admin, editor and viewer. I worked on the requirements, "
     "the reporting queries and this interface; Urmy built the database and the backend."),
    ("p1_stack_db", 1, U, "full: phpMyAdmin", "phpMyAdmin home page; the cursor points at the Database server and Web server boxes.",
     "I am Archy Mondal Urmy. Our database engine is MySQL 8.2 with InnoDB, so we have real foreign keys, transactions, "
     "CHECK constraints and triggers. The backend is PHP 8.2 running on Apache, and every query uses prepared statements."),
    ("p1_stack_fe", 1, F, "full: app", "The Map page of the application opens; the cursor pans over the Leaflet map.",
     "Firoz again. The frontend I built is HTML, CSS and plain JavaScript, with Leaflet and OpenStreetMap for the maps. "
     "In the live demo, phpMyAdmin will run beside the application, so you can see every change reach the database."),

    # ---------------------------------------------------------------- Part 2: ERD and schema
    ("p2_erd", 2, F, "full: ERD", "The complete ER diagram; the cursor visits the core entities one by one.",
     "This is our entity relationship diagram with 25 tables. The core entities are disaster, area, shelter, affected person, "
     "which is one row per family, rescue organisation, relief item and user."),
    ("p2_cardinality", 2, F, "full: ERD", "Zoom on disaster and its child tables; the cursor follows one relationship line.",
     "Each line is a one-to-many relationship. One disaster has many warnings, track points, damage records and tasks. "
     "The crow's foot marks the many side; a small circle means the link is optional."),
    ("p2_junction", 2, F, "full: ERD", "Zoom on disaster_area; the cursor points at its two key columns and Relief_Shortage.",
     "Many-to-many relationships use junction tables. A disaster affects many areas and an area is hit by many disasters, so "
     "disaster_area has a composite primary key of both foreign keys. Its extra column, Relief_Shortage, depends on that whole key."),
    ("p2_events", 2, F, "full: ERD", "Zoom on shelter_assignment, then along relief_item, relief_batch, relief_distribution, relief_recipient.",
     "Events have their own tables. A shelter assignment links one disaster, one family and one shelter. A relief item has stock "
     "batches, a batch is given out in distributions, and relief_recipient records which family received it."),
    ("p2_nf", 2, F, "full: ERD", "Zoom out; the cursor points at PK, FK and UK markers in a few tables.",
     "For normalization: every value is atomic, so first normal form. Only junction tables have composite keys, and their extra "
     "column depends on the full key, so second normal form. In every table the only determinants are keys, like shelter name or "
     "batch code, so the design is in third normal form and BCNF."),
    ("p2_exceptions", 2, F, "full: ERD", "The cursor points at relief_item.Quantity, then at Relief_ID and Batch_ID in relief_distribution.",
     "We documented two controlled exceptions: the stored stock total in relief_item, guarded by a discrepancy check, and Relief_ID "
     "in a distribution, which the batch already decides. Values like free shelter places are never stored; they are always calculated."),
    ("p2_keys", 2, U, "full: phpMyAdmin", "Database drms_demo: the list of all 25 tables; then the Structure page of shelter_assignment, scrolled to the Indexes section.",
     "I built this design in MySQL. Here is the relational schema: all 25 tables. Now shelter_assignment. Entities use an "
     "auto-increment primary key; junction tables use the pair of foreign keys. Every foreign key has an index, and this one is "
     "special: a functional unique index."),
    ("p2_openstay", 2, U, "full: phpMyAdmin", "The cursor points at uq_assignment_open_stay and its expression.",
     "It indexes the person only while Check_Out is empty. So a family can have many finished stays but only one open stay, and "
     "MySQL itself enforces it."),
    ("p2_cascade", 2, U, "full: phpMyAdmin", "Relation view: the foreign keys with their ON DELETE and ON UPDATE rules.",
     "Here are the foreign keys with their delete rules. Across the schema we have 40: 24 cascade for records that belong to a "
     "disaster, 8 restrict where deleting would lose history or stock, and 8 set null for optional links."),
    ("p2_triggers", 2, U, "full: phpMyAdmin", "Database Triggers page listing the 8 triggers; then trg_assignment_ins is opened and its SQL body is shown.",
     "18 CHECK constraints protect single values, like family size above zero or signal level one to ten. Rules that span tables are "
     "triggers. This is one I wrote: before a stay is inserted, it checks that the shelter is registered for the disaster and "
     "that the family is recorded as affected by it; otherwise it raises an error."),
    ("p2_explain", 2, U, "full: phpMyAdmin", "SQL tab: EXPLAIN of the overdue-task query is typed and run; the result shows the index.",
     "For indexing, this EXPLAIN shows that the overdue task query is answered from our composite index on status and due date alone."),

    # ---------------------------------------------------------------- Part 3a: live CRUD with phpMyAdmin beside the UI
    ("p3_create", 3, U, "split: phpMyAdmin | app", "App: Affected Person > Add record; types Shirin Akter, family size 4, area Dacope; saves. phpMyAdmin: browse affected_person, the new row 6 appears.",
     "Now the live demo, with phpMyAdmin on the left. I create a new family: Shirin Akter, four members, from Dacope. "
     "After saving, I refresh the table on the left, and the new row is already in MySQL."),
    ("p3_audit", 3, U, "split: phpMyAdmin | app", "phpMyAdmin: browse audit_log; the newest row shows add, affected_person, demo_editor and the values.",
     "Every change also writes the audit log, with the user and the exact values."),
    ("p3_link", 3, U, "split: phpMyAdmin | app", "App: Disaster Affected Person > Add: disaster 1, person 6; saves.",
     "Next I link her family to the active cyclone. This is a row in the junction table disaster_affected_person."),
    ("p3_capacity", 3, U, "split: phpMyAdmin | app", "App: Shelter Assignment > Add: disaster 1, person 6, Dacope Union Primary School Shelter. Error: over capacity 11 + 4 > 11. phpMyAdmin: SQL shows 11 people staying there.",
     "I try to place them in the Dacope school shelter. It is refused: eleven people are already staying and it has eleven places. "
     "This rule runs inside a transaction with SELECT FOR UPDATE, so two users can never take the last places together."),
    ("p3_assign", 3, U, "split: phpMyAdmin | app", "App: same form with Koyra Government Shelter; saves. phpMyAdmin: browse shelter_assignment, the new open stay.",
     "So I choose Koyra Government Shelter instead, and the new open stay appears in MySQL."),
    ("p3_read", 3, U, "split: phpMyAdmin | app", "App: Shelter Assignment list; sorts by Check In, searches 2026-10-24 (3 records match).",
     "For reading, every table can be sorted, searched and exported to CSV."),
    ("p3_update", 3, U, "split: phpMyAdmin | app", "App: Shelter > Edit the Dacope school: capacity 11 to 20; updates. phpMyAdmin: shelter shows 20; audit_log shows Capacity: 11 -> 20.",
     "Now an update: the school opens a second floor, so I raise its capacity from eleven to twenty. "
     "The table on the left shows twenty, and the audit log keeps the old and new value."),
    ("p3_check", 3, U, "split: phpMyAdmin | app", "App: Add family with size -2 is refused. phpMyAdmin SQL: UPDATE shelter SET Capacity = 0 fails with the CHECK constraint.",
     "Constraints are enforced too. A negative family size is refused, and on the left the same CHECK constraint stops a direct SQL "
     "update, so the data is protected even outside our application."),
    ("p3_trigger", 3, U, "split: phpMyAdmin | app", "phpMyAdmin SQL: INSERT a stay for the flood in Koyra shelter; trigger error: That shelter is not used for this disaster.",
     "And here a trigger inside MySQL rejects a stay in a shelter that is not registered for that disaster."),
    ("p3_restrict", 3, U, "split: phpMyAdmin | app", "App: sign out, sign in as admin; Shelter > Delete the Dacope school: the page lists the 2 stays and 2 distributions that block the deletion.",
     "Finally, delete. Only an admin may do this, so I sign in as the admin. A shelter that still has stays cannot be deleted: "
     "the page lists the records that block it. That is RESTRICT."),
    ("p3_cascade", 3, U, "split: phpMyAdmin | app", "phpMyAdmin SQL: warnings of Cyclone #3 = 1. App: Disaster > Delete the closed Cyclone #3 lists 4 dependent rows; Yes, delete. phpMyAdmin SQL again: 0.",
     "A closed cyclone is different. In MySQL, Cyclone number three has one warning. The page lists the records that will be deleted "
     "with it, and after I confirm, the same query on the left returns zero: that is CASCADE."),

    # ---------------------------------------------------------------- Part 3b: reports and multi-table queries
    ("p3_signals", 3, F, "full: app", "Live Report: warning signal panel; Cyclone #1 red, signal 10, with its areas.",
     "Now the reports. The Live Report reads many tables at once. A disaster's signal is its latest warning; an area takes the "
     "highest signal of the disasters affecting it. Our cyclone is at great danger signal ten."),
    ("p3_shelters", 3, F, "full: app", "Shelter panel: the Dacope school now green with 9 places left.",
     "Places left are capacity minus the family members staying now. After Urmy's update, the school shows nine places, calculated "
     "live; the page refreshes every five seconds."),
    ("p3_relief", 3, F, "full: app", "Relief tracker: Paikgachha and Dacope red Shortage; Koyra green.",
     "In the relief tracker, Paikgachha is short: no one reached and flagged by a coordinator. Dacope has just turned red, because the "
     "new family has not received relief yet, so only 73 percent are covered."),
    ("p3_path", 3, F, "full: app", "Path map: observed track, forecast line, impact circle; area lists: now, passed, forecast.",
     "The path map compares every area with every track point using the haversine distance: Dacope and Paikgachha are being crossed "
     "now, Koyra has been passed, and Sonadanga is in the forecast path."),
    ("p3_query", 3, F, "split: phpMyAdmin | app", "phpMyAdmin SQL tab: the six-table relief coverage query runs; the numbers match the panel on the right.",
     "Here is the query behind the relief tracker in phpMyAdmin. It joins six tables, and its numbers match the screen: fifteen people "
     "in Dacope, eleven reached."),
    ("p3_disaster", 3, F, "full: app", "Disaster report for Cyclone #1, then the Data Health page with all checks passed.",
     "The disaster report brings together areas, shelters, damage and relief for one incident, and Data Health rechecks every rule "
     "across the whole database. All seventeen checks pass."),

    # ---------------------------------------------------------------- Part 4: challenges and future scope
    ("p4_hurdle", 4, U, "full: phpMyAdmin", "shelter_assignment Structure > Indexes, then Browse: the two rows of Rahim Uddin (person 1), one finished stay and one open stay.",
     "One hurdle came from our own testing. At first, shelter_assignment had a unique key on disaster and person. It looked right, but a "
     "family that left one shelter could never be placed in another during the same cyclone, which is exactly what happens when an "
     "embankment breaks. Here, one family has two stays: one finished, one open."),
    ("p4_fix", 4, U, "full: phpMyAdmin", "SQL tab: inserting a second open stay for the same family fails with a duplicate-key error on uq_assignment_open_stay.",
     "We replaced it with the real rule, one open stay per family, using the functional index, and wrote an upgrade that changes "
     "existing databases without losing data. Here, MySQL refuses a second open stay for the same family. Concurrency was the second "
     "challenge: two coordinators saving the last places at once. We solved it with transactions and row locks, and a test proves it."),
    ("p4_future", 4, F, "full: app", "Dashboard, the cursor resting; then the map with the cyclone path.",
     "In the future we want SMS alerts when a signal reaches eight, cyclone tracks imported from the meteorological department, and "
     "matching each family's needs with the available relief. A small mobile app for field volunteers would also let them send "
     "updates from the shelters directly. Thank you for watching."),
]


def words(text: str) -> int:
    return len(text.split())


def seconds(beat) -> float:
    return round(words(beat[5]) / WPS + PAUSE, 1)


if __name__ == "__main__":
    tot = {F: 0.0, U: 0.0}
    part = {}
    for b in BEATS:
        s = seconds(b)
        tot[b[2]] += s
        part[b[1]] = part.get(b[1], 0.0) + s
    for p, s in sorted(part.items()):
        print(f"Part {p}: {s / 60:.2f} min ({s:.0f} s)")
    for who, s in tot.items():
        print(f"{who}: {s / 60:.2f} min")
    print(f"Total speaking: {sum(tot.values()) / 60:.2f} min, {len(BEATS)} beats")
