@echo off
REM Schedule system monitoring task
schtasks /create /tn "ERP Integration Health Check" /tr "%CD%\monitor-system.sh check" /sc minute /mo 15 /f
echo Health monitoring scheduled every 15 minutes
pause
