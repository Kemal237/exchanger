<?php
// admin/orders.php — админ-панель заявок с возможностью удаления

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// === Обработка действий ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Изменение статуса
    if (isset($_POST['change_status'])) {
        $order_id = $_POST['order_id'];
        $new_status = $_POST['new_status'];

        $valid_statuses = ['new', 'in_process', 'success', 'canceled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $order_id]);
            $_SESSION['admin_message'] = "Статус заявки $order_id изменён на «" . ucfirst(str_replace('_', ' ', $new_status)) . "»";
        }
    }

    // Удаление заявки
    if (isset($_POST['delete_order'])) {
        $order_id = $_POST['order_id'];
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$order_id]);
        $_SESSION['admin_message'] = "Заявка $order_id успешно удалена";
    }

    header('Location: orders.php');
    exit;
}

// Статистика
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$new_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();
$in_process   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'in_process'")->fetchColumn();
$success      = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'success'")->fetchColumn();
$canceled     = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'canceled'")->fetchColumn();

// Все заявки
$stmt = $pdo->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Заявки — Админ-панель</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-gray-900 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold">Админ-панель</h1>
      <nav class="space-x-6">
        <a href="index.php" class="hover:underline">Главная</a>
        <a href="orders.php" class="text-yellow-300 font-bold hover:underline">Заявки</a>
        <a href="users.php" class="hover:underline">Пользователи</a>
        <a href="reserves.php" class="hover:underline">Резервы</a>
        <a href="logout.php" class="hover:underline">Выйти</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10">

    <h1 class="text-3xl font-bold mb-8">Заявки</h1>

    <?php if (isset($_SESSION['admin_message'])): ?>
      <div class="bg-green-100 text-green-800 p-4 rounded mb-6">
        <?= htmlspecialchars($_SESSION['admin_message']) ?>
      </div>
      <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>

    <!-- Статистика -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-10">
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <h3 class="text-lg font-semibold text-gray-600">Всего</h3>
        <p class="text-4xl font-bold"><?= $total_orders ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <h3 class="text-lg font-semibold text-yellow-600">Новые</h3>
        <p class="text-4xl font-bold"><?= $new_orders ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <h3 class="text-lg font-semibold text-blue-600">В обработке</h3>
        <p class="text-4xl font-bold"><?= $in_process ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <h3 class="text-lg font-semibold text-green-600">Успешно</h3>
        <p class="text-4xl font-bold"><?= $success ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow text-center">
        <h3 class="text-lg font-semibold text-red-600">Отменено</h3>
        <p class="text-4xl font-bold"><?= $canceled ?></p>
      </div>
    </div>

    <!-- Таблица заявок -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-4">№</th>
            <th class="p-4">Дата</th>
            <th class="p-4">Пользователь</th>
            <th class="p-4">Отдаёт</th>
            <th class="p-4">Получает</th>
            <th class="p-4">Статус</th>
            <th class="p-4">Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4 font-medium"><?= htmlspecialchars($order['id']) ?></td>
              <td class="p-4"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
              <td class="p-4"><?= htmlspecialchars($order['username'] ?? 'ID: ' . $order['user_id']) ?></td>
              <td class="p-4"><?= number_format($order['amount_give'], 2) ?> <?= htmlspecialchars($order['give_currency']) ?></td>
              <td class="p-4"><?= number_format($order['amount_get'], 2) ?> <?= htmlspecialchars($order['get_currency']) ?></td>
              <td class="p-4">
                <?php
                $status = $order['status'] ?? 'new';
                $statuses = [
                    'new'        => ['text' => 'Новая',     'class' => 'bg-yellow-100 text-yellow-800'],
                    'in_process' => ['text' => 'В обработке','class' => 'bg-blue-100 text-blue-800'],
                    'success'    => ['text' => 'Успешно',   'class' => 'bg-green-100 text-green-800'],
                    'canceled'   => ['text' => 'Отменено',  'class' => 'bg-red-100 text-red-800'],
                ];
                $s = $statuses[$status] ?? ['text' => 'Неизвестно', 'class' => 'bg-gray-100 text-gray-800'];
                ?>
                <span class="px-3 py-1 rounded-full text-sm <?= $s['class'] ?>">
                  <?= $s['text'] ?>
                </span>
              </td>
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <!-- Изменение статуса -->
                  <form method="POST" class="flex items-center gap-2">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                    <select name="new_status" class="border rounded p-2 text-sm">
                      <option value="new"        <?= $status === 'new' ? 'selected' : '' ?>>Новая</option>
                      <option value="in_process" <?= $status === 'in_process' ? 'selected' : '' ?>>В обработке</option>
                      <option value="success"    <?= $status === 'success' ? 'selected' : '' ?>>Успешно</option>
                      <option value="canceled"   <?= $status === 'canceled' ? 'selected' : '' ?>>Отменено</option>
                    </select>
                    <button type="submit" name="change_status" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                      Изменить
                    </button>
                  </form>

                  <!-- Удаление заявки -->
                  <form method="POST" onsubmit="return confirm('Вы уверены, что хотите УДАЛИТЬ заявку <?= htmlspecialchars($order['id']) ?>? Это действие нельзя отменить!');">
                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                    <button type="submit" name="delete_order" 
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm">
                      Удалить
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

</body>
</html>