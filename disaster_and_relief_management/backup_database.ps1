[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('Backup', 'Restore')]
    [string] $Mode,

    [string] $BackupPath,

    [string] $BackupDirectory,

    [string] $WampRoot = 'C:\wamp64',

    [string] $MysqlBinDir,

    [string] $MysqlUser,

    [ValidatePattern('^[A-Za-z0-9_.-]{1,64}$')]
    [string] $LoginPath
)

$ErrorActionPreference = 'Stop'
$configPath = Join-Path $PSScriptRoot 'config.php'
if (-not (Test-Path -LiteralPath $configPath -PathType Leaf)) {
    throw 'config.php was not found. Copy config.sample.php to config.php and configure the database first.'
}

$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    $php = Get-ChildItem -LiteralPath (Join-Path $WampRoot 'bin\php') -Filter php.exe -Recurse -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending | Select-Object -First 1
}
if (-not $php) {
    throw 'PHP CLI was not found. Add PHP to PATH or pass the correct WampRoot.'
}
$phpExe = if ($php.Source) { $php.Source } else { $php.FullName }

$configJson = & $phpExe -r '$c = require $argv[1]; echo json_encode($c, JSON_THROW_ON_ERROR);' $configPath
if ($LASTEXITCODE -ne 0) {
    throw 'PHP could not read the database configuration.'
}
$config = $configJson | ConvertFrom-Json

if (-not $config.host -or -not $config.user -or -not $config.name -or -not $config.port) {
    throw 'The database configuration must include host, user, name, and port.'
}
$databaseUser = if ($MysqlUser) { $MysqlUser } else { [string] $config.user }

if (-not $MysqlBinDir) {
    $mysqlRoot = Join-Path $WampRoot 'bin\mysql'
    $mysqlTool = Get-ChildItem -LiteralPath $mysqlRoot -Filter mysql.exe -Recurse -ErrorAction SilentlyContinue |
        Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if (-not $mysqlTool) {
        throw 'MySQL CLI was not found. Pass -MysqlBinDir with the folder containing mysql.exe and mysqldump.exe.'
    }
    $MysqlBinDir = Split-Path -Parent $mysqlTool.FullName
}

$mysql = Join-Path $MysqlBinDir 'mysql.exe'
$dump = Join-Path $MysqlBinDir 'mysqldump.exe'
if (-not (Test-Path -LiteralPath $mysql -PathType Leaf) -or -not (Test-Path -LiteralPath $dump -PathType Leaf)) {
    throw "mysql.exe and mysqldump.exe must both exist in $MysqlBinDir."
}

if ($Mode -eq 'Backup') {
    if (-not $BackupPath) {
        $backupDir = if ($BackupDirectory) {
            [IO.Path]::GetFullPath($BackupDirectory)
        } else {
            Join-Path ([Environment]::GetFolderPath('MyDocuments')) 'DRMS-Backups'
        }
        $stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
        $BackupPath = Join-Path $backupDir "$($config.name)-$stamp.sql"
    }
    $BackupPath = [IO.Path]::GetFullPath($BackupPath)
    if (Test-Path -LiteralPath $BackupPath) {
        throw "Backup already exists and will not be overwritten: $BackupPath"
    }
    $backupDir = Split-Path -Parent $BackupPath
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
} else {
    if (-not $BackupPath -or -not (Test-Path -LiteralPath $BackupPath -PathType Leaf)) {
        throw 'Restore mode requires -BackupPath pointing to an existing SQL backup file.'
    }
    $BackupPath = (Resolve-Path -LiteralPath $BackupPath).Path
    if ((Get-Item -LiteralPath $BackupPath).Length -eq 0) {
        throw 'The selected backup file is empty.'
    }
    $confirmation = Read-Host "This replaces tables in '$($config.name)'. Type RESTORE $($config.name) to continue"
    if ($confirmation -cne "RESTORE $($config.name)") {
        throw 'Restore cancelled; the database was not changed.'
    }
}

$passwordPtr = [IntPtr]::Zero
$password = $null
$optionPath = $null
$mysqlAuthArgs = @()
if ($LoginPath) {
    $mysqlLoginEditor = Join-Path $MysqlBinDir 'mysql_config_editor.exe'
    if (-not (Test-Path -LiteralPath $mysqlLoginEditor -PathType Leaf)) {
        throw "mysql_config_editor.exe was not found in $MysqlBinDir; omit -LoginPath to use an interactive password prompt."
    }
    $mysqlAuthArgs = @("--login-path=$LoginPath")
} else {
    $passwordSecure = Read-Host "MySQL password for $databaseUser@$($config.host)" -AsSecureString
    $passwordPtr = [Runtime.InteropServices.Marshal]::SecureStringToGlobalAllocUnicode($passwordSecure)
    $password = [Runtime.InteropServices.Marshal]::PtrToStringUni($passwordPtr)
    $optionPath = Join-Path ([IO.Path]::GetTempPath()) ('drms-mysql-' + [guid]::NewGuid().ToString('N') + '.cnf')
    $mysqlAuthArgs = @("--defaults-extra-file=$optionPath")
}

