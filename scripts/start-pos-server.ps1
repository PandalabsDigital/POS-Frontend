param(
    [int] $Port = 8000
)

$listen = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
if ($listen) {
    exit 0
}

Set-Location (Split-Path $PSScriptRoot -Parent)
$php = 'C:\php83\php.exe'

while ($true) {
    & $php artisan serve --host=127.0.0.1 --port=$Port
    Start-Sleep -Seconds 5
}
