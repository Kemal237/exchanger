<?php
// confirm-order.php — создание заявки с проверкой и вычетом резерва

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

session_start();

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
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
    header('Location: profile.php#orders-table');   // ← автопрокрутка до таблицы заявок
    exit;
}

// Проверка резерва
if (!hasEnoughReserve($get_currency, $amount_get)) {
    $_SESSION['error'] = 'Недостаточно резерва по валюте ' . htmlspecialchars($get_currency);
    header('Location: profile.php#orders-table');   // ← автопрокрутка
    exit;
}

// Остальной код создания заявки
$give_currency = $_POST['give_currency'] ?? '';
$amount_give   = floatval($_POST['amount_give'] ?? 0);
$get_currency  = $_POST['get_currency'] ?? '';
$amount_get    = floatval($_POST['amount_get'] ?? 0);
$telegram      = $_POST['telegram'] ?? '';

if ($amount_give <= 0 || $amount_get <= 0 || empty($telegram)) {
    $_SESSION['error'] = 'Некорректные данные для создания заявки';
    header('Location: profile.php#orders-table');   // ← автопрокрутка
    exit;
}

$user_id = $_SESSION['user_id'];

$order_id = 'ORD-' . time() . '-' . rand(1000, 9999);

$stmt = $pdo->prepare("
    INSERT INTO orders 
    (id, user_id, give_currency, amount_give, get_currency, amount_get, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
");
$stmt->execute([$order_id, $user_id, $give_currency, $amount_give, $get_currency, $amount_get]);

updateReserve($get_currency, $amount_get);

$_SESSION['highlight_order'] = $order_id;
$_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка успешно создана! Номер: $order_id"];

header('Location: profile.php#orders-table');   // ← автопрокрутка
exit;
?>