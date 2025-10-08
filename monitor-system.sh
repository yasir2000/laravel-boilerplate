#!/bin/bash
# System Health Monitoring Script
# Monitors ERP integration system health and sends alerts

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
LOG_FILE="./monitoring/health-check.log"
ALERT_EMAIL="admin@yourcompany.com"
THRESHOLD_CPU=80
THRESHOLD_MEMORY=80
THRESHOLD_DISK=90

# Create monitoring directory
mkdir -p monitoring

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

# Health check functions
check_services() {
    log "Checking service health..."
    
    local status=0
    
    # Check if integration service is running
    if docker ps --format "table {{.Names}}" | grep -q "integration-service"; then
        log "✅ Integration service is running"
    else
        log "❌ Integration service is not running"
        status=1
    fi
    
    # Check if database is accessible
    if docker ps --format "table {{.Names}}" | grep -q "postgres\|mysql\|mongodb"; then
        log "✅ Database service is running"
    else
        log "❌ Database service is not running"
        status=1
    fi
    
    # Check if Redis is running
    if docker ps --format "table {{.Names}}" | grep -q "redis"; then
        log "✅ Redis service is running"
    else
        log "⚠️  Redis service is not running"
    fi
    
    return $status
}

check_connectivity() {
    log "Checking external connectivity..."
    
    local status=0
    
    # Check ERP system connectivity
    if curl -s --max-time 10 "https://httpbin.org/status/200" >/dev/null; then
        log "✅ External connectivity is working"
    else
        log "❌ External connectivity failed"
        status=1
    fi
    
    return $status
}

check_ssl_certificates() {
    log "Checking SSL certificates..."
    
    local cert_file="microservices/ssl/certs/erp-integration.crt"
    
    if [ -f "$cert_file" ]; then
        local expiry_date=$(openssl x509 -in "$cert_file" -noout -enddate | cut -d= -f2)
        local expiry_epoch=$(date -d "$expiry_date" +%s 2>/dev/null || echo 0)
        local current_epoch=$(date +%s)
        local days_until_expiry=$(( (expiry_epoch - current_epoch) / 86400 ))
        
        if [ $days_until_expiry -gt 30 ]; then
            log "✅ SSL certificate valid for $days_until_expiry days"
        elif [ $days_until_expiry -gt 7 ]; then
            log "⚠️  SSL certificate expires in $days_until_expiry days"
        else
            log "❌ SSL certificate expires in $days_until_expiry days - URGENT"
            return 1
        fi
    else
        log "❌ SSL certificate file not found"
        return 1
    fi
    
    return 0
}

check_disk_space() {
    log "Checking disk space..."
    
    local disk_usage=$(df . | awk 'NR==2 {print $5}' | sed 's/%//')
    
    if [ "$disk_usage" -lt $THRESHOLD_DISK ]; then
        log "✅ Disk usage: ${disk_usage}%"
        return 0
    else
        log "❌ Disk usage critical: ${disk_usage}%"
        return 1
    fi
}

check_backup_status() {
    log "Checking backup status..."
    
    local latest_backup=$(find ./backups -name "*backup_*" -type f -printf '%T@ %p\n' 2>/dev/null | sort -nr | head -1 | cut -d' ' -f2-)
    
    if [ -n "$latest_backup" ]; then
        local backup_age=$(( $(date +%s) - $(stat -c %Y "$latest_backup" 2>/dev/null || stat -f %m "$latest_backup") ))
        local hours_old=$(( backup_age / 3600 ))
        
        if [ $hours_old -lt 48 ]; then
            log "✅ Latest backup is $hours_old hours old"
            return 0
        else
            log "❌ Latest backup is $hours_old hours old - too old"
            return 1
        fi
    else
        log "❌ No backups found"
        return 1
    fi
}

check_integration_api() {
    log "Checking integration API health..."
    
    # Check if the integration service responds
    if curl -s --max-time 10 "http://localhost:8083/health" >/dev/null 2>&1; then
        log "✅ Integration API is responding"
        return 0
    else
        log "⚠️  Integration API is not responding (service may not be running)"
        return 0  # Not critical if service isn't started yet
    fi
}

send_alert() {
    local message="$1"
    local severity="$2"
    
    log "Sending alert: $message"
    
    # Try to send email if mail command is available
    if command -v mail >/dev/null 2>&1; then
        echo "$message" | mail -s "ERP Integration Alert - $severity" "$ALERT_EMAIL"
    fi
    
    # Log the alert
    echo "$(date '+%Y-%m-%d %H:%M:%S') - ALERT [$severity]: $message" >> "./monitoring/alerts.log"
}

