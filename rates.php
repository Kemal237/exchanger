<?php
require_once 'config.php';
require_once 'auth.php'; // для сессии и isLoggedIn(), если нужно

// Проверяем, находимся ли мы на главной странице (для навигации)
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
?>

<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Резервы и курсы — <?= htmlspecialchars(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .animate-fadeInUp {
      animation: fadeInUp 0.8s ease-out forwards;
    }
    .delay-100 { animation-delay: 0.1s; }
    .delay-200 { animation-delay: 0.2s; }
    .delay-300 { animation-delay: 0.3s; }
    .delay-400 { animation-delay: 0.4s; }
  </style>
</head>
<body class="bg-gray-50 text-gray-900">

  <header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-4 shadow-lg">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold"><?= htmlspecialchars(SITE_NAME) ?></h1>
      <nav class="flex items-center space-x-6">
        <a href="index.php" class="hover:underline">Главная</a>

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

  <main class="container mx-auto px-4 py-12 max-w-6xl">

    <h1 class="text-4xl font-bold text-center mb-4 text-gray-800">Резервы и актуальные курсы</h1>
    <p class="text-center text-lg text-gray-600 mb-12">Мгновенное обновление резервов и рыночных курсов</p>

    <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
            <tr>
              <th class="p-6 text-left text-lg font-semibold">Отдаёте</th>
              <th class="p-6 text-left text-lg font-semibold">Получаете</th>
              <th class="p-6 text-left text-lg font-semibold">Курс</th>
              <th class="p-6 text-left text-lg font-semibold">Резерв</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php
            $delay = 0;
            foreach ($rates as $from => $to_list):
              foreach ($to_list as $to => $rate):
                $delay += 100;
            ?>
              <tr class="hover:bg-gray-50 transition-colors duration-200 animate-fadeInUp delay-<?= $delay ?>">
                <td class="p-6 font-medium text-gray-800"><?= htmlspecialchars(str_replace('_', ' ', $from)) ?></td>
                <td class="p-6 font-medium text-gray-800"><?= htmlspecialchars(str_replace('_', ' ', $to)) ?></td>
                <td class="p-6 font-semibold text-blue-600"><?= number_format($rate, ($to === 'BTC' ? 8 : 4)) ?></td>
                <td class="p-6 font-medium text-green-600">
                  <?= number_format($reserves[$to] ?? $reserves[$from] ?? 0, ($to === 'BTC' ? 8 : 2), '.', ' ') ?>
                </td>
              </tr>
            <?php
              endforeach;
            endforeach;
            ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="mt-10 text-center">
      <a href="index.php" class="inline-block bg-blue-600 text-white font-bold py-4 px-10 rounded-xl shadow-lg hover:bg-blue-700 transition transform hover:scale-105">
        Вернуться к обмену →
      </a>
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