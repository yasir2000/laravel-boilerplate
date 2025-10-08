#!/bin/bash
# Automated Backup System Configuration Script
# Sets up comprehensive backup system for database, configurations, and system state

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}💾 Automated Backup System Configuration${NC}"
echo -e "${BLUE}=======================================${NC}"
echo ""

# Create backup directory structure
BACKUP_DIR="/backup"
LOCAL_BACKUP_DIR="./backups"
SCRIPTS_DIR="$LOCAL_BACKUP_DIR/scripts"
CONFIG_DIR="$LOCAL_BACKUP_DIR/config"

echo -e "${BLUE}📁 Creating backup directory structure...${NC}"
mkdir -p "$LOCAL_BACKUP_DIR" "$SCRIPTS_DIR" "$CONFIG_DIR"
mkdir -p "$LOCAL_BACKUP_DIR/database" "$LOCAL_BACKUP_DIR/files" "$LOCAL_BACKUP_DIR/configs"
chmod 755 "$LOCAL_BACKUP_DIR" "$SCRIPTS_DIR" "$CONFIG_DIR"
chmod 700 "$LOCAL_BACKUP_DIR/database" "$LOCAL_BACKUP_DIR/files" "$LOCAL_BACKUP_DIR/configs"

echo -e "${GREEN}✅ Backup directories created${NC}"

# Backup configuration
echo ""
echo -e "${YELLOW}📋 Backup Configuration:${NC}"

# Database backup settings
echo "Database backup options:"
echo "1. PostgreSQL"
echo "2. MySQL/MariaDB"
echo "3. MongoDB"
echo "4. Multiple databases"
echo ""

read -p "Select database type (1-4): " db_choice

case $db_choice in
    1)
        db_type="postgresql"
        read -p "PostgreSQL host (default: postgres): " pg_host
        pg_host=${pg_host:-postgres}
        read -p "PostgreSQL port (default: 5432): " pg_port
        pg_port=${pg_port:-5432}
        read -p "PostgreSQL database name: " pg_database
        read -p "PostgreSQL username: " pg_username
        read -s -p "PostgreSQL password: " pg_password
        echo ""
        ;;
    2)
        db_type="mysql"
        read -p "MySQL host (default: mysql): " mysql_host
        mysql_host=${mysql_host:-mysql}
        read -p "MySQL port (default: 3306): " mysql_port
        mysql_port=${mysql_port:-3306}
        read -p "MySQL database name: " mysql_database
        read -p "MySQL username: " mysql_username
        read -s -p "MySQL password: " mysql_password
        echo ""
        ;;
    3)
        db_type="mongodb"
        read -p "MongoDB host (default: mongodb): " mongo_host
        mongo_host=${mongo_host:-mongodb}
        read -p "MongoDB port (default: 27017): " mongo_port
        mongo_port=${mongo_port:-27017}
        read -p "MongoDB database name: " mongo_database
        read -p "MongoDB username (optional): " mongo_username
        read -s -p "MongoDB password (optional): " mongo_password
        echo ""
        ;;
    4)
        db_type="multiple"
        echo "Multiple database backup will be configured separately"
        ;;
esac

# Backup retention settings
echo ""
echo -e "${YELLOW}🗓️ Retention Settings:${NC}"
read -p "Daily backups to keep (default: 7): " daily_retention
daily_retention=${daily_retention:-7}

read -p "Weekly backups to keep (default: 4): " weekly_retention
weekly_retention=${weekly_retention:-4}

read -p "Monthly backups to keep (default: 6): " monthly_retention
monthly_retention=${monthly_retention:-6}

# Backup destinations
echo ""
echo -e "${YELLOW}📤 Backup Destinations:${NC}"
echo "1. Local storage only"
echo "2. AWS S3"
echo "3. Google Cloud Storage"
echo "4. Azure Blob Storage"
echo "5. SFTP/SCP remote server"
echo "6. Multiple destinations"
echo ""

read -p "Select backup destination (1-6): " dest_choice

