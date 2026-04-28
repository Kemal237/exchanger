# Telegram Mini App — Панель администратора — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Создать Telegram Mini App с полноценной админ-панелью: дашборд, заявки, пользователи, резервы, тикеты поддержки.

**Architecture:** Single-page app (`miniapp/index.html`) + 6 PHP API-эндпоинтов (`miniapp/api/`). Аутентификация через Telegram initData (HMAC-SHA256). Все эндпоинты включают `auth.php` перед выдачей данных.

**Tech Stack:** PHP 7.4, MySQL/PDO, Vanilla JS (ES6+), Telegram WebApp JS SDK

---

## File Map

| Статус | Файл | Ответственность |
|--------|------|-----------------|
| Создать | `miniapp/api/auth.php` | Верификация Telegram initData |
| Создать | `miniapp/api/dashboard.php` | Метрики + курсы |
| Создать | `miniapp/api/orders.php` | Заявки: список, статус, удаление |
| Создать | `miniapp/api/users.php` | Пользователи + история |
| Создать | `miniapp/api/reserves.php` | Резервы: чтение, пополнение, лимиты |
| Создать | `miniapp/api/tickets.php` | Тикеты: список, сообщения, ответ |
| Создать | `miniapp/index.html` | SPA — весь UI и JS |
| Изменить | `bot-cron.php` | Добавить web_app кнопку к /start и /help |

---

## Task 1: Auth helper

**Files:**
- Create: `miniapp/api/auth.php`

- [ ] **Step 1: Создать файл auth.php**

```php
<?php
// miniapp/api/auth.php — Telegram WebApp initData verification
// Include at the top of every API endpoint

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json');

function verifyTelegramInitData(string $initData) {
    $token = TG_BOT_TOKEN;
    if (!$token || !$initData) return false;

    parse_str($initData, $params);
    $hash = $params['hash'] ?? '';
    if (!$hash) return false;
    unset($params['hash']);

    // Reject stale initData (older than 1 hour)
    $authDate = (int)($params['auth_date'] ?? 0);
    if (time() - $authDate > 3600) return false;

    // Build data-check-string
    ksort($params);
    $lines = [];
    foreach ($params as $k => $v) {
        $lines[] = "$k=$v";
    }
    $dataCheckString = implode("\n", $lines);

    $secretKey    = hash_hmac('sha256', 'WebAppData', $token, true);
    $expectedHash = hash_hmac('sha256', $dataCheckString, $secretKey);

    if (!hash_equals($expectedHash, $hash)) return false;

    return $params;
}

function requireAuth() {
    $initData = $_SERVER['HTTP_X_TELEGRAM_INIT_DATA'] ?? '';
    $params   = verifyTelegramInitData($initData);
    if ($params === false) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    $user   = json_decode($params['user'] ?? '{}', true) ?? [];
    $chatId = (int)($user['id'] ?? 0);

    $raw     = defined('TG_ALLOWED_CHATS') ? TG_ALLOWED_CHATS : '';
    $allowed = array_filter(array_map('trim', explode(',', $raw)));

    if (!empty($allowed) && !in_array((string)$chatId, $allowed)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }

    return ['chat_id' => $chatId, 'user' => $user];
}
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/auth.php
git commit -m "feat: add Telegram Mini App auth helper"
```

---

## Task 2: Dashboard API

**Files:**
- Create: `miniapp/api/dashboard.php`

- [ ] **Step 1: Создать dashboard.php**

```php
<?php
// miniapp/api/dashboard.php — GET ?period=today|7d|30d

require_once __DIR__ . '/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$period = $_GET['period'] ?? 'today';
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
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/dashboard.php
git commit -m "feat: add dashboard API endpoint"
```

---

## Task 3: Orders API

**Files:**
- Create: `miniapp/api/orders.php`

- [ ] **Step 1: Создать orders.php**

```php
<?php
// miniapp/api/orders.php
// GET  ?status=all|new|in_process|success|canceled&page=1
// POST {action:"status",order_id,new_status} | {action:"delete",order_id}

require_once __DIR__ . '/auth.php';
requireAuth();

$validStatuses = ['new', 'in_process', 'success', 'canceled'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
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
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));
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
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
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
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/orders.php
git commit -m "feat: add orders API endpoint"
```

---

## Task 4: Users API

**Files:**
- Create: `miniapp/api/users.php`

- [ ] **Step 1: Создать users.php**

```php
<?php
// miniapp/api/users.php
// GET ?search=&page=1   → список пользователей
// GET ?history=USER_ID  → история заявок пользователя

require_once __DIR__ . '/auth.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

if (isset($_GET['history'])) {
    $userId = (int)$_GET['history'];
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
        LIMIT ? OFFSET ?
    ");
    $stmt->execute(array_merge($params, [$limit, $offset]));

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
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/users.php
git commit -m "feat: add users API endpoint"
```

---

## Task 5: Reserves API

**Files:**
- Create: `miniapp/api/reserves.php`

- [ ] **Step 1: Создать reserves.php**

