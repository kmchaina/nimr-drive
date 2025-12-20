@echo off
REM Mount NIMR Storage share at startup
REM Set up as a Scheduled Task that runs at system startup with highest privileges

REM Disconnect any existing connection first (ignore errors)
net use \\10.0.10.6\nimr-storage /delete /y >nul 2>&1

REM Connect with service account credentials
net use \\10.0.10.6\nimr-storage /user:NIMRHQS\svc-nimrdrive Nimr@2026 /persistent:yes >nul 2>&1

exit /b 0
