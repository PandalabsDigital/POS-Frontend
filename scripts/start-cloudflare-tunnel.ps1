$root = Split-Path $PSScriptRoot -Parent
$logDir = Join-Path $root 'storage\logs'
New-Item -ItemType Directory -Force -Path $logDir | Out-Null
$log = Join-Path $logDir 'cloudflare-tunnel.log'
$urlFile = Join-Path $logDir 'public-url.txt'
$cloudflared = 'C:\php83\cloudflared.exe'

if (Get-Process cloudflared -ErrorAction SilentlyContinue) {
    exit 0
}

$deadline = (Get-Date).AddMinutes(2)
do {
    $up = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
    if ($up) { break }
    Start-Sleep -Seconds 2
} while ((Get-Date) -lt $deadline)

while ($true) {
    $proc = Start-Process -FilePath $cloudflared -ArgumentList @('tunnel', '--url', 'http://127.0.0.1:8000') -NoNewWindow -RedirectStandardOutput $log -RedirectStandardError $log -PassThru
    while (-not $proc.HasExited) {
        if (Test-Path $log) {
            $match = Select-String -Path $log -Pattern 'https://[a-z0-9-]+\.trycloudflare\.com' -ErrorAction SilentlyContinue | Select-Object -Last 1
            if ($match) {
                $url = [regex]::Match($match.Line, 'https://[a-z0-9-]+\.trycloudflare\.com').Value
                if ($url) {
                    Set-Content -Path $urlFile -Value $url -Encoding utf8
                }
            }
        }
        Start-Sleep -Seconds 3
    }
    Start-Sleep -Seconds 5
}
