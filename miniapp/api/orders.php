<?php
// miniapp/api/orders.php
// GET  ?status=all|new|in_process|success|canceled&page=1
// POST {action:"status",order_id,new_status} | {action:"delete",order_id}

require_once __DIR__ . '/auth.php';

$validStatuses = ['new', 'in_process', 'success', 'canceled'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    requireAuth();

    $status = $_GET['status'] ?? 'all';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    try {
        if ($status === 'all' || !in_array($status, $validStatuses)) {
            $where  = '';
            $params = [];
        } else {
            $where  = 'WHERE o.status = ?';
            $params = [$status];
        }

        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $pdo->prepare("
            SELECT o.id, o.created_at, o.give_currency, o.amount_give,
                   o.get_currency, o.amount_get, o.status, u.username
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            $where
            ORDER BY o.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'orders' => $rows,
            'total'  => $total,
            'page'   => $page,
            'pages'  => (int)ceil($total / $limit),
        ]);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireAuth();

    $body    = json_decode(file_get_contents('php://input'), true);
    if (!is_array($body)) $body = [];
    $action  = $body['action']   ?? '';
    $orderId = trim($body['order_id'] ?? '');

    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'order_id required']);
        exit;
    }

    try {
        if ($action === 'status') {
            $newStatus = $body['new_status'] ?? '';
            if (!in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status']);
                exit;
            }
            $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")
                ->execute([$newStatus, $orderId]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'delete') {
            $pdo->prepare("DELETE FROM orders WHERE id = ?")
                ->execute([$orderId]);
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
