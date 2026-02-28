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

$min = $limits[$give]['min'] ?? 10;
$max = $limits[$give]['max'] ?? 100000;
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= SITE_NAME ?> — Обмен USDT, BTC, RUB, USD</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body class="bg-gray-50 text-gray-900">

  <header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-4 shadow-lg">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold"><?= SITE_NAME ?></h1>
      <nav>
        <a href="#" class="mx-3 hover:underline">Отзывы</a>
        <a href="#" class="mx-3 hover:underline">Контакты</a>
        <a href="rates.xml.php" target="_blank" class="mx-3 text-yellow-300 hover:underline">Курсы для BestChange</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10 max-w-5xl">

    <!-- Калькулятор -->
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-10">
      <h2 class="text-3xl font-bold text-center mb-8">Обменять криптовалюту быстро и выгодно</h2>

     <form action="create-order.php" method="POST" class="grid md:grid-cols-[1fr_auto_1fr] gap-4 md:gap-6 items-stretch">

  <!-- Вы отдаёте -->
  <div class="flex flex-col">
    <label class="block text-lg font-medium mb-2">Вы отдаёте</label>
    <div class="flex border border-gray-300 rounded-lg overflow-hidden">
      <select name="give_currency" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
        <?php foreach (array_keys($rates) as $cur): ?>
          <option value="<?= $cur ?>" <?= $cur === $give ? 'selected' : '' ?>>
            <?= str_replace('_', ' ', $cur) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="<?= ($give === 'BTC') ? 'text' : 'number' ?>"
             name="amount_give"
             step="<?= ($give === 'BTC') ? 'any' : '0.01' ?>"
             value="<?= $amount_give ?>"
             class="w-1/2 p-4 text-2xl font-bold focus:outline-none"
             min="<?= $min ?>"
             required
             inputmode="<?= ($give === 'BTC') ? 'decimal' : 'numeric' ?>"
             pattern="<?= ($give === 'BTC') ? '^(0|[1-9]\\d*)([.,]\\d*)?$' : '[0-9]+([.][0-9]{1,2})?' ?>"
             placeholder="<?= ($give === 'BTC') ? '0,00000000' : '100,00' ?>"
             title="<?= ($give === 'BTC') ? 'Введите число, начиная с 0 для дробной части' : 'Введите сумму' ?>">
    </div>
    <p class="text-sm text-gray-500 mt-1">Резерв: <strong><?= number_format($reserves[$give] ?? 0, 2) ?></strong></p>
  </div>

  <!-- Кнопка поменять местами — ровно по центру -->
  <div class="flex items-center justify-center">
    <button type="button" id="swap-currencies"
            class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-gray-200 hover:bg-gray-300 text-gray-700 flex items-center justify-center shadow-md transition transform hover:scale-110 focus:outline-none">
      <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
      </svg>
    </button>
  </div>

  <!-- Вы получаете -->
  <div class="flex flex-col">
    <label class="block text-lg font-medium mb-2">Вы получаете</label>
    <div class="flex border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
      <select name="get_currency" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
        <?php
        $all_currencies = array_unique(array_merge(
            array_keys($rates),
            ...array_values(array_map('array_keys', $rates))
        ));
        foreach ($all_currencies as $cur): ?>
          <option value="<?= $cur ?>" <?= $cur === $get ? 'selected' : '' ?>>
            <?= str_replace('_', ' ', $cur) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <div class="w-1/2 p-4 text-2xl font-bold text-green-600">
        <?php
        $format_digits = ($get === 'BTC') ? 8 : 2;
        echo number_format($amount_get, $format_digits, '.', ' ');
        ?>
      </div>
    </div>
  </div>

  <div class="md:col-span-3 text-center mt-6">
    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xl font-bold py-5 px-12 rounded-xl shadow-lg transition">
      Обменять →
    </button>
    <p class="mt-4 text-sm text-gray-600">
      Минимум: <?= number_format($min, 2) ?> • Максимум: <?= number_format($max, 2) ?>
    </p>
  </div>

</form>

      <!-- Передача данных в JS -->
      <script>
        const rates = <?php echo json_encode($rates); ?>;
        const reserves = <?php echo json_encode($reserves); ?>;
      </script>
    </div>

    <!-- Таблица резервов и курсов -->
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
                  <td class="p-4 font-medium"><?= str_replace('_', ' ', $from) ?></td>
                  <td class="p-4 font-medium"><?= str_replace('_', ' ', $to) ?></td>
                  <td class="p-4"><?= number_format($rate, ($to === 'BTC' ? 8 : 4)) ?></td>
                  <td class="p-4 text-green-600"><?= number_format($reserves[$to] ?? $reserves[$from] ?? 0, 2) ?></td>
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
      <p>© <?= date('Y') ?> <?= SITE_NAME ?>. Все права защищены.</p>
      <p class="mt-2 text-sm">Политика AML/KYC | Правила обмена | Контакты: <?= ADMIN_EMAIL ?></p>
    </div>
  </footer>
  <script>
    // Кнопка поменять местами
    document.getElementById('swap-currencies').addEventListener('click', function() {
      const giveSelect = document.querySelector('select[name="give_currency"]');
      const getSelect  = document.querySelector('select[name="get_currency"]');

      // Меняем значения местами
      const temp = giveSelect.value;
      giveSelect.value = getSelect.value;
      getSelect.value = temp;

      // Запускаем пересчёт
      recalculate();
    });
  </script>
  <script>
    const amountGiveInput = document.querySelector('input[name="amount_give"]');
    const giveSelect      = document.querySelector('select[name="give_currency"]');
    const getSelect       = document.querySelector('select[name="get_currency"]');
    const resultField     = document.querySelector('.text-2xl.font-bold.text-green-600');

    // Обработка ввода только для BTC
    amountGiveInput.addEventListener('input', function(e) {
      if (giveSelect.value === 'BTC') {
        let val = this.value.trim();

        // Заменяем запятую на точку
        val = val.replace(',', '.');

        // Запрещаем точку/запятую в начале без 0
        if (val === '.' || val === ',') {
          val = '0.';
        } else if (val.startsWith('.') || val.startsWith(',')) {
          val = '0' + val;
        }

        // Убираем несколько точек подряд
        val = val.replace(/\.{2,}/g, '.');

        // Ограничиваем до 8 знаков после точки
        const parts = val.split('.');
        if (parts.length > 1 && parts[1].length > 8) {
          parts[1] = parts[1].slice(0, 8);
          val = parts.join('.');
        }

        // Если после изменений значение отличается — обновляем поле
        if (this.value !== val) {
          this.value = val;
        }
      }

      recalculate();
    });

    function recalculate() {
      const giveCur = giveSelect.value;
      const getCur  = getSelect.value;

      // Берём значение и преобразуем запятую
      let amountStr = amountGiveInput.value.replace(',', '.');
      const amount  = parseFloat(amountStr) || 0;

      let rate = rates[giveCur]?.[getCur] ?? 0;
      if (rate === 0 && rates[getCur]?.[giveCur]) {
        rate = 1 / rates[getCur][giveCur];
      }

      const received = amount * rate;

      // Для BTC — 8 знаков, иначе 2
      const digits = (getCur === 'BTC') ? 8 : 2;
      resultField.textContent = received.toLocaleString('ru-RU', {
        minimumFractionDigits: digits,
        maximumFractionDigits: digits
      });
    }

    amountGiveInput.addEventListener('input', recalculate);
    giveSelect.addEventListener('change', recalculate);
    getSelect.addEventListener('change', recalculate);

    recalculate();
  </script>
</body>
</html>