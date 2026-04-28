<?php
require_once 'config.php';
require_once 'auth.php';

// Восстановление данных обмена после логина
if (isset($_SESSION['pending_exchange'])) {
    $give        = $_SESSION['pending_exchange']['give']        ?? 'USDT_TRC20';
    $get         = $_SESSION['pending_exchange']['get']         ?? 'RUB_SBP';
    $amount_give = $_SESSION['pending_exchange']['amount_give'] ?? floatval($_GET['amount'] ?? 100);
    unset($_SESSION['pending_exchange']);
} else {
    $give        = $_GET['give'] ?? 'USDT_TRC20';
    $get         = $_GET['get']  ?? 'RUB_SBP';
    $amount_give = floatval($_GET['amount'] ?? 100);
}

// Миграция старых ключей без сети
$keyMigration = ['RUB' => 'RUB_SBP', 'USDC' => 'USDC_TRC20', 'USD' => 'USD'];
$give = $keyMigration[$give] ?? $give;
$get  = $keyMigration[$get]  ?? $get;

// Флаг авто-запуска обмена после логина
$auto_exchange = isset($_SESSION['auto_exchange']);
unset($_SESSION['auto_exchange']);

if (!isset($rates) || !is_array($rates)) {
    $rates = [
        'USDT_TRC20' => ['RUB' => 95.00, 'USD' => 0.975, 'BTC' => 0.0000105, 'ETH' => 0.000398, 'SOL' => 0.622, 'USDC' => 0.975],
        'USDC'       => ['RUB' => 95.00, 'USD' => 0.975, 'BTC' => 0.0000105, 'ETH' => 0.000398, 'SOL' => 0.622, 'USDT_TRC20' => 0.975],
        'ETH'        => ['RUB' => 238000, 'USD' => 2437, 'BTC' => 0.0256, 'USDT_TRC20' => 2437, 'USDC' => 2437, 'SOL' => 15.2],
        'SOL'        => ['RUB' => 15600, 'USD' => 160, 'BTC' => 0.00168, 'USDT_TRC20' => 160, 'USDC' => 160, 'ETH' => 0.064],
        'BTC'        => ['RUB' => 9000000, 'USD' => 92000, 'USDT_TRC20' => 92000, 'USDC' => 92000, 'ETH' => 38.5, 'SOL' => 580],
        'RUB'        => ['USDT_TRC20' => 0.0102, 'USDC' => 0.0102, 'ETH' => 0.0000041, 'SOL' => 0.000063, 'BTC' => 0.00000011, 'USD' => 0.0102],
        'USD'        => ['USDT_TRC20' => 0.975, 'USDC' => 0.975, 'ETH' => 0.000398, 'SOL' => 0.00610, 'BTC' => 0.0000105, 'RUB' => 95.00],
    ];
    $reserves = [
        'USDT_TRC20' => 1500000, 'USDC' => 500000, 'ETH' => 150,
        'SOL' => 5000, 'BTC' => 15.5, 'RUB' => 50000000, 'USD' => 500000,
    ];
    $limits = [
        'USDT_TRC20' => ['min' => 50,    'max' => 55000],
        'USDC'       => ['min' => 50,    'max' => 55000],
        'ETH'        => ['min' => 0.01,  'max' => 100],
        'SOL'        => ['min' => 0.5,   'max' => 10000],
        'BTC'        => ['min' => 0.001, 'max' => 10],
        'RUB'        => ['min' => 5000,  'max' => 2000000],
        'USD'        => ['min' => 50,    'max' => 50000],
    ];
}

try {
    $stmt = $pdo->query("SELECT currency, amount AS reserve_amount, min, max FROM reserves");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cur = $row['currency'];
        $reserves[$cur] = $row['reserve_amount'] ?? $reserves[$cur] ?? 0;
        $limits[$cur]   = [
            'min' => $row['min'] ?? ($limits[$cur]['min'] ?? 0),
            'max' => $row['max'] ?? ($limits[$cur]['max'] ?? 999999999)
        ];
    }
} catch (Exception $e) { /* fallback */ }

$js_reserves = json_encode($reserves ?? []);

// Статистика для hero
try {
    $total_orders_count = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'success'")->fetchColumn();
} catch (Exception $e) { $total_orders_count = 0; }
if ($total_orders_count < 100) $total_orders_count = 12840;

$page_title = SITE_NAME . ' — Обмен USDT, USDC, ETH, SOL, BTC, RUB, USD';

function currency_digits($cur) {
    if (in_array($cur, ['BTC'])) return 8;
    if (in_array($cur, ['ETH'])) return 6;
    if (in_array($cur, ['SOL'])) return 4;
    return 2;
}

// Двухуровневый конфиг: монета → сети/методы
$coinGroups = [
    'USDT' => [
        'symbol' => '₮', 'label' => 'USDT', 'color' => '#10B981',
        'networks' => [
            'USDT_TRC20' => ['tag' => 'TRC20', 'desc' => 'TRON Network'],
            'USDT_ERC20' => ['tag' => 'ERC20', 'desc' => 'Ethereum Network'],
            'USDT_BEP20' => ['tag' => 'BEP20', 'desc' => 'BNB Chain'],
        ],
    ],
    'USDC' => [
        'symbol' => '$', 'label' => 'USDC', 'color' => '#2775CA',
        'networks' => [
            'USDC_TRC20' => ['tag' => 'TRC20', 'desc' => 'TRON Network'],
            'USDC_ERC20' => ['tag' => 'ERC20', 'desc' => 'Ethereum Network'],
        ],
    ],
    'ETH' => [
        'symbol' => 'Ξ', 'label' => 'ETH', 'color' => '#627EEA',
        'networks' => ['ETH' => ['tag' => 'ERC20', 'desc' => 'Ethereum']],
    ],
    'SOL' => [
        'symbol' => '◎', 'label' => 'SOL', 'color' => '#9945FF',
        'networks' => ['SOL' => ['tag' => 'SOL', 'desc' => 'Solana Network']],
    ],
    'BTC' => [
        'symbol' => '₿', 'label' => 'BTC', 'color' => '#F7931A',
        'networks' => ['BTC' => ['tag' => 'BTC', 'desc' => 'Bitcoin Network']],
    ],
    'RUB' => [
        'symbol' => '₽', 'label' => 'RUB', 'color' => '#A78BFA',
        'networks' => [
            'RUB_SBP'  => ['tag' => 'СБП',   'desc' => 'Система быстрых платежей'],
            'RUB_CASH' => ['tag' => 'Нал.',   'desc' => 'Наличные'],
            'RUB_CARD' => ['tag' => 'Карта',  'desc' => 'Банковская карта'],
        ],
    ],
    'USD' => [
        'symbol' => '$', 'label' => 'USD', 'color' => '#22D3EE',
        'networks' => ['USD' => ['tag' => 'SWIFT', 'desc' => 'Банковский перевод']],
    ],
];

