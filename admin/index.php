<?php
// admin/index.php — Админ-панель: обзор

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$period = $_GET['period'] ?? 'today';

$start_date = date('Y-m-d 00:00:00');
$end_date   = date('Y-m-d H:i:s');

switch ($period) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date   = date('Y-m-d 23:59:59', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    case 'all':
        $start_date = '2000-01-01 00:00:00';
        break;
}

$stmt_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at <= ?");
$stmt_users->execute([$start_date, $end_date]);
$new_users_period = $stmt_users->fetchColumn();

$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

$stmt_total     = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at <= ?");
$stmt_success   = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'success' AND created_at >= ? AND created_at <= ?");
$stmt_in_process= $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'in_process' AND created_at >= ? AND created_at <= ?");
$stmt_new       = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'new' AND created_at >= ? AND created_at <= ?");
$stmt_canceled  = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'canceled' AND created_at >= ? AND created_at <= ?");

$stmt_total->execute([$start_date, $end_date]);
$stmt_success->execute([$start_date, $end_date]);
$stmt_in_process->execute([$start_date, $end_date]);
$stmt_new->execute([$start_date, $end_date]);
$stmt_canceled->execute([$start_date, $end_date]);

$total_orders_period   = $stmt_total->fetchColumn();
$success_period        = $stmt_success->fetchColumn();
$in_process_period     = $stmt_in_process->fetchColumn();
$new_period            = $stmt_new->fetchColumn();
$canceled_period       = $stmt_canceled->fetchColumn();

$real_rates = function_exists('getRealRates') ? getRealRates() : null;

$periodLabels = [
    'today'     => 'Сегодня',
    'yesterday' => 'Вчера',
    '7days'     => '7 дней',
    '30days'    => '30 дней',
    'all'       => 'Весь период',
];

$page_title = 'Админ-панель — ' . SITE_NAME;
$admin_page = 'index.php';
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

<?php require_once __DIR__ . '/header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <section class="mb-6 sm:mb-8 fade-in">
    <div class="flex flex-wrap items-end justify-between gap-3 sm:gap-4 mb-4">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-1">Обзор</h1>
        <p class="text-xs sm:text-sm text-txt-muted flex items-center gap-2">
          <span class="pdot"></span>
          Статистика за период: <span class="text-cy ml-1"><?= $periodLabels[$period] ?? 'Сегодня' ?></span>
        </p>
      </div>
      <div class="flex flex-wrap gap-1 sm:gap-1.5 bg-bg-card p-1 rounded-lg border border-line w-full sm:w-auto overflow-x-auto">
        <?php foreach ($periodLabels as $key => $label): ?>
          <a href="?period=<?= $key ?>"
             class="px-3 h-8 rounded-md text-xs font-medium flex items-center transition whitespace-nowrap <?= $period === $key ? 'bg-cy-soft text-cy border border-cy-border' : 'text-txt-secondary hover:text-cy hover:bg-bg-soft' ?>">
            <?= $label ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Users stats -->
  <section class="mb-6 sm:mb-8">
    <div class="flex items-center gap-2 mb-3 sm:mb-4">
      <i data-lucide="users" class="w-4 h-4 text-cy"></i>
      <h2 class="text-xs sm:text-sm font-semibold text-txt-secondary uppercase tracking-wider">Пользователи</h2>
    </div>
    <div class="grid grid-cols-3 gap-3 sm:gap-4">
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">За период</span>
          <i data-lucide="user-plus" class="w-4 h-4 text-cy"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-cy count-up" data-target="<?= $new_users_period ?>">0</div>
        <div class="text-xs text-txt-muted mt-1">новых регистраций</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="2">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Всего</span>
          <i data-lucide="users" class="w-4 h-4 text-txt-secondary"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold count-up" data-target="<?= $total_users ?>">0</div>
        <div class="text-xs text-txt-muted mt-1">зарегистрировано</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="3">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Администраторов</span>
          <i data-lucide="shield-check" class="w-4 h-4 text-vi"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-vi count-up" data-target="<?= $admins ?>">0</div>
        <div class="text-xs text-txt-muted mt-1">с привилегиями</div>
      </div>
    </div>
  </section>

  <!-- Orders stats -->
  <section class="mb-6 sm:mb-8">
    <div class="flex items-center gap-2 mb-3 sm:mb-4">
      <i data-lucide="file-text" class="w-4 h-4 text-cy"></i>
      <h2 class="text-xs sm:text-sm font-semibold text-txt-secondary uppercase tracking-wider">Заявки за период</h2>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4">
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="1">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Всего</span>
          <i data-lucide="list" class="w-4 h-4 text-txt-secondary"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold count-up" data-target="<?= $total_orders_period ?>">0</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="2">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Успешные</span>
          <i data-lucide="check-circle-2" class="w-4 h-4 text-emr"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-emr count-up" data-target="<?= $success_period ?>">0</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="3">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">В работе</span>
          <i data-lucide="loader" class="w-4 h-4 text-cy"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-cy count-up" data-target="<?= $in_process_period ?>">0</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="4">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Новые</span>
          <i data-lucide="clock" class="w-4 h-4 text-warn"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-warn count-up" data-target="<?= $new_period ?>">0</div>
      </div>
      <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="5">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Отменены</span>
          <i data-lucide="x-circle" class="w-4 h-4 text-danger"></i>
        </div>
        <div class="text-xl sm:text-3xl font-bold text-danger count-up" data-target="<?= $canceled_period ?>">0</div>
      </div>
    </div>
  </section>

  <!-- Rates table -->
  <section class="reveal" data-d="1">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
      <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-line">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
            <i data-lucide="trending-up" class="w-4 h-4 text-cy"></i>
          </div>
          <h2 class="text-sm sm:text-lg font-bold truncate">Текущие курсы <span class="text-[10px] sm:text-xs text-txt-muted font-normal ml-1 hidden sm:inline">(без наценки)</span></h2>
        </div>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
              <th class="px-6 py-3 font-medium">Отдаёте</th>
              <th class="px-4 py-3 font-medium">Получаете</th>
              <th class="px-4 py-3 font-medium text-right">Курс</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($rates as $from => $to_list): ?>
              <?php foreach ($to_list as $to => $rate_with_markup):
                $real_rate = $rate_with_markup;
                if (isset($markup_sell) && $markup_sell > 0) {
                    $real_rate = $rate_with_markup / $markup_sell;
                }
              ?>
                <tr class="row-h transition">
                  <td class="px-6 py-3 font-medium whitespace-nowrap"><?= htmlspecialchars(str_replace('_', ' ', $from)) ?></td>
                  <td class="px-4 py-3 font-medium whitespace-nowrap"><?= htmlspecialchars(str_replace('_', ' ', $to)) ?></td>
                  <td class="px-4 py-3 text-right font-mono text-cy whitespace-nowrap"><?= number_format($real_rate, ($to === 'BTC' ? 8 : 4), '.', ' ') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>