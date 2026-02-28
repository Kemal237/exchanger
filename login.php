<?php
require 'auth.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (login($username, $password)) {
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Вход — <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-8">Вход</h1>

    <?php if ($error): ?>
      <p class="text-red-600 text-center mb-6"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 mb-2">Логин</label>
        <input type="text" name="username" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div>
        <label class="block text-gray-700 mb-2">Пароль</label>
        <input type="password" name="password" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
        Войти
      </button>
    </form>

    <p class="text-center mt-6">
      Нет аккаунта? <a href="register.php" class="text-blue-600 hover:underline">Зарегистрироваться</a>
    </p>
  </div>

</body>
</html>