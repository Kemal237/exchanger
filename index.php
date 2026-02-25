<?php
require 'config.php';

$give = $_GET['give'] ?? 'USDT_TRC20';
$get  = $_GET['get']  ?? 'RUB';

$amount_give = floatval($_GET['amount'] ?? 100);
$rate = $rates[$give][$get] ?? 0;
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

      <form action="create-order.php" method="POST" class="grid md:grid-cols-2 gap-8">

        <!-- Отдаёте -->
        <div>
          <label class="block text-lg font-medium mb-2">Вы отдаёте</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden">
            <select name="give_currency" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
              <?php foreach ($rates as $cur => $v): ?>
                <option value="<?= $cur ?>" <?= $cur === $give ? 'selected' : '' ?>>
                  <?= str_replace('_', ' ', $cur) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <input type="number" name="amount_give" step="0.01" value="<?= $amount_give ?>" class="w-1/2 p-4 text-2xl font-bold focus:outline-none" min="<?= $min ?>" required>
          </div>
          <p class="text-sm text-gray-500 mt-1">Резерв: <strong><?= number_format($reserves[$give] ?? 0, 2) ?></strong></p>
        </div>

        <!-- Получаете -->
        <div>
          <label class="block text-lg font-medium mb-2">Вы получаете</label>
          <div class="flex border border-gray-300 rounded-lg overflow-hidden bg-gray-50">
            <select name="get_currency" class="w-1/2 p-4 bg-gray-50 text-xl focus:outline-none">
              <?php foreach ($rates[$give] ?? [] as $cur => $r): ?>
                <option value="<?= $cur ?>" <?= $cur === $get ? 'selected' : '' ?>>
                  <?= str_replace('_', ' ', $cur) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="w-1/2 p-4 text-2xl font-bold text-green-600">
              <?= number_format($amount_get, 2) ?>
            </div>
          </div>
        </div>

        <div class="md:col-span-2 text-center mt-6">
          <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-xl font-bold py-5 px-12 rounded-xl shadow-lg transition">
            Обменять → 
          </button>
          <p class="mt-4 text-sm text-gray-600">
            Минимум: <?= number_format($min, 2) ?> • Максимум: <?= number_format($max, 2) ?>
          </p>
        </div>

      </form>
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
                  <td class="p-4"><?= number_format($rate, 4) ?></td>
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
    // Живой пересчёт при изменении суммы или направления
    document.querySelectorAll('select, input[name="amount_give"]').forEach(el => {
      el.addEventListener('input', () => {
        document.querySelector('form').submit(); // или ajax-запрос в будущем
      });
    });
  </script>

</body>
</html>