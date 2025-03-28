<?php
require_once '../config.php';

// دریافت اطلاعات فرم
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$package = $_POST['package'] ?? '';
$price = $_POST['price'] ?? 0;
$paymentMethod = $_POST['payment_method'] ?? '';

// اعتبارسنجی داده‌ها
if (empty($name) || empty($email) || empty($phone) || empty($package) || empty($paymentMethod)) {
    die('لطفاً تمام فیلدها را پر کنید.');
}

// اتصال به دیتابیس
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

// ایجاد تراکنش
try {
    $stmt = $pdo->prepare("
        INSERT INTO transactions (user_id, package_id, amount, payment_method, status)
        VALUES (?, ?, ?, ?, 'pending')
    ");
    
    $stmt->execute([
        null, // user_id بعد از ثبت نام کاربر تنظیم می‌شود
        $package,
        $price,
        $paymentMethod
    ]);
    
    $transactionId = $pdo->lastInsertId();
} catch(PDOException $e) {
    die("خطا در ثبت تراکنش: " . $e->getMessage());
}

// پردازش پرداخت بر اساس روش انتخاب شده
switch ($paymentMethod) {
    case 'zarinpal':
        $result = processZarinpalPayment($transactionId, $price);
        break;
    case 'nextpay':
        $result = processNextpayPayment($transactionId, $price);
        break;
    default:
        die('روش پرداخت نامعتبر است.');
}

// تابع پردازش پرداخت زرین‌پال
function processZarinpalPayment($transactionId, $amount) {
    $merchant = PAYMENT_MERCHANT;
    $callback = DOMAIN . '/pay/back.php';
    $description = 'پرداخت اشتراک XUI';
    
    $data = array(
        'MerchantID' => $merchant,
        'Amount' => $amount,
        'CallbackURL' => $callback,
        'Description' => $description
    );
    
    $jsonData = json_encode($data);
    $ch = curl_init('https://sandbox.zarinpal.com/pg/rest/WebGate/Request');
    curl_setopt($ch, CURLOPT_USERAGENT, 'ZarinPal Rest Api v1');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    
    $result = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($result, true);
    
    if ($result['Status'] == 100) {
        header('Location: https://sandbox.zarinpal.com/pg/StartPay/' . $result['Authority']);
        exit;
    } else {
        die('خطا در اتصال به درگاه پرداخت. کد خطا: ' . $result['Status']);
    }
}

// تابع پردازش پرداخت نکست‌پی
function processNextpayPayment($transactionId, $amount) {
    $merchant = PAYMENT_MERCHANT;
    $callback = DOMAIN . '/pay/back.php';
    $description = 'پرداخت اشتراک XUI';
    
    $data = array(
        'api_key' => $merchant,
        'amount' => $amount,
        'callback_uri' => $callback,
        'order_id' => $transactionId,
        'description' => $description
    );
    
    $jsonData = json_encode($data);
    $ch = curl_init('https://nextpay.org/nx/gateway/token');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ));
    
    $result = curl_exec($ch);
    curl_close($ch);
    $result = json_decode($result, true);
    
    if ($result['code'] == -1) {
        header('Location: https://nextpay.org/nx/gateway/payment/' . $result['trans_id']);
        exit;
    } else {
        die('خطا در اتصال به درگاه پرداخت. کد خطا: ' . $result['code']);
    }
} 