```php
<?php
// miniapp/api/reserves.php
// GET  → список резервов
// POST {action:"reserve", currency, amount, action_type:"add"|"subtract"}
// POST {action:"limits",  currency, min, max}

require_once __DIR__ . '/auth.php';
requireAuth();

$validCurrencies = ['USDT_TRC20', 'USDC', 'ETH', 'SOL', 'BTC', 'RUB', 'USD'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT currency, amount, min, max, updated_at FROM reserves ORDER BY currency");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $action   = $body['action']   ?? '';
    $currency = strtoupper(trim($body['currency'] ?? ''));

    if (!in_array($currency, $validCurrencies)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid currency']);
        exit;
    }

    try {
        if ($action === 'reserve') {
            $amount     = floatval($body['amount'] ?? 0);
            $actionType = $body['action_type'] ?? 'add';
            if ($amount <= 0) {
                http_response_code(400);
                echo json_encode(['error' => 'Amount must be positive']);
                exit;
            }
            $change = ($actionType === 'subtract') ? -$amount : $amount;
            $pdo->prepare("
                INSERT INTO reserves (currency, amount)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE amount = amount + ?
            ")->execute([$currency, $change, $change]);
            echo json_encode(['success' => true]);

        } elseif ($action === 'limits') {
            $min = floatval($body['min'] ?? 0);
            $max = floatval($body['max'] ?? 0);
            $pdo->prepare("
                INSERT INTO reserves (currency, amount, min, max)
                VALUES (?, 0, ?, ?)
                ON DUPLICATE KEY UPDATE min = VALUES(min), max = VALUES(max)
            ")->execute([$currency, $min, $max]);
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
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/reserves.php
git commit -m "feat: add reserves API endpoint"
```

---

## Task 6: Tickets API

**Files:**
- Create: `miniapp/api/tickets.php`

- [ ] **Step 1: Создать tickets.php**

```php
<?php
// miniapp/api/tickets.php
// GET ?status=open|answered|closed|all&page=1  → список тикетов
// GET ?id=TICKET_ID                             → тикет + сообщения
// POST {action:"reply",  ticket_id, message}
// POST {action:"status", ticket_id, new_status}

require_once __DIR__ . '/auth.php';
requireAuth();

$validStatuses = ['open', 'answered', 'closed'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['id'])) {
        $tid = (int)$_GET['id'];
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

            $msgs = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
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

    try {
        if ($status === 'all') {
            $where  = '';
            $params = [];
        } else {
            if (!in_array($status, $validStatuses)) $status = 'open';
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
            LIMIT ? OFFSET ?
        ");
        $stmt->execute(array_merge($params, [$limit, $offset]));

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
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $action   = $body['action']    ?? '';
    $ticketId = (int)($body['ticket_id'] ?? 0);

    if (!$ticketId) {
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
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/api/tickets.php
git commit -m "feat: add tickets API endpoint"
```

---

## Task 7: Update bot-cron.php — Add Mini App button

**Files:**
- Modify: `bot-cron.php`

- [ ] **Step 1: Заменить функцию tgSend на версию с поддержкой reply_markup**

Найти в `bot-cron.php` функцию `tgSend` и заменить полностью:

```php
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
```

- [ ] **Step 2: Заменить блок обработки /start и /help в handleMessage()**

Найти блок `if (in_array($text, ['/start', '/help']))` и заменить вызов tgSend:

```php
if (in_array($text, ['/start', '/help'])) {
    $keyboard = [
        'keyboard'        => [[
            ['text' => '📊 Открыть панель управления', 'web_app' => ['url' => 'https://cr873507.tw1.ru/miniapp/index.html']]
        ]],
        'resize_keyboard' => true,
        'persistent'      => true,
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
```

- [ ] **Step 3: Commit**

```bash
git add bot-cron.php
git commit -m "feat: add Mini App web_app button to bot /start and /help"
```

---

## Task 8: Mini App SPA (index.html)

**Files:**
- Create: `miniapp/index.html`

- [ ] **Step 1: Создать miniapp/index.html**

