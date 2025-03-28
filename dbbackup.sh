#!/bin/bash

# تنظیمات
BACKUP_DIR="/var/backups/xui-manager"
DB_NAME="xui_manager"
DB_USER="xui_manager"
DB_PASS=""
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="$BACKUP_DIR/backup_$DATE.sql"

# ایجاد دایرکتوری پشتیبان‌گیری اگر وجود نداشته باشد
mkdir -p "$BACKUP_DIR"

# پشتیبان‌گیری از دیتابیس
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE"

# فشرده‌سازی فایل پشتیبان
gzip "$BACKUP_FILE"

# حذف پشتیبان‌های قدیمی‌تر از 7 روز
find "$BACKUP_DIR" -name "backup_*.sql.gz" -mtime +7 -delete

# ثبت لاگ
echo "[$(date)] پشتیبان‌گیری با موفقیت انجام شد: $BACKUP_FILE.gz" >> "$BACKUP_DIR/backup.log" 