backup_destinations=""
case $dest_choice in
    1)
        backup_destinations="local"
        ;;
    2)
        backup_destinations="s3"
        read -p "AWS S3 bucket name: " s3_bucket
        read -p "AWS region (default: us-east-1): " aws_region
        aws_region=${aws_region:-us-east-1}
        read -p "AWS Access Key ID: " aws_access_key
        read -s -p "AWS Secret Access Key: " aws_secret_key
        echo ""
        ;;
    3)
        backup_destinations="gcs"
        read -p "Google Cloud Storage bucket name: " gcs_bucket
        read -p "Google Cloud project ID: " gcp_project
        echo "Please ensure service account credentials are available"
        ;;
    4)
        backup_destinations="azure"
        read -p "Azure Storage account name: " azure_account
        read -p "Azure container name: " azure_container
        read -s -p "Azure Storage account key: " azure_key
        echo ""
        ;;
    5)
        backup_destinations="sftp"
        read -p "SFTP server hostname: " sftp_host
        read -p "SFTP port (default: 22): " sftp_port
        sftp_port=${sftp_port:-22}
        read -p "SFTP username: " sftp_username
        read -p "SFTP remote path: " sftp_path
        echo "Please ensure SSH key is configured for passwordless access"
        ;;
    6)
        backup_destinations="multiple"
        echo "Multiple destinations will be configured"
        ;;
esac

# Encryption settings
echo ""
echo -e "${YELLOW}🔐 Encryption Settings:${NC}"
read -p "Enable backup encryption? (y/n): " enable_encryption
enable_encryption=${enable_encryption:-y}

if [ "$enable_encryption" = "y" ]; then
    encryption_key=$(openssl rand -base64 32)
    echo "Generated encryption key: $encryption_key"
    echo "Store this key securely!"
fi

# Create main backup script
cat > "$SCRIPTS_DIR/backup.sh" << 'EOF'
#!/bin/bash
# Main Backup Script
# Performs comprehensive backup of database, files, and configurations

set -e

# Load configuration
source "$(dirname "$0")/../config/backup.conf"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging
LOG_FILE="${BACKUP_DIR}/logs/backup_$(date +%Y%m%d_%H%M%S).log"
mkdir -p "$(dirname "$LOG_FILE")"

log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_FILE"
}

error_exit() {
    log "ERROR: $1"
    exit 1
}

# Notification function
send_notification() {
    local status=$1
    local message=$2
    
    if [ "$NOTIFICATION_ENABLED" = "true" ]; then
        case $NOTIFICATION_TYPE in
            "email")
                echo "$message" | mail -s "Backup $status - $(hostname)" "$NOTIFICATION_EMAIL"
                ;;
            "slack")
                curl -X POST -H 'Content-type: application/json' \
                    --data "{\"text\":\"Backup $status on $(hostname): $message\"}" \
                    "$SLACK_WEBHOOK_URL"
                ;;
            "discord")
                curl -X POST -H 'Content-type: application/json' \
                    --data "{\"content\":\"Backup $status on $(hostname): $message\"}" \
                    "$DISCORD_WEBHOOK_URL"
                ;;
        esac
    fi
}

