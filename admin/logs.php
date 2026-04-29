<?php
// admin/logs.php — просмотр логов активности

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// === Фильтры ===
$filter_role    = $_GET['role']    ?? '';
$filter_result  = $_GET['result']  ?? '';
$filter_action  = $_GET['action']  ?? '';
$filter_ip      = trim($_GET['ip'] ?? '');
$filter_user    = trim($_GET['user'] ?? '');
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to   = $_GET['date_to']   ?? '';
$search         = trim($_GET['q'] ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$per_page       = 50;
$offset         = ($page - 1) * $per_page;

// === Список уникальных action для фильтра ===
$actions_list = $pdo->query("SELECT DISTINCT action FROM activity_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

// === Построение WHERE ===
$where  = [];
$params = [];

if ($filter_role && in_array($filter_role, ['guest', 'user', 'admin'])) {
    $where[]  = 'role = ?';
    $params[] = $filter_role;
}
if ($filter_result && in_array($filter_result, ['success', 'error'])) {
    $where[]  = 'result = ?';
    $params[] = $filter_result;
}
if ($filter_action) {
    $where[]  = 'action = ?';
    $params[] = $filter_action;
}
if ($filter_ip) {
    $where[]  = 'ip LIKE ?';
    $params[] = '%' . $filter_ip . '%';
}
if ($filter_user) {
    $where[]  = '(username LIKE ? OR CAST(user_id AS CHAR) = ?)';
    $params[] = '%' . $filter_user . '%';
    $params[] = $filter_user;
}
if ($filter_date_from) {
    $where[]  = 'created_at >= ?';
    $params[] = $filter_date_from . ' 00:00:00';
}
if ($filter_date_to) {
    $where[]  = 'created_at <= ?';
    $params[] = $filter_date_to . ' 23:59:59';
}
if ($search) {
    $where[]  = '(description LIKE ? OR action LIKE ? OR ip LIKE ? OR username LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// === Подсчёт строк ===
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// === Данные ===
$dataStmt = $pdo->prepare("
    SELECT id, user_id, username, role, ip, user_agent, action, description, entity_type, entity_id, result, created_at
    FROM activity_logs
    $whereSql
    ORDER BY id DESC
    LIMIT {$per_page} OFFSET {$offset}
");
$dataStmt->execute($params);
$logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

// === Метки действий ===
$actionLabels = [
    'user_login'             => 'Вход пользователя',
    'user_login_fail'        => 'Неудачный вход',
    'user_logout'            => 'Выход пользователя',
    'user_register'          => 'Регистрация',
    'user_register_fail'     => 'Неудачная регистрация',
    'profile_update'         => 'Обновление профиля',
    'password_reset_request' => 'Запрос сброса пароля',
    'password_reset_done'    => 'Пароль сброшен',
    'email_verified'         => 'Email подтверждён',
    'email_verify_resend'    => 'Повтор письма верификации',
    'admin_login'            => 'Вход администратора',
    'admin_login_fail'       => 'Неудачный вход (адм)',
    'admin_logout'           => 'Выход администратора',
    'order_create'           => 'Создание заявки',
    'order_create_fail'      => 'Ошибка создания заявки',
    'order_cancel'           => 'Отмена заявки пользователем',
    'order_status_change'    => 'Смена статуса заявки',
    'order_delete'           => 'Удаление заявки',
    'ticket_create'          => 'Создание тикета',
    'ticket_reply'           => 'Ответ в тикет',
    'admin_ticket_reply'     => 'Ответ адм. в тикет',
    'admin_ticket_status'    => 'Смена статуса тикета',
    'admin_user_delete'      => 'Удаление пользователя',
    'admin_user_edit'        => 'Редактирование пользователя',
];

$roleColors = [
    'admin' => 'text-vi bg-vi/10 border-vi/30',
    'user'  => 'text-cy bg-cy-soft border-cy-border',
    'guest' => 'text-txt-muted bg-bg-soft border-line',
];
$roleLabels = ['admin' => 'Адм', 'user' => 'Польз.', 'guest' => 'Гость'];

// Постраничная навигация — URL без page
$qs = $_GET;
unset($qs['page']);
$base_url = '?' . http_build_query($qs) . ($qs ? '&' : '');

$page_title = 'Логи активности — Админ-панель';
$admin_page = 'logs.php';
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

<div class="aurora"><div class="ab ab-1"></div><div class="ab ab-2"></div><div class="ab ab-3"></div></div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php require_once __DIR__ . '/header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <div class="flex items-center justify-between gap-3 mb-6">
    <div>
      <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">Логи активности</h1>
      <p class="text-xs sm:text-sm text-txt-muted mt-1">Всего записей: <span class="text-cy font-medium"><?= number_format($total, 0, '.', ' ') ?></span></p>
    </div>
    <a href="?<?= http_build_query(array_diff_key($_GET, ['page' => ''])) ?>" class="btn-ghost px-3 h-9 rounded-lg text-sm flex items-center gap-2">
      <i data-lucide="refresh-cw" class="w-4 h-4"></i>
      <span class="hidden sm:inline">Обновить</span>
    </a>
  </div>

  <!-- Фильтры -->
  <form method="GET" class="gborder rounded-2xl bg-bg-card p-4 sm:p-5 mb-5 space-y-3">
    <!-- Поиск -->
    <div class="relative">
      <i data-lucide="search" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="Поиск по описанию, действию, IP, имени пользователя..."
             class="input-d w-full h-10 pl-10 pr-4 rounded-lg text-sm">
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 sm:gap-3">
      <!-- Роль -->
      <select name="role" class="input-d h-9 rounded-lg text-xs px-2">
        <option value="">Все роли</option>
        <option value="guest"  <?= $filter_role === 'guest'  ? 'selected' : '' ?>>Гость</option>
        <option value="user"   <?= $filter_role === 'user'   ? 'selected' : '' ?>>Пользователь</option>
        <option value="admin"  <?= $filter_role === 'admin'  ? 'selected' : '' ?>>Администратор</option>
      </select>

      <!-- Результат -->
      <select name="result" class="input-d h-9 rounded-lg text-xs px-2">
        <option value="">Все результаты</option>
        <option value="success" <?= $filter_result === 'success' ? 'selected' : '' ?>>Успех</option>
        <option value="error"   <?= $filter_result === 'error'   ? 'selected' : '' ?>>Ошибка</option>
      </select>

      <!-- Действие -->
      <select name="action" class="input-d h-9 rounded-lg text-xs px-2">
        <option value="">Все действия</option>
        <?php foreach ($actions_list as $a): ?>
          <option value="<?= htmlspecialchars($a) ?>" <?= $filter_action === $a ? 'selected' : '' ?>>
            <?= htmlspecialchars($actionLabels[$a] ?? $a) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Пользователь -->
      <input type="text" name="user" value="<?= htmlspecialchars($filter_user) ?>"
             placeholder="Ник / ID"
             class="input-d h-9 rounded-lg text-xs px-3">

      <!-- IP -->
      <input type="text" name="ip" value="<?= htmlspecialchars($filter_ip) ?>"
             placeholder="IP-адрес"
             class="input-d h-9 rounded-lg text-xs px-3">

      <!-- Кнопки -->
      <div class="flex gap-2 col-span-2 sm:col-span-1">
        <button type="submit" class="btn-cy flex-1 h-9 rounded-lg text-xs font-semibold flex items-center justify-center gap-1">
          <i data-lucide="filter" class="w-3.5 h-3.5"></i> Применить
        </button>
        <a href="logs.php" class="btn-ghost h-9 px-3 rounded-lg text-xs flex items-center justify-center" title="Сбросить">
          <i data-lucide="x" class="w-4 h-4"></i>
        </a>
      </div>
    </div>

    <!-- Даты -->
    <div class="flex flex-wrap gap-2 items-center">
      <span class="text-xs text-txt-muted flex items-center gap-1"><i data-lucide="calendar" class="w-3.5 h-3.5"></i> Период:</span>
      <input type="date" name="date_from" value="<?= htmlspecialchars($filter_date_from) ?>"
             class="input-d h-8 rounded-lg text-xs px-2">
      <span class="text-xs text-txt-muted">—</span>
      <input type="date" name="date_to" value="<?= htmlspecialchars($filter_date_to) ?>"
             class="input-d h-8 rounded-lg text-xs px-2">
    </div>
  </form>

  <!-- Таблица -->
  <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
    <?php if (empty($logs)): ?>
      <div class="flex flex-col items-center justify-center py-16 text-txt-muted">
        <i data-lucide="inbox" class="w-10 h-10 mb-3 opacity-40"></i>
        <p class="text-sm">Логов не найдено</p>
      </div>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead>
          <tr class="border-b border-line bg-bg-soft/50 text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">
            <th class="px-3 sm:px-4 py-3 text-left w-10">#</th>
            <th class="px-3 sm:px-4 py-3 text-left">Время</th>
            <th class="px-3 sm:px-4 py-3 text-left">Роль</th>
            <th class="px-3 sm:px-4 py-3 text-left">Пользователь</th>
            <th class="px-3 sm:px-4 py-3 text-left">Действие</th>
            <th class="px-3 sm:px-4 py-3 text-left hidden md:table-cell">Описание</th>
            <th class="px-3 sm:px-4 py-3 text-left hidden lg:table-cell">IP</th>
            <th class="px-3 sm:px-4 py-3 text-center">Рез.</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-line/40">
          <?php foreach ($logs as $log): ?>
          <?php
            $isErr = $log['result'] === 'error';
            $rowClass = $isErr ? 'bg-danger/5' : '';
          ?>
          <tr class="hover:bg-bg-soft/40 transition-colors <?= $rowClass ?> group" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
            <td class="px-3 sm:px-4 py-2.5 text-[10px] text-txt-muted font-mono"><?= $log['id'] ?></td>
            <td class="px-3 sm:px-4 py-2.5 text-xs text-txt-muted whitespace-nowrap font-mono">
              <?= date('d.m.y', strtotime($log['created_at'])) ?>
              <span class="text-txt-secondary"><?= date('H:i:s', strtotime($log['created_at'])) ?></span>
            </td>
            <td class="px-3 sm:px-4 py-2.5">
              <?php $rc = $roleColors[$log['role']] ?? 'text-txt-muted bg-bg-soft border-line'; ?>
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium border <?= $rc ?>">
                <?= $roleLabels[$log['role']] ?? $log['role'] ?>
              </span>
            </td>
            <td class="px-3 sm:px-4 py-2.5 text-xs">
              <?php if ($log['username']): ?>
                <a href="logs.php?user=<?= urlencode($log['username']) ?>" class="text-cy hover:underline font-medium">
                  <?= htmlspecialchars($log['username']) ?>
                </a>
                <?php if ($log['user_id']): ?>
                  <span class="text-txt-muted ml-1">#<?= $log['user_id'] ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-txt-muted italic">гость</span>
              <?php endif; ?>
            </td>
            <td class="px-3 sm:px-4 py-2.5">
              <span class="text-xs font-medium <?= $isErr ? 'text-danger' : 'text-txt-primary' ?>">
                <?= htmlspecialchars($actionLabels[$log['action']] ?? $log['action']) ?>
              </span>
              <?php if ($log['entity_type'] && $log['entity_id']): ?>
                <span class="text-[10px] text-txt-muted ml-1">
                  [<?= htmlspecialchars($log['entity_type']) ?>:<?= htmlspecialchars($log['entity_id']) ?>]
                </span>
              <?php endif; ?>
            </td>
            <td class="px-3 sm:px-4 py-2.5 text-xs text-txt-secondary hidden md:table-cell max-w-xs truncate">
              <?= htmlspecialchars($log['description']) ?>
            </td>
            <td class="px-3 sm:px-4 py-2.5 hidden lg:table-cell">
              <a href="logs.php?ip=<?= urlencode($log['ip']) ?>" class="text-xs font-mono text-txt-muted hover:text-cy transition">
                <?= htmlspecialchars($log['ip']) ?>
              </a>
            </td>
            <td class="px-3 sm:px-4 py-2.5 text-center">
              <?php if ($isErr): ?>
                <span title="Ошибка"><i data-lucide="x-circle" class="w-4 h-4 text-danger mx-auto"></i></span>
              <?php else: ?>
                <span title="Успех"><i data-lucide="check-circle-2" class="w-4 h-4 text-emr mx-auto"></i></span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Пагинация -->
  <?php if ($total_pages > 1): ?>
  <div class="flex items-center justify-between gap-3 mt-4 text-sm">
    <p class="text-xs text-txt-muted">
      Страница <span class="text-cy"><?= $page ?></span> из <span class="text-cy"><?= $total_pages ?></span>
      &nbsp;·&nbsp; показано <?= count($logs) ?> из <?= number_format($total, 0, '.', ' ') ?>
    </p>
    <div class="flex gap-1 flex-wrap">
      <?php if ($page > 1): ?>
        <a href="<?= $base_url ?>page=<?= $page - 1 ?>" class="px-3 h-8 rounded-lg border border-line bg-bg-card text-xs flex items-center hover:border-cy-border hover:text-cy transition">
          <i data-lucide="chevron-left" class="w-3.5 h-3.5"></i>
        </a>
      <?php endif; ?>

      <?php
      $start = max(1, $page - 2);
      $end   = min($total_pages, $page + 2);
      if ($start > 1): ?>
        <a href="<?= $base_url ?>page=1" class="px-3 h-8 rounded-lg border border-line bg-bg-card text-xs flex items-center hover:border-cy-border hover:text-cy transition">1</a>
        <?php if ($start > 2): ?><span class="px-2 h-8 flex items-center text-txt-muted text-xs">…</span><?php endif; ?>
      <?php endif; ?>

      <?php for ($p = $start; $p <= $end; $p++): ?>
        <a href="<?= $base_url ?>page=<?= $p ?>"
           class="px-3 h-8 rounded-lg border text-xs flex items-center transition
                  <?= $p === $page ? 'border-cy-border bg-cy-soft text-cy' : 'border-line bg-bg-card hover:border-cy-border hover:text-cy' ?>">
          <?= $p ?>
        </a>
      <?php endfor; ?>

      <?php if ($end < $total_pages): ?>
        <?php if ($end < $total_pages - 1): ?><span class="px-2 h-8 flex items-center text-txt-muted text-xs">…</span><?php endif; ?>
        <a href="<?= $base_url ?>page=<?= $total_pages ?>" class="px-3 h-8 rounded-lg border border-line bg-bg-card text-xs flex items-center hover:border-cy-border hover:text-cy transition"><?= $total_pages ?></a>
      <?php endif; ?>

      <?php if ($page < $total_pages): ?>
        <a href="<?= $base_url ?>page=<?= $page + 1 ?>" class="px-3 h-8 rounded-lg border border-line bg-bg-card text-xs flex items-center hover:border-cy-border hover:text-cy transition">
          <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
        </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>
