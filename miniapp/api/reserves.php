<?php
// miniapp/api/reserves.php
// GET  → list all reserves
// POST {action:"reserve", currency, amount, action_type:"add"|"subtract"}
// POST {action:"limits",  currency, min, max}

require_once __DIR__ . '/auth.php';

$validCurrencies = ['USDT_TRC20', 'USDC', 'ETH', 'SOL', 'BTC', 'RUB', 'USD'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();
    try {
        $stmt = $pdo->query("SELECT currency, amount, min, max, updated_at FROM reserves ORDER BY currency");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth();

    $body     = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];
    $action   = $body['action']   ?? '';
    $currency = strtoupper(trim($body['currency'] ?? ''));

    if (!in_array($currency, $validCurrencies)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid currency']);
        exit;
    }

    try {
        if ($action === 'reserve') {
            $amount     = floatval($body['amount'] ?? 0);
            $actionType = $body['action_type'] ?? 'add';
            if (!in_array($actionType, ['add', 'subtract'])) {
                $actionType = 'add';
            }
            if ($amount <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Amount must be positive']);
                exit;
            }
            $change = ($actionType === 'subtract') ? -$amount : $amount;
            $pdo->prepare("
                INSERT INTO reserves (currency, amount)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE amount = amount + ?
            ")->execute([$currency, $change, $change]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'limits') {
            $min = floatval($body['min'] ?? 0);
            $max = floatval($body['max'] ?? 0);
            if ($max > 0 && $min > $max) {
                http_response_code(400);
                echo json_encode(['error' => 'min must not exceed max']);
                exit;
            }
            $pdo->prepare("
                INSERT INTO reserves (currency, amount, min, max)
                VALUES (?, 0, ?, ?)
                ON DUPLICATE KEY UPDATE min = VALUES(min), max = VALUES(max)
            ")->execute([$currency, $min, $max]);
            echo json_encode(['success' => true]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
