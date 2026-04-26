<?php
// Сразу отвечаем 200 Telegram'у чтобы не было повторных запросов
http_response_code(200);

// Логируем для отладки
$logFile = __DIR__ . '/webhook.log';
$raw = file_get_contents('php://input');
file_put_contents($logFile, date('[Y-m-d H:i:s]') . " RAW: " . substr($raw, 0, 500) . "\n", FILE_APPEND);

try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/db.php';
} catch (Throwable $e) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " INCLUDE ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    exit;
}

file_put_contents($logFile, date('[Y-m-d H:i:s]') . " TOKEN_GET=" . ($_GET['token'] ?? 'none') . " TOKEN_CONST=" . TG_BOT_TOKEN . "\n", FILE_APPEND);

// Проверка токена (более мягкая)
$urlToken = $_GET['token'] ?? '';
if (TG_BOT_TOKEN && trim($urlToken) !== trim(TG_BOT_TOKEN)) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " TOKEN MISMATCH\n", FILE_APPEND);
    exit;
}

$update = json_decode($raw, true);
if (!$update || !isset($update['message'])) {
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " NO MESSAGE\n", FILE_APPEND);
    exit;
}

$message = $update['message'];
$text    = trim($message['text'] ?? '');
$chatId  = (int)($message['chat']['id'] ?? 0);

file_put_contents($logFile, date('[Y-m-d H:i:s]') . " MSG from={$chatId} text=" . substr($text, 0, 100) . "\n", FILE_APPEND);

if (!$chatId) exit;

// /start или /help
if (in_array($text, ['/start', '/help'])) {
    sendTgDirect($chatId,
        "👋 <b>Бот поддержки " . SITE_NAME . "</b>\n\n"
      . "<b>Команды:</b>\n"
      . "/list — открытые тикеты\n"
      . "/ticket 5 — посмотреть тикет #5\n"
      . "<code>/reply 5 текст</code> — ответить на тикет #5"
    );
    exit;
}

// /list
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
                      . "👤 {$r['username']} · {$r['msg_count']} сообщ.\n"
                      . "<code>/reply {$r['id']} текст</code>\n\n";
            }
            sendTgDirect($chatId, $out);
        }
    } catch (Throwable $e) {
        sendTgDirect($chatId, "Ошибка БД: " . $e->getMessage());
    }
    exit;
}

// /ticket <id>
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
                 . "👤 " . htmlspecialchars($ticket['username']) . "\n"
                 . "Статус: {$ticket['status']}\n\n";
            foreach ($msgList as $msg) {
                $s = $msg['sender'] === 'admin' ? '[Поддержка]' : '[Пользователь]';
                $out .= "<b>{$s}</b> " . htmlspecialchars(mb_substr($msg['message'], 0, 200)) . "\n";
            }
            $out .= "\n<code>/reply {$ticketId} текст</code>";
            sendTgDirect($chatId, $out);
        }
    } catch (Throwable $e) {
        sendTgDirect($chatId, "Ошибка БД: " . $e->getMessage());
    }
    exit;
}

// /reply <id> <текст>
if (preg_match('/^\/reply\s+(\d+)\s+(.+)$/su', $text, $m)) {
    $ticketId = (int)$m[1];
    $reply    = trim($m[2]);
    try {
        $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND status != 'closed'");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$ticket) {
            sendTgDirect($chatId, "Тикет #<b>{$ticketId}</b> не найден или закрыт.");
        } else {
            $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                ->execute([$ticketId, $reply]);
            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                ->execute([$ticketId]);
            sendTgDirect($chatId, "✅ Ответ на тикет <b>#{$ticketId}</b> сохранён.");
        }
    } catch (Throwable $e) {
        sendTgDirect($chatId, "Ошибка БД: " . $e->getMessage());
    }
    exit;
}

// Reply на сообщение
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
            sendTgDirect($chatId, "✅ Ответ на тикет <b>#{$ticketId}</b> сохранён.");
        } else {
            sendTgDirect($chatId, "Тикет не найден. Используйте: <code>/reply 5 текст</code>");
        }
    } catch (Throwable $e) {
        sendTgDirect($chatId, "Ошибка: " . $e->getMessage());
    }
    exit;
}

// Неизвестное сообщение
if ($text) {
    sendTgDirect($chatId, "Команды:\n/list — тикеты\n<code>/reply 5 текст</code> — ответ\n/help — справка");
}

exit;

function sendTgDirect(int $chatId, string $text): void {
    $token = TG_BOT_TOKEN;
    if (!$token || !$chatId) return;

    global $logFile;
    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " SEND to={$chatId} text=" . substr($text, 0, 100) . "\n", FILE_APPEND);

    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML',
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    file_put_contents($logFile, date('[Y-m-d H:i:s]') . " SEND RESULT: " . substr($result ?: $err, 0, 200) . "\n", FILE_APPEND);
}