```html
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>Swap Admin</title>
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<style>
:root {
  --bg:    var(--tg-theme-bg-color,           #17212b);
  --bg2:   var(--tg-theme-secondary-bg-color, #232e3c);
  --text:  var(--tg-theme-text-color,         #f5f5f5);
  --hint:  var(--tg-theme-hint-color,         #708499);
  --link:  var(--tg-theme-link-color,         #2ea6ff);
  --btn:   var(--tg-theme-button-color,       #2ea6ff);
  --btntx: var(--tg-theme-button-text-color,  #ffffff);
  --danger:  #e74c3c;
  --success: #2ecc71;
  --warning: #f39c12;
}
*{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;padding-bottom:64px;font-size:14px}

/* Tab bar */
.tabbar{position:fixed;bottom:0;left:0;right:0;height:60px;background:var(--bg2);border-top:1px solid rgba(255,255,255,.07);display:flex;z-index:200;padding-bottom:env(safe-area-inset-bottom)}
.tab{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:2px;cursor:pointer;color:var(--hint);font-size:10px;font-weight:500;transition:color .2s;padding:8px 0}
.tab.on{color:var(--btn)}
.tab svg{width:22px;height:22px;stroke-width:1.8;fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round}

/* Sections */
.sec{display:none;padding:12px 14px;animation:fadeIn .15s ease}
.sec.on{display:block}
@keyframes fadeIn{from{opacity:0;transform:translateY(4px)}to{opacity:1;transform:none}}

/* Cards */
.card{background:var(--bg2);border-radius:14px;padding:14px;margin-bottom:10px}
.card h3{font-size:13px;color:var(--hint);font-weight:500;margin-bottom:4px}
.card .val{font-size:22px;font-weight:700}
.card-row{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
.card-row:last-child{margin-bottom:0}

/* Metrics grid */
.metrics{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px}

/* Period filter */
.period{display:flex;gap:6px;margin-bottom:12px}
.period button{flex:1;padding:7px 0;border-radius:10px;border:1px solid rgba(255,255,255,.1);background:transparent;color:var(--hint);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s}
.period button.on{background:var(--btn);border-color:var(--btn);color:var(--btntx)}

/* Status tabs */
.stabs{display:flex;gap:6px;margin-bottom:12px;overflow-x:auto;scrollbar-width:none;padding-bottom:2px}
.stabs::-webkit-scrollbar{display:none}
.stab{padding:6px 12px;border-radius:8px;border:1px solid rgba(255,255,255,.1);background:transparent;color:var(--hint);font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap;transition:all .2s;flex-shrink:0}
.stab.on{background:var(--btn);border-color:var(--btn);color:var(--btntx)}

/* List items */
.item{background:var(--bg2);border-radius:12px;padding:12px 14px;margin-bottom:8px;cursor:pointer;transition:opacity .15s}
.item:active{opacity:.7}
.item-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;gap:8px}
.item-title{font-size:14px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:65%}
.item-meta{font-size:11px;color:var(--hint);margin-top:2px}

/* Badges */
.badge{display:inline-block;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;flex-shrink:0}
.badge-new{background:rgba(46,166,255,.15);color:#2ea6ff}
.badge-process{background:rgba(243,156,18,.15);color:#f39c12}
.badge-success{background:rgba(46,204,113,.15);color:#2ecc71}
.badge-canceled{background:rgba(231,76,60,.15);color:#e74c3c}
.badge-open{background:rgba(243,156,18,.15);color:#f39c12}
.badge-answered{background:rgba(46,204,113,.15);color:#2ecc71}
.badge-closed{background:rgba(120,120,120,.15);color:#888}

/* Buttons */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 18px;border-radius:10px;border:none;font-size:13px;font-weight:600;cursor:pointer;transition:opacity .2s;width:100%}
.btn:active{opacity:.75}
.btn-primary{background:var(--btn);color:var(--btntx)}
.btn-danger{background:rgba(231,76,60,.1);color:var(--danger);border:1px solid rgba(231,76,60,.3)}
.btn-ghost{background:transparent;color:var(--text);border:1px solid rgba(255,255,255,.12)}
.btn-sm{padding:6px 12px;font-size:12px;border-radius:8px;width:auto}

/* Forms */
input,textarea,select{background:var(--bg);border:1px solid rgba(255,255,255,.1);border-radius:10px;color:var(--text);font-size:14px;padding:10px 12px;width:100%;outline:none;transition:border .2s;font-family:inherit}
input:focus,textarea:focus,select:focus{border-color:var(--btn)}
label{font-size:12px;color:var(--hint);margin-bottom:4px;display:block}
.form-group{margin-bottom:12px}

/* Modal */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:300;align-items:flex-end}
.overlay.on{display:flex}
.modal{background:var(--bg2);border-radius:20px 20px 0 0;padding:20px 16px;padding-bottom:calc(20px + env(safe-area-inset-bottom));width:100%;max-height:85vh;overflow-y:auto}
.modal-handle{width:36px;height:4px;background:rgba(255,255,255,.15);border-radius:2px;margin:0 auto 16px}
.modal-title{font-size:16px;font-weight:700;margin-bottom:14px}

/* Chat */
.chat{display:flex;flex-direction:column;gap:10px;padding:8px 0}
.msg{max-width:82%;display:flex;flex-direction:column;gap:3px}
.msg.user{align-self:flex-start}
.msg.admin{align-self:flex-end}
.msg-bubble{padding:10px 14px;border-radius:16px;font-size:13px;line-height:1.5;word-break:break-word}
.msg.user .msg-bubble{background:var(--bg);border-top-left-radius:4px}
.msg.admin .msg-bubble{background:rgba(46,166,255,.15);border-top-right-radius:4px}
.msg-meta{font-size:10px;color:var(--hint)}
.msg.admin .msg-meta{text-align:right}

/* Reply form */
.reply-form{display:flex;gap:8px;align-items:flex-end;padding-top:12px;border-top:1px solid rgba(255,255,255,.07);margin-top:8px}
.reply-form textarea{resize:none;min-height:44px;max-height:100px;padding:10px 12px;border-radius:12px}
.reply-send{width:44px;height:44px;min-width:44px;border-radius:12px;background:var(--btn);border:none;color:var(--btntx);cursor:pointer;display:flex;align-items:center;justify-content:center}
.reply-send svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

/* Pagination */
.pagination{display:flex;align-items:center;justify-content:center;gap:10px;padding:12px 0}
.pagination button{padding:8px 16px;border-radius:10px;border:1px solid rgba(255,255,255,.1);background:transparent;color:var(--text);font-size:13px;cursor:pointer}
.pagination button:disabled{opacity:.3;cursor:default}
.pagination span{font-size:12px;color:var(--hint)}

/* Rates */
.rates-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.rate-item{background:var(--bg);border-radius:10px;padding:10px;text-align:center}
.rate-name{font-size:10px;color:var(--hint);margin-bottom:4px}
.rate-val{font-size:13px;font-weight:700}

/* Reserves table */
.rtable{width:100%;border-collapse:collapse}
.rtable th{font-size:11px;color:var(--hint);text-align:left;padding:6px 8px;border-bottom:1px solid rgba(255,255,255,.07)}
.rtable td{padding:10px 8px;border-bottom:1px solid rgba(255,255,255,.04);font-size:13px;vertical-align:middle}
.rtable tr:last-child td{border-bottom:none}
.rtable-actions{display:flex;gap:6px}

/* Loading / empty */
.loading{text-align:center;padding:32px;color:var(--hint);font-size:13px}
.empty{text-align:center;padding:32px;color:var(--hint);font-size:13px}
.spinner{width:24px;height:24px;border:2px solid rgba(255,255,255,.1);border-top-color:var(--btn);border-radius:50%;animation:spin .7s linear infinite;margin:0 auto 10px}
@keyframes spin{to{transform:rotate(360deg)}}

/* Toast */
.toast{position:fixed;top:16px;left:50%;transform:translateX(-50%);background:var(--bg2);color:var(--text);padding:10px 18px;border-radius:12px;font-size:13px;font-weight:500;z-index:500;opacity:0;transition:opacity .3s;pointer-events:none;white-space:nowrap;border:1px solid rgba(255,255,255,.1)}
.toast.on{opacity:1}

.sec-title{font-size:18px;font-weight:700;margin-bottom:14px}
.gap-8{display:flex;flex-direction:column;gap:8px}
</style>
</head>
<body>

<!-- ═══ SECTIONS ═══ -->
<div id="sec-dashboard" class="sec on">
  <div class="sec-title">📊 Дашборд</div>
  <div class="period">
    <button class="on" onclick="loadDashboard('today',this)">Сегодня</button>
    <button onclick="loadDashboard('7d',this)">7 дней</button>
    <button onclick="loadDashboard('30d',this)">30 дней</button>
  </div>
  <div id="dashboard-content"><div class="loading"><div class="spinner"></div>Загрузка...</div></div>
</div>

<div id="sec-orders" class="sec">
  <div class="sec-title">📋 Заявки</div>
  <div class="stabs" id="order-stabs"></div>
  <div id="orders-content"><div class="loading"><div class="spinner"></div>Загрузка...</div></div>
</div>

<div id="sec-users" class="sec">
  <div class="sec-title">👥 Пользователи</div>
  <div class="form-group"><input type="search" id="user-search" placeholder="Поиск по имени или email…" oninput="debounceSearch()"></div>
  <div id="users-content"><div class="loading"><div class="spinner"></div>Загрузка...</div></div>
</div>

<div id="sec-reserves" class="sec">
  <div class="sec-title">💰 Резервы</div>
  <div id="reserves-content"><div class="loading"><div class="spinner"></div>Загрузка...</div></div>
</div>

<div id="sec-tickets" class="sec">
  <div class="sec-title">🎫 Тикеты</div>
  <div class="stabs" id="ticket-stabs"></div>
  <div id="tickets-content"><div class="loading"><div class="spinner"></div>Загрузка...</div></div>
</div>

<!-- ═══ TAB BAR ═══ -->
<nav class="tabbar">
  <div class="tab on" data-tab="dashboard" onclick="showTab('dashboard')">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Дашборд
  </div>
  <div class="tab" data-tab="orders" onclick="showTab('orders')">
    <svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
    Заявки
  </div>
  <div class="tab" data-tab="users" onclick="showTab('users')">
    <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
    Люди
  </div>
  <div class="tab" data-tab="reserves" onclick="showTab('reserves')">
    <svg viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
    Резервы
  </div>
  <div class="tab" data-tab="tickets" onclick="showTab('tickets')">
    <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
    Тикеты
  </div>
</nav>

<!-- ═══ MODALS ═══ -->
<div id="order-modal" class="overlay" onclick="closeModal('order-modal',event)">
  <div class="modal"><div class="modal-handle"></div><div id="order-modal-content"></div></div>
</div>
<div id="user-modal" class="overlay" onclick="closeModal('user-modal',event)">
  <div class="modal"><div class="modal-handle"></div><div id="user-modal-content"></div></div>
</div>
<div id="reserve-modal" class="overlay" onclick="closeModal('reserve-modal',event)">
  <div class="modal"><div class="modal-handle"></div><div id="reserve-modal-content"></div></div>
</div>
<div id="ticket-modal" class="overlay" onclick="closeModal('ticket-modal',event)">
  <div class="modal" style="max-height:90vh"><div class="modal-handle"></div><div id="ticket-modal-content"></div></div>
</div>

<div id="toast" class="toast"></div>

<!-- ═══ SCRIPTS ═══ -->
<script>
const tg       = window.Telegram.WebApp;
const initData = tg.initData;
tg.ready();
tg.expand();

const BASE = '/miniapp/api';

async function api(path, options) {
  options = options || {};
  const res = await fetch(BASE + path, Object.assign({}, options, {
    headers: Object.assign({ 'X-Telegram-Init-Data': initData, 'Content-Type': 'application/json' }, options.headers || {})
  }));
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || 'Ошибка');
  return data;
}

function post(path, body) {
  return api(path, { method: 'POST', body: JSON.stringify(body) });
}

function toast(msg, dur) {
  dur = dur || 2500;
  var el = document.getElementById('toast');
  el.textContent = msg;
  el.classList.add('on');
  setTimeout(function(){ el.classList.remove('on'); }, dur);
}

function openModal(id) { document.getElementById(id).classList.add('on'); }
function closeModal(id, ev) { if (!ev || ev.target === document.getElementById(id)) document.getElementById(id).classList.remove('on'); }
function closeModalById(id) { document.getElementById(id).classList.remove('on'); }

var activeTab = 'dashboard';
var loaded = {};

function showTab(name) {
  document.querySelectorAll('.sec').forEach(function(s){ s.classList.remove('on'); });
  document.querySelectorAll('.tab').forEach(function(t){ t.classList.remove('on'); });
  document.getElementById('sec-' + name).classList.add('on');
  document.querySelector('[data-tab="' + name + '"]').classList.add('on');
  activeTab = name;
  if (!loaded[name]) { loaded[name] = true; loadSection(name); }
}

function loadSection(name) {
  if      (name === 'dashboard') loadDashboard('today', document.querySelector('.period .on'));
  else if (name === 'orders')    loadOrders('all', 1);
  else if (name === 'users')     loadUsers('', 1);
  else if (name === 'reserves')  loadReserves();
  else if (name === 'tickets')   loadTickets('open', 1);
}

var statusLabel = { new:'Новая', in_process:'В работе', success:'Выполнена', canceled:'Отменена', open:'Открыт', answered:'Отвечен', closed:'Закрыт' };
var statusBadgeClass = { new:'badge-new', in_process:'badge-process', success:'badge-success', canceled:'badge-canceled', open:'badge-open', answered:'badge-answered', closed:'badge-closed' };

function badge(status) {
  return '<span class="badge ' + (statusBadgeClass[status] || '') + '">' + (statusLabel[status] || status) + '</span>';
}

function fmtDate(s) {
  if (!s) return '';
  var d = new Date(s);
  return d.toLocaleDateString('ru', { day:'2-digit', month:'2-digit' }) + ' ' + d.toLocaleTimeString('ru', { hour:'2-digit', minute:'2-digit' });
}

function fmtNum(n, dec) {
  if (n == null) return '—';
  dec = dec !== undefined ? dec : 2;
  return Number(n).toLocaleString('ru', { maximumFractionDigits: dec });
}

function esc(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── DASHBOARD ─────────────────────────────────────────── */
async function loadDashboard(period, btn) {
  if (btn) {
    document.querySelectorAll('.period button').forEach(function(b){ b.classList.remove('on'); });
    btn.classList.add('on');
  }
  var el = document.getElementById('dashboard-content');
  el.innerHTML = '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  try {
    var d = await api('/dashboard.php?period=' + period);
    el.innerHTML =
      '<div class="metrics">' +
        '<div class="card"><h3>Новые пользователи</h3><div class="val">' + d.new_users + '</div></div>' +
        '<div class="card"><h3>Открытых тикетов</h3><div class="val" style="color:var(--warning)">' + d.open_tickets + '</div></div>' +
        '<div class="card"><h3>Всего заявок</h3><div class="val">' + d.orders.total + '</div></div>' +
        '<div class="card"><h3>Успешных</h3><div class="val" style="color:var(--success)">' + d.orders.success + '</div></div>' +
        '<div class="card"><h3>В работе</h3><div class="val" style="color:var(--warning)">' + d.orders.in_process + '</div></div>' +
        '<div class="card"><h3>Отменено</h3><div class="val" style="color:var(--danger)">' + d.orders.canceled + '</div></div>' +
      '</div>' +
      (d.rates ? '<div class="card"><h3 style="margin-bottom:10px">Курсы (RUB)</h3><div class="rates-grid">' +
        '<div class="rate-item"><div class="rate-name">BTC</div><div class="rate-val">' + fmtNum(d.rates.BTC_RUB, 0) + ' ₽</div></div>' +
        '<div class="rate-item"><div class="rate-name">ETH</div><div class="rate-val">' + fmtNum(d.rates.ETH_RUB, 0) + ' ₽</div></div>' +
        '<div class="rate-item"><div class="rate-name">USDT</div><div class="rate-val">' + fmtNum(d.rates.USDT_RUB, 2) + ' ₽</div></div>' +
      '</div></div>' : '');
  } catch(e) { el.innerHTML = '<div class="empty">Ошибка: ' + esc(e.message) + '</div>'; }
}

/* ── ORDERS ────────────────────────────────────────────── */
var orderStatus = 'all', orderPage = 1;

async function loadOrders(status, page) {
  orderStatus = status; orderPage = page;
  renderOrderTabs();
  var el = document.getElementById('orders-content');
  el.innerHTML = '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  try {
    var d = await api('/orders.php?status=' + status + '&page=' + page);
    var html = '';
    if (!d.orders.length) {
      html = '<div class="empty">Нет заявок</div>';
    } else {
      d.orders.forEach(function(o) {
        html += '<div class="item" onclick=\'openOrder(' + JSON.stringify(o) + ')\'>' +
          '<div class="item-top"><div class="item-title">' + esc(o.give_currency) + ' → ' + esc(o.get_currency) + '</div>' + badge(o.status) + '</div>' +
          '<div class="item-meta">#' + o.id + ' · ' + esc(o.username || '—') + ' · ' + fmtDate(o.created_at) + '</div>' +
          '<div class="item-meta">' + fmtNum(o.amount_give) + ' → ' + fmtNum(o.amount_get) + '</div>' +
        '</div>';
      });
    }
    if (d.pages > 1) {
      html += '<div class="pagination">' +
        '<button onclick="loadOrders(\'' + status + '\',' + (page-1) + ')" ' + (page<=1?'disabled':'') + '>←</button>' +
        '<span>' + page + ' / ' + d.pages + '</span>' +
        '<button onclick="loadOrders(\'' + status + '\',' + (page+1) + ')" ' + (page>=d.pages?'disabled':'') + '>→</button>' +
      '</div>';
    }
    el.innerHTML = html;
  } catch(e) { el.innerHTML = '<div class="empty">Ошибка: ' + esc(e.message) + '</div>'; }
}

function renderOrderTabs() {
  var tabs = [['all','Все'],['new','Новые'],['in_process','В работе'],['success','Выполнены'],['canceled','Отменены']];
  document.getElementById('order-stabs').innerHTML = tabs.map(function(t){
    return '<button class="stab ' + (orderStatus===t[0]?'on':'') + '" onclick="loadOrders(\'' + t[0] + '\',1)">' + t[1] + '</button>';
  }).join('');
}

function openOrder(o) {
  var others = [['new','Новая'],['in_process','В работе'],['success','Выполнена'],['canceled','Отменена']].filter(function(s){ return s[0] !== o.status; });
  document.getElementById('order-modal-content').innerHTML =
    '<div class="modal-title">Заявка #' + o.id + '</div>' +
    '<div class="card" style="margin-bottom:12px">' +
      '<div class="card-row"><span style="color:var(--hint)">Обмен</span><b>' + esc(o.give_currency) + ' → ' + esc(o.get_currency) + '</b></div>' +
      '<div class="card-row"><span style="color:var(--hint)">Сумма</span><span>' + fmtNum(o.amount_give) + ' → ' + fmtNum(o.amount_get) + '</span></div>' +
      '<div class="card-row"><span style="color:var(--hint)">Пользователь</span><span>' + esc(o.username || '—') + '</span></div>' +
      '<div class="card-row"><span style="color:var(--hint)">Дата</span><span>' + fmtDate(o.created_at) + '</span></div>' +
      '<div class="card-row"><span style="color:var(--hint)">Статус</span>' + badge(o.status) + '</div>' +
    '</div>' +
    '<div class="gap-8">' +
      others.map(function(s){ return '<button class="btn btn-ghost" onclick="changeOrderStatus(\'' + o.id + '\',\'' + s[0] + '\')">→ ' + s[1] + '</button>'; }).join('') +
      '<button class="btn btn-danger" onclick="deleteOrder(\'' + o.id + '\')">🗑 Удалить заявку</button>' +
    '</div>';
  openModal('order-modal');
}

async function changeOrderStatus(orderId, newStatus) {
  try {
    await post('/orders.php', { action:'status', order_id:orderId, new_status:newStatus });
    toast('Статус обновлён ✓');
    closeModalById('order-modal');
    loadOrders(orderStatus, orderPage);
  } catch(e) { toast('Ошибка: ' + e.message); }
}

async function deleteOrder(orderId) {
  if (!confirm('Удалить заявку #' + orderId + '?')) return;
  try {
    await post('/orders.php', { action:'delete', order_id:orderId });
    toast('Заявка удалена ✓');
    closeModalById('order-modal');
    loadOrders(orderStatus, orderPage);
  } catch(e) { toast('Ошибка: ' + e.message); }
}

/* ── USERS ─────────────────────────────────────────────── */
var userPage = 1, searchTimer;

function debounceSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(function(){ loadUsers(document.getElementById('user-search').value, 1); }, 400);
}

async function loadUsers(search, page) {
  userPage = page;
  var el = document.getElementById('users-content');
  el.innerHTML = '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  try {
    var d = await api('/users.php?search=' + encodeURIComponent(search || '') + '&page=' + page);
    var html = '';
    if (!d.users.length) {
      html = '<div class="empty">Нет пользователей</div>';
    } else {
      d.users.forEach(function(u) {
        html += '<div class="item" onclick="openUser(' + u.id + ',\'' + esc(u.username || '') + '\')">' +
          '<div class="item-top"><div class="item-title">' + esc(u.username || '—') + '</div><span style="font-size:11px;color:var(--hint)">' + esc(u.role) + '</span></div>' +
          '<div class="item-meta">' + esc(u.email) + ' · ' + u.order_count + ' заявок</div>' +
          '<div class="item-meta">' + fmtDate(u.created_at) + '</div>' +
        '</div>';
      });
    }
    if (d.pages > 1) {
      var sq = encodeURIComponent(search || '');
      html += '<div class="pagination">' +
        '<button onclick="loadUsers(\'' + sq + '\',' + (page-1) + ')" ' + (page<=1?'disabled':'') + '>←</button>' +
        '<span>' + page + ' / ' + d.pages + '</span>' +
        '<button onclick="loadUsers(\'' + sq + '\',' + (page+1) + ')" ' + (page>=d.pages?'disabled':'') + '>→</button>' +
      '</div>';
    }
    el.innerHTML = html;
  } catch(e) { el.innerHTML = '<div class="empty">Ошибка: ' + esc(e.message) + '</div>'; }
}

async function openUser(userId, username) {
  document.getElementById('user-modal-content').innerHTML =
    '<div class="modal-title">' + esc(username) + '</div><div class="loading"><div class="spinner"></div>Загрузка...</div>';
  openModal('user-modal');
  try {
    var orders = await api('/users.php?history=' + userId);
    var html = '<div class="modal-title">История: ' + esc(username) + '</div>';
    if (!orders.length) {
      html += '<div class="empty">Нет заявок</div>';
    } else {
      orders.forEach(function(o) {
        html += '<div class="item" style="cursor:default">' +
          '<div class="item-top"><div class="item-title">' + esc(o.give_currency) + ' → ' + esc(o.get_currency) + '</div>' + badge(o.status) + '</div>' +
          '<div class="item-meta">#' + o.id + ' · ' + fmtDate(o.created_at) + '</div>' +
          '<div class="item-meta">' + fmtNum(o.amount_give) + ' → ' + fmtNum(o.amount_get) + '</div>' +
        '</div>';
      });
    }
    document.getElementById('user-modal-content').innerHTML = html;
  } catch(e) {
    document.getElementById('user-modal-content').innerHTML =
      '<div class="modal-title">' + esc(username) + '</div><div class="empty">Ошибка: ' + esc(e.message) + '</div>';
  }
}

/* ── RESERVES ──────────────────────────────────────────── */
async function loadReserves() {
  var el = document.getElementById('reserves-content');
  el.innerHTML = '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  try {
    var reserves = await api('/reserves.php');
    var html = '<div class="card"><table class="rtable"><thead><tr><th>Валюта</th><th>Остаток</th><th>Лимиты</th><th></th></tr></thead><tbody>';
    reserves.forEach(function(r) {
      html += '<tr>' +
        '<td><b>' + esc(r.currency) + '</b></td>' +
        '<td>' + fmtNum(r.amount, 4) + '</td>' +
        '<td style="font-size:11px;color:var(--hint)">' + (r.min || '—') + ' / ' + (r.max || '—') + '</td>' +
        '<td><div class="rtable-actions">' +
          '<button class="btn btn-ghost btn-sm" onclick="openReserveModal(\'' + r.currency + '\',\'reserve\')">±</button>' +
          '<button class="btn btn-ghost btn-sm" onclick="openReserveModal(\'' + r.currency + '\',\'limits\')">Лим</button>' +
        '</div></td>' +
      '</tr>';
    });
    html += '</tbody></table></div>';
    el.innerHTML = html;
  } catch(e) { el.innerHTML = '<div class="empty">Ошибка: ' + esc(e.message) + '</div>'; }
}

function openReserveModal(currency, mode) {
  var html;
  if (mode === 'reserve') {
    html = '<div class="modal-title">Резерв ' + esc(currency) + '</div>' +
      '<div class="form-group"><label>Сумма</label><input type="number" id="res-amount" step="any" min="0" placeholder="0"></div>' +
      '<div class="form-group"><label>Действие</label><select id="res-action"><option value="add">Пополнить (+)</option><option value="subtract">Списать (−)</option></select></div>' +
      '<button class="btn btn-primary" onclick="submitReserve(\'' + currency + '\')">Применить</button>';
  } else {
    html = '<div class="modal-title">Лимиты ' + esc(currency) + '</div>' +
      '<div class="form-group"><label>Минимум</label><input type="number" id="res-min" step="any" min="0" placeholder="0"></div>' +
      '<div class="form-group"><label>Максимум</label><input type="number" id="res-max" step="any" min="0" placeholder="0"></div>' +
      '<button class="btn btn-primary" onclick="submitLimits(\'' + currency + '\')">Сохранить</button>';
  }
  document.getElementById('reserve-modal-content').innerHTML = html;
  openModal('reserve-modal');
}

async function submitReserve(currency) {
  var amount = parseFloat(document.getElementById('res-amount').value);
  var actionType = document.getElementById('res-action').value;
  if (!amount || amount <= 0) { toast('Введите сумму'); return; }
  try {
    await post('/reserves.php', { action:'reserve', currency:currency, amount:amount, action_type:actionType });
    toast('Резерв обновлён ✓');
    closeModalById('reserve-modal');
    loadReserves();
  } catch(e) { toast('Ошибка: ' + e.message); }
}

async function submitLimits(currency) {
  var min = parseFloat(document.getElementById('res-min').value) || 0;
  var max = parseFloat(document.getElementById('res-max').value) || 0;
  try {
    await post('/reserves.php', { action:'limits', currency:currency, min:min, max:max });
    toast('Лимиты сохранены ✓');
    closeModalById('reserve-modal');
    loadReserves();
  } catch(e) { toast('Ошибка: ' + e.message); }
}

/* ── TICKETS ───────────────────────────────────────────── */
var ticketStatus = 'open', ticketPage = 1;

async function loadTickets(status, page) {
  ticketStatus = status; ticketPage = page;
  var el = document.getElementById('tickets-content');
  el.innerHTML = '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  try {
    var d = await api('/tickets.php?status=' + status + '&page=' + page);
    var tabDefs = [['open','Открытые'],['answered','Отвечены'],['closed','Закрытые'],['all','Все']];
    document.getElementById('ticket-stabs').innerHTML = tabDefs.map(function(t){
      return '<button class="stab ' + (ticketStatus===t[0]?'on':'') + '" onclick="loadTickets(\'' + t[0] + '\',1)">' + t[1] + ' <small>' + (d.counts[t[0]]||0) + '</small></button>';
    }).join('');

    var html = '';
    if (!d.tickets.length) {
      html = '<div class="empty">Нет тикетов</div>';
    } else {
      d.tickets.forEach(function(t) {
        var waiting = (t.last_sender === 'user' && t.status === 'open') ? '<span class="badge badge-open" style="font-size:9px;margin-left:4px">Ждёт</span>' : '';
        html += '<div class="item" onclick="openTicket(' + t.id + ')">' +
          '<div class="item-top"><div class="item-title">#' + t.id + ' ' + esc(t.subject) + '</div>' + badge(t.status) + '</div>' +
          '<div class="item-meta">' + esc(t.username) + ' · ' + t.msg_count + ' сообщ. · ' + fmtDate(t.updated_at) + waiting + '</div>' +
        '</div>';
      });
    }
    if (d.pages > 1) {
      html += '<div class="pagination">' +
        '<button onclick="loadTickets(\'' + status + '\',' + (page-1) + ')" ' + (page<=1?'disabled':'') + '>←</button>' +
        '<span>' + page + ' / ' + d.pages + '</span>' +
        '<button onclick="loadTickets(\'' + status + '\',' + (page+1) + ')" ' + (page>=d.pages?'disabled':'') + '>→</button>' +
      '</div>';
    }
    el.innerHTML = html;
  } catch(e) { el.innerHTML = '<div class="empty">Ошибка: ' + esc(e.message) + '</div>'; }
}

async function openTicket(ticketId) {
  document.getElementById('ticket-modal-content').innerHTML =
    '<div class="loading"><div class="spinner"></div>Загрузка...</div>';
  openModal('ticket-modal');
  try {
    var t = await api('/tickets.php?id=' + ticketId);
    var isClosed = t.status === 'closed';
    var msgs = '<div class="chat">';
    if (!t.messages || !t.messages.length) {
      msgs += '<div class="empty">Нет сообщений</div>';
    } else {
      t.messages.forEach(function(m) {
        var side = m.sender === 'admin' ? 'admin' : 'user';
        msgs += '<div class="msg ' + side + '">' +
          '<div class="msg-bubble">' + esc(m.message).replace(/\n/g,'<br>') + '</div>' +
          '<div class="msg-meta">' + (m.sender === 'admin' ? 'Поддержка' : esc(t.username)) + ' · ' + fmtDate(m.created_at) + '</div>' +
        '</div>';
      });
    }
    msgs += '</div>';

    var replyForm = isClosed ? '' :
      '<div class="reply-form">' +
        '<textarea id="reply-text-' + ticketId + '" placeholder="Ответ пользователю…" rows="2"></textarea>' +
        '<button class="reply-send" onclick="sendReply(' + ticketId + ')">' +
          '<svg viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>' +
        '</button>' +
      '</div>';

    var statusBtns = [];
    if (t.status !== 'open')   statusBtns.push('<button class="btn btn-ghost btn-sm" onclick="changeTicketStatus(' + ticketId + ',\'open\')">Открыть заново</button>');
    if (t.status !== 'closed') statusBtns.push('<button class="btn btn-danger btn-sm" onclick="changeTicketStatus(' + ticketId + ',\'closed\')">Закрыть тикет</button>');

    document.getElementById('ticket-modal-content').innerHTML =
      '<div class="modal-title">#' + t.id + ' ' + esc(t.subject) + '</div>' +
      '<div style="font-size:12px;color:var(--hint);margin-bottom:12px">' + esc(t.username) + ' · ' + badge(t.status) + '</div>' +
      '<div style="max-height:42vh;overflow-y:auto">' + msgs + '</div>' +
      replyForm +
      '<div style="display:flex;gap:8px;margin-top:12px;flex-wrap:wrap">' + statusBtns.join('') + '</div>';

    setTimeout(function() {
      var sc = document.querySelector('#ticket-modal .modal');
      if (sc) sc.scrollTop = sc.scrollHeight;
    }, 60);
  } catch(e) {
    document.getElementById('ticket-modal-content').innerHTML =
      '<div class="empty">Ошибка: ' + esc(e.message) + '</div>';
  }
}

async function sendReply(ticketId) {
  var ta = document.getElementById('reply-text-' + ticketId);
  var message = ta ? ta.value.trim() : '';
  if (!message) { toast('Введите ответ'); return; }
  try {
    await post('/tickets.php', { action:'reply', ticket_id:ticketId, message:message });
    toast('Ответ отправлен ✓');
    closeModalById('ticket-modal');
    loadTickets(ticketStatus, ticketPage);
  } catch(e) { toast('Ошибка: ' + e.message); }
}

async function changeTicketStatus(ticketId, newStatus) {
  try {
    await post('/tickets.php', { action:'status', ticket_id:ticketId, new_status:newStatus });
    toast('Статус обновлён ✓');
    closeModalById('ticket-modal');
    loadTickets(ticketStatus, ticketPage);
  } catch(e) { toast('Ошибка: ' + e.message); }
}

/* ── Init ─── */
loadDashboard('today', document.querySelector('.period .on'));
renderOrderTabs();
</script>
</body>
</html>
```

