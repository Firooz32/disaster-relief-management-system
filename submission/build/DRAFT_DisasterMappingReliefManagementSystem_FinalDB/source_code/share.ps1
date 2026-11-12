[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [ValidateRange(1, 65535)]
    [int] $Port = 80
)

$ErrorActionPreference = 'Stop'

$cloudflared = Get-Command cloudflared -ErrorAction SilentlyContinue
if (-not $cloudflared) {
    $installedPath = @(
        [Environment]::GetEnvironmentVariable('Path', 'Machine')
        [Environment]::GetEnvironmentVariable('Path', 'User')
        $env:Path
    ) | Where-Object { $_ }
    $env:Path = $installedPath -join ';'
    $cloudflared = Get-Command cloudflared -ErrorAction SilentlyContinue
}

if (-not $cloudflared) {
    throw 'cloudflared was not found. Install it with winget install --id Cloudflare.cloudflared, then open a new PowerShell terminal.'
}

& $cloudflared.Source tunnel --url "http://127.0.0.1:$Port"
