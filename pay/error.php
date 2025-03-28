<?php
$message = $_GET['message'] ?? 'خطا در پردازش پرداخت';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطا در پرداخت</title>
    <link rel="stylesheet" href="../assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .error-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .error-icon {
            color: #f44336;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .error-title {
            color: #f44336;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: #666;
            margin-bottom: 2rem;
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
        
        .btn-retry {
            display: inline-block;
            background-color: #f44336;
            color: white;
            padding: 1rem 2rem;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 1rem;
            margin-right: 1rem;
            transition: background-color 0.3s;
        }
        
        .btn-retry:hover {
            background-color: #d32f2f;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <i class="fas fa-times-circle error-icon"></i>
        <h1 class="error-title">خطا در پرداخت</h1>
        <p class="error-message"><?php echo htmlspecialchars($message); ?></p>
        
        <div>
            <a href="javascript:history.back()" class="btn-retry">تلاش مجدد</a>
            <a href="../index.html" class="btn-home">بازگشت به صفحه اصلی</a>
        </div>
    </div>
</body>
</html> 