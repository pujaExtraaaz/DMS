@echo off
title DMS Tally Connector
cd /d "%~dp0"

if not exist "config.json" (
  echo Creating config.json from example...
  copy /Y config.example.json config.json >nul
  echo.
  echo ========================================
  echo  FIRST-TIME SETUP
  echo  1. Open SETUP-GUIDE.html for full steps
  echo  2. Edit config.json (opens next)
  echo ========================================
  echo.
  if exist SETUP-GUIDE.html start "" SETUP-GUIDE.html
  notepad config.json
  pause
)

where node >nul 2>nul
if errorlevel 1 (
  echo Node.js 18+ is required.
  echo Download: https://nodejs.org/
  pause
  exit /b 1
)

echo Starting connector... Keep this window open while Tally is running.
node connector.mjs
pause
