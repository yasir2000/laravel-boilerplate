@echo off
REM Windows Task Scheduler Setup for Automated Backups
REM This script creates a Windows scheduled task for daily backups

echo Setting up automated backup task...

REM Create a scheduled task to run backup daily at 2:00 AM
schtasks /create /tn "ERP Integration Daily Backup" /tr "%CD%\manage-backups.sh backup" /sc daily /st 02:00 /f

if %ERRORLEVEL% EQU 0 (
    echo ✅ Daily backup task scheduled successfully at 2:00 AM
) else (
    echo ❌ Failed to schedule backup task
)

REM Create a weekly backup verification task
schtasks /create /tn "ERP Integration Backup Monitor" /tr "%CD%\backups\scripts\monitor-backups.sh" /sc weekly /d MON /st 09:00 /f

if %ERRORLEVEL% EQU 0 (
    echo ✅ Weekly backup monitoring task scheduled for Mondays at 9:00 AM
) else (
    echo ❌ Failed to schedule monitoring task
)

echo.
echo 📋 Scheduled Tasks Summary:
echo - Daily backup: 2:00 AM daily
echo - Backup monitoring: 9:00 AM every Monday
echo.
echo To view scheduled tasks: schtasks /query /tn "ERP Integration*"
echo To delete tasks: schtasks /delete /tn "task_name"
echo.
pause