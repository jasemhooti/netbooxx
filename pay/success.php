<?php
require_once '../config.php';

$orderId = $_GET['order_id'] ?? '';

if (empty($orderId)) {
    header('Location: ../index.html');
    exit;
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

// دریافت اطلاعات تراکنش
try {
    $stmt = $pdo->prepare("
        SELECT t.*, p.name as package_name, p.traffic_limit, p.time_limit
        FROM transactions t
        JOIN packages p ON t.package_id = p.id
        WHERE t.id = ?
    ");
    $stmt->execute([$orderId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("خطا در دریافت اطلاعات تراکنش: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت موفق</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            color: #4CAF50;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .success-title {
            color: #4CAF50;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .transaction-details {
            margin: 2rem 0;
            padding: 1rem;
            background: var(--light-gray);
            border-radius: 5px;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .btn-home {
            display: inline-block;
            background-color: var(--primary-color);
            color: white;
            padding: 1rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-home:hover {
            background-color: #357abd;
        }
        
        .qr-code {
            margin: 2rem 0;
        }
        
        .qr-code img {
            max-width: 200px;
            border: 1px solid #ddd;
            padding: 1rem;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1 class="success-title">پرداخت با موفقیت انجام شد</h1>
        
        <div class="transaction-details">
            <div class="detail-item">
                <span>شماره تراکنش:</span>
                <span><?php echo $transaction['id']; ?></span>
            </div>
            <div class="detail-item">
                <span>پکیج:</span>
                <span><?php echo $transaction['package_name']; ?></span>
            </div>
            <div class="detail-item">
                <span>مبلغ:</span>
                <span><?php echo number_format($transaction['amount']); ?> تومان</span>
            </div>
            <div class="detail-item">
                <span>حجم ترافیک:</span>
                <span><?php echo $transaction['traffic_limit'] == -1 ? 'نامحدود' : $transaction['traffic_limit'] . ' گیگابایت'; ?></span>
            </div>
            <div class="detail-item">
                <span>مدت زمان:</span>
                <span><?php echo $transaction['time_limit']; ?> روز</span>
            </div>
        </div>
        
        <div class="qr-code">
            <h3>QR Code اشتراک</h3>
            <?php
            require_once '../phpqrcode/qrlib.php';
            
            $subscriptionData = json_encode([
                'user_id' => $transaction['user_id'],
                'package_id' => $transaction['package_id'],
                'expire_date' => date('Y-m-d', strtotime('+30 days'))
            ]);
            
            QRcode::png($subscriptionData, false, QR_ECLEVEL_L, 10);
            ?>
        </div>
        
        <a href="../index.html" class="btn-home">بازگشت به صفحه اصلی</a>
    </div>
    
    <script>
        // ذخیره QR Code
        window.onload = function() {
            const qrCode = document.querySelector('.qr-code img');
            if (qrCode) {
                qrCode.addEventListener('click', function() {
                    const link = document.createElement('a');
                    link.download = 'subscription-qr.png';
                    link.href = qrCode.src;
                    link.click();
                });
            }
        };
    </script>
</body>
</html> 