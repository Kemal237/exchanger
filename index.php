<?php
require_once 'config.php';
require_once 'auth.php';  // запускает session_start() и даёт isLoggedIn()

// Восстанавливаем данные обмена после успешного логина (если они были сохранены)
if (isset($_SESSION['pending_exchange'])) {
    $give        = $_SESSION['pending_exchange']['give_currency']  ?? $give ?? 'USDT_TRC20';
    $get         = $_SESSION['pending_exchange']['get_currency']   ?? $get  ?? 'RUB';
    $amount_give = $_SESSION['pending_exchange']['amount_give']    ?? floatval($_GET['amount'] ?? 100);

    // Очищаем временные данные после использования
    unset($_SESSION['pending_exchange']);
} else {
    // Обычная логика из GET-параметров
    $give        = $_GET['give'] ?? 'USDT_TRC20';
    $get         = $_GET['get']  ?? 'RUB';
    $amount_give = floatval($_GET['amount'] ?? 100);
}

// Получаем курс (с защитой от 0)
$rate = $rates[$give][$get] ?? 0;
if ($rate <= 0 && isset($rates[$get][$give]) && $rates[$get][$give] > 0) {
    $rate = 1 / $rates[$get][$give];
}
$amount_get = $amount_give * $rate;

$current_min = $limits[$give]['min'] ?? 10;
$current_max = $limits[$give]['max'] ?? 100000;

