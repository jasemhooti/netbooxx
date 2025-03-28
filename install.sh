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

# تابع دریافت تنظیمات از کاربر
get_settings() {
    print_message "لطفاً اطلاعات زیر را وارد کنید:" "$YELLOW"
    
    read -p "توکن ربات تلگرام: " BOT_TOKEN
    read -p "آیدی عددی ادمین: " ADMIN_ID
    read -p "دامنه پروژه: " DOMAIN
    read -p "نام کاربری دیتابیس: " DB_USER
    read -p "رمز عبور دیتابیس: " DB_PASS
    read -p "مرچنت کد درگاه پرداخت: " PAYMENT_MERCHANT
}

# تابع نصب پکیج‌های مورد نیاز
install_requirements() {
    print_message "در حال نصب پکیج‌های مورد نیاز..." "$YELLOW"
    
    if [ "$OS" = "Ubuntu" ] || [ "$OS" = "Debian GNU/Linux" ]; then
        apt update
        apt install -y php php-mysql php-gd php-mbstring mysql-server nginx certbot python3-certbot-nginx curl unzip git composer
    else
        print_message "سیستم عامل پشتیبانی نمی‌شود" "$RED"
        exit 1
    fi
}

# تابع بررسی DNS
check_dns() {
    print_message "در حال بررسی تنظیمات DNS..." "$YELLOW"
    
    SERVER_IP=$(curl -s ifconfig.me)
    DOMAIN_IP=$(dig +short $DOMAIN)
    
    if [ "$SERVER_IP" != "$DOMAIN_IP" ]; then
        print_message "خطا: دامنه $DOMAIN به سرور شما اشاره نمی‌کند" "$RED"
        exit 1
    fi
    
    print_message "تنظیمات DNS صحیح است" "$GREEN"
}

# تابع نصب SSL
install_ssl() {
    print_message "در حال نصب SSL..." "$YELLOW"
    
    certbot --nginx -d $DOMAIN --non-interactive --agree-tos --email admin@$DOMAIN
    
    if [ $? -ne 0 ]; then
        print_message "خطا در نصب SSL" "$RED"
        exit 1
    fi
    
    print_message "SSL با موفقیت نصب شد" "$GREEN"
}

# تابع تنظیم Webhook تلگرام
setup_webhook() {
    print_message "در حال تنظیم Webhook تلگرام..." "$YELLOW"
    
    WEBHOOK_URL="https://$DOMAIN/bot.php"
    curl -F "url=$WEBHOOK_URL" https://api.telegram.org/bot$BOT_TOKEN/setWebhook
    
    if [ $? -ne 0 ]; then
        print_message "خطا در تنظیم Webhook" "$RED"
        exit 1
    fi
    
    print_message "Webhook با موفقیت تنظیم شد" "$GREEN"
}

# تابع تنظیم Nginx
setup_nginx() {
    print_message "در حال تنظیم Nginx..." "$YELLOW"
    
    cat > /etc/nginx/sites-available/$DOMAIN << EOF
server {
    listen 80;
    server_name $DOMAIN;
    root /var/www/html;
    index index.php index.html;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
EOF
    
    ln -s /etc/nginx/sites-available/$DOMAIN /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    nginx -t
    if [ $? -ne 0 ]; then
        print_message "خطا در تنظیمات Nginx" "$RED"
        exit 1
    fi
    
    systemctl restart nginx
    print_message "Nginx با موفقیت تنظیم شد" "$GREEN"
}

# تابع ایجاد دیتابیس
create_database() {
    print_message "در حال ایجاد دیتابیس..." "$YELLOW"
    
    mysql -e "CREATE DATABASE IF NOT EXISTS xui_manager;"
    mysql -e "CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';"
    mysql -e "GRANT ALL PRIVILEGES ON xui_manager.* TO '$DB_USER'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    print_message "دیتابیس با موفقیت ایجاد شد" "$GREEN"
}

# تابع تنظیم دسترسی‌ها
set_permissions() {
    print_message "در حال تنظیم دسترسی‌ها..." "$YELLOW"
    
    chown -R www-data:www-data .
    chmod -R 755 .
    chmod -R 777 cache/ backups/ logs/
    
    print_message "دسترسی‌ها با موفقیت تنظیم شدند" "$GREEN"
}

# تابع نصب کرون جاب
setup_cron() {
    print_message "در حال تنظیم کرون جاب..." "$YELLOW"
    
    (crontab -l 2>/dev/null | grep -v "backup.sh") | crontab -
    (crontab -l 2>/dev/null; echo "0 0 * * * /bin/bash $(pwd)/dbbackup.sh") | crontab -
    
    print_message "کرون جاب با موفقیت تنظیم شد" "$GREEN"
}

# تابع نصب کامل
install() {
    check_root
    check_os
    get_settings
    install_requirements
    check_dns
    setup_nginx
    install_ssl
    setup_webhook
    create_database
    set_permissions
    setup_cron
    
    # ایجاد فایل config.php
    sed -i "s/define('BOT_TOKEN', '');/define('BOT_TOKEN', '$BOT_TOKEN');/" config.php
    sed -i "s/define('ADMIN_ID', '');/define('ADMIN_ID', '$ADMIN_ID');/" config.php
    sed -i "s/define('DOMAIN', '');/define('DOMAIN', '$DOMAIN');/" config.php
    sed -i "s/define('DB_USER', '');/define('DB_USER', '$DB_USER');/" config.php
    sed -i "s/define('DB_PASS', '');/define('DB_PASS', '$DB_PASS');/" config.php
    sed -i "s/define('PAYMENT_MERCHANT', '');/define('PAYMENT_MERCHANT', '$PAYMENT_MERCHANT');/" config.php
    
    print_message "\nنصب با موفقیت انجام شد!" "$GREEN"
    print_message "\nآدرس پنل وب: https://$DOMAIN" "$GREEN"
    print_message "اطلاعات دیتابیس:" "$GREEN"
    print_message "نام دیتابیس: xui_manager" "$GREEN"
    print_message "نام کاربری: $DB_USER" "$GREEN"
    print_message "رمز عبور: $DB_PASS" "$GREEN"
    print_message "آدرس ربات: https://t.me/$(curl -s "https://api.telegram.org/bot$BOT_TOKEN/getMe" | grep -o '"username":"[^"]*"' | cut -d'"' -f4)" "$GREEN"
    print_message "وضعیت Webhook: فعال شد" "$GREEN"
}

# تابع حذف کامل
uninstall() {
    print_message "در حال حذف کامل پروژه..." "$YELLOW"
    
    # حذف دیتابیس
    mysql -e "DROP DATABASE IF EXISTS xui_manager;"
    mysql -e "DROP USER IF EXISTS '$DB_USER'@'localhost';"
    mysql -e "FLUSH PRIVILEGES;"
    
    # حذف تنظیمات Nginx
    rm -f /etc/nginx/sites-enabled/$DOMAIN
    rm -f /etc/nginx/sites-available/$DOMAIN
    systemctl restart nginx
    
    # حذف SSL
    certbot delete --cert-name $DOMAIN
    
    # حذف کرون جاب
    crontab -l | grep -v "backup.sh" | crontab -
    
    # حذف فایل‌ها
    cd ..
    rm -rf xui-manager
    
    print_message "حذف کامل با موفقیت انجام شد!" "$GREEN"
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
        2) php install/update.php ;;
        3) uninstall ;;
        4) exit 0 ;;
        *) print_message "گزینه نامعتبر!" "$RED" ;;
    esac
}

# اجرای منو
show_menu 
