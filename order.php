<?php
require 'config.php';
session_start();

$order = $_SESSION['order'] ?? null;
if (!$order) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Заявка <?= $order['id'] ?> | <?= SITE_NAME ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <div class="container mx-auto px-4 py-10 max-w-3xl">
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <h1 class="text-3xl font-bold text-green-600 mb-6">Заявка создана!</h1>

      <div class="space-y-4 text-lg">
        <p><strong>Номер заявки:</strong> <?= $order['id'] ?></p>
        <p><strong>Отдаёте:</strong> <?= number_format($order['amount_give'], 2) ?> <?= $order['give_cur'] ?></p>
        <p><strong>Получаете:</strong> <?= number_format($order['amount_get'], 2) ?> <?= $order['get_cur'] ?></p>
        <p><strong>Курс:</strong> 1 <?= $order['give_cur'] ?> = <?= number_format($order['rate'], 4) ?> <?= $order['get_cur'] ?></p>
      </div>

      <div class="mt-10 p-6 bg-yellow-50 border border-yellow-300 rounded-xl">
        <h2 class="text-xl font-bold mb-4">Реквизиты для оплаты</h2>
        <p class="text-lg">
          Переведите <strong><?= number_format($order['amount_give'], 2) ?> <?= $order['give_cur'] ?></strong><br>
          на адрес: <code class="bg-gray-200 px-2 py-1 rounded">Txxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx</code><br>
          (TRC20 USDT)
        </p>
        <p class="mt-4 text-red-600 font-medium">
          Важно: после оплаты нажмите кнопку «Я оплатил» в течение 30 минут
        </p>
      </div>

      <div class="mt-10 text-center">
        <a href="index.php" class="inline-block bg-blue-600 text-white px-10 py-4 rounded-xl hover:bg-blue-700">
          Вернуться на главную
        </a>
      </div>
    </div>
  </div>

</body>
</html>