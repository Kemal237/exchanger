<?php
// miniapp/api/tickets.php
// GET ?status=open|answered|closed|all&page=1  → ticket list + counts
// GET ?id=TICKET_ID                             → single ticket + messages
// POST {action:"reply",  ticket_id, message}
// POST {action:"status", ticket_id, new_status}

require_once __DIR__ . '/auth.php';

$validStatuses = ['open', 'answered', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();

    if (isset($_GET['id'])) {
        $tid = (int)$_GET['id'];
        if ($tid <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid ticket id']);
            exit;
        }
        try {
            $stmt = $pdo->prepare("
                SELECT t.*, u.username FROM support_tickets t
                JOIN users u ON u.id = t.user_id WHERE t.id = ?
            ");
            $stmt->execute([$tid]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ticket) {
                http_response_code(404);
                echo json_encode(['error' => 'Not found']);
                exit;
            }

            $msgs = $pdo->prepare("SELECT id, sender, message, created_at FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
            $msgs->execute([$tid]);
            $ticket['messages'] = $msgs->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($ticket);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        exit;
    }

    $status = $_GET['status'] ?? 'open';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    if ($status !== 'all' && !in_array($status, $validStatuses)) {
        $status = 'open';
    }

    try {
        if ($status === 'all') {
            $where  = '';
            $params = [];
        } else {
            $where  = 'WHERE t.status = ?';
            $params = [$status];
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM support_tickets t $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT t.id, t.subject, t.status, t.updated_at,
                   u.username,
                   (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) AS msg_count,
                   (SELECT sender FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_sender
            FROM support_tickets t
            JOIN users u ON u.id = t.user_id
            $where
            ORDER BY t.updated_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);

        $counts = [];
        foreach (['open', 'answered', 'closed'] as $s) {
            $c = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE status = ?");
            $c->execute([$s]);
            $counts[$s] = (int)$c->fetchColumn();
        }
        $counts['all'] = array_sum($counts);

        echo json_encode([
            'tickets' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'   => $total,
            'page'    => $page,
            'pages'   => (int)ceil($total / $limit),
            'counts'  => $counts,
        ]);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth();

    $body     = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];
    $action   = $body['action']    ?? '';
    $ticketId = (int)($body['ticket_id'] ?? 0);

    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'ticket_id required']);
        exit;
    }

    try {
        if ($action === 'reply') {
            $message = trim($body['message'] ?? '');
            if (!$message) {
                http_response_code(400);
                echo json_encode(['error' => 'message required']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id FROM support_tickets WHERE id = ? AND status != 'closed'");
            $stmt->execute([$ticketId]);
            if (!$stmt->fetch()) {
                http_response_code(404);
                echo json_encode(['error' => 'Ticket not found or closed']);
                exit;
            }
            $pdo->prepare("INSERT INTO support_messages (ticket_id, sender, message) VALUES (?, 'admin', ?)")
                ->execute([$ticketId, $message]);
            $pdo->prepare("UPDATE support_tickets SET status = 'answered', updated_at = NOW() WHERE id = ?")
                ->execute([$ticketId]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'status') {
            $newStatus = $body['new_status'] ?? '';
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                exit;
            }
            $pdo->prepare("UPDATE support_tickets SET status = ?, updated_at = NOW() WHERE id = ?")
                ->execute([$newStatus, $ticketId]);
            echo json_encode(['success' => true]);

        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);
