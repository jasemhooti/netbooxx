<?php
require_once '../config.php';

// تابع بررسی دسترسی root
function checkRoot() {
    if (posix_getuid() !== 0) {
        die("لطفاً با دسترسی root اجرا کنید.\n");
    }
}

// تابع بررسی سیستم عامل
function checkOS() {
    if (!file_exists('/etc/os-release')) {
        die("سیستم عامل پشتیبانی نمی‌شود.\n");
    }
    
    $osInfo = parse_ini_file('/etc/os-release');
    $os = $osInfo['NAME'] ?? '';
    
    if (!in_array($os, ['Ubuntu', 'Debian GNU/Linux'])) {
        die("سیستم عامل پشتیبانی نمی‌شود.\n");
    }
    
    return $os;
}

// تابع نصب پکیج‌های مورد نیاز
function installRequirements($os) {
    echo "در حال نصب پکیج‌های مورد نیاز...\n";
    
    $packages = [
        'php', 'php-mysql', 'php-gd', 'php-mbstring',
        'mysql-server', 'nginx', 'certbot', 'python3-certbot-nginx',
        'curl', 'unzip'
    ];
    
    $cmd = "apt update && apt install -y " . implode(' ', $packages);
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        die("خطا در نصب پکیج‌ها.\n");
    }
    
    echo "پکیج‌ها با موفقیت نصب شدند.\n";
}

// تابع بررسی DNS
function checkDNS($domain) {
    echo "در حال بررسی تنظیمات DNS...\n";
    
    $ip = gethostbyname($domain);
    $serverIP = $_SERVER['SERVER_ADDR'];
    
    if ($ip !== $serverIP) {
        die("خطا: دامنه $domain به سرور شما اشاره نمی‌کند.\n");
    }
    
    echo "تنظیمات DNS صحیح است.\n";
}

// تابع نصب SSL
function installSSL($domain) {
    echo "در حال نصب SSL...\n";
    
    $cmd = "certbot --nginx -d $domain --non-interactive --agree-tos --email admin@$domain";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        die("خطا در نصب SSL.\n");
    }
    
    echo "SSL با موفقیت نصب شد.\n";
}

// تابع تنظیم Webhook تلگرام
function setupTelegramWebhook($botToken, $domain) {
    echo "در حال تنظیم Webhook تلگرام...\n";
    
    $url = "https://api.telegram.org/bot$botToken/setWebhook";
    $data = [
        'url' => "https://$domain/bot.php",
        'max_connections' => 40
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        die("خطا در تنظیم Webhook تلگرام.\n");
    }
    
    echo "Webhook تلگرام با موفقیت تنظیم شد.\n";
}

// تابع تنظیم Nginx
function setupNginx($domain) {
    echo "در حال تنظیم Nginx...\n";
    
    $config = "
server {
    listen 80;
    server_name $domain;
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
";
    
    file_put_contents("/etc/nginx/sites-available/$domain", $config);
    symlink("/etc/nginx/sites-available/$domain", "/etc/nginx/sites-enabled/");
    
    exec("nginx -t", $output, $returnVar);
    if ($returnVar !== 0) {
        die("خطا در تنظیمات Nginx.\n");
    }
    
    exec("systemctl restart nginx", $output, $returnVar);
    if ($returnVar !== 0) {
        die("خطا در راه‌اندازی مجدد Nginx.\n");
    }
    
    echo "Nginx با موفقیت تنظیم شد.\n";
}

// تابع اصلی نصب
function install() {
    checkRoot();
    $os = checkOS();
    installRequirements($os);
    
    $domain = DOMAIN;
    $botToken = BOT_TOKEN;
    
    checkDNS($domain);
    setupNginx($domain);
    installSSL($domain);
    setupTelegramWebhook($botToken, $domain);
    
    echo "\nنصب با موفقیت انجام شد!\n\n";
    echo "آدرس پنل وب: https://$domain\n";
    echo "اطلاعات دیتابیس:\n";
    echo "نام دیتابیس: " . DB_NAME . "\n";
    echo "نام کاربری: " . DB_USER . "\n";
    echo "رمز عبور: " . DB_PASS . "\n";
    echo "آدرس ربات: https://t.me/" . explode(':', $botToken)[0] . "\n";
    echo "وضعیت Webhook: فعال شد\n";
}

// اجرای نصب
install(); 