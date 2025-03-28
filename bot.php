<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class XUIBot {
    private $telegram;
    private $pdo;
    
    public function __construct() {
        try {
            $this->telegram = new Api(BOT_TOKEN);
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            die("خطا در راه‌اندازی ربات: " . $e->getMessage());
        }
    }
    
    public function handleUpdate($update) {
        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
            $text = $message['text'] ?? '';
            
            // بررسی دستورات
            switch ($text) {
                case '/start':
                    $this->handleStart($chatId);
                    break;
                case '/menu':
                    $this->showMainMenu($chatId);
                    break;
                case '/profile':
                    $this->showProfile($chatId);
                    break;
                case '/subscription':
                    $this->showSubscription($chatId);
                    break;
                case '/support':
                    $this->showSupport($chatId);
                    break;
                default:
                    $this->handleUnknownCommand($chatId);
            }
        }
    }
    
    private function handleStart($chatId) {
        $message = "به ربات مدیریت XUI خوش آمدید!\n\n";
        $message .= "برای مشاهده منوی اصلی از دستور /menu استفاده کنید.";
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
    
    private function showMainMenu($chatId) {
        $keyboard = [
            [
                ['text' => '👤 پروفایل', 'callback_data' => 'profile'],
                ['text' => '📱 اشتراک', 'callback_data' => 'subscription']
            ],
            [
                ['text' => '💳 خرید اشتراک', 'callback_data' => 'buy_subscription'],
                ['text' => '❓ پشتیبانی', 'callback_data' => 'support']
            ]
        ];
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'منوی اصلی:',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    
    private function showProfile($chatId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE telegram_id = ?");
        $stmt->execute([$chatId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $message = "👤 اطلاعات پروفایل:\n\n";
            $message .= "نام کاربری: {$user['username']}\n";
            $message .= "ایمیل: {$user['email']}\n";
            $message .= "تلفن: {$user['phone']}\n";
            $message .= "وضعیت: {$user['status']}\n";
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'کاربری یافت نشد. لطفاً ابتدا ثبت نام کنید.'
            ]);
        }
    }
    
    private function showSubscription($chatId) {
        $stmt = $this->pdo->prepare("
            SELECT s.*, p.name as package_name, p.traffic_limit, p.time_limit
            FROM subscriptions s
            JOIN packages p ON s.package_id = p.id
            WHERE s.user_id = (SELECT id FROM users WHERE telegram_id = ?)
            AND s.status = 'active'
        ");
        $stmt->execute([$chatId]);
        $subscription = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($subscription) {
            $message = "📱 اطلاعات اشتراک:\n\n";
            $message .= "پکیج: {$subscription['package_name']}\n";
            $message .= "حجم مصرف شده: {$subscription['traffic_used']} از {$subscription['traffic_limit']} گیگابایت\n";
            $message .= "تاریخ شروع: {$subscription['start_date']}\n";
            $message .= "تاریخ پایان: {$subscription['end_date']}\n";
            
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML'
            ]);
        } else {
            $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => 'شما اشتراک فعالی ندارید. برای خرید اشتراک از منوی اصلی استفاده کنید.'
            ]);
        }
    }
    
    private function showSupport($chatId) {
        $message = "❓ پشتیبانی\n\n";
        $message .= "برای ارتباط با پشتیبانی، لطفاً پیام خود را ارسال کنید.\n";
        $message .= "پشتیبانی در اسرع وقت پاسخگوی شما خواهد بود.";
        
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ]);
    }
    
    private function handleUnknownCommand($chatId) {
        $this->telegram->sendMessage([
            'chat_id' => $chatId,
            'text' => 'دستور نامعتبر است. لطفاً از منوی اصلی استفاده کنید.'
        ]);
    }
}

// دریافت آپدیت‌ها
$update = json_decode(file_get_contents('php://input'), true);
$bot = new XUIBot();
$bot->handleUpdate($update); 