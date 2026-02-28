<?php
require 'auth.php';

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Пароль должен быть минимум 6 символов';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (register($username, $email, $password)) {
        $success = 'Регистрация успешна! Теперь можете <a href="login.php" class="text-blue-600">войти</a>';
    } else {
        $error = 'Логин или email уже занят';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Регистрация — <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-8">Регистрация</h1>

    <?php if ($error): ?>
      <p class="text-red-600 text-center mb-6"><?= $error ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
      <p class="text-green-600 text-center mb-6"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 mb-2">Логин</label>
        <input type="text" name="username" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Email</label>
        <input type="email" name="email" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Пароль</label>
        <input type="password" name="password" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Повторите пароль</label>
        <input type="password" name="confirm" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
        Зарегистрироваться
      </button>
    </form>

    <p class="text-center mt-6">
      Уже есть аккаунт? <a href="login.php" class="text-blue-600 hover:underline">Войти</a>
    </p>
  </div>

</body>
</html>