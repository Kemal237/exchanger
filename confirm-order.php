<?php
// confirm-order.php — создание заявки с проверкой и вычетом резерва

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

// session_start() уже вызывается в auth.php — не дублируем!

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$give_currency = $_POST['give_currency'] ?? '';
$amount_give   = floatval($_POST['amount_give'] ?? 0);
$get_currency  = $_POST['get_currency'] ?? '';
$amount_get    = floatval($_POST['amount_get'] ?? 0);
$telegram      = $_POST['telegram'] ?? '';

if ($amount_give <= 0 || $amount_get <= 0 || empty($telegram)) {
    $_SESSION['error'] = 'Некорректные данные для создания заявки';
    header('Location: index.php');
    exit;
}

// Проверка наличия резерва
if (!hasEnoughReserve($get_currency, $amount_get)) {
    $_SESSION['error'] = 'Недостаточно резерва по валюте ' . htmlspecialchars($get_currency);
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Генерируем номер заявки
$order_id = 'ORD-' . time() . '-' . rand(1000, 9999);

// Создаём заявку БЕЗ поля telegram (его нет в таблице orders)
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (id, user_id, give_currency, amount_give, get_currency, amount_get, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
");
$stmt->execute([$order_id, $user_id, $give_currency, $amount_give, $get_currency, $amount_get]);

// Вычитаем из резерва получаемой валюты
updateReserve($get_currency, $amount_get);

// Сохраняем для подсветки в профиле
$_SESSION['highlight_order'] = $order_id;

$_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка успешно создана! Номер: $order_id"];

header('Location: profile.php');
exit;
?>