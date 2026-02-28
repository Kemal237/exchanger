<?php
require 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Получаем заявки пользователя
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name  = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    $error = $success = '';

    if ($new_pass && $new_pass !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (updateProfile($user_id, $new_name, $new_email, $new_pass ?: null)) {
        $_SESSION['username'] = $new_name;
        $_SESSION['email']    = $new_email;
        $success = 'Профиль обновлён';
    } else {
        $error = 'Ошибка обновления профиля';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Личный кабинет — <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-blue-800 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between">
      <h1 class="text-2xl font-bold"><?= SITE_NAME ?></h1>
      <div>
        <a href="index.php" class="mx-3 hover:underline">Главная</a>
        <a href="logout.php" class="mx-3 hover:underline">Выйти</a>
      </div>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10 max-w-5xl">

    <div class="bg-white rounded-2xl shadow-xl p-8">

      <h1 class="text-3xl font-bold mb-8">Личный кабинет</h1>

      <?php if (isset($success)): ?>
        <p class="text-green-600 mb-6"><?= $success ?></p>
      <?php endif; ?>
      <?php if (isset($error)): ?>
        <p class="text-red-600 mb-6"><?= $error ?></p>
      <?php endif; ?>

      <!-- Форма редактирования профиля -->
      <form method="POST" class="space-y-6 mb-12">
        <div>
          <label class="block text-gray-700 mb-2">Имя / Логин</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>" required class="w-full p-3 border rounded-lg">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email']) ?>" required class="w-full p-3 border rounded-lg">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Новый пароль (оставьте пустым, если не меняете)</label>
          <input type="password" name="new_password" class="w-full p-3 border rounded-lg">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Повторите новый пароль</label>
          <input type="password" name="confirm_password" class="w-full p-3 border rounded-lg">
        </div>

        <button type="submit" class="bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700">
          Сохранить изменения
        </button>
      </form>

      <!-- Заявки пользователя -->
      <h2 class="text-2xl font-bold mb-6">Ваши заявки</h2>

      <?php if (empty($orders)): ?>
        <p class="text-gray-600">У вас пока нет заявок.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="p-4 border">№</th>
                <th class="p-4 border">Дата</th>
                <th class="p-4 border">Отдаёте</th>
                <th class="p-4 border">Получаете</th>
                <th class="p-4 border">Статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-4 border"><?= htmlspecialchars($order['id']) ?></td>
                  <td class="p-4 border"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                  <td class="p-4 border"><?= number_format($order['amount_give'], 2) ?> <?= $order['give_currency'] ?></td>
                  <td class="p-4 border"><?= number_format($order['amount_get'], 2) ?> <?= $order['get_currency'] ?></td>
                  <td class="p-4 border">
                    <?php
                    $status = $order['status'];
                    $colors = [
                      'new'       => 'bg-yellow-100 text-yellow-800',
                      'paid'      => 'bg-blue-100 text-blue-800',
                      'processed' => 'bg-green-100 text-green-800',
                      'rejected'  => 'bg-red-100 text-red-800',
                      'canceled'  => 'bg-gray-100 text-gray-800',
                    ];
                    ?>
                    <span class="px-3 py-1 rounded-full text-sm <?= $colors[$status] ?? 'bg-gray-100' ?>">
                      <?= $status === 'new' ? 'Новая' : ($status === 'paid' ? 'Оплачена' : ($status === 'processed' ? 'Обработана' : ($status === 'rejected' ? 'Отклонена' : 'Отменена'))) ?>
                    </span>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>

  </main>

</body>
</html>