$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars(SITE_NAME) ?> — Обмен USDT, BTC, RUB</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    @keyframes pulse-disabled {
      0%, 100% { opacity: 0.65; }
      50% { opacity: 1; }
    }
    .disabled-pulse {
      animation: pulse-disabled 2s infinite ease-in-out;
      pointer-events: none;
      cursor: not-allowed;
    }
    .timer-wrapper {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 16px;
      gap: 6px;
    }
    .timer-text {
      font-size: 14px;
      font-weight: bold;
      color: #374151;
    }
    .timer-bar {
      width: 120px;
      height: 8px;
      background: #e5e7eb;
      border-radius: 4px;
      overflow: hidden;
    }
    .timer-progress {
      width: 100%;
      height: 100%;
      background: #22c55e;
      transform: scaleX(1);
      transform-origin: left;
      transition: transform 1s linear;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

  <header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-4 shadow-lg">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold"><?= htmlspecialchars(SITE_NAME) ?></h1>
      <nav class="flex items-center space-x-6">
        <?php if (!$is_home): ?>
          <a href="index.php" class="hover:underline">Главная</a>
        <?php endif; ?>
        <a href="rates.php" class="hover:underline">Резервы и курсы</a>
        <?php if (isLoggedIn()): ?>
          <a href="profile.php" class="hover:underline font-medium">Профиль</a>
          <a href="logout.php" class="hover:underline text-red-300 hover:text-red-400">Выйти</a>
        <?php else: ?>
          <a href="login.php" class="hover:underline">Вход</a>
          <a href="register.php" class="hover:underline">Регистрация</a>
        <?php endif; ?>
        <a href="rates.xml.php" target="_blank" class="text-yellow-300 hover:underline">Курсы для BestChange</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10 max-w-5xl">
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
      <h2 class="text-3xl font-bold text-center mb-8">Обменять криптовалюту быстро и выгодно</h2>

      <form action="create-order.php" method="POST" class="grid md:grid-cols-[1fr_auto_1fr] gap-4 md:gap-8 items-stretch" id="exchange-form">

        <!-- Вы отдаёте -->
        <div class="flex flex-col">
          <label class="block text-lg font-medium mb-2">Вы отдаёте</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden">
            <select name="give_currency" id="give-select" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
              <?php
              $allowed = ['USDT_TRC20', 'RUB', 'BTC'];
              foreach ($allowed as $cur): ?>
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
              <?php foreach ($allowed as $cur): ?>
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

          <button type="submit" id="submit-btn" class="inline-flex items-center justify-center bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-xl py-5 px-14 rounded-2xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300 transform disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 gap-3 mx-auto">
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
    const UPDATE_INTERVAL = 15000;
    let countdown = 15;

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
    const timerText = document.getElementById('timer-text');

    function updateTimerDisplay() {
      const progress = (countdown / 15) * 100;
      timerProgress.style.transform = `scaleX(${progress / 100})`;
      const minutes = Math.floor(countdown / 60);
      const seconds = countdown % 60;
      timerText.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    function resetTimer() {
      countdown = 15;
      updateTimerDisplay();
    }

    function updateRatesAndReserves() {
      fetch('get_rates.php')
        .then(response => response.json())
        .then(data => {
          Object.assign(rates, data.rates);
          Object.assign(reserves, data.reserves);
          Object.assign(limits, data.limits);
          recalculate('give');
          updateLimitText();
          resetTimer();
        })
        .catch(err => {
          console.error('Ошибка обновления курсов:', err);
          resetTimer();
        });
    }

    setInterval(() => {
      countdown--;
      if (countdown <= 0) {
        updateRatesAndReserves();
      }
      updateTimerDisplay();
    }, 1000);

    function updateLimitText() {
      const cur = giveSelect.value;
      const minVal = limits[cur]?.min ?? 10;
      const maxVal = limits[cur]?.max ?? 100000;
      const digits = (cur === 'BTC') ? 8 : 2;
      limitText.textContent = `Минимум: ${minVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})} • Максимум: ${maxVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})}`;
    }

    function getRate(fromCur, toCur) {
      if (fromCur === toCur) return 1;
      let rate = rates[fromCur]?.[toCur];
      if (rate !== undefined && rate > 0) return rate;
      rate = rates[toCur]?.[fromCur];
      if (rate !== undefined && rate > 0) return 1 / rate;
      return 0;
    }

    function validateButton() {
      const giveCur = giveSelect.value;
      const getCur  = getSelect.value;
      const giveVal = parseFloat(amountGiveEl.value.replace(/ /g, '').replace(',', '.')) || 0;
      const getVal  = parseFloat(amountGetEl.value.replace(/ /g, '').replace(',', '.')) || 0;

      const min = limits[giveCur]?.min ?? 10;
      const max = limits[giveCur]?.max ?? 100000;
      const reserve = reserves[getCur] ?? Infinity;

      let msg = '';
      if (giveVal > 0 && giveVal < min) msg = `Меньше минимума (${min})`;
      else if (giveVal > max) msg = `Больше максимума (${max})`;
      else if (getVal > reserve) msg = 'Превышен резерв';

      if (msg) {
        submitBtn.disabled = true;
        submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        submitBtn.classList.remove('hover:scale-105', 'hover:shadow-3xl');
        errorText.textContent = msg;
      } else {
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        submitBtn.classList.add('hover:scale-105', 'hover:shadow-3xl');
        errorText.textContent = '';
      }
    }

    function recalculate(source = 'give') {
      const giveCur = giveSelect.value;
      const getCur  = getSelect.value;

      if (reserveEl) {
        const reserveValue = reserves[getCur] ?? 0;
        const digits = (getCur === 'BTC') ? 8 : 2;
        reserveEl.textContent = reserveValue.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits});
      }

      let giveVal = parseFloat(amountGiveEl.value.replace(/ /g, '').replace(',', '.')) || 0;
      let getVal  = parseFloat(amountGetEl.value.replace(/ /g, '').replace(',', '.')) || 0;

      const rate = getRate(giveCur, getCur);

      if (rate === 0) {
        errorText.textContent = 'Курс для этой пары временно недоступен';
        validateButton();
        return;
      }

      if (source === 'get') {
        giveVal = getVal / rate;
        amountGiveEl.value = giveVal.toFixed(giveCur === 'BTC' ? 8 : 2).replace(/\.?0+$/, '');
      } else {
        getVal = giveVal * rate;
        amountGetEl.value = getVal.toFixed(getCur === 'BTC' ? 8 : 2).replace(/\.?0+$/, '');
      }

      validateButton();
    }

    amountGiveEl.addEventListener('input', () => recalculate('give'));
    amountGetEl.addEventListener('input', () => recalculate('get'));
    giveSelect.addEventListener('change', () => {
      updateLimitText();
      recalculate('give');
    });
    getSelect.addEventListener('change', () => {
      recalculate('give');
    });

    document.getElementById('swap-btn').addEventListener('click', () => {
      [giveSelect.value, getSelect.value] = [getSelect.value, giveSelect.value];
      updateLimitText();
      recalculate('give');
    });

    updateLimitText();
    recalculate('give');
    validateButton();
    updateTimerDisplay();
  </script>
</body>
</html>