<?php
// profile.php — Личный кабинет (полная версия с админ-кнопкой, toast и отменой заявок)

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$isAdmin = isAdmin();   // ← проверка, является ли пользователь админом

// Отмена заявки
if (isset($_GET['cancel_order'])) {
    $order_id = $_GET['cancel_order'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'canceled', canceled_at = NOW() WHERE id = ? AND user_id = ? AND status = 'new'");
    $stmt->execute([$order_id, $user_id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка $order_id успешно отменена"];
    header('Location: profile.php');
    exit;
}

// Получаем заявки пользователя
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Ошибка загрузки заявок: " . $e->getMessage();
}

// Обработка формы редактирования профиля
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name     = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_telegram = trim($_POST['telegram'] ?? '');
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    if (empty($new_name) || empty($new_email)) {
        $error = 'Заполните имя и email';
    } elseif ($new_pass && $new_pass !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (!empty($new_telegram) && (!str_starts_with($new_telegram, '@') || strlen($new_telegram) < 5)) {
        $error = 'Telegram должен начинаться с @ и содержать минимум 5 символов';
    } else {
        try {
            $params = [$new_name, $new_email];
            $query = "UPDATE users SET username = ?, email = ?";

            if ($new_pass) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $query .= ", password = ?";
                $params[] = $hash;
            }

            $query .= ", telegram = ?";
            $params[] = $new_telegram;

            $query .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            $_SESSION['username'] = $new_name;
            $_SESSION['email']    = $new_email;

            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Профиль успешно обновлён'];
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Получаем текущий Telegram
$stmt = $pdo->prepare("SELECT telegram FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$current_telegram = $stmt->fetchColumn() ?? '';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Личный кабинет — <?= htmlspecialchars(SITE_NAME ?? 'Swap') ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    #toast {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%);
      z-index: 9999; padding: 16px 32px; border-radius: 12px; color: white;
      font-weight: bold; box-shadow: 0 10px 25px rgba(0,0,0,0.3);
      opacity: 0; transition: opacity 0.5s ease;
    }
    #toast.show { opacity: 1; }
    #toast.success { background: #10b981; }
    #toast.error   { background: #ef4444; }
  </style>
</head>
<body class="bg-gray-100">

  <?php require_once 'header.php'; ?>

  <!-- Toast сообщение -->
  <?php if (isset($_SESSION['toast'])): ?>
    <div id="toast" class="<?= $_SESSION['toast']['type'] ?>">
      <?= htmlspecialchars($_SESSION['toast']['message']) ?>
    </div>
    <?php unset($_SESSION['toast']); ?>
  <?php endif; ?>

  <main class="container mx-auto px-4 py-10 max-w-5xl">

    <div class="bg-white rounded-2xl shadow-xl p-8">

      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold">Личный кабинет</h1>
        <?php if ($isAdmin): ?>
          <a href="admin/index.php" class="bg-purple-600 text-white px-6 py-3 rounded-xl hover:bg-purple-700 transition font-medium">
            👑 Админ-панель
          </a>
        <?php endif; ?>
      </div>

      <?php if (isset($error) && $error): ?>
        <p class="text-red-600 mb-6 bg-red-50 p-4 rounded border border-red-200"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <!-- Форма редактирования профиля -->
      <form method="POST" class="space-y-6 mb-12 bg-gray-50 p-8 rounded-xl">
        <div>
          <label class="block text-gray-700 mb-2">Имя / Логин</label>
          <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required 
                 class="w-full p-4 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required 
                 class="w-full p-4 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Telegram</label>
          <input type="text" name="telegram" value="<?= htmlspecialchars($current_telegram) ?>" placeholder="@username" 
                 class="w-full p-4 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Новый пароль (оставьте пустым, если не меняете)</label>
          <input type="password" name="new_password" class="w-full p-4 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
          <label class="block text-gray-700 mb-2">Повторите новый пароль</label>
          <input type="password" name="confirm_password" class="w-full p-4 border rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="bg-blue-600 text-white py-4 px-10 rounded-xl hover:bg-blue-700 transition font-medium">
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
                <th class="p-4 border-b">Действия</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="p-4 font-medium"><?= htmlspecialchars($order['id'] ?? '—') ?></td>
                  <td class="p-4"><?= date('d.m.Y H:i', strtotime($order['created_at'] ?? 'now')) ?></td>
                  <td class="p-4"><?= number_format($order['amount_give'] ?? 0, 2) ?> <?= htmlspecialchars($order['give_currency'] ?? '—') ?></td>
                  <td class="p-4"><?= number_format($order['amount_get'] ?? 0, 2) ?> <?= htmlspecialchars($order['get_currency'] ?? '—') ?></td>
                  <td class="p-4">
                    <?php
                    $status = $order['status'] ?? 'new';
                    $statuses = [
                        'new'       => ['text' => 'Новая',     'class' => 'bg-yellow-100 text-yellow-800'],
                        'in_process'=> ['text' => 'В обработке','class' => 'bg-blue-100 text-blue-800'],
                        'success'   => ['text' => 'Успешно',   'class' => 'bg-green-100 text-green-800'],
                        'canceled'  => ['text' => 'Отменено',  'class' => 'bg-red-100 text-red-800'],
                    ];
                    $s = $statuses[$status] ?? ['text' => 'Новая', 'class' => 'bg-yellow-100 text-yellow-800'];
                    ?>
                    <span class="px-3 py-1 rounded-full text-sm <?= $s['class'] ?>">
                      <?= $s['text'] ?>
                    </span>
                  </td>
                  <td class="p-4">
                    <?php if ($status === 'new'): ?>
                      <a href="?cancel_order=<?= htmlspecialchars($order['id']) ?>" 
                         onclick="return confirm('Вы уверены, что хотите отменить заявку?');"
                         class="text-red-600 hover:text-red-800 font-medium">Отменить</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>

  </main>

  <script>
    // Автозакрытие toast
    const toast = document.getElementById('toast');
    if (toast) {
      toast.classList.add('show');
      setTimeout(() => {
        toast.classList.remove('show');
      }, 5000);
    }
  </script>

</body>
</html>