// Найти ключ группы по ключу сети
function findCoinGroup(string $key, array $groups): string {
    foreach ($groups as $gk => $g) {
        if (array_key_exists($key, $g['networks'])) return $gk;
    }
    return 'USDT';
}

$giveCoinKey = findCoinGroup($give, $coinGroups);
$getCoinKey  = findCoinGroup($get,  $coinGroups);
?>
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title) ?></title>
<?php require_once 'theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>

<?php require_once 'header.php'; ?>

<?php if (isset($_SESSION['toast'])): ?>
  <div id="toast" class="toast-w <?= htmlspecialchars($_SESSION['toast']['type']) ?>">
    <?= htmlspecialchars($_SESSION['toast']['message']) ?>
  </div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<main class="relative max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <!-- Hero -->
  <section class="grid lg:grid-cols-[1fr,540px] gap-6 lg:gap-10 items-center min-w-0 overflow-hidden">

    <!-- Left intro -->
    <div class="fade-in min-w-0">
      <h1 class="text-2xl sm:text-4xl md:text-5xl font-bold tracking-tight leading-[1.1] mb-3 sm:mb-4">
        Обмен криптовалют<br>
        <span class="shimmer-text">быстро и безопасно</span>
      </h1>

      <p class="text-txt-secondary text-xs sm:text-base md:text-lg mb-5 sm:mb-8 leading-relaxed">
        USDT · BTC · RUB — моментальный обмен по актуальному курсу.
        Верифицированный сервис в реестре BestChange.
      </p>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-1.5 sm:gap-3 max-w-xl">
        <div class="spot bg-bg-card border border-line rounded-lg sm:rounded-xl p-2 sm:p-4 hover:border-cy-border transition min-w-0">
          <div class="text-txt-muted text-[9px] sm:text-xs mb-0.5 sm:mb-1 leading-tight">Заявок</div>
          <div class="text-xs sm:text-xl font-bold count-up truncate" data-target="<?= $total_orders_count ?>">0</div>
        </div>
        <div class="spot bg-bg-card border border-line rounded-lg sm:rounded-xl p-2 sm:p-4 hover:border-cy-border transition min-w-0">
          <div class="text-txt-muted text-[9px] sm:text-xs mb-0.5 sm:mb-1 leading-tight">Ср. время</div>
          <div class="text-xs sm:text-xl font-bold truncate">≈ <span class="count-up" data-target="7">0</span> мин</div>
        </div>
        <div class="spot bg-bg-card border border-line rounded-lg sm:rounded-xl p-2 sm:p-4 hover:border-cy-border transition min-w-0">
          <div class="text-txt-muted text-[9px] sm:text-xs mb-0.5 sm:mb-1 leading-tight">Поддержка</div>
          <div class="text-xs sm:text-xl font-bold truncate">24 / 7</div>
        </div>
      </div>

      <!-- Feature tags -->
      <div class="mt-4 sm:mt-6 flex flex-wrap gap-1 sm:gap-2">
        <span class="spot inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
          <i data-lucide="shield-check" class="w-3 h-3 sm:w-4 sm:h-4 text-cy flex-shrink-0"></i> AML проверка
        </span>
        <span class="spot inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
          <i data-lucide="zap" class="w-3 h-3 sm:w-4 sm:h-4 text-warn flex-shrink-0"></i> Быстрые выплаты
        </span>
        <span class="spot inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
          <i data-lucide="headphones" class="w-3 h-3 sm:w-4 sm:h-4 text-vi flex-shrink-0"></i> Поддержка 24/7
        </span>
      </div>
    </div>

    <!-- Right exchange card -->
    <div class="fade-in fd1 w-full min-w-0">
      <form action="order.php" method="POST" id="exchange-form" class="gborder spot bg-bg-card border border-line rounded-2xl p-3 sm:p-6 shadow-card block w-full min-w-0">
        <div class="flex items-center justify-between mb-4 sm:mb-5">
          <h2 class="text-base sm:text-[17px] font-semibold">Обменять</h2>
          <div class="flex items-center gap-1.5 text-[11px] sm:text-xs text-txt-muted">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 refresh-icon"></i>
            обновление <span class="text-cy font-medium" id="timer-text">15с</span>
          </div>
        </div>

        <!-- You give -->
        <?php
        $giveCG  = $coinGroups[$giveCoinKey];
        $giveNet = $giveCG['networks'][$give] ?? ['tag'=>'?','desc'=>''];
        $giveMulti = count($giveCG['networks']) > 1;
        ?>
        <div class="field rounded-xl p-2.5 sm:p-4 mb-2">
          <div class="flex items-center justify-between mb-1.5 sm:mb-2 gap-2">
            <span class="text-[10px] sm:text-xs text-txt-muted whitespace-nowrap">Вы отдаёте</span>
            <span class="text-[10px] sm:text-xs text-txt-muted truncate">Лимит: <span id="limit-text" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <input type="text" name="amount_give" id="amount-give" inputmode="decimal"
                   value="<?= number_format($amount_give, currency_digits($give), '.', '') ?>"
                   class="flex-1 min-w-0 w-full bg-transparent text-lg sm:text-2xl font-semibold outline-none placeholder:text-txt-muted" required>
            <input type="hidden" name="give_currency" id="give-select" value="<?= htmlspecialchars($give) ?>">
            <div class="flex items-center gap-1 flex-shrink-0">
              <!-- Coin button -->
              <button type="button" id="give-coin-btn" onclick="openDrop('give','coin')"
                      class="flex items-center gap-1 sm:gap-1.5 h-9 sm:h-10 px-2 sm:px-3 rounded-lg bg-bg-soft border border-line hover:border-cy-border transition whitespace-nowrap">
                <div id="give-coin-icon" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold flex-shrink-0"
                     style="background:<?= $giveCG['color'] ?>1A;border:1px solid <?= $giveCG['color'] ?>33;color:<?= $giveCG['color'] ?>">
                  <?= $giveCG['symbol'] ?>
                </div>
                <span id="give-coin-label" class="text-xs sm:text-sm font-medium"><?= htmlspecialchars($giveCG['label']) ?></span>
                <i data-lucide="chevron-down" class="w-3 h-3 text-txt-muted flex-shrink-0"></i>
              </button>
              <!-- Network badge -->
              <button type="button" id="give-net-btn" onclick="openDrop('give','net')"
                      class="flex items-center gap-0.5 h-6 px-2 rounded-md bg-cy-soft border border-cy-border text-cy hover:opacity-80 transition text-[11px] font-medium whitespace-nowrap">
                <span id="give-net-label"><?= htmlspecialchars($giveNet['tag']) ?></span>
                <i data-lucide="chevron-down" id="give-net-chevron" class="w-2.5 h-2.5 <?= $giveMulti ? '' : 'hidden' ?>"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Swap -->
        <div class="relative -my-3 flex justify-center z-10">
          <button type="button" id="swap-btn" class="swap-r w-10 h-10 rounded-full bg-bg-card border border-line hover:border-cy-border hover:text-cy transition flex items-center justify-center shadow-card">
            <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
          </button>
        </div>

        <!-- You get -->
        <?php
        $getCG  = $coinGroups[$getCoinKey];
        $getNet = $getCG['networks'][$get] ?? ['tag'=>'?','desc'=>''];
        $getMulti = count($getCG['networks']) > 1;
        ?>
        <div class="field rounded-xl p-2.5 sm:p-4 mt-2">
          <div class="flex items-center justify-between mb-1.5 sm:mb-2 gap-2">
            <span class="text-[10px] sm:text-xs text-txt-muted whitespace-nowrap">Вы получаете</span>
            <span class="text-[10px] sm:text-xs text-txt-muted truncate">Резерв: <span id="reserve-get" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-2">
            <input type="text" name="amount_get" id="amount-get" inputmode="decimal" class="flex-1 min-w-0 w-full bg-transparent text-lg sm:text-2xl font-semibold outline-none text-cy">
            <input type="hidden" name="get_currency" id="get-select" value="<?= htmlspecialchars($get) ?>">
            <div class="flex items-center gap-1 flex-shrink-0">
              <!-- Coin button -->
              <button type="button" id="get-coin-btn" onclick="openDrop('get','coin')"
                      class="flex items-center gap-1 sm:gap-1.5 h-9 sm:h-10 px-2 sm:px-3 rounded-lg bg-bg-soft border border-line hover:border-cy-border transition whitespace-nowrap">
                <div id="get-coin-icon" class="w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold flex-shrink-0"
                     style="background:<?= $getCG['color'] ?>1A;border:1px solid <?= $getCG['color'] ?>33;color:<?= $getCG['color'] ?>">
                  <?= $getCG['symbol'] ?>
                </div>
                <span id="get-coin-label" class="text-xs sm:text-sm font-medium"><?= htmlspecialchars($getCG['label']) ?></span>
                <i data-lucide="chevron-down" class="w-3 h-3 text-txt-muted flex-shrink-0"></i>
              </button>
              <!-- Network badge -->
              <button type="button" id="get-net-btn" onclick="openDrop('get','net')"
                      class="flex items-center gap-0.5 h-6 px-2 rounded-md bg-cy-soft border border-cy-border text-cy hover:opacity-80 transition text-[11px] font-medium whitespace-nowrap">
                <span id="get-net-label"><?= htmlspecialchars($getNet['tag']) ?></span>
                <i data-lucide="chevron-down" id="get-net-chevron" class="w-2.5 h-2.5 <?= $getMulti ? '' : 'hidden' ?>"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- Coin dropdowns (PHP rendered, portaled to body via JS) -->
        <div id="give-coin-drop" class="cur-drop hidden fixed z-[9999] min-w-[160px] bg-bg-card border border-line rounded-xl shadow-card py-1">
          <?php foreach ($coinGroups as $gk => $group): ?>
          <button type="button" data-group="<?= $gk ?>" onclick="selectCoin('give','<?= $gk ?>')"
                  class="coin-opt w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-bg-soft transition text-sm <?= $gk===$giveCoinKey?'bg-bg-soft':'' ?>">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                 style="background:<?= $group['color'] ?>1A;border:1px solid <?= $group['color'] ?>33;color:<?= $group['color'] ?>">
              <?= $group['symbol'] ?>
            </div>
            <span class="font-medium"><?= htmlspecialchars($group['label']) ?></span>
            <?php if ($gk===$giveCoinKey): ?><i data-lucide="check" class="chk-icon w-3.5 h-3.5 text-cy ml-auto flex-shrink-0"></i><?php endif; ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div id="give-net-drop" class="cur-drop hidden fixed z-[9999] min-w-[210px] bg-bg-card border border-line rounded-xl shadow-card py-1"></div>

        <div id="get-coin-drop" class="cur-drop hidden fixed z-[9999] min-w-[160px] bg-bg-card border border-line rounded-xl shadow-card py-1">
          <?php foreach ($coinGroups as $gk => $group): ?>
          <button type="button" data-group="<?= $gk ?>" onclick="selectCoin('get','<?= $gk ?>')"
                  class="coin-opt w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-bg-soft transition text-sm <?= $gk===$getCoinKey?'bg-bg-soft':'' ?>">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                 style="background:<?= $group['color'] ?>1A;border:1px solid <?= $group['color'] ?>33;color:<?= $group['color'] ?>">
              <?= $group['symbol'] ?>
            </div>
            <span class="font-medium"><?= htmlspecialchars($group['label']) ?></span>
            <?php if ($gk===$getCoinKey): ?><i data-lucide="check" class="chk-icon w-3.5 h-3.5 text-cy ml-auto flex-shrink-0"></i><?php endif; ?>
          </button>
          <?php endforeach; ?>
        </div>
        <div id="get-net-drop" class="cur-drop hidden fixed z-[9999] min-w-[210px] bg-bg-card border border-line rounded-xl shadow-card py-1"></div>

        <!-- Rate -->
        <div class="mt-3 sm:mt-4 flex items-center justify-between gap-2 text-xs sm:text-sm bg-bg-soft border border-line rounded-xl px-3 sm:px-4 py-2.5 sm:py-3">
          <span class="text-txt-muted flex-shrink-0">Курс</span>
          <span class="font-medium truncate text-right" id="rate-line">—</span>
        </div>

        <button type="submit" id="submit-btn" class="btn-cy w-full mt-4 sm:mt-5 h-11 sm:h-12 rounded-xl font-semibold text-sm sm:text-base flex items-center justify-center gap-2">
          Обменять
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>

        <p id="error-text" class="text-danger text-xs sm:text-sm text-center mt-3 min-h-[1.25rem]"></p>

        <div class="mt-2 flex items-center justify-center gap-1.5 text-[10px] sm:text-xs text-txt-muted text-center">
          <i data-lucide="lock" class="w-3 h-3 sm:w-3.5 sm:h-3.5 flex-shrink-0"></i>
          <span>Нажимая кнопку, вы соглашаетесь с <a class="text-cy hover:underline" href="aml.php">AML политикой</a></span>
        </div>
      </form>
    </div>

  </section>

  <!-- Reserves -->
  <section class="mt-12 sm:mt-16">
    <div class="flex items-end justify-between gap-3 mb-5 reveal">
      <div>
        <h3 class="text-xl sm:text-2xl font-bold tracking-tight">Резервы</h3>
        <p class="text-txt-muted text-xs sm:text-sm mt-1">Доступные средства обновляются в реальном времени</p>
      </div>
      <a href="rates.php" class="text-xs sm:text-sm text-cy hover:text-cy-dark inline-flex items-center gap-1 transition whitespace-nowrap">
        <span class="hidden sm:inline">Все курсы</span><span class="sm:hidden">Все</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
      <?php
      $reserveCards = [
          'USDT_TRC20' => ['sym' => '₮', 'name' => 'USDT',  'sub' => 'Tether',    'color' => '#10B981', 'dec' => 2, 'ldec' => 0],
          'USDC'       => ['sym' => '$', 'name' => 'USDC',  'sub' => 'USD Coin',            'color' => '#2775CA', 'dec' => 2, 'ldec' => 0],
          'ETH'        => ['sym' => 'Ξ', 'name' => 'ETH',   'sub' => 'Ethereum',            'color' => '#627EEA', 'dec' => 4, 'ldec' => 2],
          'SOL'        => ['sym' => '◎', 'name' => 'SOL',   'sub' => 'Solana',              'color' => '#9945FF', 'dec' => 2, 'ldec' => 1],
          'BTC'        => ['sym' => '₿', 'name' => 'BTC',   'sub' => 'Bitcoin',             'color' => '#F7931A', 'dec' => 6, 'ldec' => 3],
          'RUB'        => ['sym' => '₽', 'name' => 'RUB',   'sub' => 'Карта / СБП',         'color' => '#A78BFA', 'dec' => 0, 'ldec' => 0],
          'USD'        => ['sym' => '$', 'name' => 'USD',   'sub' => 'Доллар США',          'color' => '#22D3EE', 'dec' => 2, 'ldec' => 0],
      ];
      $di = 1;
      foreach ($reserveCards as $cur => $card):
          $res = $reserves[$cur] ?? 0;
          $lmin = $limits[$cur]['min'] ?? 0;
          $lmax = $limits[$cur]['max'] ?? 0;
      ?>
      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-3 sm:p-5 transition" data-d="<?= $di++ ?>" data-res-cur="<?= $cur ?>">
        <div class="flex items-center justify-between mb-2 sm:mb-3 gap-2">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full flex items-center justify-center font-bold text-sm flex-shrink-0"
                 style="background:<?= $card['color'] ?>1A;border:1px solid <?= $card['color'] ?>33;color:<?= $card['color'] ?>">
              <?= $card['sym'] ?>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-sm"><?= $card['name'] ?></div>
              <div class="text-[10px] text-txt-muted truncate"><?= $card['sub'] ?></div>
            </div>
          </div>
          <span data-res-badge="<?= $cur ?>" class="text-[10px] <?= $res > 0 ? 'text-cy bg-cy-soft border-cy-border' : 'text-txt-muted bg-bg-soft border-line' ?> border px-1.5 h-5 rounded flex items-center whitespace-nowrap flex-shrink-0">
            <?= $res > 0 ? 'В наличии' : 'Нет' ?>
          </span>
        </div>
        <div data-res-amount="<?= $cur ?>" class="text-base sm:text-xl font-bold truncate <?= $res <= 0 ? 'text-txt-muted' : '' ?>"><?= number_format($res, $card['dec'], '.', ' ') ?></div>
        <div data-res-limit="<?= $cur ?>" class="mt-1 text-[10px] sm:text-xs text-txt-muted truncate">
          Лимит <?= number_format($lmin, $card['ldec'], '.', ' ') ?> – <?= number_format($lmax, $card['ldec'], '.', ' ') ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- How it works -->
  <section class="mt-12 sm:mt-16">
    <h3 class="text-xl sm:text-2xl font-bold tracking-tight mb-5 sm:mb-6 reveal">Как это работает</h3>
    <div class="grid sm:grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-4 sm:p-5 hover:border-cy-border transition" data-d="1">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-lg bg-cy-soft border border-cy-border text-cy flex items-center justify-center flex-shrink-0">
            <i data-lucide="wallet" class="w-5 h-5"></i>
          </div>
          <div class="font-semibold">Выберите направление</div>
        </div>
        <div class="text-sm text-txt-secondary text-center">Укажите, что отдаёте и что хотите получить</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-4 sm:p-5 hover:border-cy-border transition" data-d="2">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-lg bg-cy-soft border border-cy-border text-cy flex items-center justify-center flex-shrink-0">
            <i data-lucide="user-check" class="w-5 h-5"></i>
          </div>
          <div class="font-semibold">Оставьте заявку</div>
        </div>
        <div class="text-sm text-txt-secondary text-center">Введите реквизиты и Telegram для связи</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-4 sm:p-5 hover:border-cy-border transition" data-d="3">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-lg bg-vi-soft border border-vi/20 text-vi flex items-center justify-center flex-shrink-0">
            <i data-lucide="send" class="w-5 h-5"></i>
          </div>
          <div class="font-semibold">Переведите средства</div>
        </div>
        <div class="text-sm text-txt-secondary text-center">Отправьте оплату на указанный адрес</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-4 sm:p-5 hover:border-cy-border transition" data-d="4">
        <div class="flex items-center gap-3 mb-2">
          <div class="w-10 h-10 rounded-lg bg-vi-soft border border-vi/20 text-vi flex items-center justify-center flex-shrink-0">
            <i data-lucide="check-circle-2" class="w-5 h-5"></i>
          </div>
          <div class="font-semibold">Получите обмен</div>
        </div>
        <div class="text-sm text-txt-secondary text-center">Оператор подтвердит и отправит средства</div>
      </div>

    </div>
  </section>

