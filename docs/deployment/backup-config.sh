#!/bin/bash
#
# Automated backup script for Laravel SaaS application
# This script backs up the database, .env file, and user-uploaded files
# It compresses the backups and uploads them to a remote storage provider
#
# Usage: ./backup-config.sh [environment]
# Example: ./backup-config.sh production
#
# Add to crontab for automated daily backups:
# 0 2 * * * /path/to/backup-config.sh production > /dev/null 2>&1
#

# Configuration variables
APP_ENV="${1:-production}"
BACKUP_DIR="/var/backups/laravel-saas"
APP_DIR="/home/forge/your-domain.com"
MYSQL_USER="forge"
MYSQL_PASSWORD="your-mysql-password"
MYSQL_DATABASE="your_database_name"
DATE_FORMAT=$(date +"%Y-%m-%d_%H-%M-%S")
BACKUP_FILENAME="backup_${APP_ENV}_${DATE_FORMAT}"
RETENTION_DAYS=7
S3_BUCKET="your-backup-bucket"
S3_PATH="laravel-saas/backups/${APP_ENV}"

# Optional: Slack webhook for notifications
SLACK_WEBHOOK_URL=""

# Make sure backup directory exists
mkdir -p "${BACKUP_DIR}"

# Log function
log() {
    echo "[$(date +"%Y-%m-%d %H:%M:%S")] $1"
    if [[ -n "${SLACK_WEBHOOK_URL}" ]]; then
        curl -s -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"Backup ${APP_ENV}: $1\"}" \
            "${SLACK_WEBHOOK_URL}" > /dev/null
    fi
}

# Error handling
error_exit() {
    log "ERROR: $1"
    exit 1
}

log "Starting backup process for ${APP_ENV} environment"

# Check if app directory exists
if [[ ! -d "${APP_DIR}" ]]; then
    error_exit "Application directory ${APP_DIR} not found"
fi

# Create temporary directory for this backup
TEMP_BACKUP_DIR="${BACKUP_DIR}/temp_${DATE_FORMAT}"
mkdir -p "${TEMP_BACKUP_DIR}" || error_exit "Failed to create temporary backup directory"

# Backup MySQL database
log "Backing up MySQL database..."
mysqldump --single-transaction --quick --user="${MYSQL_USER}" --password="${MYSQL_PASSWORD}" "${MYSQL_DATABASE}" > "${TEMP_BACKUP_DIR}/${MYSQL_DATABASE}.sql" || error_exit "Database backup failed"

# Backup .env file
log "Backing up environment file..."
cp "${APP_DIR}/.env" "${TEMP_BACKUP_DIR}/.env" || error_exit "Environment file backup failed"

# Backup uploaded files
log "Backing up uploaded files..."
if [[ -d "${APP_DIR}/storage/app/public" ]]; then
    mkdir -p "${TEMP_BACKUP_DIR}/storage/app"
    cp -r "${APP_DIR}/storage/app/public" "${TEMP_BACKUP_DIR}/storage/app/" || error_exit "File backup failed"
fi

# Create compressed archive
log "Creating compressed archive..."
cd "${BACKUP_DIR}" || error_exit "Failed to change directory to ${BACKUP_DIR}"
tar -czf "${BACKUP_FILENAME}.tar.gz" -C "${TEMP_BACKUP_DIR}" . || error_exit "Compression failed"

# Upload to S3 if AWS CLI is installed
if command -v aws &> /dev/null; then
    log "Uploading backup to S3..."
    aws s3 cp "${BACKUP_DIR}/${BACKUP_FILENAME}.tar.gz" "s3://${S3_BUCKET}/${S3_PATH}/${BACKUP_FILENAME}.tar.gz" || log "S3 upload failed, keeping local copy"
fi

# Clean up temporary directory
rm -rf "${TEMP_BACKUP_DIR}"

# Remove old backups (keeping last RETENTION_DAYS days)
log "Cleaning up old backups..."
find "${BACKUP_DIR}" -name "backup_${APP_ENV}_*.tar.gz" -type f -mtime +${RETENTION_DAYS} -delete

# Also clean up old backups from S3 if AWS CLI is installed
if command -v aws &> /dev/null; then
    log "Cleaning up old S3 backups..."
    OLD_S3_BACKUPS=$(aws s3 ls "s3://${S3_BUCKET}/${S3_PATH}/" | grep -v "${BACKUP_FILENAME}" | grep "backup_${APP_ENV}_" | sort -r | tail -n +$((RETENTION_DAYS + 1)) | awk '{print $4}')
    for OLD_BACKUP in ${OLD_S3_BACKUPS}; do
        aws s3 rm "s3://${S3_BUCKET}/${S3_PATH}/${OLD_BACKUP}"
    done
fi

log "Backup completed successfully: ${BACKUP_FILENAME}.tar.gz ($(du -h "${BACKUP_DIR}/${BACKUP_FILENAME}.tar.gz" | cut -f1))"

exit 0

