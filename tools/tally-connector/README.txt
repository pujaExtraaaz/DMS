DMS Tally Connector (Office Bridge)
===================================

START HERE: open SETUP-GUIDE.html in your browser
(double-click the file after unzipping).

This small app runs on the SAME Windows PC as Tally.
It downloads pending XML vouchers from DMS and posts them to local Tally.

Why you need it
---------------
DMS is hosted on the internet. Tally runs on your office PC.
The cloud server cannot talk to http://127.0.0.1:9000 on your PC.
This connector bridges that gap.

Requirements
------------
1. TallyPrime / Tally ERP 9 open with your company loaded
2. Tally HTTP / XML port enabled (often http://127.0.0.1:9000)
3. Node.js 18+  ->  https://nodejs.org/  (LTS)
4. Token from your DMS admin (TALLY_CONNECTOR_TOKEN)

Quick setup (Windows)
---------------------
1. Unzip this folder anywhere (e.g. C:\DMS-Tally-Connector)
2. Open SETUP-GUIDE.html for the full walkthrough
3. Double-click start.bat
4. On first run it opens config.json — fill in:
     "dms_url": "https://dms.extraaaz.com"
     "connector_token": "<token from admin>"
     "tally_url": "http://127.0.0.1:9000"
     "company": "<exact company name in Tally>"
5. Save config.json and run start.bat again
6. Keep the window open (or install scheduled task)

Optional: auto-start at Windows logon
-------------------------------------
Right-click PowerShell -> Run as Administrator:

  cd C:\DMS-Tally-Connector
  Set-ExecutionPolicy Bypass -Scope Process -Force
  .\install-windows-task.ps1

How to verify
-------------
1. Create/post an invoice in DMS
2. Open Tally Queue in DMS — status should stay Pending
3. Connector log should show: #123 invoice ... SENT
4. Queue status becomes Sent

Troubleshooting
---------------
- Unauthorized -> wrong connector_token
- Cannot reach DMS -> firewall / wrong dms_url
- Tally FAILED -> company not open, port wrong, or ledger/stock names mismatch
- Items Skipped -> set TALLY_USE_CONNECTOR=true on server and retry queue rows

More help
---------
https://dms.extraaaz.com/docs/CLIENT_REQUIREMENTS.html#tally-connector
https://dms.extraaaz.com/tally/queue
