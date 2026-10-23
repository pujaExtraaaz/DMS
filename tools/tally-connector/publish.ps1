$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
dotnet publish (Join-Path $root "tally_connector.csproj") -c Release -o (Join-Path $root "publish")
Copy-Item (Join-Path $root "config.example.json") (Join-Path $root "publish\config.example.json") -Force
Copy-Item (Join-Path $root "install-task.ps1") (Join-Path $root "publish\install-task.ps1") -Force
Write-Output "Built publish\tally_connector.exe"