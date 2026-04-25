<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

$allowed = ['USDT_TRC20', 'USDC', 'ETH', 'SOL', 'BTC', 'RUB', 'USD'];

// Резервы и лимиты — из БД (приоритет над кешем)
try {
    $stmt = $pdo->query("SELECT currency, amount, min, max FROM reserves");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cur = $row['currency'];
        $reserves[$cur] = (float)$row['amount'];
        $limits[$cur]   = ['min' => (float)$row['min'], 'max' => (float)$row['max']];
    }
} catch (Exception $e) { /* fallback to cache */ }

$currencyMeta = [
    'USDT_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'name' => 'USDT TRC20', 'dec' => 2],
    'USDC'       => ['icon' => 'circle-dollar-sign', 'color' => '#2775CA', 'name' => 'USDC',        'dec' => 2],
    'ETH'        => ['icon' => 'hexagon',            'color' => '#627EEA', 'name' => 'ETH',          'dec' => 6],
    'SOL'        => ['icon' => 'zap',                'color' => '#9945FF', 'name' => 'SOL',          'dec' => 4],
    'BTC'        => ['icon' => 'bitcoin',            'color' => '#F7931A', 'name' => 'BTC',          'dec' => 8],
    'RUB'        => ['icon' => 'banknote',           'color' => '#A78BFA', 'name' => 'RUB',          'dec' => 2],
    'USD'        => ['icon' => 'dollar-sign',        'color' => '#22D3EE', 'name' => 'USD',          'dec' => 2],
];

