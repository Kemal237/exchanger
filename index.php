<?php
require_once 'config.php';
require_once 'auth.php';

// Восстанавливаем данные обмена после успешного логина
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

// Защита от отсутствия курсов
if (!isset($rates) || !is_array($rates)) {
    $rates = [
        'USDT_TRC20' => ['RUB' => 95.00, 'BTC' => 0.000012],
        'RUB'        => ['USDT_TRC20' => 0.0105, 'BTC' => 0.00000012],
        'BTC'        => ['USDT_TRC20' => 82000, 'RUB' => 7800000],
    ];
    $reserves = [
        'USDT_TRC20' => 1500000,
        'RUB'        => 50000000,
        'BTC'        => 15.5,
    ];
    $limits = [
        'USDT_TRC20' => ['min' => 50, 'max' => 50000],
        'RUB'        => ['min' => 5000, 'max' => 2000000],
        'BTC'        => ['min' => 0.001, 'max' => 10],
    ];
}

$rate = $rates[$give][$get] ?? 0;
if ($rate <= 0 && isset($rates[$get][$give]) && $rates[$get][$give] > 0) {
    $rate = 1 / $rates[$get][$give];
}
$amount_get = $amount_give * $rate;

$current_min = $limits[$give]['min'] ?? 10;
$current_max = $limits[$give]['max'] ?? 100000;
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars(SITE_NAME ?? 'Swap') ?> — Обмен USDT, BTC, RUB</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    .timer-wrapper { display: flex; flex-direction: column; align-items: center; margin-bottom: 16px; gap: 6px; }
    .timer-text { font-size: 14px; font-weight: bold; color: #374151; }
    .timer-bar { width: 120px; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; }
    .timer-progress { width: 100%; height: 100%; background: #22c55e; transform: scaleX(1); transform-origin: left; transition: transform 1s linear; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php require_once 'header.php'; ?>

  <main class="container mx-auto px-4 py-10 max-w-5xl">

    <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
      <h2 class="text-3xl font-bold text-center mb-8">Обменять криптовалюту быстро и выгодно</h2>

      <form action="order.php" method="POST" class="grid md:grid-cols-[1fr_auto_1fr] gap-4 md:gap-8 items-stretch" id="exchange-form">

        <!-- Вы отдаёте -->
        <div class="flex flex-col">
          <label class="block text-lg font-medium mb-2">Вы отдаёте</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden">
            <select name="give_currency" id="give-select" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
              <?php foreach (array_keys($rates) as $cur): ?>
                <option value="<?= htmlspecialchars($cur) ?>" <?= $cur === $give ? 'selected' : '' ?>>
                  <?= htmlspecialchars(str_replace('_', ' ', $cur)) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <input type="text"
                   name="amount_give"
                   id="amount-give"
                   value="<?= number_format($amount_give, ($give === 'BTC' ? 8 : 2), '.', '') ?>"
                   class="w-1/2 p-4 text-2xl font-bold focus:outline-none bg-white"
                   required
                   placeholder="<?= ($give === 'BTC') ? '0.00000000' : '100.00' ?>">
          </div>
        </div>

        <!-- Swap -->
        <div class="flex items-center justify-center">
          <button type="button" id="swap-btn" class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gray-200 hover:bg-gray-300 text-gray-700 flex items-center justify-center shadow-md transition transform hover:scale-110 focus:outline-none">
            <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m4 5H4m0 0l4 4m-4-4l4-4"/>
            </svg>
          </button>
        </div>

        <!-- Вы получаете -->
        <div class="flex flex-col">
          <label class="block text-lg font-medium mb-2">Вы получаете</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
            <select name="get_currency" id="get-select" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
              <?php
              $all_currencies = array_unique(array_merge(
                  array_keys($rates),
                  ...array_values(array_map('array_keys', $rates))
              ));
              foreach ($all_currencies as $cur): ?>
                <option value="<?= htmlspecialchars($cur) ?>" <?= $cur === $get ? 'selected' : '' ?>>
                  <?= htmlspecialchars(str_replace('_', ' ', $cur)) ?>
                </option>
              <?php endforeach; ?>
            </select>

            <input type="text"
                   name="amount_get"
                   id="amount-get"
                   value="<?= number_format($amount_get, ($get === 'BTC' ? 8 : 2), '.', '') ?>"
                   class="w-1/2 p-4 text-2xl font-bold focus:outline-none border-l-0 text-green-600 bg-white"
                   required
                   placeholder="<?= ($get === 'BTC') ? '0.00000000' : '100.00' ?>">
          </div>
          <div class="relative">
            <p class="text-sm text-gray-500 mt-1">Резерв: <strong id="reserve-get"><?= number_format($reserves[$get] ?? 0, ($get === 'BTC' ? 8 : 2), '.', ' ') ?></strong></p>
          </div>
        </div>

        <div class="md:col-span-3 text-center mt-6">
          <div class="timer-wrapper">
            <div class="timer-text" id="timer-text">00:15</div>
            <div class="timer-bar">
              <div class="timer-progress" id="timer-progress"></div>
            </div>
          </div>

          <button type="submit" id="submit-btn" 
                  class="inline-flex items-center justify-center bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-xl py-5 px-14 rounded-2xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300 transform disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 gap-3 mx-auto">
            Обменять
            <i class="fas fa-arrow-right"></i>
          </button>

          <div class="mt-4 flex flex-col gap-1">
            <p class="text-sm text-gray-600" id="limit-text">
              Минимум: <?= number_format($current_min, ($give === 'BTC' ? 8 : 2), '.', ' ') ?> • Максимум: <?= number_format($current_max, ($give === 'BTC' ? 8 : 2), '.', ' ') ?>
            </p>
            <p id="error-text" class="text-sm font-medium text-red-600 min-h-[1.25rem]"></p>
          </div>
        </div>
      </form>

      <div class="mt-10 text-center">
        <a href="rates.php" class="inline-block bg-gradient-to-r from-green-500 to-teal-600 text-white font-semibold text-lg py-3 px-8 rounded-xl shadow-lg hover:shadow-2xl hover:scale-105 transition-all duration-300 transform">
          <i class="fas fa-chart-line mr-2"></i> Актуальные резервы и курсы
        </a>
      </div>
    </div>

  </main>

  <footer class="bg-gray-800 text-white py-8 mt-16">
    <div class="container mx-auto px-4 text-center">
      <p>© <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Все права защищены.</p>
      <p class="mt-2 text-sm">Политика AML/KYC | Правила обмена | Контакты: <?= htmlspecialchars(ADMIN_EMAIL) ?></p>
    </div>
  </footer>

  <script>
    const rates = <?= json_encode($rates) ?>;
    const reserves = <?= json_encode($reserves) ?>;
    const limits = <?= json_encode($limits) ?>;

    const amountGiveEl = document.getElementById('amount-give');
    const amountGetEl  = document.getElementById('amount-get');
    const giveSelect   = document.getElementById('give-select');
    const getSelect    = document.getElementById('get-select');
    const reserveEl    = document.getElementById('reserve-get');
    const submitBtn    = document.getElementById('submit-btn');
    const errorText    = document.getElementById('error-text');
    const limitText    = document.getElementById('limit-text');

    const timerProgress = document.getElementById('timer-progress');
    const timerText     = document.getElementById('timer-text');

    let countdown = 15;

    function updateTimerDisplay() {
      const progress = (countdown / 15) * 100;
      timerProgress.style.transform = `scaleX(${progress / 100})`;
      const min = Math.floor(countdown / 60);
      const sec = countdown % 60;
      timerText.textContent = `${min.toString().padStart(2,'0')}:${sec.toString().padStart(2,'0')}`;
    }

    function getRate(from, to) {
      if (from === to) return 1;
      let rate = rates[from]?.[to];
      if (rate !== undefined && rate > 0) return rate;
      rate = rates[to]?.[from];
      if (rate !== undefined && rate > 0) return 1 / rate;
      return 0;
    }

    function recalculate() {
      const from = giveSelect.value;
      const to   = getSelect.value;
      const rate = getRate(from, to);

      let gv = parseFloat(amountGiveEl.value.replace(/[^0-9.]/g, '')) || 0;
      let tv = parseFloat(amountGetEl.value.replace(/[^0-9.]/g, '')) || 0;

      if (rate > 0) {
        tv = gv * rate;
        amountGetEl.value = tv.toFixed(to === 'BTC' ? 8 : 2);
      }

      if (reserveEl) {
        reserveEl.textContent = Number(reserves[to] || 0).toLocaleString('ru-RU', {
          minimumFractionDigits: to === 'BTC' ? 8 : 2,
          maximumFractionDigits: to === 'BTC' ? 8 : 2
        });
      }
    }

    function updateLimitText() {
      const cur = giveSelect.value;
      const minVal = limits[cur]?.min ?? 10;
      const maxVal = limits[cur]?.max ?? 100000;
      const digits = (cur === 'BTC') ? 8 : 2;
      limitText.textContent = `Минимум: ${minVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})} • Максимум: ${maxVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})}`;
    }

    // Слушатели
    amountGiveEl.addEventListener('input', recalculate);
    amountGetEl.addEventListener('input', recalculate);
    giveSelect.addEventListener('change', () => { updateLimitText(); recalculate(); });
    getSelect.addEventListener('change', recalculate);

    document.getElementById('swap-btn').addEventListener('click', () => {
      [giveSelect.value, getSelect.value] = [getSelect.value, giveSelect.value];
      updateLimitText();
      recalculate();
    });

    // При нажатии "Обменять" — если не авторизован → на логин
    document.getElementById('exchange-form').addEventListener('submit', function(e) {
      if (!<?= json_encode(isLoggedIn()) ?>) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('save-pending-exchange.php', { method: 'POST', body: formData })
          .then(() => window.location.href = 'login.php');
        return;
      }
      // Если авторизован — форма отправляется нормально
    });

    // Инициализация
    updateLimitText();
    recalculate();
    updateTimerDisplay();

    setInterval(() => {
      countdown--;
      if (countdown <= 0) countdown = 15;
      updateTimerDisplay();
    }, 1000);
  </script>

</body>
</html>