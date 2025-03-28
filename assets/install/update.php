<?php
require_once '../config.php';

// تابع بررسی دسترسی root
function checkRoot() {
    if (posix_getuid() !== 0) {
        die("لطفاً با دسترسی root اجرا کنید.\n");
    }
}

// تابع پشتیبان‌گیری از فایل‌ها
function backupFiles() {
    echo "در حال پشتیبان‌گیری از فایل‌ها...\n";
    
    $backupDir = BACKUP_PATH . 'updates/';
    if (!file_exists($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    $date = date('Y-m-d_H-i-s');
    $backupFile = $backupDir . "backup_$date.tar.gz";
    
    $cmd = "tar -czf $backupFile . --exclude='vendor' --exclude='node_modules' --exclude='cache/*' --exclude='backups/*'";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        die("خطا در پشتیبان‌گیری از فایل‌ها.\n");
    }
    
    echo "پشتیبان‌گیری با موفقیت انجام شد.\n";
}

// تابع بروزرسانی کد
function updateCode() {
    echo "در حال بروزرسانی کد...\n";
    
    $cmd = "git pull origin main";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        die("خطا در بروزرسانی کد.\n");
    }
    
    echo "کد با موفقیت بروزرسانی شد.\n";
}

// تابع بروزرسانی وابستگی‌ها
function updateDependencies() {
    echo "در حال بروزرسانی وابستگی‌ها...\n";
    
    $cmd = "composer update --no-dev";
    exec($cmd, $output, $returnVar);
    
    if ($returnVar !== 0) {
        die("خطا در بروزرسانی وابستگی‌ها.\n");
    }
    
    echo "وابستگی‌ها با موفقیت بروزرسانی شدند.\n";
}

// تابع پاکسازی کش
function clearCache() {
    echo "در حال پاکسازی کش...\n";
    
    $cacheDir = CACHE_PATH;
    if (file_exists($cacheDir)) {
        array_map('unlink', glob("$cacheDir/*.*"));
    }
    
    echo "کش با موفقیت پاکسازی شد.\n";
}

// تابع تنظیم دسترسی‌ها
function setPermissions() {
    echo "در حال تنظیم دسترسی‌ها...\n";
    
    $commands = [
        "chmod -R 755 .",
        "chmod -R 777 cache/ backups/ logs/"
    ];
    
    foreach ($commands as $cmd) {
        exec($cmd, $output, $returnVar);
        if ($returnVar !== 0) {
            die("خطا در تنظیم دسترسی‌ها.\n");
        }
    }
    
    echo "دسترسی‌ها با موفقیت تنظیم شدند.\n";
}

// تابع بروزرسانی دیتابیس
function updateDatabase() {
    echo "در حال بروزرسانی دیتابیس...\n";
    
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
            DB_USER,
            DB_PASS,
            array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // اجرای فایل‌های SQL بروزرسانی
        $sqlFiles = glob(__DIR__ . '/sql/*.sql');
        foreach ($sqlFiles as $file) {
            $sql = file_get_contents($file);
            $pdo->exec($sql);
        }
        
        echo "دیتابیس با موفقیت بروزرسانی شد.\n";
    } catch(PDOException $e) {
        die("خطا در بروزرسانی دیتابیس: " . $e->getMessage() . "\n");
    }
}

// تابع اصلی بروزرسانی
function update() {
    checkRoot();
    backupFiles();
    updateCode();
    updateDependencies();
    clearCache();
    setPermissions();
    updateDatabase();
    
    echo "\nبروزرسانی با موفقیت انجام شد!\n";
}

// اجرای بروزرسانی
update(); 