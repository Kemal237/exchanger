<?php
// admin/reserves.php — Управление резервами + идентичный стиль в обоих разделах

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// 1. Изменение резерва
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reserve'])) {
    $currency = strtoupper(trim($_POST['currency'] ?? ''));
    $amount   = floatval($_POST['amount'] ?? 0);
    $action   = $_POST['action'] ?? 'add';

    if (!empty($currency) && $amount > 0) {
        $change = ($action === 'subtract') ? -$amount : $amount;
        $stmt = $pdo->prepare("
            INSERT INTO reserves (currency, amount) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE amount = amount + ?
        ");
        $stmt->execute([$currency, $change, $change]);
        $_SESSION['admin_message'] = "Резерв по валюте $currency обновлён";
    }
    header('Location: reserves.php');
    exit;
}

// 2. Обновление лимитов (min / max)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_limits'])) {
    $currency = strtoupper(trim($_POST['currency'] ?? ''));
    $min      = floatval($_POST['min'] ?? 0);
    $max      = floatval($_POST['max'] ?? 0);

    if (!empty($currency)) {
        $stmt = $pdo->prepare("
            INSERT INTO reserves (currency, min, max) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE min = VALUES(min), max = VALUES(max)
        ");
        $stmt->execute([$currency, $min, $max]);
        $_SESSION['admin_message'] = "Лимиты для валюты $currency успешно обновлены";
    }
    header('Location: reserves.php');
    exit;
}

// Получаем все резервы
$stmt = $pdo->query("
    SELECT currency, amount, min, max, updated_at 
    FROM reserves 
    ORDER BY currency
");
$reserves = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Данные для автозаполнения
$js_limits = [];
foreach ($reserves as $r) {
    $js_limits[$r['currency']] = [
        'min' => $r['min'],
        'max' => $r['max']
    ];
}
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
      <div class="bg-green-100 text-green-800 p-4 rounded-xl mb-8">
        <?= htmlspecialchars($_SESSION['admin_message']) ?>
      </div>
      <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>

    <!-- 1. Изменить резерв -->
    <div class="bg-white rounded-2xl shadow p-8 mb-12">
      <h2 class="text-2xl font-bold mb-6">Изменить резерв</h2>
      <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-gray-700 mb-2">Валюта</label>
          <select name="currency" required class="w-full p-3 border rounded-xl">
            <option value="">Выберите валюту</option>
            <option value="USDT_TRC20">USDT_TRC20</option>
            <option value="RUB">RUB</option>
            <option value="BTC">BTC</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Сумма</label>
          <input type="number" name="amount" step="0.00000001" value="0" class="w-full p-3 border rounded-xl">
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Действие</label>
          <select name="action" class="w-full p-3 border rounded-xl">
            <option value="add">Добавить к резерву</option>
            <option value="subtract">Вычесть из резерва</option>
          </select>
        </div>
        <div class="flex items-end">
          <button type="submit" name="update_reserve" 
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-2xl font-medium">
            Применить
          </button>
        </div>
      </form>
    </div>

    <!-- 2. Установить лимиты — полностью в том же стиле -->
    <div class="bg-white rounded-2xl shadow p-8 mb-12">
      <h2 class="text-2xl font-bold mb-6">Установить лимиты обмена (минимум и максимум)</h2>
      <form method="POST" id="limits-form" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label class="block text-gray-700 mb-2">Валюта</label>
          <select name="currency" id="limit-currency" required class="w-full p-3 border rounded-xl">
            <option value="">Выберите валюту</option>
            <option value="USDT_TRC20">USDT_TRC20</option>
            <option value="RUB">RUB</option>
            <option value="BTC">BTC</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Минимум</label>
          <input type="number" name="min" id="min-input" step="0.00000001" class="w-full p-3 border rounded-xl">
        </div>
        <div>
          <label class="block text-gray-700 mb-2">Максимум</label>
          <input type="number" name="max" id="max-input" step="0.00000001" class="w-full p-3 border rounded-xl">
        </div>
        <div class="flex items-end">
          <button type="submit" name="update_limits" 
                  class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-2xl font-medium">
            Сохранить лимиты
          </button>
        </div>
      </form>
    </div>

    <!-- Таблица -->
    <div class="bg-white rounded-2xl shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-4">Валюта</th>
            <th class="p-4">Текущий резерв</th>
            <th class="p-4">Минимум</th>
            <th class="p-4">Максимум</th>
            <th class="p-4">Последнее обновление</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reserves as $r): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4 font-medium"><?= htmlspecialchars($r['currency']) ?></td>
              <td class="p-4 font-mono text-green-600"><?= number_format($r['amount'], 8) ?></td>
              <td class="p-4 font-mono"><?= number_format($r['min'], 8) ?></td>
              <td class="p-4 font-mono"><?= number_format($r['max'], 8) ?></td>
              <td class="p-4 text-gray-500"><?= $r['updated_at'] ? date('d.m.Y H:i', strtotime($r['updated_at'])) : '—' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

  <script>
    // Автозаполнение min и max
    const limitsData = <?= json_encode($js_limits) ?>;

    document.getElementById('limit-currency').addEventListener('change', function() {
      const cur = this.value;
      if (cur && limitsData[cur]) {
        document.getElementById('min-input').value = limitsData[cur].min;
        document.getElementById('max-input').value = limitsData[cur].max;
      } else {
        document.getElementById('min-input').value = '';
        document.getElementById('max-input').value = '';
      }
    });
  </script>

</body>
</html>