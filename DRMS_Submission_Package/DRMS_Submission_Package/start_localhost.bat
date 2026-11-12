@echo off
rem Starts MySQL and Apache (WampServer's own programs, no administrator rights needed) and opens
rem   http://localhost/disaster_and_relief_management/
rem Run it again at any time: programs that are already running are not started twice.
rem Add  /nobrowser  to start the servers without opening the browser.
rem
rem Needs: C:\wamp64\bin\apache\apache2.4.58\bin\vcruntime140.dll (a copy of the one in the PHP folder, see README.md,
rem        "Apache will not start"). Edit the versions below if your WampServer has other folders.
set WAMP=C:\wamp64
set MYSQLD=%WAMP%\bin\mysql\mysql8.2.0\bin\mysqld.exe
set MYINI=%WAMP%\bin\mysql\mysql8.2.0\my.ini
set APACHE=%WAMP%\bin\apache\apache2.4.58
set APP=%~dp0disaster_and_relief_management

rem make the project visible to Apache (a folder link, created once)
if not exist "%WAMP%\www\disaster_and_relief_management" mklink /J "%WAMP%\www\disaster_and_relief_management" "%APP%"

tasklist | find /i "mysqld.exe" >nul || start "" /min "%MYSQLD%" --defaults-file="%MYINI%"
tasklist | find /i "httpd.exe" >nul || start "" /min "%APACHE%\bin\httpd.exe" -d "%APACHE%"

echo Waiting for the servers...
timeout /t 8 /nobreak >nul
if /i not "%~1"=="/nobrowser" start http://localhost/disaster_and_relief_management/
echo Ready: http://localhost/disaster_and_relief_management/
