<?php
require 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$give_cur = $_POST['give_currency'] ?? '';
$get_cur  = $_POST['get_currency']  ?? '';
$amount   = floatval($_POST['amount_give'] ?? 0);

if ($amount <= 0 || !isset($rates[$give_cur][$get_cur])) {
    die('Неверные данные');
}

$rate = $rates[$give_cur][$get_cur];
$to_receive = $amount * $rate;

// Здесь в реальном проекте:
// 1. Записать в базу
// 2. Сгенерировать уникальный ID заявки
// 3. Отправить email админу и пользователю

$order_id = 'ORD-' . time() . '-' . rand(1000,9999);

$_SESSION['order'] = [
    'id'          => $order_id,
    'give_cur'    => $give_cur,
    'amount_give' => $amount,
    'get_cur'     => $get_cur,
    'amount_get'  => $to_receive,
    'rate'        => $rate,
    'created'     => date('c'),
];

header('Location: order.php?id=' . $order_id);
exit;