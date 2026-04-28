<?php
// miniapp/api/dashboard.php — GET ?period=today|7d|30d

require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireAuth();

$period = $_GET['period'] ?? 'today';
if (!in_array($period, ['today', '7d', '30d'])) {
    $period = 'today';
}
$now    = date('Y-m-d H:i:s');

switch ($period) {
    case '7d':
        $start = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30d':
        $start = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    default:
        $start = date('Y-m-d 00:00:00');
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at <= ?");
    $stmt->execute([$start, $now]);
    $newUsers = (int)$stmt->fetchColumn();

    $statuses = ['new', 'in_process', 'success', 'canceled'];
    $orders = [];
    foreach ($statuses as $s) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = ? AND created_at >= ? AND created_at <= ?");
        $stmt->execute([$s, $start, $now]);
        $orders[$s] = (int)$stmt->fetchColumn();
    }
    $orders['total'] = array_sum($orders);

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount_give), 0) FROM orders WHERE status = 'success' AND created_at >= ? AND created_at <= ?");
    $stmt->execute([$start, $now]);
    $volume = (float)$stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status != 'closed'");
    $openTickets = (int)$stmt->fetchColumn();

    $rates = null;
    $cacheFile = __DIR__ . '/../../cache_rates.json';
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if ($cached && isset($cached['rates'])) {
            $r = $cached['rates'];
            $rates = [
                'BTC_RUB'  => $r['BTC']['RUB']          ?? null,
                'ETH_RUB'  => $r['ETH']['RUB']          ?? null,
                'USDT_RUB' => $r['USDT_TRC20']['RUB']   ?? null,
            ];
        }
    }

    echo json_encode([
        'period'       => $period,
        'new_users'    => $newUsers,
        'orders'       => $orders,
        'volume'       => $volume,
        'open_tickets' => $openTickets,
        'rates'        => $rates,
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
