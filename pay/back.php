 <?php
require_once '../config.php';

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

// دریافت پارامترها از درگاه پرداخت
$authority = $_GET['Authority'] ?? '';
$status = $_GET['Status'] ?? '';
$refId = $_GET['RefID'] ?? '';

// بررسی وضعیت پرداخت
if ($status == 'OK') {
    // تابع بررسی پرداخت زرین‌پال
    function verifyZarinpalPayment($authority, $amount) {
        $merchant = PAYMENT_MERCHANT;
        
        $data = array(
            'MerchantID' => $merchant,
            'Authority' => $authority,
            'Amount' => $amount
        );
        
        $jsonData = json_encode($data);
        $ch = curl_init('https://sandbox.zarinpal.com/pg/rest/WebGate/Verification');
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
        return json_decode($result, true);
    }
    
    // تابع بررسی پرداخت نکست‌پی
    function verifyNextpayPayment($transId) {
        $merchant = PAYMENT_MERCHANT;
        
        $data = array(
            'api_key' => $merchant,
            'trans_id' => $transId
        );
        
        $jsonData = json_encode($data);
        $ch = curl_init('https://nextpay.org/nx/gateway/verify');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData)
        ));
        
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
    
    // دریافت اطلاعات تراکنش
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, p.traffic_limit, p.time_limit, p.device_limit
            FROM transactions t
            JOIN packages p ON t.package_id = p.id
            WHERE t.id = ?
        ");
        $stmt->execute([$authority]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            header('Location: error.php?message=تراکنش یافت نشد');
            exit;
        }
        
        // بررسی پرداخت بر اساس روش پرداخت
        $verifyResult = false;
        if ($transaction['payment_method'] == 'zarinpal') {
            $verifyResult = verifyZarinpalPayment($authority, $transaction['amount']);
        } elseif ($transaction['payment_method'] == 'nextpay') {
            $verifyResult = verifyNextpayPayment($refId);
        }
        
        if ($verifyResult && ($verifyResult['Status'] == 100 || $verifyResult['code'] == 0)) {
            // بروزرسانی وضعیت تراکنش
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'completed', 
                    transaction_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$refId, $authority]);
            
            // ایجاد کاربر جدید
            $stmt = $pdo->prepare("
                INSERT INTO users (username, email, phone, status)
                VALUES (?, ?, ?, 'active')
            ");
            $stmt->execute([
                $transaction['username'],
                $transaction['email'],
                $transaction['phone']
            ]);
            $userId = $pdo->lastInsertId();
            
            // ایجاد اشتراک
            $stmt = $pdo->prepare("
                INSERT INTO subscriptions (
                    user_id, package_id, start_date, end_date,
                    traffic_used, status
                )
                VALUES (?, ?, CURRENT_TIMESTAMP, DATE_ADD(CURRENT_TIMESTAMP, INTERVAL ? DAY), 0, 'active')
            ");
            $stmt->execute([
                $userId,
                $transaction['package_id'],
                $transaction['time_limit']
            ]);
            
            // ثبت لاگ
            $stmt = $pdo->prepare("
                INSERT INTO logs (user_id, action, description)
                VALUES (?, 'subscription_created', 'اشتراک جدید ایجاد شد')
            ");
            $stmt->execute([$userId]);
            
            // انتقال به صفحه موفقیت
            header('Location: success.php?order_id=' . $authority);
            exit;
        } else {
            // بروزرسانی وضعیت تراکنش به ناموفق
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'failed',
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$authority]);
            
            header('Location: error.php?message=پرداخت ناموفق بود');
            exit;
        }
    } catch(PDOException $e) {
        header('Location: error.php?message=خطا در پردازش تراکنش');
        exit;
    }
} else {
    // پرداخت لغو شده
    header('Location: error.php?message=پرداخت لغو شد');
    exit;
} 