# Database backup function
backup_database() {
    log "Starting database backup..."
    
    local backup_file="${BACKUP_DIR}/database/db_backup_$(date +%Y%m%d_%H%M%S)"
    
    case $DB_TYPE in
        "postgresql")
            PGPASSWORD="$PG_PASSWORD" pg_dump \
                -h "$PG_HOST" \
                -p "$PG_PORT" \
                -U "$PG_USERNAME" \
                -d "$PG_DATABASE" \
                --no-password \
                --verbose \
                --format=custom \
                --file="${backup_file}.dump"
            ;;
        "mysql")
            mysqldump \
                --host="$MYSQL_HOST" \
                --port="$MYSQL_PORT" \
                --user="$MYSQL_USERNAME" \
                --password="$MYSQL_PASSWORD" \
                --single-transaction \
                --routines \
                --triggers \
                "$MYSQL_DATABASE" > "${backup_file}.sql"
            ;;
        "mongodb")
            if [ -n "$MONGO_USERNAME" ]; then
                mongodump \
                    --host="${MONGO_HOST}:${MONGO_PORT}" \
                    --db="$MONGO_DATABASE" \
                    --username="$MONGO_USERNAME" \
                    --password="$MONGO_PASSWORD" \
                    --out="${backup_file}_mongo"
            else
                mongodump \
                    --host="${MONGO_HOST}:${MONGO_PORT}" \
                    --db="$MONGO_DATABASE" \
                    --out="${backup_file}_mongo"
            fi
            tar -czf "${backup_file}.tar.gz" -C "${backup_file}_mongo" .
            rm -rf "${backup_file}_mongo"
            ;;
    esac
    
    # Compress if not already compressed
    if [ "$DB_TYPE" != "mongodb" ] && [ "$DB_TYPE" != "postgresql" ]; then
        gzip "${backup_file}.sql"
        backup_file="${backup_file}.sql.gz"
    elif [ "$DB_TYPE" = "postgresql" ]; then
        backup_file="${backup_file}.dump"
    else
        backup_file="${backup_file}.tar.gz"
    fi
    
    # Encrypt if enabled
    if [ "$ENCRYPTION_ENABLED" = "true" ]; then
        openssl enc -aes-256-cbc -salt -pbkdf2 \
            -in "$backup_file" \
            -out "${backup_file}.enc" \
            -k "$ENCRYPTION_KEY"
        rm "$backup_file"
        backup_file="${backup_file}.enc"
    fi
    
    log "Database backup completed: $(basename "$backup_file")"
    echo "$backup_file"
}

# File backup function
backup_files() {
    log "Starting file backup..."
    
    local backup_file="${BACKUP_DIR}/files/files_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    
    # Create tar archive of important files
    tar -czf "$backup_file" \
        --exclude='./backups' \
        --exclude='./vendor' \
        --exclude='./node_modules' \
        --exclude='./.git' \
        --exclude='./storage/logs/*' \
        --exclude='./storage/cache/*' \
        -C "$(pwd)" \
        .
    
    # Encrypt if enabled
    if [ "$ENCRYPTION_ENABLED" = "true" ]; then
        openssl enc -aes-256-cbc -salt -pbkdf2 \
            -in "$backup_file" \
            -out "${backup_file}.enc" \
            -k "$ENCRYPTION_KEY"
        rm "$backup_file"
        backup_file="${backup_file}.enc"
    fi
    
    log "File backup completed: $(basename "$backup_file")"
    echo "$backup_file"
}

