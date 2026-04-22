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

<main class="relative max-w-7xl mx-auto px-6 py-10">

  <!-- Hero -->
  <section class="grid lg:grid-cols-[1fr,480px] gap-10 items-start">

    <!-- Left intro -->
    <div class="fade-in">
      <div class="inline-flex items-center gap-2 px-3 h-7 rounded-full bg-cy-soft border border-cy-border text-cy text-xs font-medium mb-5">
        <span class="pdot"></span>
        Онлайн · Курс обновляется каждые 15 секунд
      </div>

      <h1 class="text-4xl md:text-5xl font-bold tracking-tight leading-[1.08] mb-4">
        Обмен криптовалют<br>
        <span class="shimmer-text">быстро и безопасно</span>
      </h1>

      <p class="text-txt-secondary text-base md:text-lg max-w-xl mb-8 leading-relaxed">
        USDT · BTC · RUB — моментальный обмен по актуальному курсу.
        Верифицированный сервис в реестре BestChange.
      </p>

      <!-- Stats -->
      <div class="grid grid-cols-3 gap-3 max-w-xl">
        <div class="spot bg-bg-card border border-line rounded-xl p-4 hover:border-cy-border transition">
          <div class="text-txt-muted text-xs mb-1">Заявок выполнено</div>
          <div class="text-xl font-bold count-up" data-target="<?= $total_orders_count ?>">0</div>
        </div>
        <div class="spot bg-bg-card border border-line rounded-xl p-4 hover:border-cy-border transition">
          <div class="text-txt-muted text-xs mb-1">Среднее время</div>
          <div class="text-xl font-bold">≈ <span class="count-up" data-target="7">0</span> мин</div>
        </div>
        <div class="spot bg-bg-card border border-line rounded-xl p-4 hover:border-cy-border transition">
          <div class="text-txt-muted text-xs mb-1">Поддержка</div>
          <div class="text-xl font-bold">24 / 7</div>
        </div>
      </div>

      <!-- Feature tags -->
      <div class="mt-6 flex flex-wrap gap-2">
        <span class="tag-h inline-flex items-center gap-2 text-sm text-txt-secondary px-3 h-9 rounded-lg border border-line bg-bg-card">
          <i data-lucide="shield-check" class="w-4 h-4 text-cy"></i> AML проверка
        </span>
        <span class="tag-h inline-flex items-center gap-2 text-sm text-txt-secondary px-3 h-9 rounded-lg border border-line bg-bg-card">
          <i data-lucide="zap" class="w-4 h-4 text-warn"></i> Быстрые выплаты
        </span>
        <span class="tag-h inline-flex items-center gap-2 text-sm text-txt-secondary px-3 h-9 rounded-lg border border-line bg-bg-card">
          <i data-lucide="headphones" class="w-4 h-4 text-vi"></i> Поддержка 24/7
        </span>
      </div>
    </div>

    <!-- Right exchange card -->
    <div class="fade-in fd1">
      <form action="order.php" method="POST" id="exchange-form" class="gborder spot bg-bg-card border border-line rounded-2xl p-6 shadow-card block">
        <div class="flex items-center justify-between mb-5">
          <h2 class="text-[17px] font-semibold">Обменять</h2>
          <div class="flex items-center gap-1.5 text-xs text-txt-muted">
            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 refresh-icon"></i>
            обновление <span class="text-cy font-medium" id="timer-text">15с</span>
          </div>
        </div>

        <!-- You give -->
        <div class="field rounded-xl p-4 mb-2">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-txt-muted">Вы отдаёте</span>
            <span class="text-xs text-txt-muted">Лимит: <span id="limit-text" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-3">
            <input type="text" name="amount_give" id="amount-give"
                   value="<?= number_format($amount_give, ($give === 'BTC' ? 8 : 2), '.', '') ?>"
                   class="flex-1 bg-transparent text-2xl font-semibold outline-none placeholder:text-txt-muted" required>
            <select name="give_currency" id="give-select" class="input-d text-sm rounded-lg px-3 h-10 cursor-pointer">
              <?php foreach (array_keys($rates) as $cur): ?>
                <option value="<?= htmlspecialchars($cur) ?>" <?= $cur === $give ? 'selected' : '' ?>>
                  <?= htmlspecialchars(currency_label($cur)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Swap -->
        <div class="relative -my-3 flex justify-center z-10">
          <button type="button" id="swap-btn" class="swap-r w-10 h-10 rounded-full bg-bg-card border border-line hover:border-cy-border hover:text-cy transition flex items-center justify-center shadow-card">
            <i data-lucide="arrow-up-down" class="w-4 h-4"></i>
          </button>
        </div>

        <!-- You get -->
        <div class="field rounded-xl p-4 mt-2">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs text-txt-muted">Вы получаете</span>
            <span class="text-xs text-txt-muted">Резерв: <span id="reserve-get" class="text-txt-secondary">—</span></span>
          </div>
          <div class="flex items-center gap-3">
            <input type="text" name="amount_get" id="amount-get" class="flex-1 bg-transparent text-2xl font-semibold outline-none text-cy">
            <select name="get_currency" id="get-select" class="input-d text-sm rounded-lg px-3 h-10 cursor-pointer">
              <?php
              $all_currencies = array_unique(array_merge(
                  array_keys($rates),
                  ...array_values(array_map('array_keys', $rates))
              ));
              foreach ($all_currencies as $cur): ?>
                <option value="<?= htmlspecialchars($cur) ?>" <?= $cur === $get ? 'selected' : '' ?>>
                  <?= htmlspecialchars(currency_label($cur)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Rate -->
        <div class="mt-4 flex items-center justify-between text-sm bg-bg-soft border border-line rounded-xl px-4 py-3">
          <span class="text-txt-muted">Курс</span>
          <span class="font-medium" id="rate-line">—</span>
        </div>

        <button type="submit" id="submit-btn" class="btn-cy w-full mt-5 h-12 rounded-xl font-semibold text-base flex items-center justify-center gap-2">
          Обменять
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>

        <p id="error-text" class="text-danger text-sm text-center mt-3 min-h-[1.25rem]"></p>

        <div class="mt-2 flex items-center justify-center gap-2 text-xs text-txt-muted">
          <i data-lucide="lock" class="w-3.5 h-3.5"></i>
          Нажимая кнопку, вы соглашаетесь с
          <a class="text-cy hover:underline" href="aml.php">AML политикой</a>
        </div>
      </form>
    </div>

  </section>

  <!-- Reserves -->
  <section class="mt-16">
    <div class="flex items-end justify-between mb-5 reveal">
      <div>
        <h3 class="text-2xl font-bold tracking-tight">Резервы</h3>
        <p class="text-txt-muted text-sm mt-1">Доступные средства обновляются в реальном времени</p>
      </div>
      <a href="rates.php" class="text-sm text-cy hover:text-cy-dark inline-flex items-center gap-1 transition">
        Все курсы <i data-lucide="arrow-right" class="w-4 h-4"></i>
      </a>
    </div>

    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-5 transition" data-d="1">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-emr/10 border border-emr/20 flex items-center justify-center text-emr font-bold text-sm">₮</div>
            <div>
              <div class="font-semibold">USDT</div>
              <div class="text-xs text-txt-muted">Tether · TRC20</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center">В наличии</span>
        </div>
        <div class="text-2xl font-bold"><?= number_format($reserves['USDT_TRC20'] ?? 0, 2, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted">Лимит <?= number_format($limits['USDT_TRC20']['min'] ?? 0, 0, '.', ' ') ?> – <?= number_format($limits['USDT_TRC20']['max'] ?? 0, 0, '.', ' ') ?></div>
      </div>

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-5 transition" data-d="2">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-[#F7931A]/10 border border-[#F7931A]/20 flex items-center justify-center text-[#F7931A] font-bold text-sm">₿</div>
            <div>
              <div class="font-semibold">BTC</div>
              <div class="text-xs text-txt-muted">Bitcoin</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center">В наличии</span>
        </div>
        <div class="text-2xl font-bold"><?= number_format($reserves['BTC'] ?? 0, 8, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted">Лимит <?= number_format($limits['BTC']['min'] ?? 0, 4, '.', ' ') ?> – <?= number_format($limits['BTC']['max'] ?? 0, 4, '.', ' ') ?></div>
      </div>

      <div class="reveal spot bg-bg-card border border-line hover:border-cy-border rounded-xl p-5 transition" data-d="3">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-vi-soft border border-vi/20 flex items-center justify-center text-vi font-bold text-sm">₽</div>
            <div>
              <div class="font-semibold">RUB</div>
              <div class="text-xs text-txt-muted">Карта / СБП</div>
            </div>
          </div>
          <span class="text-xs text-cy bg-cy-soft border border-cy-border px-2 h-6 rounded-md flex items-center">В наличии</span>
        </div>
        <div class="text-2xl font-bold"><?= number_format($reserves['RUB'] ?? 0, 0, '.', ' ') ?></div>
        <div class="mt-2 text-xs text-txt-muted">Лимит <?= number_format($limits['RUB']['min'] ?? 0, 0, '.', ' ') ?> – <?= number_format($limits['RUB']['max'] ?? 0, 0, '.', ' ') ?></div>
      </div>

    </div>
  </section>

  <!-- How it works -->
  <section class="mt-16">
    <h3 class="text-2xl font-bold tracking-tight mb-6 reveal">Как это работает</h3>
    <div class="grid md:grid-cols-4 gap-4">

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-5 hover:border-cy-border transition" data-d="1">
        <div class="step-num">1</div>
        <div class="w-10 h-10 rounded-lg bg-cy-soft border border-cy-border text-cy flex items-center justify-center mb-3">
          <i data-lucide="wallet" class="w-5 h-5"></i>
        </div>
        <div class="font-semibold mb-1">Выберите направление</div>
        <div class="text-sm text-txt-secondary">Укажите, что отдаёте и что хотите получить</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-5 hover:border-cy-border transition" data-d="2">
        <div class="step-num">2</div>
        <div class="w-10 h-10 rounded-lg bg-cy-soft border border-cy-border text-cy flex items-center justify-center mb-3">
          <i data-lucide="user-check" class="w-5 h-5"></i>
        </div>
        <div class="font-semibold mb-1">Оставьте заявку</div>
        <div class="text-sm text-txt-secondary">Введите реквизиты и Telegram для связи</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-5 hover:border-cy-border transition" data-d="3">
        <div class="step-num">3</div>
        <div class="w-10 h-10 rounded-lg bg-vi-soft border border-vi/20 text-vi flex items-center justify-center mb-3">
          <i data-lucide="send" class="w-5 h-5"></i>
        </div>
        <div class="font-semibold mb-1">Переведите средства</div>
        <div class="text-sm text-txt-secondary">Отправьте оплату на указанный адрес</div>
      </div>

      <div class="reveal spot bg-bg-card border border-line rounded-xl p-5 hover:border-cy-border transition" data-d="4">
        <div class="step-num">4</div>
        <div class="w-10 h-10 rounded-lg bg-vi-soft border border-vi/20 text-vi flex items-center justify-center mb-3">
          <i data-lucide="check-circle-2" class="w-5 h-5"></i>
        </div>
        <div class="font-semibold mb-1">Получите обмен</div>
        <div class="text-sm text-txt-secondary">Оператор подтвердит и отправит средства</div>
      </div>

    </div>
  </section>

</main>

<!-- Telegram modal -->
<div id="telegram-modal" class="hidden fixed inset-0 bg-black/70 backdrop-blur-sm flex items-center justify-center z-50">
  <div class="gborder bg-bg-card border border-line rounded-2xl p-8 max-w-md w-full mx-4 shadow-card relative">
    <button type="button" id="close-telegram" class="absolute top-4 right-4 text-txt-muted hover:text-txt-primary transition">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
    <div class="w-12 h-12 rounded-xl bg-cy-soft border border-cy-border flex items-center justify-center mb-4">
      <i data-lucide="send" class="w-6 h-6 text-cy"></i>
    </div>
    <h3 class="text-xl font-bold mb-2">Укажите Telegram</h3>
    <p class="text-txt-secondary text-sm mb-6">Чтобы оператор мог оперативно связаться с вами по заявке</p>
    <form id="telegram-form" class="space-y-4">
      <input type="text" id="telegram-input" name="telegram" placeholder="@username"
             class="input-d w-full px-4 py-3 rounded-lg text-base" required>
      <button type="submit" class="btn-cy w-full h-12 rounded-lg font-semibold flex items-center justify-center gap-2">
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
    [giveSelect.value, getSelect.value] = [getSelect.value, giveSelect.value];
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

  // init
  updateLimitText();
  recalculate('give');
  updateGetReserve();
  updateRateLine();
</script>

</body>
</html>