<?php
// bot-webhook.php — получение ответов администратора из Telegram
// Зарегистрируйте webhook: https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://cr873507.tw1.ru/bot-webhook.php?token=<TOKEN>

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Защита: токен в URL должен совпадать с токеном бота
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
$chatId  = (int)($message['chat']['id'] ?? 0);

if (!$chatId) {
    http_response_code(200);
    exit;
}

// /start или /help — инструкция
if (in_array($text, ['/start', '/help'])) {
    sendTgDirect($chatId,
        "<b>Бот поддержки " . SITE_NAME . "</b>\n\n"
      . "<b>Как ответить на тикет:</b>\n\n"
      . "Способ 1 — команда:\n"
      . "<code>/reply 5 Ваш ответ здесь</code>\n"
      . "(где 5 — номер тикета)\n\n"
      . "Способ 2 — нажмите Reply на сообщение тикета в Telegram\n\n"
      . "<b>Другие команды:</b>\n"
      . "/list — открытые тикеты\n"
      . "/ticket 5 — посмотреть тикет #5"
    );
    http_response_code(200);
    exit;
}

// /list — список открытых тикетов
if ($text === '/list') {
    try {
        $stmt = $pdo->query("
            SELECT t.id, t.subject, t.status, u.username,
                   (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) AS msg_count
            FROM support_tickets t
            JOIN users u ON u.id = t.user_id
            WHERE t.status != 'closed'
            ORDER BY t.updated_at DESC
            LIMIT 10
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            sendTgDirect($chatId, "Открытых тикетов нет.");
        } else {
            $out = "<b>Открытые тикеты:</b>\n\n";
            foreach ($rows as $r) {
                $status = $r['status'] === 'open' ? '[Открыт]' : '[Отвечен]';
                $out .= "{$status} <b>#{$r['id']}</b> — " . htmlspecialchars($r['subject']) . "\n"
                      . "   Пользователь: {$r['username']} | {$r['msg_count']} сообщ.\n"
                      . "   Ответ: <code>/reply {$r['id']} текст</code>\n\n";
            }
            sendTgDirect($chatId, $out);
        }
    } catch (Exception $e) {
        sendTgDirect($chatId, "Ошибка базы данных.");
    }
    http_response_code(200);
    exit;
}

// /ticket <id> — просмотр тикета
if (preg_match('/^\/ticket\s+(\d+)$/u', $text, $m)) {
    $ticketId = (int)$m[1];
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, u.username FROM support_tickets t
            JOIN users u ON u.id = t.user_id WHERE t.id = ?
        ");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            sendTgDirect($chatId, "Тикет #<b>{$ticketId}</b> не найден.");
        } else {
            $msgs = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC LIMIT 5");
            $msgs->execute([$ticketId]);
            $msgList = $msgs->fetchAll(PDO::FETCH_ASSOC);

            $out = "<b>Тикет #{$ticketId}</b> — " . htmlspecialchars($ticket['subject']) . "\n"
                 . "Пользователь: " . htmlspecialchars($ticket['username']) . "\n"
                 . "Статус: {$ticket['status']}\n\n";

            foreach ($msgList as $msg) {
                $sender = $msg['sender'] === 'admin' ? '[Поддержка]' : '[Пользователь]';
                $out .= "<b>{$sender}</b> " . htmlspecialchars(mb_substr($msg['message'], 0, 200)) . "\n";
            }
            $out .= "\nОтветить: <code>/reply {$ticketId} текст</code>";
            sendTgDirect($chatId, $out);
        }
    } catch (Exception $e) {
        sendTgDirect($chatId, "Ошибка базы данных.");
    }
    http_response_code(200);
    exit;
}

// /reply <id> <текст> — ответить на тикет
if (preg_match('/^\/reply\s+(\d+)\s+(.+)$/su', $text, $m)) {
    $ticketId = (int)$m[1];
    $reply    = trim($m[2]);

    try {
        $stmt = $pdo->prepare("SELECT id, tg_message_id FROM support_tickets WHERE id = ? AND status != 'closed'");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            sendTgDirect($chatId, "Тикет #<b>{$ticketId}</b> не найден или закрыт.");
        } else {
            $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                ->execute([$ticketId, $reply]);

            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                ->execute([$ticketId]);

            sendTgDirect($chatId, "Ответ на тикет <b>#{$ticketId}</b> сохранён.");
        }
    } catch (Exception $e) {
        sendTgDirect($chatId, "Ошибка базы данных: " . $e->getMessage());
    }
    http_response_code(200);
    exit;
}

// Reply на сообщение бота в Telegram (по tg_message_id)
if (isset($message['reply_to_message']) && !empty($text)) {
    $repliedToMsgId = (int)$message['reply_to_message']['message_id'];

    try {
        $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE tg_message_id = ? AND status != 'closed'");
        $stmt->execute([$repliedToMsgId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($ticket) {
            $ticketId = (int)$ticket['id'];

            $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                ->execute([$ticketId, $text]);

            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                ->execute([$ticketId]);

            sendTgDirect($chatId, "Ответ на тикет <b>#{$ticketId}</b> сохранён.");
        } else {
            sendTgDirect($chatId, "Тикет не найден. Используйте:\n<code>/reply 5 Текст ответа</code>\nили /list для списка тикетов.");
        }
    } catch (Exception $e) {
        sendTgDirect($chatId, "Ошибка: " . $e->getMessage());
    }
    http_response_code(200);
    exit;
}

// Неизвестный текст
if ($text && $text[0] !== '/') {
    sendTgDirect($chatId, "Для работы с тикетами:\n/list — список тикетов\n<code>/reply 5 Текст</code> — ответ на тикет #5\n/help — справка");
}

http_response_code(200);
exit;

function sendTgDirect(int $chatId, string $text): void {
    $token = TG_BOT_TOKEN;
    if (!$token) return;
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
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
