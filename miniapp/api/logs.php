<?php
// miniapp/api/logs.php — activity logs for mini-app

require_once __DIR__ . '/auth.php';
requireAuth();

$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 30;
$offset   = ($page - 1) * $per_page;

$filter_role   = $_GET['role']   ?? '';
$filter_result = $_GET['result'] ?? '';
$filter_action = $_GET['action'] ?? '';
$search        = trim($_GET['q'] ?? '');

$where  = [];
$params = [];

if ($filter_role && in_array($filter_role, ['guest','user','admin'])) {
    $where[]  = 'role = ?';
    $params[] = $filter_role;
}
if ($filter_result && in_array($filter_result, ['success','error'])) {
    $where[]  = 'result = ?';
    $params[] = $filter_result;
}
if ($filter_action) {
    $where[]  = 'action = ?';
    $params[] = $filter_action;
}
if ($search) {
    $where[]  = '(description LIKE ? OR username LIKE ? OR ip LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$dataStmt = $pdo->prepare("
    SELECT id, created_at, role, username, ip, action, description, entity_type, entity_id, result
    FROM activity_logs
    $whereSql
    ORDER BY id DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$dataStmt->execute($params);
$rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct actions for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")
               ->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'rows'       => $rows,
    'total'      => $total,
    'page'       => $page,
    'per_page'   => $per_page,
    'total_pages'=> max(1, (int)ceil($total / $per_page)),
    'actions'    => $actions,
]);
