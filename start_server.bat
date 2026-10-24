@echo off
rem Starts MySQL (WampServer's, no admin needed) and PHP's built-in server, then opens the system in the browser.
rem router.php keeps config.php, the SQL dump, README files and the inc/ and tests/ folders private (php -S ignores .htaccess).
set MYSQLD=C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqld.exe
set MYINI=C:\wamp64\bin\mysql\mysql8.2.0\my.ini
set PHPEXE=C:\wamp64\bin\php\php8.2.13\php.exe
set APPDIR=%~dp0disaster_and_relief_management
tasklist | find /i "mysqld.exe" >nul || start "" /min "%MYSQLD%" --defaults-file="%MYINI%"
timeout /t 8 /nobreak >nul
start http://127.0.0.1:8099/index.php
"%PHPEXE%" -S 127.0.0.1:8099 -t "%APPDIR%" "%APPDIR%\router.php"
