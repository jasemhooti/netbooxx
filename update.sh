#!/bin/bash

# رنگ‌ها برای نمایش بهتر
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# تابع نمایش پیام
print_message() {
    echo -e "${2}${1}${NC}"
}

# تابع بررسی دسترسی root
check_root() {
    if [ "$EUID" -ne 0 ]; then 
        print_message "لطفاً با دسترسی root اجرا کنید" "$RED"
        exit 1
    fi
}

# تابع پشتیبان‌گیری از فایل‌ها
backup_files() {
    print_message "در حال پشتیبان‌گیری از فایل‌ها..." "$YELLOW"
    
    BACKUP_DIR="/var/backups/xui-manager/updates"
    mkdir -p "$BACKUP_DIR"
    
    DATE=$(date +%Y%m%d_%H%M%S)
    BACKUP_FILE="$BACKUP_DIR/backup_$DATE.tar.gz"
    
    tar -czf "$BACKUP_FILE" . --exclude='vendor' --exclude='node_modules'
    
    if [ $? -eq 0 ]; then
        print_message "پشتیبان‌گیری با موفقیت انجام شد" "$GREEN"
    else
        print_message "خطا در پشتیبان‌گیری" "$RED"
        exit 1
    fi
}

# تابع بروزرسانی کد
update_code() {
    print_message "در حال بروزرسانی کد..." "$YELLOW"
    
    # دریافت آخرین تغییرات از گیت
    git pull origin main
    
    if [ $? -eq 0 ]; then
        print_message "کد با موفقیت بروزرسانی شد" "$GREEN"
    else
        print_message "خطا در بروزرسانی کد" "$RED"
        exit 1
    fi
}

# تابع بروزرسانی وابستگی‌ها
update_dependencies() {
    print_message "در حال بروزرسانی وابستگی‌ها..." "$YELLOW"
    
    composer update --no-dev
    
    if [ $? -eq 0 ]; then
        print_message "وابستگی‌ها با موفقیت بروزرسانی شدند" "$GREEN"
    else
        print_message "خطا در بروزرسانی وابستگی‌ها" "$RED"
        exit 1
    fi
}

# تابع پاکسازی کش
clear_cache() {
    print_message "در حال پاکسازی کش..." "$YELLOW"
    
    rm -rf cache/*
    
    if [ $? -eq 0 ]; then
        print_message "کش با موفقیت پاکسازی شد" "$GREEN"
    else
        print_message "خطا در پاکسازی کش" "$RED"
        exit 1
    fi
}

# تابع تنظیم دسترسی‌ها
set_permissions() {
    print_message "در حال تنظیم دسترسی‌ها..." "$YELLOW"
    
    chmod -R 755 .
    chmod -R 777 cache/ backups/ logs/
    
    if [ $? -eq 0 ]; then
        print_message "دسترسی‌ها با موفقیت تنظیم شدند" "$GREEN"
    else
        print_message "خطا در تنظیم دسترسی‌ها" "$RED"
        exit 1
    fi
}

# تابع بروزرسانی دیتابیس
update_database() {
    print_message "در حال بروزرسانی دیتابیس..." "$YELLOW"
    
    php install/update.php
    
    if [ $? -eq 0 ]; then
        print_message "دیتابیس با موفقیت بروزرسانی شد" "$GREEN"
    else
        print_message "خطا در بروزرسانی دیتابیس" "$RED"
        exit 1
    fi
}

# تابع اصلی بروزرسانی
update() {
    check_root
    backup_files
    update_code
    update_dependencies
    clear_cache
    set_permissions
    update_database
    
    print_message "بروزرسانی با موفقیت انجام شد!" "$GREEN"
}

# اجرای تابع بروزرسانی
update 