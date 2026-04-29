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

$allowed = ['USDT_TRC20', 'USDC', 'ETH', 'SOL', 'BTC', 'RUB', 'USD'];

$currencyMeta = [
    'USDT_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'name' => 'USDT', 'dec' => 2],
    'USDC'       => ['icon' => 'circle-dollar-sign', 'color' => '#2775CA', 'name' => 'USDC',        'dec' => 2],
    'ETH'        => ['icon' => 'hexagon',            'color' => '#627EEA', 'name' => 'ETH',          'dec' => 6],
    'SOL'        => ['icon' => 'zap',                'color' => '#9945FF', 'name' => 'SOL',          'dec' => 4],
    'BTC'        => ['icon' => 'bitcoin',            'color' => '#F7931A', 'name' => 'BTC',          'dec' => 8],
    'RUB'        => ['icon' => 'banknote',           'color' => '#A78BFA', 'name' => 'RUB',          'dec' => 2],
    'USD'        => ['icon' => 'dollar-sign',        'color' => '#22D3EE', 'name' => 'USD',          'dec' => 2],
];

// Чистые курсы без наценки из сырых данных CoinGecko
$clean_rates = [];
if ($real_rates) {
    $ur = $real_rates['tether']['rub'];    $uu = $real_rates['tether']['usd'];
    $cr = $real_rates['usd-coin']['rub'];  $cu = $real_rates['usd-coin']['usd'];
    $er = $real_rates['ethereum']['rub'];  $eu = $real_rates['ethereum']['usd'];
    $sr = $real_rates['solana']['rub'];    $su = $real_rates['solana']['usd'];
    $br = $real_rates['bitcoin']['rub'];   $bu = $real_rates['bitcoin']['usd'];

    $clean_rates = [
        'USDT_TRC20' => ['RUB'=>round($ur,2),'USD'=>round($uu,4),'USDC'=>$cu>0?round($uu/$cu,4):0,'ETH'=>$eu>0?round($uu/$eu,6):0,'SOL'=>$su>0?round($uu/$su,4):0,'BTC'=>$bu>0?round($uu/$bu,8):0],
        'USDC'       => ['RUB'=>round($cr,2),'USD'=>round($cu,4),'USDT_TRC20'=>$uu>0?round($cu/$uu,4):0,'ETH'=>$eu>0?round($cu/$eu,6):0,'SOL'=>$su>0?round($cu/$su,4):0,'BTC'=>$bu>0?round($cu/$bu,8):0],
        'ETH'        => ['RUB'=>round($er,0),'USD'=>round($eu,2),'USDT_TRC20'=>round($eu,2),'USDC'=>round($eu,2),'SOL'=>$su>0?round($eu/$su,2):0,'BTC'=>$bu>0?round($eu/$bu,6):0],
        'SOL'        => ['RUB'=>round($sr,2),'USD'=>round($su,2),'USDT_TRC20'=>round($su,2),'USDC'=>round($su,2),'ETH'=>$eu>0?round($su/$eu,6):0,'BTC'=>$bu>0?round($su/$bu,8):0],
        'BTC'        => ['RUB'=>round($br,0),'USD'=>round($bu,2),'USDT_TRC20'=>round($bu,2),'USDC'=>round($bu,2),'ETH'=>$eu>0?round($bu/$eu,4):0,'SOL'=>$su>0?round($bu/$su,2):0],
        'RUB'        => ['USDT_TRC20'=>$ur>0?round(1/$ur,6):0,'USDC'=>$cr>0?round(1/$cr,6):0,'ETH'=>$er>0?round(1/$er,8):0,'SOL'=>$sr>0?round(1/$sr,6):0,'BTC'=>$br>0?round(1/$br,10):0,'USD'=>$ur>0?round(1/$ur,4):0],
        'USD'        => ['USDT_TRC20'=>$uu>0?round(1/$uu,4):0,'USDC'=>$cu>0?round(1/$cu,4):0,'ETH'=>$eu>0?round(1/$eu,8):0,'SOL'=>$su>0?round(1/$su,6):0,'BTC'=>$bu>0?round(1/$bu,8):0,'RUB'=>round($ur,2)],
    ];
} elseif (!empty($rates)) {
    foreach ($allowed as $from) {
        foreach ($allowed as $to) {
            if ($to === $from || !isset($rates[$from][$to])) continue;
            $clean_rates[$from][$to] = $rates[$from][$to];
        }
    }
}

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

  <!-- Rates accordion -->
  <section class="reveal" data-d="1">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">

      <div class="flex items-center justify-between gap-2 px-4 sm:px-6 py-4 sm:py-5 border-b border-line">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
            <i data-lucide="trending-up" class="w-4 h-4 text-cy"></i>
          </div>
          <h2 class="text-base sm:text-lg font-bold truncate">Текущие курсы <span class="text-[10px] sm:text-xs text-txt-muted font-normal ml-1">(без наценки)</span></h2>
        </div>
        <?php if (empty($clean_rates)): ?>
          <span class="text-xs text-warn flex items-center gap-1">
            <i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> CoinGecko недоступен
          </span>
        <?php endif; ?>
      </div>

      <?php foreach ($allowed as $idx => $from):
        $fromMeta   = $currencyMeta[$from] ?? ['icon'=>'coins','color'=>'#888','name'=>$from,'dec'=>2];
        $fromRates  = $clean_rates[$from] ?? [];
        $validPairs = array_filter($fromRates, fn($r, $to) => $r > 0 && in_array($to, $allowed) && $to !== $from, ARRAY_FILTER_USE_BOTH);
        if (empty($validPairs)) continue;
        $isOpen = ($idx === 0);
      ?>
      <div class="border-b border-line last:border-0">

        <button type="button" onclick="toggleGroup('adm_<?= $from ?>')"
                class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 hover:bg-bg-soft/60 transition-colors text-left group">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:<?= $fromMeta['color'] ?>1A;border:1px solid <?= $fromMeta['color'] ?>33;">
              <i data-lucide="<?= $fromMeta['icon'] ?>" class="w-4 h-4 sm:w-5 sm:h-5" style="color:<?= $fromMeta['color'] ?>"></i>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-sm sm:text-base"><?= htmlspecialchars($fromMeta['name']) ?></div>
              <div class="text-[10px] sm:text-xs text-txt-muted"><?= count($validPairs) ?> направлений</div>
            </div>
          </div>
          <i data-lucide="chevron-down" id="adm-chevron-<?= $from ?>"
             class="w-4 h-4 text-txt-muted flex-shrink-0 transition-transform duration-200 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
        </button>

        <div id="adm-group-<?= $from ?>" class="<?= $isOpen ? '' : 'hidden' ?> border-t border-line/50">

          <?php $isFiatFrom = in_array($from, ['RUB', 'USD']); ?>
          <div class="hidden sm:grid grid-cols-[1fr,auto] gap-4 px-6 py-2 bg-bg-soft/30 text-[10px] text-txt-muted uppercase tracking-wider">
            <span>Получаете</span>
            <span class="w-44 text-right"><?= $isFiatFrom ? 'Курс (за 1 ед.)' : 'Курс (за 1 ' . htmlspecialchars($fromMeta['name']) . ')' ?></span>
          </div>

          <?php foreach ($validPairs as $to => $rate):
            $toMeta = $currencyMeta[$to] ?? ['icon'=>'coins','color'=>'#888','name'=>$to,'dec'=>2];
            if ($isFiatFrom) {
                $displayRate = $rate > 0 ? round(1 / $rate, 2) : 0;
                $dec = 2;
            } else {
                $displayRate = $rate;
                $dec = 2;
                if ($to === 'BTC') $dec = 8;
                elseif ($to === 'ETH') $dec = 6;
                elseif ($to === 'SOL') $dec = 4;
            }
          ?>
          <div class="flex sm:grid sm:grid-cols-[1fr,auto] items-center gap-3 sm:gap-4 px-4 sm:px-6 py-2.5 sm:py-3 border-t border-line/40 hover:bg-bg-soft/40 transition-colors">

            <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
              <i data-lucide="arrow-right" class="w-3 h-3 text-txt-muted flex-shrink-0 hidden sm:block"></i>
              <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:<?= $toMeta['color'] ?>1A;border:1px solid <?= $toMeta['color'] ?>33;">
                <i data-lucide="<?= $toMeta['icon'] ?>" class="w-3.5 h-3.5" style="color:<?= $toMeta['color'] ?>"></i>
              </div>
              <span class="font-medium text-sm truncate"><?= htmlspecialchars($toMeta['name']) ?></span>
            </div>

            <div class="sm:w-44 text-right flex-shrink-0">
              <span class="font-mono text-cy text-sm font-medium"><?= number_format($displayRate, $dec, '.', ' ') ?></span>
            </div>

          </div>
          <?php endforeach; ?>
        </div>

      </div>
      <?php endforeach; ?>

    </div>
  </section>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
function toggleGroup(id) {
  const body    = document.getElementById('adm-group-' + id.replace('adm_', ''));
  const chevron = document.getElementById('adm-chevron-' + id.replace('adm_', ''));
  if (!body) return;
  const isHidden = body.classList.contains('hidden');
  body.classList.toggle('hidden', !isHidden);
  if (chevron) chevron.classList.toggle('rotate-180', isHidden);
}
</script>

</body>
</html>