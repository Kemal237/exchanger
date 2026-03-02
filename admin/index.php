<?php
// admin/index.php — Главная админ-панели (статистика + все курсы)

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Выбор периода (по умолчанию — сегодня)
$period = $_GET['period'] ?? 'today';

$start_date = date('Y-m-d 00:00:00');
$end_date = date('Y-m-d H:i:s');

switch ($period) {
    case 'today':
        $start_date = date('Y-m-d 00:00:00');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_date   = date('Y-m-d 23:59:59', strtotime('-1 day'));
        break;
    case '7days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-7 days'));
        break;
    case '30days':
        $start_date = date('Y-m-d 00:00:00', strtotime('-30 days'));
        break;
    case 'all':
        $start_date = '2000-01-01 00:00:00'; // весь период
        break;
}

// Статистика пользователей за период
$stmt_users_period = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= ? AND created_at <= ?");
$stmt_users_period->execute([$start_date, $end_date]);
$new_users_period = $stmt_users_period->fetchColumn();

// Общая статистика пользователей (за всё время)
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$admins = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();

// Статистика заявок за выбранный период
$stmt_orders_period = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= ? AND created_at <= ?");
$stmt_orders_period->execute([$start_date, $end_date]);
$total_orders_period = $stmt_orders_period->fetchColumn();

$stmt_processed_period = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'processed' AND created_at >= ? AND created_at <= ?");
$stmt_processed_period->execute([$start_date, $end_date]);
$processed_period = $stmt_processed_period->fetchColumn();

$stmt_rejected_period = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status = 'rejected' AND created_at >= ? AND created_at <= ?");
$stmt_rejected_period->execute([$start_date, $end_date]);
$rejected_period = $stmt_rejected_period->fetchColumn();

// Текущие курсы без наценки (все пары)
$real_rates = getRealRates();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Админ-панель — <?= htmlspecialchars(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-gray-900 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold">Админ-панель</h1>
      <nav class="space-x-6">
        <a href="index.php" class="text-yellow-300 font-bold hover:underline">Главная</a>
        <a href="orders.php" class="hover:underline">Заявки</a>
        <a href="users.php" class="hover:underline">Пользователи</a>
        <a href="logout.php" class="hover:underline">Выйти</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10">

    <!-- Выбор периода -->
    <section class="mb-8">
      <h2 class="text-2xl font-bold mb-4">Статистика за период</h2>
      <div class="flex flex-wrap gap-3">
        <a href="?period=today" class="px-4 py-2 rounded-lg <?= $period === 'today' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Сегодня</a>
        <a href="?period=yesterday" class="px-4 py-2 rounded-lg <?= $period === 'yesterday' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Вчера</a>
        <a href="?period=7days" class="px-4 py-2 rounded-lg <?= $period === '7days' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">7 дней</a>
        <a href="?period=30days" class="px-4 py-2 rounded-lg <?= $period === '30days' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">30 дней</a>
        <a href="?period=all" class="px-4 py-2 rounded-lg <?= $period === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">Весь период</a>
      </div>
    </section>

    <!-- Статистика пользователей -->
    <section class="mb-10">
      <h2 class="text-2xl font-bold mb-6">Пользователи</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-blue-600">За выбранный период</h3>
          <p class="text-4xl font-bold"><?= $new_users_period ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-gray-600">Всего зарегистрировано</h3>
          <p class="text-4xl font-bold"><?= $total_users ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-purple-600">Администраторов</h3>
          <p class="text-4xl font-bold"><?= $admins ?></p>
        </div>
      </div>
    </section>

    <!-- Статистика заявок (теперь полностью за выбранный период) -->
    <section class="mb-10">
      <h2 class="text-2xl font-bold mb-6">Заявки (за выбранный период)</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-gray-600">Всего</h3>
          <p class="text-4xl font-bold"><?= $total_orders_period ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-green-600">Обработано</h3>
          <p class="text-4xl font-bold"><?= $processed_period ?></p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow">
          <h3 class="text-lg font-semibold text-red-600">Отклонено</h3>
          <p class="text-4xl font-bold"><?= $rejected_period ?></p>
        </div>
      </div>
    </section>

    <!-- Все курсы без наценки -->
    <section>
      <h2 class="text-2xl font-bold mb-6">Текущие рыночные курсы (без наценки)</h2>
      <div class="bg-white rounded-xl shadow overflow-x-auto">
        <table class="w-full text-left min-w-max">
          <thead class="bg-gray-100">
            <tr>
              <th class="p-4">Отдаёте</th>
              <th class="p-4">Получаете</th>
              <th class="p-4">Курс (реальный)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rates as $from => $to_list): ?>
              <?php foreach ($to_list as $to => $rate_with_markup): ?>
                <?php
                // Обратный расчёт реального курса (без наценки)
                $real_rate = $rate_with_markup;
                if ($markup_sell > 0) {
                    $real_rate = $rate_with_markup / $markup_sell;
                }
                ?>
                <tr class="border-t hover:bg-gray-50">
                  <td class="p-4 font-medium"><?= htmlspecialchars(str_replace('_', ' ', $from)) ?></td>
                  <td class="p-4 font-medium"><?= htmlspecialchars(str_replace('_', ' ', $to)) ?></td>
                  <td class="p-4"><?= number_format($real_rate, ($to === 'BTC' ? 8 : 4)) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>

  </main>

</body>
</html>