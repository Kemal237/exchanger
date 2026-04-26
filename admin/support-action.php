<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: support.php');
    exit;
}

$action    = $_POST['action'] ?? '';
$ticket_id = (int)($_POST['ticket_id'] ?? 0);

// === Ответить пользователю ===
if ($action === 'reply') {
    $message = trim($_POST['message'] ?? '');
    if (!$ticket_id || empty($message)) {
        header('Location: support.php');
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM support_tickets WHERE id = ? AND status != 'closed'");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Тикет не найден или закрыт'];
        header('Location: support.php');
        exit;
    }

    $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
        ->execute([$ticket_id, $message]);

    $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
        ->execute([$ticket_id]);

    // Уведомление в Telegram (ответ в цепочку тикета)
    $tgText = "📤 <b>Ответ поддержки на тикет #{$ticket_id}</b>\n\n"
            . htmlspecialchars($message);
    sendTelegramMessage($tgText, $ticket['tg_message_id'] ?: null);

    $_SESSION['toast'] = ['type' => 'success', 'message' => "Ответ на тикет #{$ticket_id} отправлен"];
    header("Location: support.php?status=answered&ticket={$ticket_id}#ticket-{$ticket_id}");
    exit;
}

// === Изменить статус ===
if ($action === 'status') {
    $new_status = $_POST['new_status'] ?? '';
    if (!$ticket_id || !in_array($new_status, ['open', 'answered', 'closed'])) {
        header('Location: support.php');
        exit;
    }

    $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?")
        ->execute([$new_status, $ticket_id]);

    $redirect = $new_status === 'closed' ? 'closed' : ($new_status === 'answered' ? 'answered' : 'open');

    header("Location: support.php?status={$redirect}");
    exit;
}

header('Location: support.php');
exit;
