<?php
// header.php

// Определяем, находимся ли мы уже на главной
$is_home = basename($_SERVER['SCRIPT_NAME']) === 'index.php';
?>

<header class="bg-gradient-to-r from-blue-700 to-indigo-800 text-white py-4 shadow-lg">
  <div class="container mx-auto px-4 flex justify-between items-center">
    <!-- Название сайта — теперь кликабельное и ведёт на главную -->
    <a href="index.php" class="text-2xl font-bold hover:underline">
      <?= htmlspecialchars(SITE_NAME) ?>
    </a>

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