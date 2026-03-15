<?php
require_once 'config.php';
require_once 'auth.php';

// Восстанавливаем данные обмена после успешного логина (если они были сохранены)
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

// Защита: если $rates не определён — задаём fallback-значения
if (!isset($rates) || !is_array($rates)) {
    $rates = [
        'USDT_TRC20' => ['RUB' => 95.00, 'USD' => 1.02, 'EUR' => 0.94, 'BTC' => 0.000012],
        'RUB'        => ['USDT_TRC20' => 0.0105, 'USD' => 0.0111, 'EUR' => 0.0102, 'BTC' => 0.00000012],
        'BTC'        => ['USDT_TRC20' => 82000, 'RUB' => 7800000, 'USD' => 85000, 'EUR' => 78000],
        'USD'        => ['USDT_TRC20' => 0.98, 'RUB' => 90.00, 'EUR' => 0.92, 'BTC' => 0.000012],
        'EUR'        => ['USDT_TRC20' => 1.07, 'RUB' => 97.00, 'USD' => 1.09, 'BTC' => 0.000013],
    ];
    $reserves = [
        'USDT_TRC20' => 1500000,
        'RUB'        => 50000000,
        'BTC'        => 15.5,
        'USD'        => 120000,
        'EUR'        => 350000,
    ];
    $limits = [
        'USDT_TRC20' => ['min' => 50, 'max' => 50000],
        'RUB'        => ['min' => 5000, 'max' => 2000000],
        'BTC'        => ['min' => 0.001, 'max' => 10],
        'USD'        => ['min' => 50, 'max' => 50000],
        'EUR'        => ['min' => 50, 'max' => 50000],
    ];
}

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
  <title><?= htmlspecialchars(SITE_NAME) ?> — Обмен USDT, BTC, RUB, USD</title>
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

      <?php if (!isLoggedIn()): ?>
        <div class="text-center py-12 bg-yellow-50 rounded-xl border border-yellow-400 mb-8">
          <p class="text-xl font-medium text-yellow-800 mb-6">Для создания заказа войдите в аккаунт</p>
          <div class="flex justify-center gap-6">
            <a href="login.php" class="bg-blue-600 text-white font-bold py-4 px-10 rounded-xl hover:bg-blue-700">Войти</a>
            <a href="register.php" class="bg-green-600 text-white font-bold py-4 px-10 rounded-xl hover:bg-green-700">Зарегистрироваться</a>
          </div>
        </div>
      <?php else: ?>

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

      <?php endif; ?>

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

  <!-- Модальное окно Telegram (появляется ТОЛЬКО при нажатии "Обменять", если Telegram пустой) -->
  <div id="telegram-modal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-3xl p-10 max-w-md w-full mx-4 shadow-2xl relative">
      <button id="close-telegram" class="absolute top-5 right-5 text-gray-500 hover:text-gray-800 text-3xl font-bold">×</button>

      <h2 class="text-3xl font-bold text-center mb-4">Укажите Telegram</h2>
      <p class="text-center text-gray-600 mb-8">Это нужно для связи по заявке и уведомлений</p>

      <form id="telegram-form">
        <input type="text" name="telegram" placeholder="@username" required autofocus
               class="w-full p-5 border border-gray-300 rounded-2xl text-xl focus:outline-none focus:ring-4 focus:ring-green-400 mb-6">

        <button type="button" id="save-telegram-btn" class="w-full bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-xl py-5 rounded-2xl hover:scale-105 transition">
          Сохранить и продолжить
        </button>
      </form>
    </div>
  </div>

  <script>
    // Твой скрипт recalculate, validateButton, timer и т.д. остаётся без изменений
    // ... (вставь сюда весь свой существующий JavaScript-код до конца) ...

    // Логика модалки Telegram — ТОЛЬКО при нажатии "Обменять"
    document.getElementById('exchange-form')?.addEventListener('submit', function(e) {
      e.preventDefault(); // Сначала останавливаем отправку

      // Проверяем наличие Telegram через AJAX
      fetch('telegram-handler.php')
        .then(response => response.json())
        .then(data => {
          if (!data.hasTelegram) {
            // Показываем модалку
            document.getElementById('telegram-modal').classList.remove('hidden');
          } else {
            // Telegram есть — отправляем форму
            this.submit();
          }
        })
        .catch(() => {
          // На всякий случай показываем модалку
          document.getElementById('telegram-modal').classList.remove('hidden');
        });
    });

    // Сохранение Telegram из модалки (AJAX)
    document.getElementById('save-telegram-btn')?.addEventListener('click', function() {
      const telegramInput = document.querySelector('#telegram-form input[name="telegram"]');
      const telegramValue = telegramInput.value.trim();

      if (!telegramValue || !telegramValue.startsWith('@') || telegramValue.length < 5) {
        alert('Telegram должен начинаться с @ и содержать минимум 5 символов');
        return;
      }

      const formData = new FormData();
      formData.append('telegram', telegramValue);

      fetch('telegram-handler.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Закрываем модалку и отправляем основную форму
          document.getElementById('telegram-modal').classList.add('hidden');
          document.getElementById('exchange-form').submit();
        } else {
          alert(data.message || 'Ошибка сохранения');
        }
      })
      .catch(() => alert('Ошибка соединения'));
    });

    // Закрытие модалки
    document.getElementById('close-telegram')?.addEventListener('click', () => {
      document.getElementById('telegram-modal').classList.add('hidden');
    });

    document.getElementById('telegram-modal')?.addEventListener('click', e => {
      if (e.target === document.getElementById('telegram-modal')) {
        document.getElementById('telegram-modal').classList.add('hidden');
      }
    });
  </script>

</body>
</html>