</main>

<!-- Telegram modal -->
<div id="telegram-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50 p-3 sm:p-4">
  <div class="gborder bg-bg-card border border-line rounded-2xl p-5 sm:p-8 max-w-md w-full shadow-card relative">
    <button type="button" id="close-telegram" class="absolute top-3 right-3 sm:top-4 sm:right-4 text-txt-muted hover:text-txt-primary transition w-8 h-8 flex items-center justify-center rounded-lg hover:bg-bg-soft">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-cy-soft border border-cy-border flex items-center justify-center mb-3 sm:mb-4">
      <i data-lucide="send" class="w-5 h-5 sm:w-6 sm:h-6 text-cy"></i>
    </div>
    <h3 class="text-lg sm:text-xl font-bold mb-1.5 sm:mb-2">Укажите Telegram</h3>
    <p class="text-txt-secondary text-xs sm:text-sm mb-4 sm:mb-6">Чтобы оператор мог оперативно связаться с вами по заявке</p>
    <form id="telegram-form" class="space-y-3 sm:space-y-4">
      <input type="text" id="telegram-input" name="telegram" placeholder="@username"
             class="input-d w-full px-3 sm:px-4 py-2.5 sm:py-3 rounded-lg text-sm sm:text-base" required>
      <button type="submit" class="btn-cy w-full h-11 sm:h-12 rounded-lg font-semibold flex items-center justify-center gap-2 text-sm sm:text-base">
        Подтвердить <i data-lucide="check" class="w-4 h-4"></i>
      </button>
    </form>
  </div>
