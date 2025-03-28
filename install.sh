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

# تابع بررسی سیستم عامل
check_os() {
    if [ -f /etc/os-release ]; then
        . /etc/os-release
        OS=$NAME
    else
        print_message "سیستم عامل پشتیبانی نمی‌شود" "$RED"
        exit 1
    fi
}

# تابع نصب پکیج‌های مورد نیاز
install_requirements() {
    print_message "در حال نصب پکیج‌های مورد نیاز..." "$YELLOW"
    
    if [ "$OS" = "Ubuntu" ] || [ "$OS" = "Debian GNU/Linux" ]; then
        apt update
        apt install -y php php-mysql php-gd php-mbstring mysql-server curl unzip
    else
        print_message "سیستم عامل پشتیبانی نمی‌شود" "$RED"
        exit 1
    fi
}

# تابع دریافت تنظیمات از کاربر
get_settings() {
    print_message "لطفاً اطلاعات زیر را وارد کنید:" "$YELLOW"
    
    read -p "توکن ربات تلگرام: " BOT_TOKEN
    read -p "آیدی عددی ادمین: " ADMIN_ID
    read -p "دامنه پروژه: " DOMAIN
    read -p "نام کاربری دیتابیس: " DB_USER
    read -p "رمز عبور دیتابیس: " DB_PASS
    read -p "آدرس پنل XUI: " XUI_PANEL_URL
    read -p "نام کاربری پنل XUI: " XUI_PANEL_USER
    read -p "رمز عبور پنل XUI: " XUI_PANEL_PASS
    read -p "مرچنت کد درگاه پرداخت: " PAYMENT_MERCHANT
}

# تابع ایجاد دیتابیس
create_database() {
    print_message "در حال ایجاد دیتابیس..." "$YELLOW"
    
    mysql -e "CREATE DATABASE IF NOT EXISTS xui_manager;"
    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT ALL PRIVILEGES ON xui_manager.* TO '$DB_USER'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
}

# تابع تنظیم دسترسی‌ها
set_permissions() {
    print_message "در حال تنظیم دسترسی‌ها..." "$YELLOW"
    
    chmod -R 755 .
    chmod -R 777 cache/ backups/ logs/
}

# تابع نصب کرون جاب
setup_cron() {
    print_message "در حال تنظیم کرون جاب..." "$YELLOW"
    
    (crontab -l 2>/dev/null | grep -v "backup.sh") | crontab -
    (crontab -l 2>/dev/null; echo "0 0 * * * /bin/bash $(pwd)/backup.sh") | crontab -
}

# تابع نصب کامل
install() {
    check_root
    check_os
    install_requirements
    get_settings
    
    # ایجاد فایل config.php
    sed -i "s/define('BOT_TOKEN', '');/define('BOT_TOKEN', '$BOT_TOKEN');/" config.php
    sed -i "s/define('ADMIN_ID', '');/define('ADMIN_ID', '$ADMIN_ID');/" config.php
    sed -i "s/define('DOMAIN', '');/define('DOMAIN', '$DOMAIN');/" config.php
    sed -i "s/define('DB_USER', '');/define('DB_USER', '$DB_USER');/" config.php
    sed -i "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" config.php
    sed -i "s/define('XUI_PANEL_URL', '');/define('XUI_PANEL_URL', '$XUI_PANEL_URL');/" config.php
    sed -i "s/define('XUI_PANEL_USER', '');/define('XUI_PANEL_USER', '$XUI_PANEL_USER');/" config.php
    sed -i "s/define('XUI_PANEL_PASS', '');/define('XUI_PANEL_PASS', '$XUI_PANEL_PASS');/" config.php
    sed -i "s/define('PAYMENT_MERCHANT', '');/define('PAYMENT_MERCHANT', '$PAYMENT_MERCHANT');/" config.php
    
    create_database
    set_permissions
    setup_cron
    
    print_message "نصب با موفقیت انجام شد!" "$GREEN"
}

# تابع بروزرسانی
update() {
    print_message "در حال بروزرسانی..." "$YELLOW"
    git pull
    print_message "بروزرسانی با موفقیت انجام شد!" "$GREEN"
}

# تابع حذف
uninstall() {
    print_message "در حال حذف پروژه..." "$YELLOW"
    
    # حذف دیتابیس
    mysql -e "DROP DATABASE IF EXISTS xui_manager;"
    
    # حذف کرون جاب
    crontab -l | grep -v "backup.sh" | crontab -
    
    # حذف فایل‌ها
    cd ..
    rm -rf xui-manager
    
    print_message "حذف با موفقیت انجام شد!" "$GREEN"
}

# منوی اصلی
show_menu() {
    print_message "=== منوی نصب XUI Panel Manager ===" "$YELLOW"
    print_message "1) نصب کامل" "$GREEN"
    print_message "2) بروزرسانی پروژه" "$GREEN"
    print_message "3) حذف کامل" "$RED"
    print_message "4) خروج" "$YELLOW"
    
    read -p "لطفاً یک گزینه را انتخاب کنید: " choice
    
    case $choice in
        1) install ;;
        2) update ;;
        3) uninstall ;;
        4) exit 0 ;;
        *) print_message "گزینه نامعتبر!" "$RED" ;;
    esac
}

# اجرای منو
show_menu 