<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

session_start();

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Данные из формы index.php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$give_currency = $_POST['give_currency'] ?? '';
$amount_give   = floatval($_POST['amount_give'] ?? 0);
$get_currency  = $_POST['get_currency'] ?? '';
$amount_get    = floatval($_POST['amount_get'] ?? 0);

if ($amount_give <= 0 || $amount_get <= 0) {
    $_SESSION['error'] = 'Некорректные данные';
    header('Location: index.php');
    exit;
}

// Получаем Telegram (он уже должен быть сохранён на главной)
$stmt = $pdo->prepare("SELECT telegram FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$telegram = $stmt->fetchColumn();

if (empty($telegram)) {
    header('Location: index.php'); // на случай, если кто-то обошёл модалку
    exit;
}

// Курс и другие детали
$rate = $rates[$give_currency][$get_currency] ?? 0;
if ($rate <= 0 && isset($rates[$get_currency][$give_currency]) && $rates[$get_currency][$give_currency] > 0) {
    $rate = 1 / $rates[$get_currency][$give_currency];
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Подтвердите заявку — <?= htmlspecialchars(SITE_NAME) ?></title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-900">

  <?php require_once 'header.php'; ?>

  <main class="container mx-auto px-4 py-12 max-w-4xl">
    <div class="bg-white rounded-3xl shadow-2xl p-10">

      <h1 class="text-4xl font-bold text-center text-gray-900 mb-10">Подтвердите заявку</h1>

      <div class="bg-gradient-to-br from-gray-50 to-gray-100 p-8 rounded-2xl border border-gray-200 mb-10">
        <div class="grid md:grid-cols-2 gap-10 mb-8">
          <div class="text-center md:text-left">
            <p class="text-gray-600 text-xl mb-2">Вы отдаёте</p>
            <p class="text-5xl font-extrabold text-gray-900">
              <?= number_format($amount_give, ($give_currency === 'BTC' ? 8 : 2), '.', ' ') ?>
              <?= htmlspecialchars(str_replace('_', ' ', $give_currency)) ?>
            </p>
          </div>
          <div class="text-center md:text-left">
            <p class="text-gray-600 text-xl mb-2">Вы получаете</p>
            <p class="text-5xl font-extrabold text-green-600">
              <?= number_format($amount_get, ($get_currency === 'BTC' ? 8 : 2), '.', ' ') ?>
              <?= htmlspecialchars(str_replace('_', ' ', $get_currency)) ?>
            </p>
          </div>
        </div>

        <div class="text-center border-t border-gray-200 pt-6">
          <p class="text-2xl font-semibold text-gray-800 mb-4">
            Курс: 1 <?= htmlspecialchars(str_replace('_', ' ', $give_currency)) ?> = 
            <?= number_format($rate, 4, '.', ' ') ?> <?= htmlspecialchars(str_replace('_', ' ', $get_currency)) ?>
          </p>
          <p class="text-xl text-gray-700 mb-3">
            Telegram для связи: <strong><?= htmlspecialchars($telegram) ?></strong>
          </p>
          <p class="text-lg text-gray-600">
            После подтверждения администратор свяжется с вами в Telegram в течение 5–30 минут
          </p>
        </div>
      </div>

      <div class="bg-yellow-50 border border-yellow-300 rounded-2xl p-6 mb-10">
        <h3 class="text-xl font-bold text-yellow-800 mb-4">Важно перед подтверждением</h3>
        <ul class="list-disc pl-6 text-lg text-yellow-900 space-y-2">
          <li>Убедитесь, что указанные суммы верны — после создания изменить их нельзя</li>
          <li>Оплата производится только после получения реквизитов от администратора в Telegram</li>
          <li>Срок оплаты — 30 минут после получения инструкций</li>
        </ul>
      </div>

      <form method="POST" action="confirm-order.php" class="text-center">
        <input type="hidden" name="give_currency" value="<?= htmlspecialchars($give_currency) ?>">
        <input type="hidden" name="amount_give" value="<?= $amount_give ?>">
        <input type="hidden" name="get_currency" value="<?= htmlspecialchars($get_currency) ?>">
        <input type="hidden" name="amount_get" value="<?= $amount_get ?>">
        <input type="hidden" name="telegram" value="<?= htmlspecialchars($telegram) ?>">

        <button type="submit" class="inline-block bg-gradient-to-r from-green-500 to-teal-600 text-white font-bold text-2xl py-6 px-20 rounded-3xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300">
          Подтвердить и создать заявку
        </button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-8">
        Нажимая кнопку, вы соглашаетесь с <a href="#" class="text-blue-600 hover:underline">правилами обмена</a> и <a href="#" class="text-blue-600 hover:underline">политикой AML/KYC</a>
      </p>

    </div>
  </main>

</body>
</html>