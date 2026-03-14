<?php
require 'config.php';
require 'auth.php'; // здесь isLoggedIn()

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Проверка авторизации
if (!isLoggedIn()) {
    // Сохраняем данные формы в сессию
    $_SESSION['pending_exchange'] = [
        'give_currency'  => $_POST['give_currency'] ?? '',
        'get_currency'   => $_POST['get_currency']  ?? '',
        'amount_give'    => floatval($_POST['amount_give'] ?? 0),
    ];

    // Показываем сообщение и редиректим на логин
    $_SESSION['auth_message'] = 'Для создания заявки необходимо войти в аккаунт. После входа вы вернётесь к обмену.';

    header('Location: login.php?redirect=index.php');
    exit;
}

// Если авторизован — продолжаем как раньше
$give_cur = $_POST['give_currency'] ?? '';
$get_cur  = $_POST['get_currency']  ?? '';
$amount   = floatval($_POST['amount_give'] ?? 0);

$allowed = ['USDT_TRC20', 'RUB', 'BTC'];

if (!in_array($give_cur, $allowed) || !in_array($get_cur, $allowed)) {
    die('Недопустимая валютная пара');
}

if ($amount <= 0 || !isset($rates[$give_cur][$get_cur]) || $rates[$give_cur][$get_cur] <= 0) {
    die('Неверные данные или курс недоступен');
}

$rate = $rates[$give_cur][$get_cur];
$to_receive = $amount * $rate;

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