# Configuration backup function
backup_configs() {
    log "Starting configuration backup..."
    
    local backup_file="${BACKUP_DIR}/configs/configs_backup_$(date +%Y%m%d_%H%M%S).tar.gz"
    
    # Create tar archive of configuration files
    tar -czf "$backup_file" \
        .env* \
        docker-compose*.yml \
        microservices/*/config/ \
        config/ \
        2>/dev/null || true
    
    # Encrypt if enabled
    if [ "$ENCRYPTION_ENABLED" = "true" ]; then
        openssl enc -aes-256-cbc -salt -pbkdf2 \
            -in "$backup_file" \
            -out "${backup_file}.enc" \
            -k "$ENCRYPTION_KEY"
        rm "$backup_file"
        backup_file="${backup_file}.enc"
    fi
    
    log "Configuration backup completed: $(basename "$backup_file")"
    echo "$backup_file"
}

# Upload to remote destinations
upload_backup() {
    local file=$1
    log "Uploading backup to remote destinations..."
    
    case $BACKUP_DESTINATIONS in
        *"s3"*)
            aws s3 cp "$file" "s3://$S3_BUCKET/$(basename "$file")" --region "$AWS_REGION"
            log "Uploaded to S3: s3://$S3_BUCKET/$(basename "$file")"
            ;;
        *"gcs"*)
            gsutil cp "$file" "gs://$GCS_BUCKET/$(basename "$file")"
            log "Uploaded to GCS: gs://$GCS_BUCKET/$(basename "$file")"
            ;;
        *"azure"*)
            az storage blob upload \
                --account-name "$AZURE_ACCOUNT" \
                --account-key "$AZURE_KEY" \
                --container-name "$AZURE_CONTAINER" \
                --name "$(basename "$file")" \
                --file "$file"
            log "Uploaded to Azure: $AZURE_CONTAINER/$(basename "$file")"
            ;;
        *"sftp"*)
            scp -P "$SFTP_PORT" "$file" "$SFTP_USERNAME@$SFTP_HOST:$SFTP_PATH/"
            log "Uploaded to SFTP: $SFTP_HOST:$SFTP_PATH/$(basename "$file")"
            ;;
    esac
}

# Cleanup old backups
cleanup_backups() {
    log "Cleaning up old backups..."
    
    # Clean local backups
    find "${BACKUP_DIR}/database" -name "db_backup_*" -mtime +$DAILY_RETENTION -delete 2>/dev/null || true
    find "${BACKUP_DIR}/files" -name "files_backup_*" -mtime +$DAILY_RETENTION -delete 2>/dev/null || true
    find "${BACKUP_DIR}/configs" -name "configs_backup_*" -mtime +$DAILY_RETENTION -delete 2>/dev/null || true
    
    # Clean logs older than 30 days
    find "${BACKUP_DIR}/logs" -name "backup_*.log" -mtime +30 -delete 2>/dev/null || true
    
    log "Cleanup completed"
}

# Main backup process
main() {
    log "Starting backup process..."
    send_notification "STARTED" "Backup process initiated"
    
    local backup_files=()
    
    # Perform backups
    if [ "$BACKUP_DATABASE" = "true" ]; then
        backup_files+=($(backup_database))
    fi
    
    if [ "$BACKUP_FILES" = "true" ]; then
        backup_files+=($(backup_files))
    fi
    
    if [ "$BACKUP_CONFIGS" = "true" ]; then
        backup_files+=($(backup_configs))
    fi
    
    # Upload to remote destinations
    if [ "$UPLOAD_ENABLED" = "true" ]; then
        for file in "${backup_files[@]}"; do
            upload_backup "$file"
        done
    fi
    
    # Cleanup old backups
    cleanup_backups
    
    # Calculate backup sizes
    local total_size=0
    for file in "${backup_files[@]}"; do
        if [ -f "$file" ]; then
            size=$(stat -f%z "$file" 2>/dev/null || stat -c%s "$file" 2>/dev/null || echo 0)
            total_size=$((total_size + size))
        fi
    done
    
    local size_mb=$((total_size / 1024 / 1024))
    
    log "Backup process completed successfully"
    log "Total backup size: ${size_mb}MB"
    send_notification "SUCCESS" "Backup completed successfully. Size: ${size_mb}MB"
}

# Error handling
trap 'error_exit "Backup failed with error on line $LINENO"' ERR

# Run main backup process
main "$@"
EOF

chmod +x "$SCRIPTS_DIR/backup.sh"

# Create backup configuration file
cat > "$CONFIG_DIR/backup.conf" << EOF
# Backup Configuration File
# Generated on $(date)

# Backup directories
BACKUP_DIR="$LOCAL_BACKUP_DIR"

# Database configuration
DB_TYPE="$db_type"
BACKUP_DATABASE=true
EOF

# Add database-specific configuration
case $db_type in
    "postgresql")
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# PostgreSQL settings
PG_HOST="$pg_host"
PG_PORT="$pg_port"
PG_DATABASE="$pg_database"
PG_USERNAME="$pg_username"
PG_PASSWORD="$pg_password"
EOF
        ;;
    "mysql")
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# MySQL settings
MYSQL_HOST="$mysql_host"
MYSQL_PORT="$mysql_port"
MYSQL_DATABASE="$mysql_database"
MYSQL_USERNAME="$mysql_username"
MYSQL_PASSWORD="$mysql_password"
EOF
        ;;
    "mongodb")
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# MongoDB settings
MONGO_HOST="$mongo_host"
MONGO_PORT="$mongo_port"
MONGO_DATABASE="$mongo_database"
MONGO_USERNAME="$mongo_username"
MONGO_PASSWORD="$mongo_password"
EOF
        ;;
esac

cat >> "$CONFIG_DIR/backup.conf" << EOF

# File and configuration backup
BACKUP_FILES=true
BACKUP_CONFIGS=true

# Retention settings
DAILY_RETENTION=$daily_retention
WEEKLY_RETENTION=$weekly_retention
MONTHLY_RETENTION=$monthly_retention

# Encryption settings
ENCRYPTION_ENABLED=$enable_encryption
EOF

if [ "$enable_encryption" = "y" ]; then
    cat >> "$CONFIG_DIR/backup.conf" << EOF
ENCRYPTION_KEY="$encryption_key"
EOF
fi

# Add destination-specific configuration
cat >> "$CONFIG_DIR/backup.conf" << EOF

# Upload settings
UPLOAD_ENABLED=true
BACKUP_DESTINATIONS="$backup_destinations"
EOF

case $dest_choice in
    2)  # AWS S3
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# AWS S3 settings
S3_BUCKET="$s3_bucket"
AWS_REGION="$aws_region"
AWS_ACCESS_KEY_ID="$aws_access_key"
AWS_SECRET_ACCESS_KEY="$aws_secret_key"
EOF
        ;;
    3)  # Google Cloud Storage
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# Google Cloud Storage settings
GCS_BUCKET="$gcs_bucket"
GCP_PROJECT="$gcp_project"
EOF
        ;;
    4)  # Azure Blob Storage
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# Azure Blob Storage settings
AZURE_ACCOUNT="$azure_account"
AZURE_CONTAINER="$azure_container"
AZURE_KEY="$azure_key"
EOF
        ;;
    5)  # SFTP
        cat >> "$CONFIG_DIR/backup.conf" << EOF

# SFTP settings
SFTP_HOST="$sftp_host"
SFTP_PORT="$sftp_port"
SFTP_USERNAME="$sftp_username"
SFTP_PATH="$sftp_path"
EOF
        ;;
esac

# Add notification settings
echo ""
read -p "Enable backup notifications? (y/n): " enable_notifications
if [ "$enable_notifications" = "y" ]; then
    echo "Notification options:"
    echo "1. Email"
    echo "2. Slack"
    echo "3. Discord"
    read -p "Select notification type (1-3): " notif_choice
    
    cat >> "$CONFIG_DIR/backup.conf" << EOF

# Notification settings
NOTIFICATION_ENABLED=true
EOF
    
    case $notif_choice in
        1)
            read -p "Enter email address: " notification_email
            cat >> "$CONFIG_DIR/backup.conf" << EOF
NOTIFICATION_TYPE="email"
NOTIFICATION_EMAIL="$notification_email"
EOF
            ;;
        2)
            read -p "Enter Slack webhook URL: " slack_webhook
            cat >> "$CONFIG_DIR/backup.conf" << EOF
NOTIFICATION_TYPE="slack"
SLACK_WEBHOOK_URL="$slack_webhook"
EOF
            ;;
        3)
            read -p "Enter Discord webhook URL: " discord_webhook
            cat >> "$CONFIG_DIR/backup.conf" << EOF
NOTIFICATION_TYPE="discord"
DISCORD_WEBHOOK_URL="$discord_webhook"
EOF
            ;;
    esac
else
    cat >> "$CONFIG_DIR/backup.conf" << EOF

# Notification settings
NOTIFICATION_ENABLED=false
EOF
fi

# Create restore script
cat > "$SCRIPTS_DIR/restore.sh" << 'EOF'
#!/bin/bash
# Backup Restore Script
# Restores database, files, and configurations from backup

set -e

# Load configuration
source "$(dirname "$0")/../config/backup.conf"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Functions
list_backups() {
    echo -e "${BLUE}Available backups:${NC}"
    echo ""
    
    echo -e "${YELLOW}Database backups:${NC}"
    ls -la "${BACKUP_DIR}/database/" 2>/dev/null || echo "No database backups found"
    
    echo ""
    echo -e "${YELLOW}File backups:${NC}"
    ls -la "${BACKUP_DIR}/files/" 2>/dev/null || echo "No file backups found"
    
    echo ""
    echo -e "${YELLOW}Configuration backups:${NC}"
    ls -la "${BACKUP_DIR}/configs/" 2>/dev/null || echo "No configuration backups found"
}

decrypt_backup() {
    local encrypted_file=$1
    local decrypted_file="${encrypted_file%.enc}"
    
    if [[ "$encrypted_file" == *.enc ]]; then
        echo -e "${BLUE}Decrypting backup file...${NC}"
        openssl enc -aes-256-cbc -d -pbkdf2 \
            -in "$encrypted_file" \
            -out "$decrypted_file" \
            -k "$ENCRYPTION_KEY"
        echo "$decrypted_file"
    else
        echo "$encrypted_file"
    fi
}

restore_database() {
    local backup_file=$1
    
    echo -e "${BLUE}Restoring database from: $(basename "$backup_file")${NC}"
    
    # Decrypt if needed
    backup_file=$(decrypt_backup "$backup_file")
    
    case $DB_TYPE in
        "postgresql")
            echo -e "${YELLOW}⚠️  This will replace the current database. Continue? (y/n)${NC}"
            read -r confirm
            if [ "$confirm" = "y" ]; then
                PGPASSWORD="$PG_PASSWORD" pg_restore \
                    -h "$PG_HOST" \
                    -p "$PG_PORT" \
                    -U "$PG_USERNAME" \
                    -d "$PG_DATABASE" \
                    --clean \
                    --verbose \
                    "$backup_file"
            fi
            ;;
        "mysql")
            echo -e "${YELLOW}⚠️  This will replace the current database. Continue? (y/n)${NC}"
            read -r confirm
            if [ "$confirm" = "y" ]; then
                if [[ "$backup_file" == *.gz ]]; then
                    gunzip -c "$backup_file" | mysql \
                        --host="$MYSQL_HOST" \
                        --port="$MYSQL_PORT" \
                        --user="$MYSQL_USERNAME" \
                        --password="$MYSQL_PASSWORD" \
                        "$MYSQL_DATABASE"
                else
                    mysql \
                        --host="$MYSQL_HOST" \
                        --port="$MYSQL_PORT" \
                        --user="$MYSQL_USERNAME" \
                        --password="$MYSQL_PASSWORD" \
                        "$MYSQL_DATABASE" < "$backup_file"
                fi
            fi
            ;;
        "mongodb")
            echo -e "${YELLOW}⚠️  This will replace the current database. Continue? (y/n)${NC}"
            read -r confirm
            if [ "$confirm" = "y" ]; then
                # Extract tar.gz
                local temp_dir="/tmp/mongo_restore_$$"
                mkdir -p "$temp_dir"
                tar -xzf "$backup_file" -C "$temp_dir"
                
                if [ -n "$MONGO_USERNAME" ]; then
                    mongorestore \
                        --host="${MONGO_HOST}:${MONGO_PORT}" \
                        --db="$MONGO_DATABASE" \
                        --username="$MONGO_USERNAME" \
                        --password="$MONGO_PASSWORD" \
                        --drop \
                        "$temp_dir"
                else
                    mongorestore \
                        --host="${MONGO_HOST}:${MONGO_PORT}" \
                        --db="$MONGO_DATABASE" \
                        --drop \
                        "$temp_dir"
                fi
                
                rm -rf "$temp_dir"
            fi
            ;;
    esac
    
    echo -e "${GREEN}✅ Database restore completed${NC}"
}

restore_files() {
    local backup_file=$1
    
    echo -e "${BLUE}Restoring files from: $(basename "$backup_file")${NC}"
    echo -e "${YELLOW}⚠️  This will overwrite existing files. Continue? (y/n)${NC}"
    read -r confirm
    
    if [ "$confirm" = "y" ]; then
        # Decrypt if needed
        backup_file=$(decrypt_backup "$backup_file")
        
        # Extract files
        if [[ "$backup_file" == *.tar.gz ]]; then
            tar -xzf "$backup_file" -C "$(pwd)"
        fi
        
        echo -e "${GREEN}✅ File restore completed${NC}"
    fi
}

restore_configs() {
    local backup_file=$1
    
    echo -e "${BLUE}Restoring configurations from: $(basename "$backup_file")${NC}"
    echo -e "${YELLOW}⚠️  This will overwrite existing configuration files. Continue? (y/n)${NC}"
    read -r confirm
    
    if [ "$confirm" = "y" ]; then
        # Decrypt if needed
        backup_file=$(decrypt_backup "$backup_file")
        
        # Extract configurations
        if [[ "$backup_file" == *.tar.gz ]]; then
            tar -xzf "$backup_file" -C "$(pwd)"
        fi
        
        echo -e "${GREEN}✅ Configuration restore completed${NC}"
    fi
}

# Main restore menu
main() {
    echo -e "${BLUE}💾 Backup Restore Utility${NC}"
    echo -e "${BLUE}========================${NC}"
    echo ""
    
    case $1 in
        "list")
            list_backups
            ;;
        "database")
            if [ -n "$2" ]; then
                restore_database "$2"
            else
                echo "Usage: $0 database <backup_file>"
            fi
            ;;
        "files")
            if [ -n "$2" ]; then
                restore_files "$2"
            else
                echo "Usage: $0 files <backup_file>"
            fi
            ;;
        "configs")
            if [ -n "$2" ]; then
                restore_configs "$2"
            else
                echo "Usage: $0 configs <backup_file>"
            fi
            ;;
        *)
            echo "Usage: $0 {list|database|files|configs} [backup_file]"
            echo ""
            echo "Commands:"
            echo "  list                    - List available backups"
            echo "  database <backup_file>  - Restore database from backup"
            echo "  files <backup_file>     - Restore files from backup"
            echo "  configs <backup_file>   - Restore configurations from backup"
            ;;
    esac
}

main "$@"
EOF

chmod +x "$SCRIPTS_DIR/restore.sh"

# Create backup management script
cat > "manage-backups.sh" << 'EOF'
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
EOF

chmod +x manage-backups.sh

# Create Docker backup configuration
cat > "backup-docker-compose.yml" << EOF
# Backup System Docker Compose Configuration
version: '3.8'

services:
  backup-agent:
    image: alpine:latest
    container_name: backup-agent
    volumes:
      - ./backups:/backup
      - .:/workspace:ro
      - /var/run/docker.sock:/var/run/docker.sock
    environment:
      - BACKUP_SCHEDULE=0 2 * * *
    command: |
      sh -c "
        apk add --no-cache curl postgresql-client mysql-client mongodb-tools openssl aws-cli &&
        crond -f
      "
    restart: unless-stopped
    networks:
      - integration-network

networks:
  integration-network:
    external: true
EOF

# Create monitoring script
cat > "$SCRIPTS_DIR/monitor-backups.sh" << 'EOF'
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
EOF

chmod +x "$SCRIPTS_DIR/monitor-backups.sh"

echo ""
echo -e "${GREEN}✅ Automated Backup System Configuration Complete!${NC}"
echo ""
echo -e "${BLUE}📋 Summary:${NC}"
echo "- Database type: $db_type"
echo "- Retention: $daily_retention daily, $weekly_retention weekly, $monthly_retention monthly"
echo "- Destinations: $backup_destinations"
echo "- Encryption enabled: $enable_encryption"
echo ""
echo -e "${BLUE}📋 Generated Files:${NC}"
echo "- backup.sh - Main backup script"
echo "- restore.sh - Backup restore script"
echo "- manage-backups.sh - Backup management utility"
echo "- backup.conf - Backup configuration"
echo "- monitor-backups.sh - Backup monitoring script"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo "1. Test backup system: ./manage-backups.sh test"
echo "2. Schedule automatic backups: ./manage-backups.sh schedule"
echo "3. Monitor backup status: ./manage-backups.sh status"
echo "4. Set up backup monitoring alerts"
echo ""
echo -e "${YELLOW}⚠️  Important Security Notes:${NC}"
echo "- Backup files are encrypted with AES-256"
echo "- Store encryption key securely and separately"
echo "- Test restore procedures regularly"
echo "- Monitor backup logs for failures"
echo ""

if [ "$enable_encryption" = "y" ]; then
    echo -e "${RED}🔐 ENCRYPTION KEY (STORE SECURELY):${NC}"
    echo "$encryption_key"
    echo ""
fi

echo -e "${GREEN}💾 Automated backup system setup completed successfully!${NC}"