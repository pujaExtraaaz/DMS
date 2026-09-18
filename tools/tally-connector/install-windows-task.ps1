# Install DMS Tally Connector as a Windows Scheduled Task (runs at logon)
# Run in PowerShell as Administrator:
#   Set-ExecutionPolicy Bypass -Scope Process -Force
#   .\install-windows-task.ps1

$ErrorActionPreference = 'Stop'
$here = Split-Path -Parent $MyInvocation.MyCommand.Path
$bat = Join-Path $here 'start.bat'

if (-not (Test-Path $bat)) {
  throw "start.bat not found in $here"
}

$action = New-ScheduledTaskAction -Execute $bat -WorkingDirectory $here
$trigger = New-ScheduledTaskTrigger -AtLogOn
$principal = New-ScheduledTaskPrincipal -UserId $env:USERNAME -LogonType Interactive -RunLevel Limited
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)

Register-ScheduledTask -TaskName 'DMS-Tally-Connector' -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force | Out-Null
Write-Host "Scheduled task 'DMS-Tally-Connector' installed. It will start at logon."
Write-Host "You can also run start.bat manually anytime."
