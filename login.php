<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

$error = '';
$success = '';
$message = $_SESSION['auth_message'] ?? ''; // сообщение от create-order.php
unset($_SESSION['auth_message']); // чистим после показа

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (login($username, $password)) {
        // После успешного логина — проверяем, куда вернуть
        $redirect = $_GET['redirect'] ?? 'profile.php';
        
        // Если был сохранён обмен — редиректим на index.php
        if (isset($_SESSION['pending_exchange'])) {
            $redirect = 'index.php';
        }

        header("Location: $redirect");
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Вход — <?= htmlspecialchars(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
  <div class="bg-white p-10 rounded-2xl shadow-2xl w-full max-w-md">
    <h1 class="text-3xl font-bold text-center mb-8">Вход</h1>

    <?php if ($message): ?>
      <p class="text-blue-600 text-center mb-6 bg-blue-50 p-3 rounded border border-blue-200">
        <?= htmlspecialchars($message) ?>
      </p>
    <?php endif; ?>

    <?php if ($error): ?>
      <p class="text-red-600 text-center mb-6 bg-red-50 p-3 rounded">
        <?= htmlspecialchars($error) ?>
      </p>
    <?php endif; ?>

    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 mb-2">Логин</label>
        <input type="text" name="username" required autofocus class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <div>
        <label class="block text-gray-700 mb-2">Пароль</label>
        <input type="password" name="password" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>
      <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition font-medium">
        Войти
      </button>
    </form>

    <p class="text-center mt-6 text-gray-600">
      Нет аккаунта? <a href="register.php" class="text-blue-600 hover:underline">Зарегистрироваться</a>
    </p>
  </div>
</body>
</html>