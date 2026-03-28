<?php
// admin/reserves.php — Управление резервами

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Обработка изменения резерва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reserve'])) {
    $currency = strtoupper(trim($_POST['currency'] ?? ''));
    $amount   = floatval($_POST['amount'] ?? 0);
    $action   = $_POST['action'] ?? 'add'; // add или subtract

    if (!empty($currency) && $amount > 0) {
        if ($action === 'subtract') {
            $amount = -$amount;
        }

        $stmt = $pdo->prepare("
            INSERT INTO reserves (currency, amount) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE amount = amount + ?
        ");
        $stmt->execute([$currency, $amount, $amount]);

        $_SESSION['admin_message'] = "Резерв по валюте $currency успешно обновлён";
    } else {
        $_SESSION['admin_message'] = "Ошибка: укажите валюту и сумму больше 0";
    }

    header('Location: reserves.php');
    exit;
}

// Получаем все резервы
$stmt = $pdo->query("SELECT currency, amount, updated_at FROM reserves ORDER BY currency");
$reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Резервы — Админ-панель</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-gray-900 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold">Админ-панель</h1>
      <nav class="space-x-6">
        <a href="index.php" class="hover:underline">Главная</a>
        <a href="orders.php" class="hover:underline">Заявки</a>
        <a href="users.php" class="hover:underline">Пользователи</a>
        <a href="reserves.php" class="text-yellow-300 font-bold hover:underline">Резервы</a>
        <a href="logout.php" class="hover:underline">Выйти</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10">

    <h1 class="text-3xl font-bold mb-8">Управление резервами</h1>

    <?php if (isset($_SESSION['admin_message'])): ?>
      <div class="bg-green-100 text-green-800 p-4 rounded mb-6">
        <?= htmlspecialchars($_SESSION['admin_message']) ?>
      </div>
      <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>

    <!-- Форма изменения резерва -->
    <div class="bg-white rounded-xl shadow p-8 mb-10">
      <h2 class="text-2xl font-bold mb-6">Изменить резерв</h2>
      <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-gray-700 mb-2">Валюта</label>
          <select name="currency" required class="w-full p-3 border rounded-lg">
            <option value="">Выберите валюту</option>
            <option value="USDT_TRC20">USDT_TRC20</option>
            <option value="RUB">RUB</option>
            <option value="BTC">BTC</option>
            <!-- Добавляйте новые валюты сюда при необходимости -->
          </select>
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Сумма</label>
          <input type="number" name="amount" step="0.00000001" required 
                 class="w-full p-3 border rounded-lg">
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Действие</label>
          <select name="action" class="w-full p-3 border rounded-lg">
            <option value="add">Добавить к резерву</option>
            <option value="subtract">Вычесть из резерва</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" name="update_reserve" 
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg">
            Применить
          </button>
        </div>
      </form>
    </div>

    <!-- Текущие резервы -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-4">Валюта</th>
            <th class="p-4">Текущий резерв</th>
            <th class="p-4">Последнее обновление</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reserves as $r): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4 font-medium"><?= htmlspecialchars($r['currency']) ?></td>
              <td class="p-4 font-mono"><?= number_format($r['amount'], 8) ?></td>
              <td class="p-4 text-gray-500"><?= date('d.m.Y H:i', strtotime($r['updated_at'])) ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($reserves)): ?>
            <tr>
              <td colspan="3" class="p-8 text-center text-gray-500">Резервы ещё не добавлены</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </main>

</body>
</html>