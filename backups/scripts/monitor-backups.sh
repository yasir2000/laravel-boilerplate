#!/bin/bash
# Backup Monitoring Script

set -e

BACKUP_DIR="./backups"
LOG_DIR="$BACKUP_DIR/logs"

# Check last backup status
check_last_backup() {
    local last_log=$(ls -t "$LOG_DIR"/backup_*.log 2>/dev/null | head -1)
    
    if [ -n "$last_log" ]; then
        echo "Last backup log: $(basename "$last_log")"
        
        if grep -q "SUCCESS" "$last_log"; then
            echo "✅ Last backup completed successfully"
        elif grep -q "ERROR" "$last_log"; then
            echo "❌ Last backup failed"
            echo "Error details:"
            grep "ERROR" "$last_log" | tail -5
        else
            echo "⚠️  Last backup status unclear"
        fi
    else
        echo "⚠️  No backup logs found"
    fi
}

# Check backup age
check_backup_age() {
    local latest_backup=$(find "$BACKUP_DIR" -name "*backup_*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -nr | head -1 | cut -d' ' -f2-)
    
    if [ -n "$latest_backup" ]; then
        local backup_age=$(( $(date +%s) - $(stat -c %Y "$latest_backup" 2>/dev/null || stat -f %m "$latest_backup") ))
        local hours_old=$(( backup_age / 3600 ))
        
        echo "Latest backup: $(basename "$latest_backup")"
        echo "Age: ${hours_old} hours"
        
        if [ $hours_old -gt 48 ]; then
            echo "⚠️  Warning: Latest backup is over 48 hours old"
        elif [ $hours_old -gt 24 ]; then
            echo "⚠️  Warning: Latest backup is over 24 hours old"
        else
            echo "✅ Backup age is acceptable"
        fi
    else
        echo "❌ No backups found"
    fi
}

# Check storage usage
check_storage() {
    echo "Backup storage usage:"
    du -sh "$BACKUP_DIR"/*/ 2>/dev/null || echo "No backup directories found"
    
    # Check disk space
    local available=$(df "$(pwd)" | awk 'NR==2 {print $4}')
    local total=$(df "$(pwd)" | awk 'NR==2 {print $2}')
    local usage_percent=$(( (total - available) * 100 / total ))
    
    echo "Disk usage: ${usage_percent}%"
    
    if [ $usage_percent -gt 90 ]; then
        echo "⚠️  Warning: Disk usage is over 90%"
    fi
}

echo "💾 Backup System Monitoring Report"
echo "================================="
echo ""

check_last_backup
echo ""

check_backup_age
echo ""

check_storage
