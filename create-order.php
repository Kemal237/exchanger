<?php
// create-order.php — сохраняет данные и перенаправляет на подтверждение Telegram

require_once 'config.php';
require_once 'auth.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

if (!isLoggedIn()) {
    $_SESSION['pending_exchange'] = [
        'give_currency' => $_POST['give_currency'] ?? '',
        'get_currency'  => $_POST['get_currency'] ?? '',
        'amount_give'   => floatval($_POST['amount_give'] ?? 0),
    ];
    header('Location: login.php');
    exit;
}

$give_currency = $_POST['give_currency'] ?? '';
$amount_give   = floatval($_POST['amount_give'] ?? 0);
$get_currency  = $_POST['get_currency'] ?? '';
$amount_get    = floatval($_POST['amount_get'] ?? 0);

if ($amount_give <= 0 || $amount_get <= 0) {
    $_SESSION['error'] = 'Некорректные данные обмена';
    header('Location: index.php');
    exit;
}

// Сохраняем данные для страницы подтверждения Telegram
$_SESSION['pending_order'] = [
    'give_currency' => $give_currency,
    'amount_give'   => $amount_give,
    'get_currency'  => $get_currency,
    'amount_get'    => $amount_get,
];

header('Location: order.php');
exit;
?>