<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("خطا در اتصال به دیتابیس: " . $e->getMessage());
}

// ایجاد جدول کاربران
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
    is_admin BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول users با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول users: " . $e->getMessage() . "\n";
}

// ایجاد جدول پکیج‌ها
$sql = "CREATE TABLE IF NOT EXISTS packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    traffic_limit INT NOT NULL,
    time_limit INT NOT NULL,
    device_limit INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول packages با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول packages: " . $e->getMessage() . "\n";
}

// ایجاد جدول اشتراک‌ها
$sql = "CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP,
    traffic_used BIGINT DEFAULT 0,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (package_id) REFERENCES packages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول subscriptions با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول subscriptions: " . $e->getMessage() . "\n";
}

// ایجاد جدول تراکنش‌ها
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    package_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    transaction_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (package_id) REFERENCES packages(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول transactions با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول transactions: " . $e->getMessage() . "\n";
}

// ایجاد جدول تنظیمات
$sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول settings با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول settings: " . $e->getMessage() . "\n";
}

// ایجاد جدول لاگ‌ها
$sql = "CREATE TABLE IF NOT EXISTS logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;";

try {
    $pdo->exec($sql);
    echo "جدول logs با موفقیت ایجاد شد.\n";
} catch(PDOException $e) {
    echo "خطا در ایجاد جدول logs: " . $e->getMessage() . "\n";
}

echo "\nتمام جداول با موفقیت ایجاد شدند.\n"; 