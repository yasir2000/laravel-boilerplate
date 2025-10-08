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