- [ ] **Step 2: Commit**

```bash
git add miniapp/index.html
git commit -m "feat: add Telegram Mini App SPA (index.html)"
```

---

## Task 9: Настройка Mini App и деплой

- [ ] **Step 1: Загрузить файлы на Timeweb**

Загрузить по FTP/SSH в `/home/c/cr873507/exchanger/public_html/`:
```
miniapp/index.html
miniapp/api/auth.php
miniapp/api/dashboard.php
miniapp/api/orders.php
miniapp/api/users.php
miniapp/api/reserves.php
miniapp/api/tickets.php
bot-cron.php  (обновлённый)
```

- [ ] **Step 2: Настроить Menu Button в BotFather**

Написать @BotFather:
1. `/mybots` → выбрать бота
2. `Bot Settings` → `Menu Button`
3. Ввести URL: `https://cr873507.tw1.ru/miniapp/index.html`
4. Ввести текст кнопки: `Панель управления`

- [ ] **Step 3: Проверить каждый раздел**

Написать боту `/start` → нажать кнопку **"📊 Открыть панель управления"**:

- [ ] Дашборд открывается, переключение периодов работает, курсы отображаются
- [ ] Заявки: фильтры переключаются, тап на заявку открывает модалку, смена статуса работает
- [ ] Пользователи: поиск срабатывает с задержкой, история заявок открывается
- [ ] Резервы: данные видны, пополнение/списание обновляет таблицу
- [ ] Тикеты: список с фильтрами, чат открывается, ответ сохраняется, статус меняется