$page_title = 'Резервы и курсы — ' . SITE_NAME;
$current_page = 'rates.php';
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
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php require_once 'header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <section class="mb-8 sm:mb-10 fade-in">
    <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs text-txt-muted mb-3 sm:mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">Курсы и резервы</span>
    </div>
    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight mb-2">
      Резервы и <span class="shimmer-text">актуальные курсы</span>
    </h1>
    <p class="text-xs sm:text-sm text-txt-muted flex items-center gap-2">
      <span class="pdot"></span>
      Источник CoinGecko · обновление через <span id="rates-timer" class="text-cy font-medium">30с</span>
    </p>
  </section>

  <!-- Reserve cards -->
  <section class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4 mb-8 sm:mb-10">
    <?php foreach ($allowed as $idx => $cur):
      $meta = $currencyMeta[$cur];
      $reserve = $reserves[$cur] ?? 0;
      $lmin = $limits[$cur]['min'] ?? 0;
      $lmax = $limits[$cur]['max'] ?? 0;
    ?>
      <div class="gborder spot rounded-xl bg-bg-card p-4 sm:p-5 reveal" data-d="<?= $idx + 1 ?>" data-res-cur="<?= $cur ?>">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background: <?= $meta['color'] ?>1A; border: 1px solid <?= $meta['color'] ?>33;">
              <i data-lucide="<?= $meta['icon'] ?>" class="w-4 h-4 sm:w-5 sm:h-5" style="color: <?= $meta['color'] ?>"></i>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-sm truncate"><?= htmlspecialchars($meta['name']) ?></div>
              <div class="text-[10px] text-txt-muted">Резерв</div>
            </div>
          </div>
          <span data-res-badge="<?= $cur ?>" class="st <?= $reserve > 0 ? 'st-ok' : 'st-cancel' ?> flex-shrink-0">
            <i data-lucide="<?= $reserve > 0 ? 'check' : 'x' ?>" class="w-3 h-3 res-badge-icon"></i>
            <span class="hidden sm:inline res-badge-text"><?= $reserve > 0 ? 'В наличии' : 'Нет' ?></span>
          </span>
        </div>
        <div data-res-amount="<?= $cur ?>" class="text-lg sm:text-2xl font-bold tracking-tight mb-1 truncate <?= $reserve <= 0 ? 'text-txt-muted' : '' ?>">
          <?= number_format($reserve, $meta['dec'], '.', ' ') ?>
        </div>
        <div data-res-limit="<?= $cur ?>" class="text-[10px] sm:text-xs text-txt-muted truncate">
          <?php $ldec = in_array($cur, ['BTC','ETH','SOL']) ? 3 : 0; ?>
          Лимит <?= number_format($lmin, $ldec, '.', ' ') ?> – <?= number_format($lmax, $ldec, '.', ' ') ?>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Rates accordion -->
  <section class="reveal" data-d="4">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">

      <!-- Header -->
      <div class="flex items-center justify-between gap-2 px-4 sm:px-6 py-4 sm:py-5 border-b border-line">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
            <i data-lucide="trending-up" class="w-4 h-4 text-cy"></i>
          </div>
          <h2 class="text-base sm:text-lg font-bold truncate">Курсы обмена</h2>
        </div>
        <a href="rates.xml.php" target="_blank" class="text-xs text-cy hover:underline flex items-center gap-1.5 flex-shrink-0 whitespace-nowrap">
          <i data-lucide="code-xml" class="w-3.5 h-3.5"></i>
          <span class="hidden sm:inline">BestChange XML</span><span class="sm:hidden">XML</span>
        </a>
      </div>

      <!-- Accordion groups -->
      <?php foreach ($allowed as $idx => $from):
        $fromMeta = $currencyMeta[$from] ?? ['icon'=>'coins','color'=>'#888','name'=>$from,'dec'=>2];
        $fromRates = $rates[$from] ?? [];
        $validPairs = array_filter($fromRates, fn($r, $to) => $r > 0 && in_array($to, $allowed) && $to !== $from, ARRAY_FILTER_USE_BOTH);
        if (empty($validPairs)) continue;
        $isOpen = ($idx === 0);
      ?>
      <div class="border-b border-line last:border-0">

        <!-- Group header (toggle) -->
        <button type="button" onclick="toggleGroup('<?= $from ?>')"
                class="w-full flex items-center justify-between gap-3 px-4 sm:px-6 py-3 sm:py-4 hover:bg-bg-soft/60 transition-colors text-left group">
          <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                 style="background:<?= $fromMeta['color'] ?>1A;border:1px solid <?= $fromMeta['color'] ?>33;">
              <i data-lucide="<?= $fromMeta['icon'] ?>" class="w-4 h-4 sm:w-5 sm:h-5" style="color:<?= $fromMeta['color'] ?>"></i>
            </div>
            <div class="min-w-0">
              <div class="font-semibold text-sm sm:text-base"><?= htmlspecialchars($fromMeta['name']) ?></div>
              <div class="text-[10px] sm:text-xs text-txt-muted"><?= count($validPairs) ?> направлений обмена</div>
            </div>
          </div>
          <i data-lucide="chevron-down" id="chevron-<?= $from ?>"
             class="w-4 h-4 text-txt-muted flex-shrink-0 transition-transform duration-200 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
        </button>

        <!-- Group body -->
        <div id="group-<?= $from ?>" class="<?= $isOpen ? '' : 'hidden' ?> border-t border-line/50">

          <!-- Column labels (only on desktop) -->
          <div class="hidden sm:grid grid-cols-[1fr,auto,auto,auto] gap-4 px-6 py-2 bg-bg-soft/30 text-[10px] text-txt-muted uppercase tracking-wider">
            <span>Получаете</span>
            <span class="w-40 text-right">Курс (за 1 <?= htmlspecialchars($fromMeta['name']) ?>)</span>
            <span class="w-36 text-right">Резерв</span>
            <span class="w-24 text-right"></span>
          </div>

          <?php foreach ($validPairs as $to => $rate):
            $toMeta  = $currencyMeta[$to] ?? ['icon'=>'coins','color'=>'#888','name'=>$to,'dec'=>2];
            $reserve = $reserves[$to] ?? 0;
            $dec = 2;
            if ($to === 'BTC') $dec = 8;
            elseif ($to === 'ETH') $dec = 6;
            elseif ($to === 'SOL') $dec = 4;
          ?>
          <div class="flex sm:grid sm:grid-cols-[1fr,auto,auto,auto] items-center gap-3 sm:gap-4 px-4 sm:px-6 py-2.5 sm:py-3 border-t border-line/40 hover:bg-bg-soft/40 transition-colors">

            <!-- To currency -->
            <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
              <i data-lucide="arrow-right" class="w-3 h-3 text-txt-muted flex-shrink-0 hidden sm:block"></i>
              <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0"
                   style="background:<?= $toMeta['color'] ?>1A;border:1px solid <?= $toMeta['color'] ?>33;">
                <i data-lucide="<?= $toMeta['icon'] ?>" class="w-3.5 h-3.5" style="color:<?= $toMeta['color'] ?>"></i>
              </div>
              <span class="font-medium text-sm truncate"><?= htmlspecialchars($toMeta['name']) ?></span>
            </div>

            <!-- Rate -->
            <div class="sm:w-40 text-right flex-shrink-0">
              <span class="font-mono text-cy text-sm font-medium"><?= number_format($rate, $dec, '.', ' ') ?></span>
            </div>

            <!-- Reserve -->
            <div class="sm:w-36 text-right flex-shrink-0 hidden sm:block">
              <span data-pair-res="<?= $to ?>" class="text-xs <?= $reserve > 0 ? 'text-emr font-medium' : 'text-txt-muted' ?>">
                <?= $reserve > 0 ? number_format($reserve, $toMeta['dec'], '.', ' ') : '—' ?>
              </span>
            </div>

            <!-- Exchange button -->
            <div class="sm:w-24 text-right flex-shrink-0">
              <a href="index.php?give=<?= urlencode($from) ?>&get=<?= urlencode($to) ?>"
                 class="inline-flex items-center gap-1 px-2.5 h-7 rounded-lg text-[11px] sm:text-xs font-medium btn-cy whitespace-nowrap">
                <i data-lucide="arrow-left-right" class="w-3 h-3"></i>
                <span class="hidden sm:inline">Обменять</span>
              </a>
            </div>

          </div>
          <?php endforeach; ?>
        </div>

      </div>
      <?php endforeach; ?>

    </div>
  </section>

  <div class="mt-10 text-center reveal" data-d="5">
    <a href="index.php" class="btn-cy inline-flex items-center gap-2 px-6 h-12 rounded-xl text-sm font-semibold">
      <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
      Перейти к обмену
    </a>
  </div>

