<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (!isLoggedIn() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: support.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$action  = $_POST['action'] ?? '';

// === Создать тикет ===
if ($action === 'create') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (empty($subject) || empty($message)) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Заполните все поля'];
        header('Location: support.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT username, email, telegram FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("INSERT INTO support_tickets (user_id, subject, status) VALUES (?, ?, 'open')");
    $stmt->execute([$user_id, $subject]);
    $ticket_id = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")
        ->execute([$ticket_id, $message]);

    $tgText = "🎫 <b>Новый тикет #{$ticket_id}</b>\n"
            . "👤 Пользователь: <b>" . htmlspecialchars($u['username']) . "</b> (ID: {$user_id})\n"
            . "📧 Email: " . htmlspecialchars($u['email']) . "\n"
            . ($u['telegram'] ? "💬 Telegram: " . htmlspecialchars($u['telegram']) . "\n" : "")
            . "📌 Тема: <b>" . htmlspecialchars($subject) . "</b>\n\n"
            . htmlspecialchars($message) . "\n\n"
            . "<i>Ответить: /reply {$ticket_id} текст</i>";

    $tgMsgId = sendTelegramMessage($tgText);
    if ($tgMsgId) {
        $pdo->prepare("UPDATE support_tickets SET tg_message_id = ? WHERE id = ?")
            ->execute([$tgMsgId, $ticket_id]);
    }

    $_SESSION['toast'] = ['type' => 'success', 'message' => "Обращение #{$ticket_id} отправлено. Ждите ответа."];
    header("Location: support.php?ticket={$ticket_id}#ticket-{$ticket_id}");
    exit;
}

// === Ответить в тикет ===
if ($action === 'reply') {
    $ticket_id = (int)($_POST['ticket_id'] ?? 0);
    $message   = trim($_POST['message'] ?? '');

    if (!$ticket_id || empty($message)) {
        header('Location: support.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND user_id = ? AND status != 'closed'");
    $stmt->execute([$ticket_id, $user_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Тикет не найден или уже закрыт'];
        header('Location: support.php');
        exit;
    }

    $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'user', ?)")
        ->execute([$ticket_id, $message]);

    $pdo->prepare("UPDATE support_tickets SET status = 'open', updated_at = NOW() WHERE id = ?")
        ->execute([$ticket_id]);

    $uName = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $uName->execute([$user_id]);
    $name = $uName->fetchColumn();

    // Получаем тему тикета
    $tSubject = $pdo->prepare("SELECT subject FROM support_tickets WHERE id = ?");
    $tSubject->execute([$ticket_id]);
    $ticketSubject = $tSubject->fetchColumn() ?: '';

    $tgText = "↩️ <b>Ответ пользователя — тикет #{$ticket_id}</b>\n"
            . "📌 Тема: " . htmlspecialchars($ticketSubject) . "\n"
            . "👤 Пользователь: " . htmlspecialchars($name) . "\n\n"
            . htmlspecialchars($message);

    sendTelegramMessage($tgText, $ticket['tg_message_id'] ?: null);

    header("Location: support.php?ticket={$ticket_id}#ticket-{$ticket_id}");
    exit;
}

header('Location: support.php');
exit;