- [ ] **Step 4: Final commit**

```bash
git add .
git commit -m "feat: complete Telegram Mini App admin panel"
```

---

## Self-Review

**Spec coverage:**
- ✅ auth.php — HMAC-SHA256, auth_date freshness, whitelist check (Task 1)
- ✅ Dashboard API — метрики по периодам, курсы из кеша (Task 2)
- ✅ Orders API — список с пагинацией, смена статуса, удаление (Task 3)
- ✅ Users API — список, поиск, история (Task 4)
- ✅ Reserves API — чтение, пополнение/списание, лимиты (Task 5)
- ✅ Tickets API — список, детали+сообщения, ответ, смена статуса (Task 6)
- ✅ bot-cron.php — web_app keyboard button в /start и /help (Task 7)
- ✅ SPA — 5 разделов, нижний таб-бар, модальные окна, мобильная вёрстка (Task 8)
- ✅ BotFather настройка + деплой (Task 9)

**Type/naming consistency:**
- `api()` и `post()` используются одинаково во всех разделах ✅
- `BASE = '/miniapp/api'` соответствует путям файлов `miniapp/api/*.php` ✅
- `ticket_id` передаётся числом, PHP принимает через `(int)` ✅
- `order_id` передаётся строкой (может быть UUID), PHP `trim()` ✅
- `requireAuth()` вызывается первой строкой в каждом API-файле ✅

**Placeholder scan:** нет TBD, нет TODO, весь код полный ✅
