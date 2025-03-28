<?php
require_once 'config.php';

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

// دریافت پارامترهای جستجو
$query = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$results = [];
$total = 0;

if (!empty($query)) {
    switch ($type) {
        case 'users':
            // جستجو در کاربران
            $stmt = $pdo->prepare("
                SELECT id, username, email, phone, status, created_at
                FROM users
                WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // تعداد کل نتایج
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM users
                WHERE username LIKE ? OR email LIKE ? OR phone LIKE ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            break;
            
        case 'transactions':
            // جستجو در تراکنش‌ها
            $stmt = $pdo->prepare("
                SELECT t.*, u.username, p.name as package_name
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN packages p ON t.package_id = p.id
                WHERE t.id LIKE ? OR u.username LIKE ? OR p.name LIKE ?
                ORDER BY t.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // تعداد کل نتایج
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM transactions t
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN packages p ON t.package_id = p.id
                WHERE t.id LIKE ? OR u.username LIKE ? OR p.name LIKE ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            break;
            
        case 'subscriptions':
            // جستجو در اشتراک‌ها
            $stmt = $pdo->prepare("
                SELECT s.*, u.username, p.name as package_name
                FROM subscriptions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN packages p ON s.package_id = p.id
                WHERE s.id LIKE ? OR u.username LIKE ? OR p.name LIKE ?
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $searchTerm = "%$query%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $perPage, $offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // تعداد کل نتایج
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM subscriptions s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN packages p ON s.package_id = p.id
                WHERE s.id LIKE ? OR u.username LIKE ? OR p.name LIKE ?
            ");
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            break;
    }
}

$totalPages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جستجو</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 100px auto;
            padding: 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .search-input {
            flex: 1;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .search-type {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }
        
        .search-button {
            background-color: var(--primary-color);
            color: white;
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            transition: background-color 0.3s;
        }
        
        .search-button:hover {
            background-color: #357abd;
        }
        
        .results-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        
        .result-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .result-item:last-child {
            border-bottom: none;
        }
        
        .result-title {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .result-details {
            color: #666;
            font-size: 0.9rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s;
        }
        
        .page-link:hover {
            background-color: var(--light-gray);
        }
        
        .page-link.active {
            background-color: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .no-results {
            text-align: center;
            color: #666;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="search-container">
        <form class="search-form" method="GET">
            <input type="text" name="q" class="search-input" value="<?php echo htmlspecialchars($query); ?>" placeholder="جستجو...">
            <select name="type" class="search-type">
                <option value="all" <?php echo $type == 'all' ? 'selected' : ''; ?>>همه</option>
                <option value="users" <?php echo $type == 'users' ? 'selected' : ''; ?>>کاربران</option>
                <option value="transactions" <?php echo $type == 'transactions' ? 'selected' : ''; ?>>تراکنش‌ها</option>
                <option value="subscriptions" <?php echo $type == 'subscriptions' ? 'selected' : ''; ?>>اشتراک‌ها</option>
            </select>
            <button type="submit" class="search-button">
                <i class="fas fa-search"></i> جستجو
            </button>
        </form>
        
        <div class="results-container">
            <?php if (empty($results)): ?>
                <div class="no-results">
                    نتیجه‌ای یافت نشد.
                </div>
            <?php else: ?>
                <?php foreach ($results as $result): ?>
                    <div class="result-item">
                        <?php if ($type == 'users'): ?>
                            <div class="result-title"><?php echo htmlspecialchars($result['username']); ?></div>
                            <div class="result-details">
                                <div>ایمیل: <?php echo htmlspecialchars($result['email']); ?></div>
                                <div>تلفن: <?php echo htmlspecialchars($result['phone']); ?></div>
                                <div>وضعیت: <?php echo htmlspecialchars($result['status']); ?></div>
                                <div>تاریخ ثبت نام: <?php echo date('Y-m-d H:i', strtotime($result['created_at'])); ?></div>
                            </div>
                        <?php elseif ($type == 'transactions'): ?>
                            <div class="result-title">تراکنش #<?php echo htmlspecialchars($result['id']); ?></div>
                            <div class="result-details">
                                <div>کاربر: <?php echo htmlspecialchars($result['username']); ?></div>
                                <div>پکیج: <?php echo htmlspecialchars($result['package_name']); ?></div>
                                <div>مبلغ: <?php echo number_format($result['amount']); ?> تومان</div>
                                <div>وضعیت: <?php echo htmlspecialchars($result['status']); ?></div>
                                <div>تاریخ: <?php echo date('Y-m-d H:i', strtotime($result['created_at'])); ?></div>
                            </div>
                        <?php elseif ($type == 'subscriptions'): ?>
                            <div class="result-title">اشتراک #<?php echo htmlspecialchars($result['id']); ?></div>
                            <div class="result-details">
                                <div>کاربر: <?php echo htmlspecialchars($result['username']); ?></div>
                                <div>پکیج: <?php echo htmlspecialchars($result['package_name']); ?></div>
                                <div>تاریخ شروع: <?php echo date('Y-m-d', strtotime($result['start_date'])); ?></div>
                                <div>تاریخ پایان: <?php echo date('Y-m-d', strtotime($result['end_date'])); ?></div>
                                <div>وضعیت: <?php echo htmlspecialchars($result['status']); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?q=<?php echo urlencode($query); ?>&type=<?php echo urlencode($type); ?>&page=<?php echo $i; ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 
