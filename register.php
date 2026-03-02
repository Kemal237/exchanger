<?php
// register.php

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

// session_start() уже в auth.php

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    // Валидация
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email';
    } elseif (strlen($password) < 8) {
        $error = 'Пароль должен быть минимум 8 символов';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну заглавную букву';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну строчную букву';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну цифру';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        if (register($username, $email, $password)) {
            // Автоматический вход после регистрации
            if (login($username, $password)) {
                $success = 'Регистрация успешна! Вы автоматически вошли.';
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Регистрация прошла, но автоматический вход не удался. Попробуйте войти вручную.';
            }
        } else {
            $error = 'Логин или email уже занят';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Регистрация — <?= htmlspecialchars(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-8">Регистрация</h1>

    <?php if ($error): ?>
      <p class="text-red-600 text-center mb-6 bg-red-50 p-3 rounded"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <p class="text-green-600 text-center mb-6 bg-green-50 p-3 rounded"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 mb-2">Логин</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Пароль</label>
        <input type="password" name="password" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Повторите пароль</label>
        <input type="password" name="confirm" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium">
        Зарегистрироваться
      </button>
    </form>

    <p class="text-center mt-6 text-gray-600">
      Уже есть аккаунт? <a href="login.php" class="text-blue-600 hover:underline">Войти</a>
    </p>
  </div>

</body>
</html>