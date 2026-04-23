<?php
require_once 'config.php';
require_once 'auth.php';

// Восстановление данных обмена после логина
if (isset($_SESSION['pending_exchange'])) {
    $give        = $_SESSION['pending_exchange']['give']        ?? 'USDT_TRC20';
    $get         = $_SESSION['pending_exchange']['get']         ?? 'RUB';
    $amount_give = $_SESSION['pending_exchange']['amount_give'] ?? floatval($_GET['amount'] ?? 100);
    unset($_SESSION['pending_exchange']);
} else {
    $give        = $_GET['give'] ?? 'USDT_TRC20';
    $get         = $_GET['get']  ?? 'RUB';
    $amount_give = floatval($_GET['amount'] ?? 100);
}

// Флаг авто-запуска обмена после логина
$auto_exchange = isset($_SESSION['auto_exchange']);
unset($_SESSION['auto_exchange']);

if (!isset($rates) || !is_array($rates)) {
    $rates = [
        'USDT_TRC20' => ['RUB' => 95.00, 'BTC' => 0.000012],
        'RUB'        => ['USDT_TRC20' => 0.0105, 'BTC' => 0.00000012],
        'BTC'        => ['USDT_TRC20' => 82000, 'RUB' => 7800000],
    ];
    $reserves = [ 'USDT_TRC20' => 1500000, 'RUB' => 50000000, 'BTC' => 15.5 ];
    $limits = [
        'USDT_TRC20' => ['min' => 50, 'max' => 50000],
        'RUB'        => ['min' => 5000, 'max' => 2000000],
        'BTC'        => ['min' => 0.001, 'max' => 10],
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

$page_title = SITE_NAME . ' — Обмен USDT, BTC, RUB';

// Лейблы валют
function currency_label($cur) {
    return str_replace('_', ' ', $cur);
}
function currency_digits($cur) { return $cur === 'BTC' ? 8 : 2; }

$currencyConfig = [
    'USDT_TRC20' => ['symbol' => '₮', 'label' => 'USDT TRC20', 'short' => 'USDT', 'icolor' => '#10B981'],
    'BTC'        => ['symbol' => '₿', 'label' => 'BTC',        'short' => 'BTC',  'icolor' => '#F7931A'],
    'RUB'        => ['symbol' => '₽', 'label' => 'RUB',        'short' => 'RUB',  'icolor' => '#A78BFA'],
];
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
  <section class="grid lg:grid-cols-[1fr,540px] gap-6 lg:gap-10 items-start min-w-0 overflow-hidden">

    <!-- Left intro -->
    <div class="fade-in min-w-0">
      <div class="inline-flex items-center gap-2 px-3 h-7 rounded-full bg-cy-soft border border-cy-border text-cy text-[11px] sm:text-xs font-medium mb-4 sm:mb-5">
        <span class="pdot"></span>
        <span class="hidden sm:inline">Онлайн · Курс обновляется каждые 15 секунд</span>
        <span class="sm:hidden">Онлайн · обновление 15с</span>
      </div>

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
        <span class="tag-h inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
          <i data-lucide="shield-check" class="w-3 h-3 sm:w-4 sm:h-4 text-cy flex-shrink-0"></i> AML проверка
        </span>
        <span class="tag-h inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
          <i data-lucide="zap" class="w-3 h-3 sm:w-4 sm:h-4 text-warn flex-shrink-0"></i> Быстрые выплаты
        </span>
        <span class="tag-h inline-flex items-center gap-1 sm:gap-2 text-[10.5px] sm:text-sm text-txt-secondary px-1.5 sm:px-3 h-7 sm:h-9 rounded-lg border border-line bg-bg-card whitespace-nowrap">
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
        <div class="field rounded-xl p-2.5 sm:p-4 mb-2">
          <div class="flex items-center justify-between mb-1.5 sm:mb-2 gap-2">
            <span class="text-[10px] sm:text-xs text-txt-muted whitespace-nowrap">Вы отдаёте</span>
            <span class="text-[10px] sm:text-xs text-txt-muted truncate">Лимит: <span id="limit-text" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-3">
            <input type="text" name="amount_give" id="amount-give" inputmode="decimal"
                   value="<?= number_format($amount_give, $give === 'BTC' ? 8 : 2, '.', '') ?>"
                   class="flex-1 min-w-0 w-full bg-transparent text-lg sm:text-2xl font-semibold outline-none placeholder:text-txt-muted" required>
            <div class="relative flex-shrink-0" id="give-select-wrapper">
              <input type="hidden" name="give_currency" id="give-select" value="<?= htmlspecialchars($give) ?>">
              <?php $gc = $currencyConfig[$give] ?? ['symbol'=>'?','label'=>$give,'icolor'=>'#888']; ?>
              <button type="button" onclick="toggleCurrencyDrop('give-select-wrapper')"
                      class="currency-btn flex items-center gap-1.5 sm:gap-2 h-9 sm:h-10 px-2 sm:px-3 rounded-lg bg-bg-soft border border-line hover:border-cy-border transition whitespace-nowrap">
                <div class="cur-icon w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold flex-shrink-0"
                     style="background:<?= $gc['icolor'] ?>1A;border:1px solid <?= $gc['icolor'] ?>33;color:<?= $gc['icolor'] ?>">
                  <?= $gc['symbol'] ?>
                </div>
                <span class="cur-label text-xs sm:text-sm font-medium"><?= htmlspecialchars($gc['short'] ?? $gc['label']) ?></span>
                <i data-lucide="chevron-down" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-txt-muted"></i>
              </button>
              <div id="give-drop" class="cur-drop hidden fixed z-[9999] min-w-[155px] max-w-[calc(100vw-16px)] bg-bg-card border border-line rounded-xl shadow-card py-1">
                <?php foreach (array_keys($rates) as $cur):
                  $c = $currencyConfig[$cur] ?? ['symbol'=>'?','label'=>$cur,'icolor'=>'#888'];
                ?>
                <button type="button" data-value="<?= $cur ?>" onclick="setCurrency('give','<?= $cur ?>')"
                        class="cur-opt w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-bg-soft transition text-sm <?= $cur===$give?'bg-bg-soft':'' ?>">
                  <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                       style="background:<?= $c['icolor'] ?>1A;border:1px solid <?= $c['icolor'] ?>33;color:<?= $c['icolor'] ?>">
                    <?= $c['symbol'] ?>
                  </div>
                  <span><?= htmlspecialchars($c['label']) ?></span>
                  <?php if ($cur===$give): ?><i data-lucide="check" class="chk-icon w-3.5 h-3.5 text-cy ml-auto"></i><?php endif; ?>
                </button>
                <?php endforeach; ?>
              </div>
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
        <div class="field rounded-xl p-2.5 sm:p-4 mt-2">
          <div class="flex items-center justify-between mb-1.5 sm:mb-2 gap-2">
            <span class="text-[10px] sm:text-xs text-txt-muted whitespace-nowrap">Вы получаете</span>
            <span class="text-[10px] sm:text-xs text-txt-muted truncate">Резерв: <span id="reserve-get" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-1.5 sm:gap-3">
            <input type="text" name="amount_get" id="amount-get" inputmode="decimal" class="flex-1 min-w-0 w-full bg-transparent text-lg sm:text-2xl font-semibold outline-none text-cy">
            <div class="relative flex-shrink-0" id="get-select-wrapper">
              <input type="hidden" name="get_currency" id="get-select" value="<?= htmlspecialchars($get) ?>">
              <?php $gc2 = $currencyConfig[$get] ?? ['symbol'=>'?','label'=>$get,'icolor'=>'#888']; ?>
              <button type="button" onclick="toggleCurrencyDrop('get-select-wrapper')"
                      class="currency-btn flex items-center gap-1.5 sm:gap-2 h-9 sm:h-10 px-2 sm:px-3 rounded-lg bg-bg-soft border border-line hover:border-cy-border transition whitespace-nowrap">
                <div class="cur-icon w-5 h-5 sm:w-6 sm:h-6 rounded-full flex items-center justify-center text-[10px] sm:text-xs font-bold flex-shrink-0"
                     style="background:<?= $gc2['icolor'] ?>1A;border:1px solid <?= $gc2['icolor'] ?>33;color:<?= $gc2['icolor'] ?>">
                  <?= $gc2['symbol'] ?>
                </div>
                <span class="cur-label text-xs sm:text-sm font-medium"><?= htmlspecialchars($gc2['short'] ?? $gc2['label']) ?></span>
                <i data-lucide="chevron-down" class="w-3 h-3 sm:w-3.5 sm:h-3.5 text-txt-muted"></i>
              </button>
              <div id="get-drop" class="cur-drop hidden fixed z-[9999] min-w-[155px] max-w-[calc(100vw-16px)] bg-bg-card border border-line rounded-xl shadow-card py-1">
                <?php
                $all_currencies = array_unique(array_merge(
                    array_keys($rates),
                    ...array_values(array_map('array_keys', $rates))
                ));
                foreach ($all_currencies as $cur):
                  $c = $currencyConfig[$cur] ?? ['symbol'=>'?','label'=>$cur,'icolor'=>'#888'];
                ?>
                <button type="button" data-value="<?= $cur ?>" onclick="setCurrency('get','<?= $cur ?>')"
                        class="cur-opt w-full flex items-center gap-2.5 px-3 py-2.5 hover:bg-bg-soft transition text-sm <?= $cur===$get?'bg-bg-soft':'' ?>">
                  <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0"
                       style="background:<?= $c['icolor'] ?>1A;border:1px solid <?= $c['icolor'] ?>33;color:<?= $c['icolor'] ?>">
                    <?= $c['symbol'] ?>
                  </div>
                  <span><?= htmlspecialchars($c['label']) ?></span>
                  <?php if ($cur===$get): ?><i data-lucide="check" class="chk-icon w-3.5 h-3.5 text-cy ml-auto"></i><?php endif; ?>
                </button>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>

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

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-4 sm:p-5 transition" data-d="1">
        <div class="flex items-center justify-between mb-3 gap-2">
          <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-emr/10 border border-emr/20 flex items-center justify-center text-emr font-bold text-sm flex-shrink-0">₮</div>
            <div class="min-w-0">
              <div class="font-semibold">USDT</div>
              <div class="text-xs text-txt-muted">Tether · TRC20</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center whitespace-nowrap">В наличии</span>
        </div>
        <div class="text-lg sm:text-2xl font-bold truncate"><?= number_format($reserves['USDT_TRC20'] ?? 0, 2, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted">Лимит <?= number_format($limits['USDT_TRC20']['min'] ?? 0, 0, '.', ' ') ?> – <?= number_format($limits['USDT_TRC20']['max'] ?? 0, 0, '.', ' ') ?></div>
      </div>

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-4 sm:p-5 transition" data-d="2">
        <div class="flex items-center justify-between mb-3 gap-2">
          <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-[#F7931A]/10 border border-[#F7931A]/20 flex items-center justify-center text-[#F7931A] font-bold text-sm flex-shrink-0">₿</div>
            <div class="min-w-0">
              <div class="font-semibold">BTC</div>
              <div class="text-xs text-txt-muted">Bitcoin</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center whitespace-nowrap">В наличии</span>
        </div>
        <div class="text-lg sm:text-2xl font-bold truncate"><?= number_format($reserves['BTC'] ?? 0, 8, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted truncate">Лимит <?= number_format($limits['BTC']['min'] ?? 0, 4, '.', ' ') ?> – <?= number_format($limits['BTC']['max'] ?? 0, 4, '.', ' ') ?></div>
      </div>

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-4 sm:p-5 transition" data-d="3">
        <div class="flex items-center justify-between mb-3 gap-2">
          <div class="flex items-center gap-2 sm:gap-3 min-w-0">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-vi-soft border border-vi/20 flex items-center justify-center text-vi font-bold text-sm flex-shrink-0">₽</div>
            <div class="min-w-0">
              <div class="font-semibold">RUB</div>
              <div class="text-xs text-txt-muted">Карта / СБП</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center whitespace-nowrap">В наличии</span>
        </div>
        <div class="text-lg sm:text-2xl font-bold truncate"><?= number_format($reserves['RUB'] ?? 0, 0, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted">Лимит <?= number_format($limits['RUB']['min'] ?? 0, 0, '.', ' ') ?> – <?= number_format($limits['RUB']['max'] ?? 0, 0, '.', ' ') ?></div>
      </div>

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

  const timerText    = document.getElementById('timer-text');
  const refreshIcon  = document.querySelector('.refresh-icon');

  let countdown = 15;
  setInterval(() => {
    countdown--;
    if (countdown <= 0) {
      countdown = 15;
      if (refreshIcon) {
        refreshIcon.style.transition = 'transform .6s';
        refreshIcon.style.transform = 'rotate(360deg)';
        setTimeout(() => { refreshIcon.style.transition = 'none'; refreshIcon.style.transform = 'rotate(0)'; }, 650);
      }
    }
    if (timerText) timerText.textContent = countdown + 'с';
  }, 1000);

  function fmtDigits(cur) { return cur === 'BTC' ? 8 : 2; }
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

  // Currency dropdown helpers
  const currencyConfig = {
    'USDT_TRC20': { symbol: '₮', label: 'USDT TRC20', short: 'USDT', icolor: '#10B981' },
    'BTC':        { symbol: '₿', label: 'BTC',        short: 'BTC',  icolor: '#F7931A' },
    'RUB':        { symbol: '₽', label: 'RUB',        short: 'RUB',  icolor: '#A78BFA' },
  };

  function toggleCurrencyDrop(wrapperId) {
    const side = wrapperId.replace('-select-wrapper', '');
    const wrapper = document.getElementById(wrapperId);
    const drop = document.getElementById(side + '-drop');
    const btn = wrapper.querySelector('.currency-btn');
    document.querySelectorAll('.cur-drop').forEach(d => { if (d !== drop) d.classList.add('hidden'); });
    if (drop.classList.contains('hidden')) {
      const rect = btn.getBoundingClientRect();
      drop.style.top   = (rect.bottom + 6) + 'px';
      drop.style.right = (window.innerWidth - rect.right) + 'px';
      drop.style.left  = 'auto';
    }
    drop.classList.toggle('hidden');
  }

  function updateCurrencyDisplay(side, value) {
    const wrapper = document.getElementById(side + '-select-wrapper');
    const drop = document.getElementById(side + '-drop');
    const btn = wrapper.querySelector('.currency-btn');
    const cfg = currencyConfig[value] || { symbol: '?', label: value, icolor: '#888' };
    const iconEl = btn.querySelector('.cur-icon');
    iconEl.style.background = cfg.icolor + '1A';
    iconEl.style.border = '1px solid ' + cfg.icolor + '33';
    iconEl.style.color = cfg.icolor;
    iconEl.textContent = cfg.symbol;
    btn.querySelector('.cur-label').textContent = cfg.short || cfg.label;
    drop.querySelectorAll('.cur-opt').forEach(opt => {
      const isActive = opt.dataset.value === value;
      opt.classList.toggle('bg-bg-soft', isActive);
      const chk = opt.querySelector('.chk-icon');
      if (isActive && !chk) {
        const i = document.createElement('i');
        i.setAttribute('data-lucide', 'check');
        i.className = 'chk-icon w-3.5 h-3.5 text-cy ml-auto';
        opt.appendChild(i);
        if (window.lucide) lucide.createIcons({ elements: [i] });
      } else if (!isActive && chk) {
        chk.remove();
      }
    });
  }

  function setCurrency(side, value, dispatch = true) {
    const input = document.getElementById(side + '-select');
    input.value = value;
    updateCurrencyDisplay(side, value);
    document.getElementById(side + '-drop').classList.add('hidden');
    if (dispatch) input.dispatchEvent(new Event('change'));
  }

  document.addEventListener('click', e => {
    if (!e.target.closest('[id$="-select-wrapper"]') && !e.target.closest('.cur-drop')) {
      document.querySelectorAll('.cur-drop').forEach(d => d.classList.add('hidden'));
    }
  });

  // Move dropdowns to body to escape transform/overflow containing blocks
  ['give-drop', 'get-drop'].forEach(id => {
    const el = document.getElementById(id);
    if (el) document.body.appendChild(el);
  });

  // init
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