</main>

<?php require_once 'footer.php'; ?>

<script>
function toggleGroup(cur) {
  const body    = document.getElementById('group-' + cur);
  const chevron = document.getElementById('chevron-' + cur);
  const isHidden = body.classList.contains('hidden');
  body.classList.toggle('hidden', !isHidden);
  chevron.classList.toggle('rotate-180', isHidden);
}

// Десятичные знаки для резервов
const resDec = { BTC: 8, ETH: 6, SOL: 4, USDT_TRC20: 2, USDC: 2, RUB: 2, USD: 2 };
const limDec = { BTC: 3, ETH: 3, SOL: 3, USDT_TRC20: 0, USDC: 0, RUB: 0, USD: 0 };

function fmtRes(val, cur) {
  const d = resDec[cur] ?? 2;
  return Number(val).toLocaleString('ru-RU', { minimumFractionDigits: d, maximumFractionDigits: d });
}
function fmtLim(val, cur) {
  const d = limDec[cur] ?? 0;
  return Number(val).toLocaleString('ru-RU', { minimumFractionDigits: d, maximumFractionDigits: d });
}

function applyReserveUpdates(newReserves, newLimits) {
  Object.entries(newReserves).forEach(([cur, amount]) => {
    // Карточки резервов — сумма
    const amtEl = document.querySelector('[data-res-amount="' + cur + '"]');
    if (amtEl) {
      amtEl.textContent = fmtRes(amount, cur);
      amtEl.classList.toggle('text-txt-muted', amount <= 0);
    }

    // Карточки резервов — бейдж
    const badgeEl = document.querySelector('[data-res-badge="' + cur + '"]');
    if (badgeEl) {
      const iconEl = badgeEl.querySelector('.res-badge-icon');
      const textEl = badgeEl.querySelector('.res-badge-text');
      if (amount > 0) {
        badgeEl.className = 'st st-ok flex-shrink-0';
        if (iconEl) iconEl.setAttribute('data-lucide', 'check');
        if (textEl) textEl.textContent = 'В наличии';
      } else {
        badgeEl.className = 'st st-cancel flex-shrink-0';
        if (iconEl) iconEl.setAttribute('data-lucide', 'x');
        if (textEl) textEl.textContent = 'Нет';
      }
      if (window.lucide) lucide.createIcons({ elements: [badgeEl] });
    }

    // Карточки резервов — лимит
    if (newLimits?.[cur]) {
      const limitEl = document.querySelector('[data-res-limit="' + cur + '"]');
      if (limitEl) {
        limitEl.textContent = 'Лимит ' + fmtLim(newLimits[cur].min, cur) + ' – ' + fmtLim(newLimits[cur].max, cur);
      }
    }

    // Резервы в аккордеоне (все ячейки с данной валютой)
    document.querySelectorAll('[data-pair-res="' + cur + '"]').forEach(el => {
      if (amount > 0) {
        el.textContent = fmtRes(amount, cur);
        el.className = 'text-xs text-emr font-medium';
      } else {
        el.textContent = '—';
        el.className = 'text-xs text-txt-muted';
      }
    });
  });
}

// Счётчик обновления
const timerEl = document.getElementById('rates-timer');
let countdown = 15;
setInterval(() => {
  countdown--;
  if (timerEl) timerEl.textContent = countdown + 'с';
  if (countdown <= 0) {
    countdown = 15;
    fetch('get_rates.php')
      .then(r => r.json())
      .then(data => { if (data.reserves) applyReserveUpdates(data.reserves, data.limits); })
      .catch(() => {});
  }
}, 1000);
</script>

</body>
</html>