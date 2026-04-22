<?php
// admin/orders.php — админ-панель заявок

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['change_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];

        $valid_statuses = ['new', 'in_process', 'success', 'canceled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $_SESSION['toast'] = ['type' => 'success', 'message' => "Статус заявки $order_id изменён"];
        }
    }

    if (isset($_POST['delete_order'])) {
        $order_id = $_POST['order_id'];
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка $order_id удалена"];
    }

    header('Location: orders.php');
    exit;
}

$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$new_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$in_process   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'in_process'")->fetchColumn();
$success      = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'success'")->fetchColumn();
$canceled     = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'canceled'")->fetchColumn();

// Фильтр по статусу
$filter = $_GET['status'] ?? 'all';
$validFilters = ['all', 'new', 'in_process', 'success', 'canceled'];
if (!in_array($filter, $validFilters)) $filter = 'all';

if ($filter === 'all') {
    $stmt = $pdo->query("SELECT o.*, u.username, u.telegram FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT o.*, u.username, u.telegram FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.status = ? ORDER BY o.created_at DESC");
    $stmt->execute([$filter]);
}
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filterLabels = [
    'all'        => ['label' => 'Все',        'count' => $total_orders],
    'new'        => ['label' => 'Новые',      'count' => $new_orders],
    'in_process' => ['label' => 'В работе',   'count' => $in_process],
    'success'    => ['label' => 'Успешные',   'count' => $success],
    'canceled'   => ['label' => 'Отменены',   'count' => $canceled],
];

