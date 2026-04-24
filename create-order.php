<?php
// confirm-order.php — финальное создание заявки

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

session_start();

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
    $_SESSION['error'] = 'Некорректные данные';
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Генерируем номер заявки
$order_id = generateOrderId($pdo);

// Создаём заявку с явным статусом 'new'
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (id, user_id, give_currency, amount_give, get_currency, amount_get, telegram, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, 'new', NOW())
");
$stmt->execute([$order_id, $user_id, $give_currency, $amount_give, $get_currency, $amount_get, $telegram]);

$_SESSION['success'] = "Заявка успешно создана! Номер: $order_id";
header('Location: profile.php');
exit;
?>