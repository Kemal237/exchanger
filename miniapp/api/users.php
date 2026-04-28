<?php
// miniapp/api/users.php
// GET ?search=&page=1   → paginated user list
// GET ?history=USER_ID  → order history for one user

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireAuth();

if (isset($_GET['history'])) {
    $userId = (int)$_GET['history'];
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid user_id']);
        exit;
    }
    try {
        $stmt = $pdo->prepare("
            SELECT id, created_at, give_currency, amount_give,
                   get_currency, amount_get, status
            FROM orders WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

try {
    if ($search !== '') {
        $like   = '%' . $search . '%';
        $where  = 'WHERE u.username LIKE ? OR u.email LIKE ?';
        $params = [$like, $like];
    } else {
        $where  = '';
        $params = [];
    }

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.email, u.telegram, u.role, u.created_at,
               (SELECT COUNT(*) FROM orders WHERE user_id = u.id) AS order_count
        FROM users u
        $where
        ORDER BY u.created_at DESC
        LIMIT {$limit} OFFSET {$offset}
    ");
    $stmt->execute($params);

    echo json_encode([
        'users' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => $total,
        'page'  => $page,
        'pages' => (int)ceil($total / $limit),
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
