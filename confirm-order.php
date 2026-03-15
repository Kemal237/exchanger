<?php
// confirm-order.php — финальное создание заявки после подтверждения

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

// session_start() НЕ НУЖЕН — он уже в auth.php

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Получаем данные из формы подтверждения (order.php)
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

$user_id = $_SESSION['user_id'];

// Генерируем уникальный номер заявки
$order_id = 'ORD-' . time() . '-' . rand(1000, 9999);

// Создаём заявку БЕЗ поля telegram (потому что его нет в таблице orders)
$stmt = $pdo->prepare("
    INSERT INTO orders 
    (id, user_id, give_currency, amount_give, get_currency, amount_get, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'new', NOW())
");
$stmt->execute([$order_id, $user_id, $give_currency, $amount_give, $get_currency, $amount_get]);

// Успех
$_SESSION['success'] = "Заявка успешно создана! Номер: <strong>$order_id</strong>";
header('Location: profile.php');
exit;
?>