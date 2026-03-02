<?php
// profile.php — Личный кабинет

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// Получаем заявки пользователя
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Ошибка загрузки заявок: " . $e->getMessage();
}

// Обработка формы изменения профиля
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name  = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_pass  = $_POST['new_password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (empty($new_name) || empty($new_email)) {
        $error = 'Заполните имя и email';
    } elseif ($new_pass && $new_pass !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            if (updateProfile($user_id, $new_name, $new_email, $new_pass ?: null)) {
                $_SESSION['username'] = $new_name;
                $_SESSION['email']    = $new_email;
                $success = 'Профиль успешно обновлён';
            } else {
                $error = 'Ошибка при сохранении профиля';
            }
        } catch (Exception $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Личный кабинет — <?= htmlspecialchars(SITE_NAME ?? 'Swap') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <?php require_once 'header.php'; ?>

  <main class="container mx-auto px-4 py-10 max-w-5xl">

    <div class="bg-white rounded-2xl shadow-xl p-8">

      <h1 class="text-3xl font-bold mb-8">Личный кабинет</h1>

      <?php if (isset($error) && $error): ?>
        <p class="text-red-600 mb-6 bg-red-50 p-4 rounded border border-red-200">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <?php if ($success): ?>
        <p class="text-green-600 mb-6 bg-green-50 p-4 rounded border border-green-200">
          <?= htmlspecialchars($success) ?>
        </p>
      <?php endif; ?>

      <!-- Форма редактирования профиля -->
      <form method="POST" class="space-y-6 mb-12">
        <div>
          <label class="block text-gray-700 mb-2">Имя / Логин</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Новый пароль (оставьте пустым, если не меняете)</label>
          <input type="password" name="new_password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Повторите новый пароль</label>
          <input type="password" name="confirm_password" class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700 transition font-medium">
          Сохранить изменения
        </button>
      </form>

      <!-- Заявки -->
      <h2 class="text-2xl font-bold mb-6">Ваши заявки</h2>

      <?php if (empty($orders)): ?>
        <p class="text-gray-600">У вас пока нет заявок.</p>
      <?php else: ?>
        <div class="overflow-x-auto rounded-lg border border-gray-200">
          <table class="w-full text-left">
            <thead class="bg-gray-100">
              <tr>
                <th class="p-4 border-b">№ заявки</th>
                <th class="p-4 border-b">Дата</th>
                <th class="p-4 border-b">Отдаёте</th>
                <th class="p-4 border-b">Получаете</th>
                <th class="p-4 border-b">Статус</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-4"><?= htmlspecialchars($order['id'] ?? '—') ?></td>
                  <td class="p-4"><?= date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')) ?></td>
                  <td class="p-4"><?= number_format($order['amount_give'] ?? 0, 2) ?> <?= htmlspecialchars($order['give_currency'] ?? '—') ?></td>
                  <td class="p-4"><?= number_format($order['amount_get'] ?? 0, 2) ?> <?= htmlspecialchars($order['get_currency'] ?? '—') ?></td>
                  <td class="p-4">
                    <?php
                    $status = $order['status'] ?? 'new';
                    $status_text = [
                        'new'       => 'Новая',
                        'paid'      => 'Оплачена',
                        'processed' => 'Обработана',
                        'rejected'  => 'Отклонена',
                        'canceled'  => 'Отменена',
                    ][$status] ?? 'Неизвестно';
                    $status_color = [
                        'new'       => 'bg-yellow-100 text-yellow-800',
                        'paid'      => 'bg-blue-100 text-blue-800',
                        'processed' => 'bg-green-100 text-green-800',
                        'rejected'  => 'bg-red-100 text-red-800',
                        'canceled'  => 'bg-gray-100 text-gray-800',
                    ][$status] ?? 'bg-gray-100';
                    ?>
                    <span class="px-3 py-1 rounded-full text-sm <?= $status_color ?>">
                      <?= $status_text ?>
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