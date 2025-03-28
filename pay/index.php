<?php
require_once '../config.php';

// دریافت اطلاعات پکیج
$package = $_GET['package'] ?? '';
$packages = [
    'basic' => [
        'name' => 'پکیج پایه',
        'price' => 50000,
        'traffic' => 10,
        'time' => 30,
        'devices' => 1
    ],
    'pro' => [
        'name' => 'پکیج حرفه‌ای',
        'price' => 100000,
        'traffic' => 50,
        'time' => 30,
        'devices' => 3
    ],
    'unlimited' => [
        'name' => 'پکیج نامحدود',
        'price' => 200000,
        'traffic' => -1,
        'time' => 30,
        'devices' => 5
    ]
];

if (!isset($packages[$package])) {
    header('Location: ../index.html');
    exit;
}

$selectedPackage = $packages[$package];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پرداخت - <?php echo $selectedPackage['name']; ?></title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        
        .package-details {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .package-details h2 {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .package-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .feature-item i {
            color: var(--primary-color);
        }
        
        .payment-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 500;
        }
        
        .form-group input {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .payment-method {
            padding: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-method:hover {
            border-color: var(--primary-color);
        }
        
        .payment-method.selected {
            border-color: var(--primary-color);
            background-color: var(--light-gray);
        }
        
        .payment-method img {
            height: 30px;
            margin-bottom: 0.5rem;
        }
        
        .total-price {
            text-align: left;
            font-size: 1.2rem;
            font-weight: 500;
            margin: 1rem 0;
        }
        
        .btn-pay {
            background-color: var(--primary-color);
            color: white;
            padding: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.1rem;
            transition: background-color 0.3s;
        }
        
        .btn-pay:hover {
            background-color: #357abd;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="package-details">
            <h2><?php echo $selectedPackage['name']; ?></h2>
            <div class="price"><?php echo number_format($selectedPackage['price']); ?> تومان</div>
            
            <div class="package-features">
                <div class="feature-item">
                    <i class="fas fa-database"></i>
                    <span><?php echo $selectedPackage['traffic'] == -1 ? 'ترافیک نامحدود' : $selectedPackage['traffic'] . ' گیگابایت'; ?></span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <span><?php echo $selectedPackage['time']; ?> روز</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-mobile-alt"></i>
                    <span><?php echo $selectedPackage['devices']; ?> دستگاه همزمان</span>
                </div>
            </div>
        </div>
        
        <form class="payment-form" action="process.php" method="POST">
            <input type="hidden" name="package" value="<?php echo $package; ?>">
            <input type="hidden" name="price" value="<?php echo $selectedPackage['price']; ?>">
            
            <div class="form-group">
                <label for="name">نام و نام خانوادگی</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">ایمیل</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">شماره موبایل</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label>روش پرداخت</label>
                <div class="payment-methods">
                    <div class="payment-method" data-method="zarinpal">
                        <img src="images/zarinpal.png" alt="زرین‌پال">
                        <div>زرین‌پال</div>
                    </div>
                    <div class="payment-method" data-method="nextpay">
                        <img src="images/nextpay.png" alt="نکست‌پی">
                        <div>نکست‌پی</div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="payment_method" required>
            </div>
            
            <div class="total-price">
                مبلغ قابل پرداخت: <?php echo number_format($selectedPackage['price']); ?> تومان
            </div>
            
            <button type="submit" class="btn-pay">پرداخت و فعال‌سازی</button>
        </form>
    </div>
    
    <script>
        // انتخاب روش پرداخت
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', () => {
                document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                method.classList.add('selected');
                document.getElementById('payment_method').value = method.dataset.method;
            });
        });
        
        // اعتبارسنجی فرم
        document.querySelector('.payment-form').addEventListener('submit', (e) => {
            const paymentMethod = document.getElementById('payment_method').value;
            if (!paymentMethod) {
                e.preventDefault();
                alert('لطفاً یک روش پرداخت را انتخاب کنید.');
            }
        });
    </script>
</body>
</html> 