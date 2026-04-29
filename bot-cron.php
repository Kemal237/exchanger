<?php
// bot-cron.php — Telegram polling (запускать через cron каждую минуту)
// Скрипт сам опрашивает Telegram дважды: в 0с и в 30с → ответы каждые 30 секунд

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

if (!TG_BOT_TOKEN) exit;

// Очистка логов старше 90 дней с вероятностью 1% (≈раз в 1.5ч при крон каждую минуту)
if (rand(1, 100) === 1) {
    try {
        $pdo->exec("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    } catch (Exception $e) { /* не критично */ }
}

// 5 опросов с интервалом 10 секунд = максимальная задержка ответа ~10 секунд
// Суммарное время выполнения ~50с — безопасно вписывается в минуту cron
for ($i = 0; $i < 5; $i++) {
    pollOnce();
    if ($i < 4) sleep(10);
}

// ===================================================================
function pollOnce(): void {
    global $pdo;

    $offsetFile = __DIR__ . '/bot-offset.txt';
    $offset     = file_exists($offsetFile) ? (int)file_get_contents($offsetFile) : 0;

    $ch = curl_init("https://api.telegram.org/bot" . TG_BOT_TOKEN . "/getUpdates"
                  . "?offset=" . ($offset + 1) . "&limit=100&timeout=0");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $result = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($result, true);
    if (!$data || !($data['ok'] ?? false) || empty($data['result'])) return;

    foreach ($data['result'] as $update) {
        $updateId = (int)$update['update_id'];
        if ($updateId > $offset) $offset = $updateId;

        if (!isset($update['message'])) continue;

        $message = $update['message'];
        $text    = trim($message['text'] ?? '');
        $chatId  = (int)($message['chat']['id'] ?? 0);

        if ($chatId) handleMessage($chatId, $text, $message);
    }

    file_put_contents($offsetFile, $offset);
}

// ===================================================================
function isAllowed(int $chatId): bool {
    $raw     = defined('TG_ALLOWED_CHATS') ? TG_ALLOWED_CHATS : '';
    $allowed = array_filter(array_map('trim', explode(',', $raw)));
    return empty($allowed) || in_array((string)$chatId, $allowed);
}

// ===================================================================
function handleMessage(int $chatId, string $text, array $message): void {
    global $pdo;

    // Проверка доступа
    if (!isAllowed($chatId)) {
        tgSend($chatId, "Доступ запрещён.\n\nВаш chat_id: <code>{$chatId}</code>\nПередайте его администратору для получения доступа.");
        return;
    }

    // /start, /help
    if (in_array($text, ['/start', '/help'])) {
        // Generate 24-hour auth token for Mini App (works on all Telegram clients)
        $token     = bin2hex(random_bytes(16));
        $tokenFile = __DIR__ . '/miniapp_tokens.json';
        $tokens    = file_exists($tokenFile) ? (json_decode(file_get_contents($tokenFile), true) ?: []) : [];
        // Remove expired tokens
        foreach ($tokens as $t => $info) {
            if ($info['expires'] < time()) unset($tokens[$t]);
        }
        $tokens[$token] = ['chat_id' => $chatId, 'expires' => time() + 86400];
        file_put_contents($tokenFile, json_encode($tokens));

        $miniappUrl = 'https://cr873507.tw1.ru/miniapp/index.html?auth=' . $token;
        $keyboard = [
            'inline_keyboard' => [[
                ['text' => '📊 Открыть панель управления', 'web_app' => ['url' => $miniappUrl]]
            ]],
        ];
        tgSend($chatId,
            "👋 <b>Бот поддержки " . SITE_NAME . "</b>\n\n"
          . "<b>Тикеты:</b>\n"
          . "/list — открытые тикеты\n"
          . "/ticket 5 — посмотреть тикет #5\n"
          . "<code>/reply 5 текст</code> — ответить\n"
          . "<code>/close 5</code> — закрыть тикет\n"
          . "<code>/open 5</code> — открыть заново",
            $keyboard
        );
        return;
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
                ORDER BY t.updated_at DESC LIMIT 10
            ");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                tgSend($chatId, "Открытых тикетов нет.");
            } else {
                $out = "<b>Открытые тикеты:</b>\n\n";
                foreach ($rows as $r) {
                    $st = $r['status'] === 'open' ? '[Открыт]' : '[Отвечен]';
                    $out .= "{$st} <b>#{$r['id']}</b> — " . htmlspecialchars($r['subject']) . "\n"
                          . "👤 {$r['username']} · {$r['msg_count']} сообщ.\n"
                          . "<code>/reply {$r['id']} текст</code>\n\n";
                }
                tgSend($chatId, $out);
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка БД: " . $e->getMessage());
        }
        return;
    }

    // /ticket <id>
    if (preg_match('/^\/ticket\s+(\d+)$/u', $text, $m)) {
        $tid = (int)$m[1];
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, u.username FROM support_tickets t
                JOIN users u ON u.id = t.user_id WHERE t.id = ?
            ");
            $stmt->execute([$tid]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                tgSend($chatId, "Тикет #<b>{$tid}</b> не найден.");
            } else {
                $msgs = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC LIMIT 5");
                $msgs->execute([$tid]);
                $msgList = $msgs->fetchAll(PDO::FETCH_ASSOC);

                $out = "<b>Тикет #{$tid}</b> — " . htmlspecialchars($ticket['subject']) . "\n"
                     . "👤 " . htmlspecialchars($ticket['username']) . "\n"
                     . "Статус: {$ticket['status']}\n\n";
                foreach ($msgList as $msg) {
                    $s = $msg['sender'] === 'admin' ? '[Поддержка]' : '[Пользователь]';
                    $out .= "<b>{$s}</b> " . htmlspecialchars(mb_substr($msg['message'], 0, 200)) . "\n";
                }
                $out .= "\n<code>/reply {$tid} текст</code>";
                tgSend($chatId, $out);
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка БД: " . $e->getMessage());
        }
        return;
    }

    // /reply <id> <текст>
    if (preg_match('/^\/reply\s+(\d+)\s+(.+)$/su', $text, $m)) {
        $tid   = (int)$m[1];
        $reply = trim($m[2]);
        try {
            $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND status != 'closed'");
            $stmt->execute([$tid]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                tgSend($chatId, "Тикет #<b>{$tid}</b> не найден или закрыт.");
            } else {
                $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                    ->execute([$tid, $reply]);
                $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                    ->execute([$tid]);
                tgSend($chatId, "✅ Ответ на тикет <b>#{$tid}</b> сохранён.");
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка БД: " . $e->getMessage());
        }
        return;
    }

    // /close <id>
    if (preg_match('/^\/close\s+(\d+)$/u', $text, $m)) {
        $tid = (int)$m[1];
        try {
            $stmt = $pdo->prepare("SELECT id, subject FROM support_tickets WHERE id = ? AND status != 'closed'");
            $stmt->execute([$tid]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                tgSend($chatId, "Тикет #<b>{$tid}</b> не найден или уже закрыт.");
            } else {
                $pdo->prepare("UPDATE support_tickets SET status = 'closed', updated_at = NOW() WHERE id = ?")
                    ->execute([$tid]);
                tgSend($chatId, "🔒 Тикет <b>#{$tid}</b> закрыт.\n📌 " . htmlspecialchars($ticket['subject']));
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка: " . $e->getMessage());
        }
        return;
    }

    // /open <id>
    if (preg_match('/^\/open\s+(\d+)$/u', $text, $m)) {
        $tid = (int)$m[1];
        try {
            $stmt = $pdo->prepare("SELECT id, subject FROM support_tickets WHERE id = ?");
            $stmt->execute([$tid]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                tgSend($chatId, "Тикет #<b>{$tid}</b> не найден.");
            } else {
                $pdo->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?")
                    ->execute([$tid]);
                tgSend($chatId, "🔓 Тикет <b>#{$tid}</b> открыт заново.\n📌 " . htmlspecialchars($ticket['subject']));
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка: " . $e->getMessage());
        }
        return;
    }

    // Reply на сообщение бота (по tg_message_id)
    if (isset($message['reply_to_message']) && !empty($text)) {
        $repliedId = (int)$message['reply_to_message']['message_id'];
        try {
            $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE tg_message_id = ? AND status != 'closed'");
            $stmt->execute([$repliedId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ticket) {
                $tid = (int)$ticket['id'];
                $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                    ->execute([$tid, $text]);
                $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                    ->execute([$tid]);
                tgSend($chatId, "✅ Ответ на тикет <b>#{$tid}</b> сохранён.");
            } else {
                tgSend($chatId, "Тикет не найден. Используйте: <code>/reply 5 текст</code>");
            }
        } catch (Throwable $e) {
            tgSend($chatId, "Ошибка: " . $e->getMessage());
        }
        return;
    }

    // Неизвестное сообщение
    if ($text) {
        tgSend($chatId, "Команды:\n/list — тикеты\n<code>/reply 5 текст</code> — ответ\n/help — справка");
    }
}

// ===================================================================
function tgSend(int $chatId, string $text, ?array $replyMarkup = null): void {
    $token = TG_BOT_TOKEN;
    if (!$token || !$chatId) return;
    $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
    $postFields = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML',
    ];
    if ($replyMarkup !== null) {
        $postFields['reply_markup'] = json_encode($replyMarkup);
    }
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
