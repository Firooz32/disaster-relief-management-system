DEPLOY TO INFINITYFREE (about 10 minutes)
1. infinityfree.com -> Sign Up -> Create Account -> choose a subdomain (e.g. drms-demo.infinityfreeapp.com).
2. Client Area -> Control Panel -> MySQL Databases -> create a database named "drms". Note: host (sqlXXX.infinityfree.com), username (if0_...), password, full DB name (if0_..._drms).
3. Edit htdocs/config.php: replace the 4 placeholders with those values.
4. Control Panel -> phpMyAdmin -> open that database -> Import -> choose import_this_in_phpmyadmin.sql -> Go. (Creates all tables, triggers, sample data.)
5. Control Panel -> Online File Manager (or FTP) -> open the "htdocs" folder of your site, delete its default files, upload everything INSIDE this package's htdocs folder (index.php must sit directly in htdocs).
6. Open https://<your-subdomain>.infinityfreeapp.com/ . Wait up to a few minutes after signup if the site shows a DNS error.
Known limits: free hosting may block outgoing requests (weather feed) and shows an anti-bot page on first visit.
