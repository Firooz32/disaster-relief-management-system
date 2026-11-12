# Recreates the video-demo database `drms_demo` from submission/database/schema.sql + sample_data.sql and starts the app on it.
#   powershell -ExecutionPolicy Bypass -File tools\reset_demo.ps1            (reset + start http://127.0.0.1:8099/)
#   powershell -ExecutionPolicy Bypass -File tools\reset_demo.ps1 -NoStart   (reset only)
# Run it before every rehearsal and every take, so each recording starts from exactly the same data.
# It only ever touches `drms_demo`; the real database `disaster_and_relief_management` is never opened.
param([switch]$NoStart)

$demo  = 'drms_demo'
$bin   = 'C:\wamp64\bin\mysql\mysql8.2.0\bin'
$php   = 'C:\wamp64\bin\php\php8.2.13\php.exe'
$root  = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$app   = Join-Path $root 'disaster_and_relief_management'
if ($demo -eq 'disaster_and_relief_management') { throw 'Refusing to reset the real database.' }

if (-not (Get-Process mysqld -ErrorAction SilentlyContinue)) {
    Start-Process -WindowStyle Minimized "$bin\mysqld.exe" -ArgumentList "--defaults-file=`"$bin\..\my.ini`""
    Start-Sleep -Seconds 8
}
& "$bin\mysql.exe" -uroot -e "DROP DATABASE IF EXISTS ``$demo``"
foreach ($f in 'schema.sql', 'sample_data.sql') {
    $sql = (Get-Content -Raw -Encoding UTF8 (Join-Path $root "submission\database\$f")) -replace '`disaster_and_relief_management`', "``$demo``"
    $sql | & "$bin\mysql.exe" -uroot --default-character-set=utf8mb4
    if ($LASTEXITCODE -ne 0) { throw "$f failed to load" }
}
& "$bin\mysql.exe" -uroot -N -e "SELECT CONCAT('drms_demo ready: ', COUNT(*), ' tables') FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$demo'"

if (-not $NoStart) {
    Get-CimInstance Win32_Process -Filter "Name = 'php.exe'" | Where-Object { $_.CommandLine -like '*127.0.0.1:8099*' } |
        ForEach-Object { Stop-Process -Id $_.ProcessId -Force }
    $env:DRMS_DB_NAME = $demo
    Start-Process -WindowStyle Minimized $php -ArgumentList '-S', '127.0.0.1:8099', '-t', "`"$app`"", "`"$app\router.php`""
    Write-Output 'App: http://127.0.0.1:8099/  (demo_admin / DemoAdmin2026, demo_editor / DemoEditor2026, demo_viewer / DemoViewer2026)'
    Write-Output "phpMyAdmin: open the database '$demo' (WampServer: http://localhost/phpmyadmin/, user root)"
}
