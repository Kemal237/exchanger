<?php
session_start();

define('ADMIN_PASSWORD', 'adminsecret2026'); // ← смени на свой

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
  <title>Админ-панель — Вход</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 min-h-screen flex items-center justify-center">

  <div class="bg-gray-800 p-10 rounded-2xl shadow-2xl w-full max-w-md text-white">
    <h1 class="text-3xl font-bold text-center mb-8">Вход в админ-панель</h1>

    <?php if (isset($error)): ?>
      <p class="text-red-400 text-center mb-6"><?= $error ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block mb-2">Пароль</label>
        <input type="password" name="password" required class="w-full p-3 bg-gray-700 border border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition">
        Войти
      </button>
    </form>
  </div>

</body>
</html>