try {
    if (-not $LoginPath) {
        New-Item -ItemType File -Path $optionPath | Out-Null
        $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
        $acl = New-Object Security.AccessControl.FileSecurity
        $acl.SetAccessRuleProtection($true, $false)
        $acl.SetOwner($identity.User)
        $rule = New-Object Security.AccessControl.FileSystemAccessRule(
            $identity.User,
            [Security.AccessControl.FileSystemRights]::FullControl,
            [Security.AccessControl.AccessControlType]::Allow
        )
        $acl.AddAccessRule($rule)
        Set-Acl -LiteralPath $optionPath -AclObject $acl

        $escapeOption = {
            param([string] $Value)
            '"' + $Value.Replace('\', '\\').Replace('"', '\"').Replace("`r", '\r').Replace("`n", '\n').Replace("`t", '\t') + '"'
        }
        $optionFile = @(
            '[client]'
            "host=$(& $escapeOption ([string] $config.host))"
            "port=$([int] $config.port)"
            "user=$(& $escapeOption $databaseUser)"
            "password=$(& $escapeOption $password)"
        ) -join "`n"
        $utf8NoBom = New-Object System.Text.UTF8Encoding -ArgumentList $false
        [IO.File]::WriteAllText($optionPath, $optionFile, $utf8NoBom)
    }

    if ($Mode -eq 'Backup') {
        & $dump @mysqlAuthArgs '--single-transaction' '--routines' '--triggers' '--events' '--hex-blob' '--default-character-set=utf8mb4' "--result-file=$BackupPath" ([string] $config.name)
        if ($LASTEXITCODE -ne 0) {
            Remove-Item -LiteralPath $BackupPath -Force -ErrorAction SilentlyContinue
            throw "mysqldump failed with exit code $LASTEXITCODE."
        }
        if (-not (Test-Path -LiteralPath $BackupPath -PathType Leaf) -or (Get-Item -LiteralPath $BackupPath).Length -eq 0) {
            Remove-Item -LiteralPath $BackupPath -Force -ErrorAction SilentlyContinue
            throw 'mysqldump did not create a non-empty backup.'
        }
        Write-Output "Backup created: $BackupPath"
    } else {
        $authArgument = if ($LoginPath) {
            '--login-path="' + $LoginPath + '"'
        } else {
            '--defaults-extra-file="' + $optionPath.Replace('"', '\"') + '"'
        }
        $quotedDatabase = '"' + ([string] $config.name).Replace('"', '\"') + '"'
        $start = New-Object Diagnostics.ProcessStartInfo
        $start.FileName = $mysql
        $start.Arguments = "$authArgument --default-character-set=utf8mb4 --database=$quotedDatabase"
        $start.UseShellExecute = $false
        $start.RedirectStandardInput = $true
        $start.RedirectStandardOutput = $true
        $start.RedirectStandardError = $true
        $process = New-Object Diagnostics.Process
        $process.StartInfo = $start
        [void] $process.Start()
        $stdoutTask = $process.StandardOutput.ReadToEndAsync()
        $stderrTask = $process.StandardError.ReadToEndAsync()
        $input = [IO.File]::OpenRead($BackupPath)
        try {
            $input.CopyTo($process.StandardInput.BaseStream)
        } finally {
            $input.Dispose()
            $process.StandardInput.Close()
        }
        $process.WaitForExit()
        $stdout = $stdoutTask.Result
        $stderr = $stderrTask.Result
        if ($process.ExitCode -ne 0) {
            if ($stderr) { Write-Error $stderr }
            throw "mysql restore failed with exit code $($process.ExitCode)."
        }
        if ($stdout) { Write-Output $stdout }
        Write-Output "Restore completed from: $BackupPath"
    }
} finally {
    if ($passwordPtr -ne [IntPtr]::Zero) {
        [Runtime.InteropServices.Marshal]::ZeroFreeGlobalAllocUnicode($passwordPtr)
    }
    $password = $null
    if ($optionPath) {
        Remove-Item -LiteralPath $optionPath -Force -ErrorAction SilentlyContinue
    }
}
