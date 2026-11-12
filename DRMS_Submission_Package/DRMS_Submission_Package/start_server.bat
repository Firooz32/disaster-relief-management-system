@echo off
rem Starts MySQL (WampServer's, no admin needed) and PHP's built-in server, then opens the system in the browser.
rem router.php keeps config.php, the SQL dump, README files and the inc/ and tests/ folders private (php -S ignores .htaccess).
set "MYSQLD=C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqld.exe"
set "MYSQLADMIN=C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqladmin.exe"
set "MYINI=C:\wamp64\bin\mysql\mysql8.2.0\my.ini"
set "PHPEXE=C:\wamp64\bin\php\php8.2.13\php.exe"
set "APPDIR=%~dp0disaster_and_relief_management"
if not exist "%MYSQLD%" (echo MySQL was not found: "%MYSQLD%"& exit /b 1)
if not exist "%MYSQLADMIN%" (echo mysqladmin was not found: "%MYSQLADMIN%"& exit /b 1)
if not exist "%MYINI%" (echo MySQL configuration was not found: "%MYINI%"& exit /b 1)
if not exist "%PHPEXE%" (echo PHP was not found: "%PHPEXE%"& exit /b 1)
if not exist "%APPDIR%\router.php" (echo Application folder was not found: "%APPDIR%"& exit /b 1)
tasklist | find /i "mysqld.exe" >nul || start "" /min "%MYSQLD%" --defaults-file="%MYINI%"
:wait_mysql
"%MYSQLADMIN%" --connect-timeout=1 --host=127.0.0.1 --port=3306 --user=root ping >nul 2>&1
if not errorlevel 1 goto mysql_ready
set /a MYSQL_WAIT+=1
if %MYSQL_WAIT% geq 30 goto mysql_failed
timeout /t 2 /nobreak >nul
goto wait_mysql
:mysql_failed
echo MySQL did not become ready on 127.0.0.1:3306. Start WampServer, check its configuration, and try again.
exit /b 1
:mysql_ready
echo MySQL is ready.
start http://127.0.0.1:8099/index.php
"%PHPEXE%" -S 127.0.0.1:8099 -t "%APPDIR%" "%APPDIR%\router.php"
