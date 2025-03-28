<?php
// تنظیمات اصلی
define('BOT_TOKEN', ''); // توکن ربات تلگرام
define('ADMIN_ID', ''); // آیدی عددی ادمین
define('DOMAIN', ''); // دامنه پروژه

// تنظیمات دیتابیس
define('DB_HOST', 'localhost');
define('DB_USER', 'xui_manager');
define('DB_PASS', '');
define('DB_NAME', 'xui_manager');

// تنظیمات پنل XUI
define('XUI_PANEL_URL', '');
define('XUI_PANEL_USER', '');
define('XUI_PANEL_PASS', '');

// تنظیمات پرداخت
define('PAYMENT_GATEWAY', 'zarinpal'); // zarinpal, nextpay, etc.
define('PAYMENT_MERCHANT', ''); // مرچنت کد درگاه پرداخت

// تنظیمات سیستم
define('TIMEZONE', 'Asia/Tehran');
define('DEBUG_MODE', false);
define('BACKUP_PATH', __DIR__ . '/backups/');
define('CACHE_PATH', __DIR__ . '/cache/');

// تنظیمات امنیتی
define('SECURE_SESSION', true);
define('SESSION_LIFETIME', 3600); // 1 ساعت
define('MAX_LOGIN_ATTEMPTS', 5);

// تنظیمات محدودیت‌ها
define('DEFAULT_TRAFFIC_LIMIT', 100); // گیگابایت
define('DEFAULT_TIME_LIMIT', 30); // روز
define('DEFAULT_DEVICE_LIMIT', 1); // تعداد دستگاه

// تنظیمات کرون جاب
define('CRON_ENABLED', true);
define('BACKUP_CRON', '0 0 * * *'); // هر روز در ساعت 00:00
define('CLEANUP_CRON', '0 1 * * *'); // هر روز در ساعت 01:00

// تنظیمات لاگ
define('LOG_ENABLED', true);
define('LOG_PATH', __DIR__ . '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// تنظیمات ایمیل
define('SMTP_ENABLED', false);
define('SMTP_HOST', '');
define('SMTP_PORT', 587);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', ''); 