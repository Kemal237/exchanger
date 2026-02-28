<?php
require 'config.php';

$give = $_GET['give'] ?? 'USDT_TRC20';
$get  = $_GET['get']  ?? 'RUB';

$amount_give = floatval($_GET['amount'] ?? 100);
$rate = $rates[$give][$get] ?? 0;
if ($rate === 0 && isset($rates[$get][$give])) {
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
      <nav>
        <a href="#" class="mx-3 hover:underline">Отзывы</a>
        <a href="#" class="mx-3 hover:underline">Контакты</a>
        <a href="rates.xml.php" target="_blank" class="mx-3 text-yellow-300 hover:underline">Курсы для BestChange</a>
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
          <button type="submit" id="submit-btn" class="text-white text-xl font-bold py-5 px-12 rounded-xl shadow-lg transition w-full md:w-auto">
            Обменять →
          </button>

          <div class="mt-4 flex flex-col gap-1">
            <p class="text-sm text-gray-600" id="limit-text">
              Минимум: <?= number_format($current_min, ($give === 'BTC' ? 8 : 2), '.', ' ') ?> • Максимум: <?= number_format($current_max, ($give === 'BTC' ? 8 : 2), '.', ' ') ?>
            </p>
            <p id="error-text" class="text-sm font-medium text-red-600 min-h-[1.25rem]"></p>
          </div>
        </div>

      </form>

      <script>
        const rates = <?= json_encode($rates) ?>;
        const reserves = <?= json_encode($reserves) ?>;
        const limits = <?= json_encode($limits) ?>;

        const amountGive = document.getElementById('amount-give');
        const amountGet  = document.getElementById('amount-get');
        const giveSelect = document.getElementById('give-select');
        const getSelect  = document.getElementById('get-select');
        const reserveEl  = document.getElementById('reserve-get');
        const submitBtn  = document.getElementById('submit-btn');
        const errorText  = document.getElementById('error-text');
        const limitText  = document.getElementById('limit-text');

        function updateLimitText() {
          const cur = giveSelect.value;
          const minVal = limits[cur]?.min ?? 10;
          const maxVal = limits[cur]?.max ?? 100000;
          const digits = (cur === 'BTC') ? 8 : 2;
          limitText.textContent = `Минимум: ${minVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})} • Максимум: ${maxVal.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits})}`;
        }

        function validateButton() {
          const giveCur = giveSelect.value;
          const getCur  = getSelect.value;

          const giveVal = parseFloat(amountGive.value.replace(/ /g, '').replace(',', '.')) || 0;
          const getVal  = parseFloat(amountGet.value.replace(/ /g, '').replace(',', '.')) || 0;

          const min = limits[giveCur]?.min ?? 10;
          const max = limits[giveCur]?.max ?? 100000;
          const reserve = reserves[getCur] ?? Infinity;

          let msg = '';

          if (giveVal > 0 && giveVal < min) msg = `Сумма меньше минимума (${min})`;
          else if (giveVal > max) msg = `Сумма больше максимума (${max})`;
          else if (getVal > reserve) msg = 'Превышен резерв получаемой валюты';

          if (msg) {
            submitBtn.disabled = true;
            submitBtn.classList.add('bg-gray-400', 'cursor-not-allowed', 'disabled-pulse');
            submitBtn.classList.remove('bg-green-600', 'hover:bg-green-700');
            errorText.textContent = msg;
          } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('bg-gray-400', 'cursor-not-allowed', 'disabled-pulse');
            submitBtn.classList.add('bg-green-600', 'hover:bg-green-700');
            errorText.textContent = '';
          }
        }

        function recalculate() {
          const giveCur = giveSelect.value;
          const getCur  = getSelect.value;

          // Обновляем резерв
          const reserveValue = reserves[getCur] ?? 0;
          if (reserveEl) {
            const digits = (getCur === 'BTC') ? 8 : 2;
            reserveEl.textContent = reserveValue.toLocaleString('ru-RU', {minimumFractionDigits: digits, maximumFractionDigits: digits});
          }

          const giveVal = parseFloat(amountGive.value.replace(/ /g, '').replace(',', '.')) || 0;

          let rate = rates[giveCur]?.[getCur] ?? 0;
          if (rate === 0 && rates[getCur]?.[giveCur]) rate = 1 / rates[getCur][giveCur];

          const receiveVal = giveVal * rate;

          amountGet.value = receiveVal.toFixed(8).replace(/\.?0+$/, '');

          validateButton();
        }

        // Слушатели
        amountGive.addEventListener('input', recalculate);
        amountGet.addEventListener('input', recalculate);
        giveSelect.addEventListener('change', () => {
          updateLimitText();
          recalculate();
        });
        getSelect.addEventListener('change', recalculate);

        document.getElementById('swap-btn').addEventListener('click', () => {
          [giveSelect.value, getSelect.value] = [getSelect.value, giveSelect.value];
          updateLimitText();
          recalculate();
        });

        // Старт
        updateLimitText();
        recalculate();
      </script>
    </div>

    <!-- Таблица -->
    <div class="bg-white rounded-xl shadow p-6">
      <h3 class="text-2xl font-bold mb-6">Резервы и курсы</h3>
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-100">
              <th class="p-4">Отдаёте</th>
              <th class="p-4">Получаете</th>
              <th class="p-4">Курс</th>
              <th class="p-4">Резерв</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rates as $from => $to_list): ?>
              <?php foreach ($to_list as $to => $rate): ?>
                <tr class="border-t hover:bg-gray-50">
                  <td class="p-4 font-medium"><?= htmlspecialchars(str_replace('_', ' ', $from)) ?></td>
                  <td class="p-4 font-medium"><?= htmlspecialchars(str_replace('_', ' ', $to)) ?></td>
                  <td class="p-4"><?= number_format($rate, ($to === 'BTC' ? 8 : 4)) ?></td>
                  <td class="p-4 text-green-600">
                    <?= number_format($reserves[$to] ?? $reserves[$from] ?? 0, ($to === 'BTC' ? 8 : 2), '.', ' ') ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

  </main>

  <footer class="bg-gray-800 text-white py-8 mt-16">
    <div class="container mx-auto px-4 text-center">
      <p>© <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Все права защищены.</p>
      <p class="mt-2 text-sm">Политика AML/KYC | Правила обмена | Контакты: <?= htmlspecialchars(ADMIN_EMAIL) ?></p>
    </div>
  </footer>

</body>
</html>