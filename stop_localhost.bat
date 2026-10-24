@echo off
rem Stops Apache and MySQL that were started with start_localhost.bat (MySQL is shut down cleanly).
taskkill /im httpd.exe /f >nul 2>&1
"C:\wamp64\bin\mysql\mysql8.2.0\bin\mysqladmin.exe" -uroot -h127.0.0.1 shutdown >nul 2>&1
echo Apache and MySQL stopped.
