<?php
require_once 'config.php';
require_once 'auth.php';  // запускает session_start() и даёт isLoggedIn()

$give = $_GET['give'] ?? 'USDT_TRC20';
$get  = $_GET['get']  ?? 'RUB';

$amount_give = floatval($_GET['amount'] ?? 100);

// Получаем курс (с защитой от 0)
$rate = $rates[$give][$get] ?? 0;
if ($rate <= 0 && isset($rates[$get][$give]) && $rates[$get][$give] > 0) {
    $rate = 1 / $rates[$get][$give];
}
$amount_get = $amount_give * $rate;

// Проверяем, реальный ли курс
$rate_note = '';
if ($rate <= 0 || $rate < 0.0001) {  // очень маленький курс тоже считаем ошибкой
    $rate_note = '⚠️ Реальный курс временно недоступен (API CoinGecko не ответил). Показан примерный/старый курс. Обновите страницу позже.';
    error_log("[" . date('Y-m-d H:i:s') . "] CoinGecko API не вернул реальный курс для $give → $get. Использован fallback или 0.");
}

$current_min = $limits[$give]['min'] ?? 10;
$current_max = $limits[$give]['max'] ?? 100000;

// Проверяем, находимся ли мы на главной странице
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars(SITE_NAME) ?> — Обмен USDT, BTC, RUB, USD</title>
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

      <?php if (!empty($rate_note)): ?>
        <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 rounded-r-lg">
          <p class="font-medium"><?= $rate_note ?></p>
        </div>
      <?php endif; ?>

      <form action="create-order.php" method="POST" class="grid md:grid-cols-[1fr_auto_1fr] gap-4 md:gap-8 items-stretch" id="exchange-form">

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
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
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
          <button type="submit" id="submit-btn" class="inline-flex items-center justify-center bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-xl py-5 px-14 rounded-2xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300 transform disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 gap-3">
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

      <!-- Уменьшенная кнопка на страницу резервов и курсов -->
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

  <!-- JavaScript для калькулятора -->
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

      if (rate === 0 && giveCur !== getCur) {
        if (source === 'get') {
          amountGiveEl.value = '0.00';
        } else {
          amountGetEl.value = '0.00';
        }
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

    // Слушатели
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

    // Старт
    updateLimitText();
    recalculate('give');
    validateButton(); // инициализация цвета кнопки при загрузке
  </script>

</body>
</html>