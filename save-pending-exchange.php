<?php
// save-pending-exchange.php — сохраняет данные обмена в сессию до редиректа на логин
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false]);
    exit;
}

$_SESSION['pending_exchange'] = [
    'give'        => $_POST['give_currency'] ?? 'USDT_TRC20',
    'get'         => $_POST['get_currency']  ?? 'RUB',
    'amount_give' => floatval($_POST['amount_give'] ?? 100),
];

echo json_encode(['ok' => true]);
exit;