# Main health check function
main_health_check() {
    log "🔍 Starting system health check..."
    
    local overall_status=0
    local issues=()
    
    # Run all checks
    if ! check_services; then
        issues+=("Service health issues detected")
        overall_status=1
    fi
    
    if ! check_connectivity; then
        issues+=("Connectivity issues detected")
        overall_status=1
    fi
    
    if ! check_ssl_certificates; then
        issues+=("SSL certificate issues detected")
        overall_status=1
    fi
    
    if ! check_disk_space; then
        issues+=("Disk space issues detected")
        overall_status=1
    fi
    
    if ! check_backup_status; then
        issues+=("Backup issues detected")
        overall_status=1
    fi
    
    check_integration_api  # Non-critical check
    
    # Report overall status
    if [ $overall_status -eq 0 ]; then
        log "✅ All health checks passed"
        echo "$(date '+%Y-%m-%d %H:%M:%S') - HEALTHY" >> "./monitoring/status.log"
    else
        local issue_summary=$(IFS=', '; echo "${issues[*]}")
        log "❌ Health check failed: $issue_summary"
        echo "$(date '+%Y-%m-%d %H:%M:%S') - UNHEALTHY: $issue_summary" >> "./monitoring/status.log"
        send_alert "$issue_summary" "CRITICAL"
    fi
    
    log "🔍 Health check completed"
    return $overall_status
}

# Generate monitoring report
generate_report() {
    local report_file="./monitoring/health-report-$(date +%Y%m%d).html"
    
    cat > "$report_file" << EOF
<!DOCTYPE html>
<html>
<head>
    <title>ERP Integration System Health Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background-color: #f0f0f0; padding: 20px; border-radius: 5px; }
        .status { margin: 10px 0; padding: 10px; border-radius: 3px; }
        .healthy { background-color: #d4edda; color: #155724; }
        .warning { background-color: #fff3cd; color: #856404; }
        .critical { background-color: #f8d7da; color: #721c24; }
        .logs { background-color: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="header">
        <h1>ERP Integration System Health Report</h1>
        <p>Generated on: $(date)</p>
    </div>
    
    <h2>Current Status</h2>
    <div class="status healthy">
        <strong>Overall System Status:</strong> $(if [ -f "./monitoring/status.log" ]; then tail -1 "./monitoring/status.log"; else echo "Unknown"; fi)
    </div>
    
    <h2>Recent Health Checks</h2>
    <div class="logs">
        <pre>$(if [ -f "$LOG_FILE" ]; then tail -20 "$LOG_FILE"; else echo "No health check logs available"; fi)</pre>
    </div>
    
    <h2>Recent Alerts</h2>
    <div class="logs">
        <pre>$(if [ -f "./monitoring/alerts.log" ]; then tail -10 "./monitoring/alerts.log"; else echo "No alerts"; fi)</pre>
    </div>
    
    <h2>System Information</h2>
    <div class="logs">
        <pre>
Docker Containers:
$(docker ps --format "table {{.Names}}\t{{.Status}}\t{{.Ports}}" 2>/dev/null || echo "Docker not available")

Disk Usage:
$(df -h . 2>/dev/null || echo "Unable to check disk usage")

Recent Backups:
$(ls -la ./backups/database/ ./backups/files/ ./backups/configs/ 2>/dev/null | tail -5 || echo "No backups found")
        </pre>
    </div>
    
    <footer style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ccc; color: #666;">
        <p>ERP Integration System Monitoring - $(date)</p>
    </footer>
</body>
</html>
EOF

    log "📊 Health report generated: $report_file"
}

# Setup monitoring cron job (Linux/Mac)
setup_monitoring_cron() {
    echo "Setting up monitoring cron job..."
    
    # Add cron job for health checks every 15 minutes
    local cron_entry="*/15 * * * * $(pwd)/monitor-system.sh check"
    
    if command -v crontab >/dev/null 2>&1; then
        (crontab -l 2>/dev/null; echo "$cron_entry") | crontab -
        log "✅ Monitoring cron job added"
    else
        log "⚠️  Crontab not available - manual scheduling required"
        
        # Create Windows task scheduler script
        cat > "schedule-monitoring.bat" << 'BATCH_EOF'
@echo off
REM Schedule system monitoring task
schtasks /create /tn "ERP Integration Health Check" /tr "%CD%\monitor-system.sh check" /sc minute /mo 15 /f
echo Health monitoring scheduled every 15 minutes
pause
BATCH_EOF
        
        log "📅 Created schedule-monitoring.bat for Windows task scheduling"
    fi
}

# Main script logic
case ${1:-"check"} in
    "check")
        main_health_check
        ;;
    "report")
        generate_report
        ;;
    "setup")
        setup_monitoring_cron
        ;;
    "status")
        echo "🔍 System Health Monitoring Status"
        echo "=================================="
        echo ""
        
        if [ -f "./monitoring/status.log" ]; then
            echo "Latest Status:"
            tail -1 "./monitoring/status.log"
            echo ""
            
            echo "Recent Status History:"
            tail -5 "./monitoring/status.log"
        else
            echo "No monitoring data available"
        fi
        
        echo ""
        echo "Monitoring Files:"
        ls -la ./monitoring/ 2>/dev/null || echo "Monitoring directory not found"
        ;;
    *)
        echo "Usage: $0 {check|report|setup|status}"
        echo ""
        echo "Commands:"
        echo "  check  - Run system health check"
        echo "  report - Generate HTML health report"
        echo "  setup  - Set up automated monitoring"
        echo "  status - Show monitoring status"
        exit 1
        ;;
esac