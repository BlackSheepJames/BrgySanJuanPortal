$ErrorActionPreference = 'Stop'
$mysqlBin = 'C:\xampp\mysql\bin'
$backupDir = 'C:\xampp\backups\brgySanJuan'
$database = 'brgy_sanjuan'
$user = 'root'
$password = ''

if (-not (Test-Path -LiteralPath (Join-Path $mysqlBin 'mysqldump.exe'))) { throw "mysqldump.exe was not found in $mysqlBin. Update `$mysqlBin for your XAMPP installation." }
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$output = Join-Path $backupDir "$database-$stamp.sql"
$arguments = @('--single-transaction','--routines','--triggers','--default-character-set=utf8mb4','--user',$user)
if ($password) { $arguments += "--password=$password" }
$arguments += @('--result-file', $output, $database)
& (Join-Path $mysqlBin 'mysqldump.exe') @arguments
if ($LASTEXITCODE -ne 0) { Remove-Item -LiteralPath $output -ErrorAction SilentlyContinue; throw "Database backup failed with exit code $LASTEXITCODE." }
Write-Output "Backup created: $output"
