$exe = Join-Path $PSScriptRoot "tally_connector.exe"
if (-not (Test-Path $exe)) {
    Write-Error "tally_connector.exe was not found next to this script."
    exit 1
}
schtasks /Create /TN "DMS Tally Connector" /SC ONLOGON /RL LIMITED /F /TR "`"$exe`""
Write-Output "Logon task DMS Tally Connector is registered."