$page_title = 'Заявки — Админ-панель';
$admin_page = 'orders.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once __DIR__ . '/../theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php if (isset($_SESSION['toast'])): ?>
  <div id="toast" class="toast-w <?= htmlspecialchars($_SESSION['toast']['type']) ?>">
    <?= htmlspecialchars($_SESSION['toast']['message']) ?>
  </div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-6 py-10">

  <section class="mb-8 fade-in">
    <h1 class="text-3xl font-bold tracking-tight mb-1">Заявки</h1>
    <p class="text-sm text-txt-muted">Управление заявками на обмен</p>
  </section>

  <!-- Stat filters -->
  <section class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <?php foreach ($filterLabels as $key => $row):
      $iconMap = ['all' => 'list', 'new' => 'clock', 'in_process' => 'loader', 'success' => 'check-circle-2', 'canceled' => 'x-circle'];
      $colorMap = ['all' => 'text-txt-secondary', 'new' => 'text-warn', 'in_process' => 'text-cy', 'success' => 'text-emr', 'canceled' => 'text-danger'];
      $active = $filter === $key;
    ?>
      <a href="?status=<?= $key ?>" class="gborder spot rounded-xl bg-bg-card p-4 transition <?= $active ? 'ring-2 ring-cy/40' : '' ?>">
        <div class="flex items-center justify-between mb-1.5">
          <span class="text-[11px] text-txt-muted uppercase tracking-wider"><?= $row['label'] ?></span>
          <i data-lucide="<?= $iconMap[$key] ?>" class="w-4 h-4 <?= $colorMap[$key] ?>"></i>
        </div>
        <div class="text-2xl font-bold <?= $colorMap[$key] ?>"><?= $row['count'] ?></div>
      </a>
    <?php endforeach; ?>
  </section>

  <!-- Orders table -->
  <section class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
    <div class="flex items-center justify-between px-6 py-5 border-b border-line">
      <div class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center">
          <i data-lucide="file-text" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-lg font-bold">
          <?= htmlspecialchars($filterLabels[$filter]['label']) ?>
          <span class="text-xs text-txt-muted font-normal ml-2"><?= count($orders) ?> шт.</span>
        </h2>
      </div>
    </div>

    <?php if (empty($orders)): ?>
      <div class="p-12 text-center">
        <div class="w-16 h-16 rounded-full bg-bg-soft border border-line mx-auto mb-4 flex items-center justify-center">
          <i data-lucide="inbox" class="w-7 h-7 text-txt-muted"></i>
        </div>
        <p class="text-txt-secondary">Заявок в этом статусе нет</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
              <th class="px-5 py-3 font-medium">№ / Дата</th>
              <th class="px-3 py-3 font-medium">Пользователь</th>
              <th class="px-3 py-3 font-medium">Отдаёт</th>
              <th class="px-3 py-3 font-medium">Получает</th>
              <th class="px-3 py-3 font-medium">Статус</th>
              <th class="px-5 py-3 font-medium text-right">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($orders as $order):
              $status = $order['status'] ?? 'new';
              $statuses = [
                  'new'        => ['text' => 'Новая',       'cls' => 'st-new',    'icon' => 'clock'],
                  'in_process' => ['text' => 'В обработке', 'cls' => 'st-proc',   'icon' => 'loader'],
                  'success'    => ['text' => 'Успешно',     'cls' => 'st-ok',     'icon' => 'check-circle-2'],
                  'canceled'   => ['text' => 'Отменено',    'cls' => 'st-cancel', 'icon' => 'x-circle'],
              ];
              $s = $statuses[$status] ?? $statuses['new'];
            ?>
              <tr class="row-h transition">
                <td class="px-5 py-4 align-top whitespace-nowrap">
                  <div class="font-mono text-xs text-txt-secondary">#<?= htmlspecialchars($order['id']) ?></div>
                  <div class="text-[11px] text-txt-muted mt-0.5"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                </td>
                <td class="px-3 py-4 align-top">
                  <div class="font-medium"><?= htmlspecialchars($order['username'] ?? 'ID: ' . $order['user_id']) ?></div>
                  <?php if (!empty($order['telegram'])): ?>
                    <div class="text-[11px] text-cy mt-0.5"><?= htmlspecialchars($order['telegram']) ?></div>
                  <?php endif; ?>
                </td>
                <td class="px-3 py-4 align-top whitespace-nowrap">
                  <div class="font-medium"><?= number_format($order['amount_give'], 2, '.', ' ') ?></div>
                  <div class="text-[11px] text-txt-muted"><?= htmlspecialchars($order['give_currency']) ?></div>
                </td>
                <td class="px-3 py-4 align-top whitespace-nowrap">
                  <div class="font-medium text-emr"><?= number_format($order['amount_get'], 2, '.', ' ') ?></div>
                  <div class="text-[11px] text-txt-muted"><?= htmlspecialchars($order['get_currency']) ?></div>
                </td>
                <td class="px-3 py-4 align-top">
                  <span class="st <?= $s['cls'] ?>">
                    <i data-lucide="<?= $s['icon'] ?>" class="w-3 h-3"></i>
                    <?= $s['text'] ?>
                  </span>
                </td>
                <td class="px-5 py-4 align-top">
                  <div class="flex items-center justify-end gap-2 flex-wrap">
                    <form method="POST" class="flex items-center gap-1.5">
                      <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                      <select name="new_status" class="input-d h-8 px-2 pr-7 rounded-md text-xs">
                        <option value="new"        <?= $status === 'new' ? 'selected' : '' ?>>Новая</option>
                        <option value="in_process" <?= $status === 'in_process' ? 'selected' : '' ?>>В обработке</option>
                        <option value="success"    <?= $status === 'success' ? 'selected' : '' ?>>Успешно</option>
                        <option value="canceled"   <?= $status === 'canceled' ? 'selected' : '' ?>>Отменено</option>
                      </select>
                      <button type="submit" name="change_status" class="btn-cy h-8 px-3 rounded-md text-xs font-medium flex items-center gap-1">
                        <i data-lucide="check" class="w-3 h-3"></i>
                      </button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Удалить заявку #<?= htmlspecialchars($order['id']) ?>? Это действие необратимо.');">
                      <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                      <button type="submit" name="delete_order" class="btn-danger h-8 px-2.5 rounded-md text-xs flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3 h-3"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>