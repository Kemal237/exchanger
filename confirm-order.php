<?php
// confirm-order.php — создание заявки с проверкой и вычетом резерва

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

function generateOrderId(PDO $pdo): string {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    do {
        $id = 'SW-';
        for ($i = 0; $i < 5; $i++) {
            $id .= $chars[random_int(0, 35)];
        }
        $exists = $pdo->prepare("SELECT 1 FROM orders WHERE id = ?");
        $exists->execute([$id]);
    } while ($exists->fetchColumn());
    return $id;
}

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// === ВАЛИДАЦИЯ ВХОДНЫХ ДАННЫХ ===
$give_currency = $_POST['give_currency'] ?? '';
$amount_give   = floatval($_POST['amount_give'] ?? 0);
$get_currency  = $_POST['get_currency'] ?? '';
$amount_get    = floatval($_POST['amount_get'] ?? 0);
$telegram      = $_POST['telegram'] ?? '';

if ($amount_give <= 0 || $amount_get <= 0 || empty($telegram)) {
    $_SESSION['error'] = 'Некорректные данные для создания заявки';
    header('Location: profile.php#orders-table');
    exit;
}

// === ПРОВЕРКА ЛИМИТА ЗАЯВОК (3 за 10 минут) ===
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM orders
    WHERE user_id = ?
    AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
");
$stmt->execute([$_SESSION['user_id']]);
$count = $stmt->fetchColumn();

if ($count >= 3) {
    $_SESSION['toast'] = [
        'type' => 'error',
        'message' => 'Вы уже создали 3 заявки за последние 10 минут. Попробуйте позже.'
    ];
    header('Location: profile.php#orders-table');
    exit;
}

// Нормализация: операции с резервами используют базовый ключ (без сети/метода)
$netToBase = [
    'USDT_TRC20' => 'USDT_TRC20', 'USDT_ERC20' => 'USDT_TRC20', 'USDT_BEP20' => 'USDT_TRC20',
    'USDC_TRC20' => 'USDC',       'USDC_ERC20' => 'USDC',
    'ETH'        => 'ETH',         'SOL'        => 'SOL',         'BTC'        => 'BTC',
    'RUB_SBP'    => 'RUB',        'RUB_CASH'   => 'RUB',         'RUB_CARD'   => 'RUB',
    'USD'        => 'USD',
];
$get_base = $netToBase[$get_currency] ?? $get_currency;

// === ПРОВЕРКА РЕЗЕРВА (по базовому ключу) ===
if (!hasEnoughReserve($get_base, $amount_get)) {
    $_SESSION['error'] = 'Недостаточно резерва по валюте ' . htmlspecialchars(currencyLabel($get_currency));
    header('Location: profile.php#orders-table');
    exit;
}

$user_id  = $_SESSION['user_id'];
$rate     = $amount_give > 0 ? round($amount_get / $amount_give, 8) : 0;
$order_id = generateOrderId($pdo);

$pdo->beginTransaction();
try {
    // В orders сохраняем полный ключ (RUB_SBP, USDT_ERC20 и т.д.) — для отображения сети
    $stmt = $pdo->prepare("
        INSERT INTO orders
        (id, user_id, give_currency, amount_give, get_currency, amount_get, rate, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())
    ");
    $stmt->execute([$order_id, $user_id, $give_currency, $amount_give, $get_currency, $amount_get, $rate]);

    // Резерв списываем по базовому ключу (RUB, не RUB_SBP)
    updateReserve($get_base, $amount_get);
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Ошибка создания заявки. Попробуйте ещё раз.';
    header('Location: profile.php#orders-table');
    exit;
}

$_SESSION['highlight_order'] = $order_id;
$_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка успешно создана! Номер: $order_id"];

header('Location: profile.php#orders-table');   // ← автопрокрутка
exit;
?>