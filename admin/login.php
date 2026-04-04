<?php
session_start();

require_once '../config.php';
require_once '../auth.php';   // ← обязательно, чтобы работала функция isAdmin()

// Если уже авторизован как администратор — сразу на панель
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

// Проверка: только администратор может видеть форму
if (!isAdmin()) {
    $error = 'Доступ запрещён. Только администратор может войти в панель.';
}

define('ADMIN_PASSWORD', '14751475'); // ← смени на свой надёжный пароль

// Обработка формы (только если это администратор)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $pass = $_POST['password'] ?? '';
    if ($pass === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Админ-панель — Вход</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">

  <div class="bg-gray-800 p-10 rounded-2xl shadow-2xl w-full max-w-md text-white">
    <h1 class="text-3xl font-bold text-center mb-8">Вход в админ-панель</h1>

    <?php if ($error): ?>
      <div class="bg-red-600 text-white p-4 rounded-xl mb-6 text-center font-medium">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
      <!-- Форма видна только администратору -->
      <form method="POST" class="space-y-6">
        <div>
          <label class="block mb-2 text-gray-300">Пароль</label>
          <input type="password" name="password" required 
                 class="w-full p-3 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" 
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition font-medium">
          Войти
        </button>
      </form>
    <?php endif; ?>

    <!-- Кнопка "На главную" -->
    <div class="mt-6 text-center">
      <a href="../index.php" 
         class="inline-block text-gray-400 hover:text-white transition text-sm">
        ← На главную страницу сайта
      </a>
    </div>
  </div>

</body>
</html>