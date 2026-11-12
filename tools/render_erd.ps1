# Renders submission/diagrams/ERD_*.html (made by tools/make_erd.php) to PNG and to an A3-landscape PDF with headless Edge.
#   powershell -ExecutionPolicy Bypass -File tools\render_erd.ps1
# Needs internet once per run (Mermaid 11.6.0 + ELK layout from cdn.jsdelivr.net).
$edge = 'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
$dir  = (Resolve-Path (Join-Path $PSScriptRoot '..\submission\diagrams')).Path
$prof = Join-Path $env:TEMP 'drms_erd_edge'
$base = 'file:///' + ($dir -replace '\\', '/')

foreach ($name in 'ERD_Overview', 'ERD_Full') {
    # 1. measure the drawn SVG, 2. screenshot at that size (plus the title), 3. wrap the PNG in an A3 page and print it
    $dom = & $edge --headless=new --disable-gpu --no-first-run "--user-data-dir=$prof" --virtual-time-budget=40000 --dump-dom "$base/$name.html" 2>$null | Out-String
    $vb  = [regex]::Match($dom, 'viewBox="[\d.]+ [\d.]+ ([\d.]+) ([\d.]+)"')
    if (-not $vb.Success) { throw "Mermaid did not render $name (offline?)" }
    $w = [int][math]::Ceiling([double]$vb.Groups[1].Value) + 60
    $h = [int][math]::Ceiling([double]$vb.Groups[2].Value) + 140
    & $edge --headless=new --disable-gpu --no-first-run "--user-data-dir=$prof" --hide-scrollbars --virtual-time-budget=40000 "--window-size=$w,$h" "--screenshot=$dir\$name.png" "$base/$name.html" 2>$null | Out-Null
    $wrap = "$dir\$name.print.html"
    "<!DOCTYPE html><html><head><meta charset='utf-8'><style>@page{size:A3 landscape;margin:8mm}html,body{margin:0}img{width:100%;height:auto}</style></head><body><img src='$name.png'></body></html>" |
        Out-File -Encoding utf8 $wrap
    & $edge --headless=new --disable-gpu --no-first-run "--user-data-dir=$prof" --no-pdf-header-footer "--print-to-pdf=$dir\$name.pdf" "$base/$name.print.html" 2>$null | Out-Null
    Start-Sleep -Seconds 2
    Remove-Item $wrap
    Write-Output ("{0}: {1} x {2} px, PDF {3:N0} KB" -f $name, $w, $h, ((Get-Item "$dir\$name.pdf").Length / 1KB))
}
Remove-Item "$dir\*.dom.html" -ErrorAction SilentlyContinue
