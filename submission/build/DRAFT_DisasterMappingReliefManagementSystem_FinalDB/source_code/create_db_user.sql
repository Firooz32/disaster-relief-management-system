-- Run once as MySQL root (phpMyAdmin > SQL) AFTER the app has created / upgraded the database (open index.php once as root).
-- The web app then no longer needs root: it can only read/write rows in its own database.
-- The integrity triggers still fire for this user (running a trigger needs no extra right).
-- It cannot create or alter tables, so a future upgrade of the database needs root in config.php again (just once).
-- Change the password, then put the same user/password in config.php.
CREATE USER IF NOT EXISTS 'drms_app'@'localhost' IDENTIFIED BY 'change-me';
GRANT SELECT, INSERT, UPDATE, DELETE ON `disaster_and_relief_management`.* TO 'drms_app'@'localhost';
FLUSH PRIVILEGES;
