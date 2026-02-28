<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require '../db.php';

// Статистика
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$processed    = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processed'")->fetchColumn();
$rejected     = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'rejected'")->fetchColumn();
$new_orders   = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn();

// Список заявок
$stmt = $pdo->query("SELECT * FROM orders ORDER BY created_at DESC");
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Админ-панель — <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-gray-900 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between">
      <h1 class="text-2xl font-bold">Админ-панель</h1>
      <a href="logout.php" class="hover:underline">Выйти</a>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10">

    <!-- Статистика -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-lg font-semibold text-gray-600">Всего заявок</h3>
        <p class="text-4xl font-bold"><?= $total_orders ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-lg font-semibold text-green-600">Обработано</h3>
        <p class="text-4xl font-bold"><?= $processed ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-lg font-semibold text-red-600">Отклонено</h3>
        <p class="text-4xl font-bold"><?= $rejected ?></p>
      </div>
      <div class="bg-white p-6 rounded-xl shadow">
        <h3 class="text-lg font-semibold text-yellow-600">Новые</h3>
        <p class="text-4xl font-bold"><?= $new_orders ?></p>
      </div>
    </div>

    <!-- Список заявок -->
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
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4"><?= htmlspecialchars($order['id']) ?></td>
              <td class="p-4"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
              <td class="p-4"><?= $order['user_id'] ? htmlspecialchars($pdo->query("SELECT username FROM users WHERE id = {$order['user_id']}")->fetchColumn()) : 'Гость' ?></td>
              <td class="p-4"><?= number_format($order['amount_give'], 2) ?> <?= $order['give_currency'] ?></td>
              <td class="p-4"><?= number_format($order['amount_get'], 2) ?> <?= $order['get_currency'] ?></td>
              <td class="p-4">
                <span class="px-3 py-1 rounded-full text-sm
                  <?= $order['status'] === 'processed' ? 'bg-green-100 text-green-800' : 
                      ($order['status'] === 'rejected' ? 'bg-red-100 text-red-800' : 
                      ($order['status'] === 'new' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) ?>">
                  <?= $order['status'] === 'new' ? 'Новая' : ($order['status'] === 'processed' ? 'Обработана' : ($order['status'] === 'rejected' ? 'Отклонена' : 'Другое')) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

</body>
</html>