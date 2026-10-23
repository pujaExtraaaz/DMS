$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
$out = Join-Path $root "publish"
dotnet publish (Join-Path $root "tally_connector.csproj") -c Release -o $out
Copy-Item (Join-Path $root "config.example.json") (Join-Path $out "config.example.json") -Force
Copy-Item (Join-Path $root "install-task.ps1") (Join-Path $out "install-task.ps1") -Force
Remove-Item (Join-Path $out "tally_connector.pdb") -Force -ErrorAction SilentlyContinue
Write-Output "Built publish\tally_connector.exe"