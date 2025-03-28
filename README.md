# XUI Panel Manager

یک ابزار قدرتمند برای مدیریت پنل‌های XUI با قابلیت‌های پیشرفته

## ویژگی‌ها

- مدیریت کامل کاربران XUI Panel
- سیستم پرداخت خودکار
- تولید QR Code و لینک اشتراک
- محدودیت‌های حجم، زمان و تعداد دستگاه
- پشتیبان‌گیری خودکار
- رابط کاربری ساده و کاربرپسند
- نصب و بروزرسانی خودکار

## پیش‌نیازها

- سرور Ubuntu/Debian یا محیط Local
- PHP 7.4+
- MySQL یا MariaDB
- Curl
- دسترسی Shell و Sudo

## نصب سریع

برای نصب پروژه، دستور زیر را اجرا کنید:

```bash
bash <(curl -s https://raw.githubusercontent.com/YOUR_GITHUB_USERNAME/XUI-Panel-Manager/main/install.sh)
```

## گزینه‌های نصب

1. نصب کامل
2. بروزرسانی پروژه
3. حذف کامل

## تنظیمات

پس از نصب، فایل `config.php` را ویرایش کنید:

```php
define('BOT_TOKEN', 'YOUR_BOT_TOKEN');
define('ADMIN_ID', 'YOUR_ADMIN_ID');
define('DOMAIN', 'your-domain.com');
```

## مستندات

برای اطلاعات بیشتر، لطفاً به [مستندات کامل](docs/README.md) مراجعه کنید.

## لایسنس

این پروژه تحت لایسنس MIT منتشر شده است. برای اطلاعات بیشتر به فایل [LICENSE](LICENSE) مراجعه کنید. 