<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$ticketId = (int)($_GET['ticket_id'] ?? 0);
$lastId   = (int)($_GET['last_id']   ?? 0);
$userId   = (int)$_SESSION['user_id'];

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ticket_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT status FROM support_tickets WHERE id = ? AND user_id = ?");
    $stmt->execute([$ticketId, $userId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }

    $msgs = $pdo->prepare("SELECT id, sender, message, created_at FROM support_messages WHERE ticket_id = ? AND id > ? ORDER BY created_at ASC");
    $msgs->execute([$ticketId, $lastId]);

    echo json_encode([
        'status'   => $ticket['status'],
        'messages' => $msgs->fetchAll(PDO::FETCH_ASSOC),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
