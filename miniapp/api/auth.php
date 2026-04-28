<?php
// miniapp/api/auth.php — Telegram WebApp initData verification
// Include at the top of every API endpoint

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

function verifyTelegramInitData(string $initData) {
    $token = TG_BOT_TOKEN;
    if (!$token || !$initData) return false;

    parse_str($initData, $params);
    foreach ($params as $v) {
        if (is_array($v)) return false;
    }
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
    header('Content-Type: application/json');
    $chatId = 0;
    $user   = [];

    // Method 1: Full HMAC verification (mobile / modern Telegram clients)
    $initData = $_SERVER['HTTP_X_TELEGRAM_INIT_DATA'] ?? '';
    if ($initData) {
        $params = verifyTelegramInitData($initData);
        if ($params !== false) {
            $u      = json_decode($params['user'] ?? '{}', true);
            $chatId = (int)(is_array($u) ? ($u['id'] ?? 0) : 0);
            $user   = is_array($u) ? $u : [];
        }
    }

    // Method 2: Dynamic token from /start command (inline button with ?auth=TOKEN)
    if ($chatId <= 0) {
        $authToken = $_SERVER['HTTP_X_AUTH_TOKEN'] ?? '';
        if ($authToken) {
            $tokenFile = __DIR__ . '/../../miniapp_tokens.json';
            if (file_exists($tokenFile)) {
                $tokens = json_decode(file_get_contents($tokenFile), true) ?: [];
                if (isset($tokens[$authToken]) && $tokens[$authToken]['expires'] > time()) {
                    $chatId = (int)$tokens[$authToken]['chat_id'];
                }
            }
        }
    }

    // Method 3: Static key (Menu Button — fixed URL with ?key=MINIAPP_KEY)
    if ($chatId <= 0) {
        $key = $_SERVER['HTTP_X_MINIAPP_KEY'] ?? '';
        if ($key && defined('MINIAPP_KEY') && hash_equals(MINIAPP_KEY, $key)) {
            return ['chat_id' => 0, 'user' => []]; // key is the auth, no chat_id needed
        }
    }

    if ($chatId <= 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $raw     = defined('TG_ALLOWED_CHATS') ? TG_ALLOWED_CHATS : '';
    $allowed = array_filter(array_map('trim', explode(',', $raw)));

    if (!empty($allowed) && !in_array((string)$chatId, $allowed)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    return ['chat_id' => $chatId, 'user' => $user];
}
