<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: application/json');

$ticketId = (int)($_GET['ticket_id'] ?? 0);
$lastId   = (int)($_GET['last_id']   ?? 0);

if ($ticketId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid ticket_id']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT t.status, u.username FROM support_tickets t JOIN users u ON u.id = t.user_id WHERE t.id = ?");
    $stmt->execute([$ticketId]);
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
        'username' => $ticket['username'],
        'messages' => $msgs->fetchAll(PDO::FETCH_ASSOC),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