</div>

<?php require_once 'footer.php'; ?>

<script>
  const rates = <?= json_encode($rates) ?>;
  const limits = <?= json_encode($limits) ?>;
  const reserves = <?= $js_reserves ?>;

  const amountGiveEl = document.getElementById('amount-give');
  const amountGetEl  = document.getElementById('amount-get');
  const giveSelect   = document.getElementById('give-select');
  const getSelect    = document.getElementById('get-select');
  const reserveEl    = document.getElementById('reserve-get');
  const submitBtn    = document.getElementById('submit-btn');
  const errorText    = document.getElementById('error-text');
  const limitText    = document.getElementById('limit-text');
  const rateLine     = document.getElementById('rate-line');

  const timerText   = document.getElementById('timer-text');
  const refreshIcon = document.querySelector('.refresh-icon');

  // Конфиг десятичных знаков для каждой валюты
  const resCardCfg = <?= json_encode(array_map(fn($c) => ['dec' => $c['dec'], 'ldec' => $c['ldec']], $reserveCards)) ?>;

  function fmtReserve(val, cur) {
    const d = resCardCfg[cur]?.dec ?? 2;
    return Number(val).toLocaleString('ru-RU', { minimumFractionDigits: d, maximumFractionDigits: d });
  }
  function fmtLimit(val, cur) {
    const d = resCardCfg[cur]?.ldec ?? 0;
    return Number(val).toLocaleString('ru-RU', { minimumFractionDigits: d, maximumFractionDigits: d });
  }

  function applyReserveUpdates(newReserves, newLimits) {
    Object.entries(newReserves).forEach(([cur, amount]) => {
      reserves[cur] = amount;

      const amtEl   = document.querySelector('[data-res-amount="' + cur + '"]');
      const badgeEl = document.querySelector('[data-res-badge="' + cur + '"]');
      const limitEl = document.querySelector('[data-res-limit="' + cur + '"]');

      if (amtEl) {
        amtEl.textContent = fmtReserve(amount, cur);
        amtEl.classList.toggle('text-txt-muted', amount <= 0);
      }
      if (badgeEl) {
        if (amount > 0) {
          badgeEl.textContent = 'В наличии';
          badgeEl.className = 'text-[10px] text-cy bg-cy-soft border border-cy-border px-1.5 h-5 rounded flex items-center whitespace-nowrap flex-shrink-0';
        } else {
          badgeEl.textContent = 'Нет';
          badgeEl.className = 'text-[10px] text-txt-muted bg-bg-soft border border-line px-1.5 h-5 rounded flex items-center whitespace-nowrap flex-shrink-0';
        }
      }
      if (limitEl && newLimits?.[cur]) {
        limits[cur] = newLimits[cur];
        limitEl.textContent = 'Лимит ' + fmtLimit(newLimits[cur].min, cur) + ' – ' + fmtLimit(newLimits[cur].max, cur);
      }
    });
    updateGetReserve();
    updateLimitText();
    validateButton();
  }

  function fetchLiveRates() {
    if (refreshIcon) {
      refreshIcon.style.transition = 'transform .6s';
      refreshIcon.style.transform  = 'rotate(360deg)';
      setTimeout(() => { refreshIcon.style.transition = 'none'; refreshIcon.style.transform = 'rotate(0)'; }, 650);
    }
    fetch('get_rates.php')
      .then(r => r.json())
      .then(data => {
        if (data.reserves) applyReserveUpdates(data.reserves, data.limits);
      })
      .catch(() => {});
  }

  let countdown = 15;
  setInterval(() => {
    countdown--;
    if (countdown <= 0) {
      countdown = 15;
      fetchLiveRates();
    }
    if (timerText) timerText.textContent = countdown + 'с';
  }, 1000);

  function fmtDigits(cur) {
    if (cur === 'BTC') return 8;
    if (cur === 'ETH') return 6;
    if (cur === 'SOL') return 4;
    return 2;
  }
  function fmtNum(v, cur) {
    return Number(v).toLocaleString('ru-RU', {
      minimumFractionDigits: fmtDigits(cur),
      maximumFractionDigits: fmtDigits(cur)
    });
  }

  function updateGetReserve() {
    const to = getSelect.value;
    const res = reserves[to] || 0;
    reserveEl.textContent = fmtNum(res, to) + ' ' + to.replace('_', ' ');
  }

  function getRate(from, to) {
    if (from === to) return 1;
    let rate = rates[from]?.[to];
    if (rate !== undefined && rate > 0) return rate;
    rate = rates[to]?.[from];
    if (rate !== undefined && rate > 0) return 1 / rate;
    return 0;
  }

  function updateRateLine() {
    const from = giveSelect.value;
    const to   = getSelect.value;
    const r    = getRate(from, to);
    if (r > 0 && from !== to) {
      rateLine.innerHTML = `1 ${from.replace('_',' ')} ≈ <span class="text-cy font-semibold">${fmtNum(r, to)} ${to.replace('_',' ')}</span>`;
    } else {
      rateLine.textContent = '—';
    }
  }

  function recalculate(source = 'give') {
    const from = giveSelect.value;
    const to   = getSelect.value;
    const rate = getRate(from, to);

    if (source === 'get') {
      let tv = parseFloat(amountGetEl.value.replace(/ /g, '').replace(',', '.')) || 0;
      if (rate > 0) {
        amountGiveEl.value = (tv / rate).toFixed(fmtDigits(from)).replace(/\.?0+$/, '');
      }
    } else {
      let gv = parseFloat(amountGiveEl.value.replace(/ /g, '').replace(',', '.')) || 0;
      if (rate > 0) {
        amountGetEl.value = (gv * rate).toFixed(fmtDigits(to)).replace(/\.?0+$/, '');
      }
    }

    updateGetReserve();
    updateRateLine();
    validateButton();
  }

  function updateLimitText() {
    const cur = giveSelect.value;
    const minVal = limits[cur]?.min ?? 10;
    const maxVal = limits[cur]?.max ?? 100000;
    const d = cur === 'BTC' ? 4 : 0;
    limitText.textContent = `${Number(minVal).toLocaleString('ru-RU',{minimumFractionDigits:d,maximumFractionDigits:d})} – ${Number(maxVal).toLocaleString('ru-RU',{minimumFractionDigits:d,maximumFractionDigits:d})}`;
  }

  function setDisabled(flag, msg = '') {
    submitBtn.disabled = flag;
    errorText.textContent = msg;
  }

  function validateButton() {
    const from = giveSelect.value;
    const to   = getSelect.value;

    if (from === to) return setDisabled(true, 'Выберите разные валюты');

    const minVal = limits[from]?.min ?? 0;
    const maxVal = limits[from]?.max ?? Infinity;
    const getReserveVal = reserves[to] || 0;

    let gv = parseFloat(amountGiveEl.value.replace(/ /g, '').replace(',', '.')) || 0;
    let tv = parseFloat(amountGetEl.value.replace(/ /g, '').replace(',', '.')) || 0;

    if (gv <= 0)        return setDisabled(true, 'Введите сумму');
    if (gv < minVal)    return setDisabled(true, 'Сумма меньше минимума');
    if (gv > maxVal)    return setDisabled(true, 'Сумма превышает максимум');
    if (tv > getReserveVal) return setDisabled(true, 'Превышен резерв');
    setDisabled(false, '');
  }

  amountGiveEl.addEventListener('input', () => recalculate('give'));
  amountGetEl.addEventListener('input',  () => recalculate('get'));
  giveSelect.addEventListener('change',  () => { updateLimitText(); recalculate('give'); });
  getSelect.addEventListener('change',   () => recalculate('give'));

  document.getElementById('swap-btn').addEventListener('click', () => {
    const tmp = amountGiveEl.value;
    amountGiveEl.value = amountGetEl.value;
    amountGetEl.value = tmp;
    const tmpVal = giveSelect.value;
    setCurrency('give', getSelect.value, false);
    setCurrency('get', tmpVal, false);
    updateLimitText();
    recalculate('give');
  });

  // Telegram modal
  const telegramModal = document.getElementById('telegram-modal');
  const telegramForm  = document.getElementById('telegram-form');
  document.getElementById('close-telegram').addEventListener('click', () => telegramModal.classList.add('hidden'));
  telegramModal.addEventListener('click', e => { if (e.target === telegramModal) telegramModal.classList.add('hidden'); });

  document.getElementById('exchange-form').addEventListener('submit', function(e) {
    validateButton();
    if (submitBtn.disabled) { e.preventDefault(); return; }

    if (!<?= json_encode(isLoggedIn()) ?>) {
      e.preventDefault();
      const formData = new FormData(this);
      fetch('save-pending-exchange.php', { method: 'POST', body: formData })
        .then(() => window.location.href = 'login.php');
      return;
    }

    e.preventDefault();
    fetch('telegram-handler.php?t=' + Date.now())
      .then(r => r.json())
      .then(data => {
        if (data.hasTelegram === true) this.submit();
        else telegramModal.classList.remove('hidden');
      })
      .catch(() => telegramModal.classList.remove('hidden'));
  });

  telegramForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const telegram = document.getElementById('telegram-input').value.trim();
    fetch('telegram-handler.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'telegram=' + encodeURIComponent(telegram)
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        telegramModal.classList.add('hidden');
        document.getElementById('exchange-form').submit();
      } else {
        alert(data.message || 'Ошибка сохранения Telegram');
      }
    });
  });

  // ── Двухуровневый пикер валют ──────────────────────────────
  const coinGroups = <?= json_encode($coinGroups) ?>;

  // Маппинг: любой ключ сети → базовый ключ в таблице курсов
  const netToBase = {
    'USDT_TRC20':'USDT_TRC20','USDT_ERC20':'USDT_TRC20','USDT_BEP20':'USDT_TRC20',
    'USDC_TRC20':'USDC','USDC_ERC20':'USDC',
    'ETH':'ETH','SOL':'SOL','BTC':'BTC',
    'RUB_SBP':'RUB','RUB_CASH':'RUB','RUB_CARD':'RUB',
    'USD':'USD',
  };
  function base(key) { return netToBase[key] || key; }

  function findGroup(key) {
    for (const [gk, g] of Object.entries(coinGroups))
      if (key in g.networks) return gk;
    return null;
  }

  function openDrop(side, type) {
    const drop = document.getElementById(side + '-' + type + '-drop');
    const btn  = document.getElementById(side + '-' + type + '-btn');
    document.querySelectorAll('.cur-drop').forEach(d => { if (d !== drop) d.classList.add('hidden'); });
    if (drop.classList.contains('hidden')) {
      const rect = btn.getBoundingClientRect();
      drop.style.top   = (rect.bottom + 6) + 'px';
      drop.style.right = (window.innerWidth - rect.right) + 'px';
      drop.style.left  = 'auto';
      drop.style.maxHeight = (window.innerHeight - rect.bottom - 10) + 'px';
      drop.style.overflowY = 'auto';
    }
    drop.classList.toggle('hidden');
  }

  function selectCoin(side, groupKey) {
    const group   = coinGroups[groupKey];
    const netKeys = Object.keys(group.networks);

    // обновить иконку монеты
    const iconEl  = document.getElementById(side + '-coin-icon');
    iconEl.textContent = group.symbol;
    iconEl.style.cssText = `background:${group.color}1A;border:1px solid ${group.color}33;color:${group.color}`;
    document.getElementById(side + '-coin-label').textContent = group.label;

    // галочки в дропдауне монет
    document.querySelectorAll('#' + side + '-coin-drop .coin-opt').forEach(opt => {
      const active = opt.dataset.group === groupKey;
      opt.classList.toggle('bg-bg-soft', active);
      const chk = opt.querySelector('.chk-icon');
      if (active && !chk) {
        const i = document.createElement('i');
        i.setAttribute('data-lucide','check');
        i.className = 'chk-icon w-3.5 h-3.5 text-cy ml-auto flex-shrink-0';
        opt.appendChild(i);
        if (window.lucide) lucide.createIcons({ elements:[i] });
      } else if (!active && chk) chk.remove();
    });

    document.getElementById(side + '-coin-drop').classList.add('hidden');

    // показать/скрыть шеврон сети
    const chev = document.getElementById(side + '-net-chevron');
    if (chev) chev.classList.toggle('hidden', netKeys.length <= 1);

    // выбрать первую сеть
    selectNet(side, netKeys[0], false);
    document.getElementById(side + '-select').dispatchEvent(new Event('change'));
  }

  function selectNet(side, netKey, dispatch = true) {
    const gk  = findGroup(netKey);
    const net = coinGroups[gk]?.networks[netKey];
    if (!net) return;

    document.getElementById(side + '-net-label').textContent = net.tag;
    document.getElementById(side + '-select').value = netKey;
    buildNetDrop(side, gk, netKey);
    document.getElementById(side + '-net-drop').classList.add('hidden');

    if (dispatch) document.getElementById(side + '-select').dispatchEvent(new Event('change'));
  }

  function buildNetDrop(side, groupKey, currentNet) {
    const drop  = document.getElementById(side + '-net-drop');
    const group = coinGroups[groupKey];
    drop.innerHTML = '';
    for (const [nk, net] of Object.entries(group.networks)) {
      const isActive = nk === currentNet;
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'w-full flex items-center justify-between gap-3 px-3 py-2.5 hover:bg-bg-soft transition ' + (isActive ? 'bg-bg-soft' : '');
      btn.innerHTML = `
        <div class="text-left min-w-0">
          <div class="text-sm font-medium">${net.tag}</div>
          <div class="text-[11px] text-txt-muted">${net.desc}</div>
        </div>
        ${isActive ? '<i data-lucide="check" class="w-3.5 h-3.5 text-cy flex-shrink-0"></i>' : ''}
      `;
      btn.onclick = () => selectNet(side, nk);
      drop.appendChild(btn);
    }
    if (window.lucide) lucide.createIcons({ elements:[drop] });
  }

  // закрытие дропдаунов по клику вне
  document.addEventListener('click', e => {
    if (!e.target.closest('[id$="-coin-btn"]') && !e.target.closest('[id$="-net-btn"]') && !e.target.closest('.cur-drop'))
      document.querySelectorAll('.cur-drop').forEach(d => d.classList.add('hidden'));
  });

  // портировать дропдауны в body
  ['give-coin-drop','give-net-drop','get-coin-drop','get-net-drop'].forEach(id => {
    const el = document.getElementById(id);
    if (el) document.body.appendChild(el);
  });

  // swap
  document.getElementById('swap-btn').addEventListener('click', () => {
    const giveKey = giveSelect.value, getKey = getSelect.value;
    const tmp = amountGiveEl.value;
    amountGiveEl.value = amountGetEl.value;
    amountGetEl.value  = tmp;

    const giveGK = findGroup(giveKey), getGK = findGroup(getKey);
    const giveG = coinGroups[giveGK], getG = coinGroups[getGK];

    // give ← old get
    document.getElementById('give-coin-icon').textContent = getG.symbol;
    document.getElementById('give-coin-icon').style.cssText = `background:${getG.color}1A;border:1px solid ${getG.color}33;color:${getG.color}`;
    document.getElementById('give-coin-label').textContent = getG.label;
    document.getElementById('give-net-label').textContent  = getG.networks[getKey]?.tag || '';
    document.getElementById('give-net-chevron')?.classList.toggle('hidden', Object.keys(getG.networks).length <= 1);
    giveSelect.value = getKey;
    buildNetDrop('give', getGK, getKey);

    // get ← old give
    document.getElementById('get-coin-icon').textContent = giveG.symbol;
    document.getElementById('get-coin-icon').style.cssText = `background:${giveG.color}1A;border:1px solid ${giveG.color}33;color:${giveG.color}`;
    document.getElementById('get-coin-label').textContent = giveG.label;
    document.getElementById('get-net-label').textContent  = giveG.networks[giveKey]?.tag || '';
    document.getElementById('get-net-chevron')?.classList.toggle('hidden', Object.keys(giveG.networks).length <= 1);
    getSelect.value = giveKey;
    buildNetDrop('get', giveGK, giveKey);

    updateLimitText(); recalculate('give');
  });

  // ── Вычисления (с нормализацией base()) ────────────────────
  function fmtDigits(cur) {
    const b = base(cur);
    if (b === 'BTC') return 8; if (b === 'ETH') return 6; if (b === 'SOL') return 4;
    return 2;
  }

  function getRate(from, to) {
    if (base(from) === base(to)) return 1;
    const f = base(from), t = base(to);
    let r = rates[f]?.[t];
    if (r !== undefined && r > 0) return r;
    r = rates[t]?.[f];
    if (r !== undefined && r > 0) return 1 / r;
    return 0;
  }

  function updateGetReserve() {
    const to  = getSelect.value;
    const res = reserves[base(to)] || 0;
    const gk  = findGroup(to);
    const lbl = (coinGroups[gk]?.label || to) + ' · ' + (coinGroups[gk]?.networks[to]?.tag || '');
    reserveEl.textContent = fmtNum(res, base(to)) + ' ' + lbl;
  }

  function updateRateLine() {
    const from = giveSelect.value, to = getSelect.value;
    const r = getRate(from, to);
    if (r > 0 && base(from) !== base(to)) {
      const fGK = findGroup(from), tGK = findGroup(to);
      const fLabel = coinGroups[fGK]?.label || from;
      const tLabel = coinGroups[tGK]?.label || to;
      const tNet   = coinGroups[tGK]?.networks[to]?.tag || '';
      rateLine.innerHTML = `1 ${fLabel} ≈ <span class="text-cy font-semibold">${fmtNum(r, base(to))} ${tLabel} · ${tNet}</span>`;
    } else { rateLine.textContent = '—'; }
  }

  function updateLimitText() {
    const cur = giveSelect.value, b = base(cur);
    const minVal = limits[b]?.min ?? 10, maxVal = limits[b]?.max ?? 100000;
    const d = b === 'BTC' ? 4 : (b === 'ETH' || b === 'SOL' ? 2 : 0);
    limitText.textContent = `${Number(minVal).toLocaleString('ru-RU',{minimumFractionDigits:d,maximumFractionDigits:d})} – ${Number(maxVal).toLocaleString('ru-RU',{minimumFractionDigits:d,maximumFractionDigits:d})}`;
  }

  function validateButton() {
    const from = giveSelect.value, to = getSelect.value;
    if (base(from) === base(to)) return setDisabled(true, 'Выберите разные валюты');
    const minVal = limits[base(from)]?.min ?? 0, maxVal = limits[base(from)]?.max ?? Infinity;
    const resVal = reserves[base(to)] || 0;
    const gv = parseFloat(amountGiveEl.value.replace(/ /g,'').replace(',','.')) || 0;
    const tv = parseFloat(amountGetEl.value.replace(/ /g,'').replace(',','.'))  || 0;
    if (gv <= 0)       return setDisabled(true, 'Введите сумму');
    if (gv < minVal)   return setDisabled(true, 'Сумма меньше минимума');
    if (gv > maxVal)   return setDisabled(true, 'Сумма превышает максимум');
    if (tv > resVal)   return setDisabled(true, 'Превышен резерв');
    setDisabled(false, '');
  }

  // init
  buildNetDrop('give', '<?= $giveCoinKey ?>', '<?= $give ?>');
  buildNetDrop('get',  '<?= $getCoinKey ?>',  '<?= $get ?>');
  updateLimitText();
  recalculate('give');
  updateGetReserve();
  updateRateLine();

  // Авто-запуск обмена после редиректа с логина (если была сохранена заявка)
  <?php if ($auto_exchange && isLoggedIn()): ?>
  window.addEventListener('load', function () {
    setTimeout(function () {
      fetch('telegram-handler.php?t=' + Date.now())
        .then(r => r.json())
        .then(data => {
          if (data.hasTelegram === true) {
            document.getElementById('exchange-form').submit();
          } else {
            telegramModal.classList.remove('hidden');
          }
        })
        .catch(function () {
          telegramModal.classList.remove('hidden');
        });
    }, 800); // небольшая пауза, чтобы toast успел отобразиться
  });
  <?php endif; ?>
</script>

</body>
</html>