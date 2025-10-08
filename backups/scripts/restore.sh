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
