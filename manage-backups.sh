#!/bin/bash
# Backup Management Script

set -e

BACKUP_SCRIPT="./backups/scripts/backup.sh"
RESTORE_SCRIPT="./backups/scripts/restore.sh"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

case $1 in
    "backup")
        echo -e "${BLUE}🔄 Starting backup...${NC}"
        $BACKUP_SCRIPT
        ;;
    "restore")
        echo -e "${BLUE}📥 Starting restore...${NC}"
        $RESTORE_SCRIPT "$2" "$3"
        ;;
    "list")
        echo -e "${BLUE}📋 Listing backups...${NC}"
        $RESTORE_SCRIPT list
        ;;
    "test")
        echo -e "${BLUE}🧪 Testing backup system...${NC}"
        # Run a test backup
        $BACKUP_SCRIPT
        echo -e "${GREEN}✅ Backup test completed${NC}"
        ;;
    "schedule")
        echo -e "${BLUE}📅 Setting up backup schedule...${NC}"
        # Add cron job for daily backup
        (crontab -l 2>/dev/null; echo "0 2 * * * $(pwd)/manage-backups.sh backup") | crontab -
        echo -e "${GREEN}✅ Daily backup scheduled at 2:00 AM${NC}"
        ;;
    "status")
        echo -e "${BLUE}📊 Backup system status:${NC}"
        echo ""
        echo "Last backup logs:"
        ls -la ./backups/logs/ | tail -5
        echo ""
        echo "Storage usage:"
        du -sh ./backups/
        ;;
    *)
        echo "Usage: $0 {backup|restore|list|test|schedule|status}"
        echo ""
        echo "Commands:"
        echo "  backup                          - Run backup now"
        echo "  restore {database|files|configs} <file> - Restore from backup"
        echo "  list                           - List available backups"
        echo "  test                           - Test backup system"
        echo "  schedule                       - Set up automatic daily backups"
        echo "  status                         - Show backup system status"
        exit 1
        ;;
esac
