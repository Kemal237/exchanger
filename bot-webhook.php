<?php
// bot-webhook.php — получение ответов администратора из Telegram
// URL для регистрации: https://your-domain.com/bot-webhook.php?token=BOT_TOKEN

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Проверяем токен в URL для защиты от посторонних запросов
$urlToken = $_GET['token'] ?? '';
if (!TG_BOT_TOKEN || $urlToken !== TG_BOT_TOKEN) {
    http_response_code(403);
    exit;
}

$input  = file_get_contents('php://input');
$update = json_decode($input, true);

if (!$update || !isset($update['message'])) {
    http_response_code(200);
    exit;
}

$message = $update['message'];
$text    = trim($message['text'] ?? '');
$chatId  = $message['chat']['id'] ?? null;
$fromId  = $message['from']['id'] ?? null;

if (empty($text) || !$chatId) {
    http_response_code(200);
    exit;
}

// Команда /start — справка
if ($text === '/start') {
    $info = "👋 <b>Бот поддержки " . SITE_NAME . "</b>\n\n"
          . "Для ответа на тикет — просто ответьте (Reply) на сообщение с тикетом.\n"
          . "Ответ автоматически сохранится и пользователь увидит его на сайте.";
    sendTgDirect($chatId, $info);
    http_response_code(200);
    exit;
}

// Ответ на сообщение — ищем тикет по tg_message_id
if (!isset($message['reply_to_message'])) {
    http_response_code(200);
    exit;
}

$repliedToMsgId = (int)$message['reply_to_message']['message_id'];

$stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE tg_message_id = ? AND status != 'closed'");
$stmt->execute([$repliedToMsgId]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    sendTgDirect($chatId, "⚠️ Тикет не найден или уже закрыт.");
    http_response_code(200);
    exit;
}

$ticketId = (int)$ticket['id'];

$pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
    ->execute([$ticketId, $text]);

$pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
    ->execute([$ticketId]);

sendTgDirect($chatId, "✅ Ответ на тикет <b>#{$ticketId}</b> сохранён. Пользователь увидит его на сайте.");

http_response_code(200);
exit;

// Вспомогательная функция для прямой отправки (не использует TG_ADMIN_CHAT_ID)
function sendTgDirect(int $chatId, string $text): void {
    $token = TG_BOT_TOKEN;
    if (!$token) return;
    $url = "https://api.telegram.org/bot{$token}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML']),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
