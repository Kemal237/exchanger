<?php
// miniapp/api/auth.php — Telegram WebApp initData verification
// Include at the top of every API endpoint

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

function verifyTelegramInitData(string $initData) {
    $token = TG_BOT_TOKEN;
    if (!$token || !$initData) return false;

    parse_str($initData, $params);
    $hash = $params['hash'] ?? '';
    if (!$hash) return false;
    unset($params['hash']);

    // Reject stale initData (older than 1 hour)
    $authDate = (int)($params['auth_date'] ?? 0);
    if (time() - $authDate > 3600) return false;

    // Build data-check-string
    ksort($params);
    $lines = [];
    foreach ($params as $k => $v) {
        $lines[] = "$k=$v";
    }
    $dataCheckString = implode("\n", $lines);

    $secretKey    = hash_hmac('sha256', 'WebAppData', $token, true);
    $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($expectedHash, $hash)) return false;

    return $params;
}

function requireAuth() {
    $initData = $_SERVER['HTTP_X_TELEGRAM_INIT_DATA'] ?? '';
    $params   = verifyTelegramInitData($initData);
    if ($params === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user   = json_decode($params['user'] ?? '{}', true) ?? [];
    $chatId = (int)($user['id'] ?? 0);

    $raw     = defined('TG_ALLOWED_CHATS') ? TG_ALLOWED_CHATS : '';
    $allowed = array_filter(array_map('trim', explode(',', $raw)));

    if (!empty($allowed) && !in_array((string)$chatId, $allowed)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    return ['chat_id' => $chatId, 'user' => $user];
}
