<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

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

$stmt = $pdo->prepare("SELECT telegram FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$telegram = $stmt->fetchColumn();

if (empty($telegram)) {
    header('Location: index.php');
    exit;
}

// Нормализация сетевых ключей к базовым ключам таблицы курсов
$netToBase = [
    'USDT_TRC20' => 'USDT_TRC20', 'USDT_ERC20' => 'USDT_TRC20', 'USDT_BEP20' => 'USDT_TRC20',
    'USDC_TRC20' => 'USDC',       'USDC_ERC20' => 'USDC',
    'ETH'        => 'ETH',         'SOL'        => 'SOL',         'BTC'        => 'BTC',
    'RUB_SBP'   => 'RUB',        'RUB_CASH'   => 'RUB',         'RUB_CARD'   => 'RUB',
    'USD'        => 'USD',
];
$give_base = $netToBase[$give_currency] ?? $give_currency;
$get_base  = $netToBase[$get_currency]  ?? $get_currency;

$rate = $rates[$give_base][$get_base] ?? 0;
if ($rate <= 0 && isset($rates[$get_base][$give_base]) && $rates[$get_base][$give_base] > 0) {
    $rate = 1 / $rates[$get_base][$give_base];
}

// Маппинг: ключ → иконка/цвет/метка
$currencyIcons = [
    'USDT_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'label' => 'USDT', 'net' => 'TRC20'],
    'USDT_ERC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'label' => 'USDT', 'net' => 'ERC20'],
    'USDT_BEP20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'label' => 'USDT', 'net' => 'BEP20'],
    'USDC_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#2775CA', 'label' => 'USDC', 'net' => 'TRC20'],
    'USDC_ERC20' => ['icon' => 'circle-dollar-sign', 'color' => '#2775CA', 'label' => 'USDC', 'net' => 'ERC20'],
    'ETH'        => ['icon' => 'hexagon',            'color' => '#627EEA', 'label' => 'ETH',  'net' => 'ERC20'],
    'SOL'        => ['icon' => 'zap',                'color' => '#9945FF', 'label' => 'SOL',  'net' => 'SOL'],
    'BTC'        => ['icon' => 'bitcoin',            'color' => '#F7931A', 'label' => 'BTC',  'net' => 'BTC'],
    'RUB_CASH'  => ['icon' => 'banknote',           'color' => '#A78BFA', 'label' => 'RUB',  'net' => 'Наличные'],
    'USD'        => ['icon' => 'dollar-sign',        'color' => '#22D3EE', 'label' => 'USD',  'net' => 'Наличные'],
];
$giveIcon = $currencyIcons[$give_currency] ?? ['icon' => 'coins', 'color' => '#A1A1AA'];
$getIcon  = $currencyIcons[$get_currency]  ?? ['icon' => 'coins', 'color' => '#A1A1AA'];

$page_title = 'Подтвердите заявку — ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once 'theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php require_once 'header.php'; ?>

<main class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs text-txt-muted mb-3 sm:mb-4 fade-in">
    <a href="index.php" class="hover:text-cy transition">Главная</a>
    <i data-lucide="chevron-right" class="w-3 h-3"></i>
    <span class="text-txt-secondary">Подтверждение заявки</span>
  </div>

  <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight mb-6 sm:mb-8 fade-in">
    Подтвердите <span class="shimmer-text">заявку</span>
  </h1>

  <div class="gborder spot rounded-2xl bg-bg-card p-4 sm:p-8 shadow-card mb-5 sm:mb-6 reveal" data-d="1">

    <div class="grid grid-cols-[1fr,auto,1fr] items-center gap-3 sm:gap-6 mb-6 sm:mb-8">
      <!-- Give -->
      <div class="text-center min-w-0">
        <div class="flex items-center justify-center gap-1.5 sm:gap-2 mb-2 sm:mb-3 text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">
          <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-danger"></i>
          <span>Отдаёте</span>
        </div>
        <div class="inline-flex items-center justify-center w-11 h-11 sm:w-14 sm:h-14 rounded-2xl mb-2 sm:mb-3" style="background: <?= $giveIcon['color'] ?>1A; border: 1px solid <?= $giveIcon['color'] ?>33;">
          <i data-lucide="<?= $giveIcon['icon'] ?>" class="w-5 h-5 sm:w-7 sm:h-7" style="color: <?= $giveIcon['color'] ?>"></i>
        </div>
        <div class="text-lg sm:text-3xl md:text-4xl font-bold tracking-tight mb-1 break-all">
          <?php
          $give_dec = 2;
          if ($give_currency === 'BTC') $give_dec = 8;
          elseif ($give_currency === 'ETH') $give_dec = 6;
          elseif (in_array($give_currency, ['SOL'])) $give_dec = 4;
          echo number_format($amount_give, $give_dec, '.', ' ');
          ?>
        </div>
        <div class="text-[11px] sm:text-sm text-txt-secondary font-medium"><?= htmlspecialchars($giveIcon['label'] ?? $give_currency) ?></div>
        <?php if (!empty($giveIcon['net'])): ?>
        <div class="text-[10px] text-txt-muted mt-0.5"><?= htmlspecialchars($giveIcon['net']) ?></div>
        <?php endif; ?>
      </div>

      <!-- Arrow -->
      <div class="flex items-center justify-center">
        <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-cy-soft border border-cy-border flex items-center justify-center">
          <i data-lucide="arrow-right" class="w-4 h-4 sm:w-5 sm:h-5 text-cy"></i>
        </div>
      </div>

      <!-- Get -->
      <div class="text-center min-w-0">
        <div class="flex items-center justify-center gap-1.5 sm:gap-2 mb-2 sm:mb-3 text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">
          <i data-lucide="arrow-down-left" class="w-3.5 h-3.5 text-emr"></i>
          <span>Получаете</span>
        </div>
        <div class="inline-flex items-center justify-center w-11 h-11 sm:w-14 sm:h-14 rounded-2xl mb-2 sm:mb-3" style="background: <?= $getIcon['color'] ?>1A; border: 1px solid <?= $getIcon['color'] ?>33;">
          <i data-lucide="<?= $getIcon['icon'] ?>" class="w-5 h-5 sm:w-7 sm:h-7" style="color: <?= $getIcon['color'] ?>"></i>
        </div>
        <div class="text-lg sm:text-3xl md:text-4xl font-bold tracking-tight mb-1 text-emr break-all">
          <?php
          $get_dec = 2;
          if ($get_currency === 'BTC') $get_dec = 8;
          elseif ($get_currency === 'ETH') $get_dec = 6;
          elseif (in_array($get_currency, ['SOL'])) $get_dec = 4;
          echo number_format($amount_get, $get_dec, '.', ' ');
          ?>
        </div>
        <div class="text-[11px] sm:text-sm text-txt-secondary font-medium"><?= htmlspecialchars($getIcon['label'] ?? $get_currency) ?></div>
        <?php if (!empty($getIcon['net'])): ?>
        <div class="text-[10px] text-txt-muted mt-0.5"><?= htmlspecialchars($getIcon['net']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="border-t border-line pt-5 sm:pt-6 space-y-3 sm:space-y-4">
      <div class="flex items-center justify-between gap-3 text-xs sm:text-sm">
        <span class="text-txt-muted flex items-center gap-2 flex-shrink-0">
          <i data-lucide="trending-up" class="w-4 h-4"></i>
          <span class="hidden sm:inline">Фиксированный курс</span><span class="sm:hidden">Курс</span>
        </span>
        <span class="font-mono font-medium text-right truncate">
          <?php
          $giveLabel = ($currencyIcons[$give_currency]['label'] ?? '') . ' ' . ($currencyIcons[$give_currency]['net'] ?? '');
          $getLabel  = ($currencyIcons[$get_currency]['label']  ?? '') . ' ' . ($currencyIcons[$get_currency]['net']  ?? '');
          $rateDec = in_array($get_base, ['BTC']) ? 8 : (in_array($get_base, ['ETH']) ? 6 : (in_array($get_base, ['SOL']) ? 4 : 2));
          ?>
          1 <?= htmlspecialchars(trim($giveLabel)) ?>
          <span class="text-txt-muted mx-1">=</span>
          <?= number_format($rate, $rateDec, '.', ' ') ?>
          <?= htmlspecialchars(trim($getLabel)) ?>
        </span>
      </div>
      <div class="flex items-center justify-between gap-3 text-xs sm:text-sm">
        <span class="text-txt-muted flex items-center gap-2 flex-shrink-0">
          <i data-lucide="send" class="w-4 h-4"></i>
          Telegram
        </span>
        <span class="font-mono font-medium text-cy truncate"><?= htmlspecialchars($telegram) ?></span>
      </div>
      <div class="flex items-center justify-between gap-3 text-xs sm:text-sm">
        <span class="text-txt-muted flex items-center gap-2 flex-shrink-0">
          <i data-lucide="clock" class="w-4 h-4"></i>
          Время ответа
        </span>
        <span class="font-medium">5–30 минут</span>
      </div>
    </div>
  </div>

  <!-- Warning -->
  <div class="gborder rounded-2xl bg-bg-card p-4 sm:p-6 mb-5 sm:mb-6 reveal" data-d="2" style="--tw-border-opacity:0;">
    <div class="flex items-start gap-3">
      <div class="w-9 h-9 rounded-lg bg-warn/10 border border-warn/30 flex items-center justify-center flex-shrink-0">
        <i data-lucide="alert-triangle" class="w-5 h-5 text-warn"></i>
      </div>
      <div class="flex-1">
        <h3 class="font-semibold mb-3">Важно перед подтверждением</h3>
        <ul class="space-y-2 text-sm text-txt-secondary">
          <li class="flex items-start gap-2">
            <i data-lucide="check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i>
            <span>Убедитесь, что указанные суммы верны — после создания изменить их нельзя</span>
          </li>
          <li class="flex items-start gap-2">
            <i data-lucide="check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i>
            <span>Оплата производится только после получения реквизитов от администратора в Telegram</span>
          </li>
          <li class="flex items-start gap-2">
            <i data-lucide="check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i>
            <span>Срок оплаты — 30 минут после получения инструкций</span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Actions -->
  <form method="POST" action="confirm-order.php" class="reveal" data-d="3">
    <input type="hidden" name="give_currency" value="<?= htmlspecialchars($give_currency) ?>">
    <input type="hidden" name="amount_give" value="<?= $amount_give ?>">
    <input type="hidden" name="get_currency" value="<?= htmlspecialchars($get_currency) ?>">
    <input type="hidden" name="amount_get" value="<?= $amount_get ?>">
    <input type="hidden" name="telegram" value="<?= htmlspecialchars($telegram) ?>">

    <div class="flex flex-col gap-3">
      <button type="submit" class="btn-cy w-full h-14 sm:h-12 rounded-xl text-base sm:text-sm font-semibold flex items-center justify-center gap-2">
        <i data-lucide="check-circle-2" class="w-5 h-5 sm:w-4 sm:h-4"></i>
        Подтвердить и создать заявку
      </button>
      <a href="index.php" class="btn-ghost w-full h-12 sm:h-12 rounded-xl text-sm font-medium flex items-center justify-center gap-2">
        <i data-lucide="arrow-left" class="w-4 h-4"></i>
        Назад
      </a>
    </div>
  </form>

  <p class="text-center text-[11px] sm:text-xs text-txt-muted mt-4 sm:mt-6 leading-relaxed">
    Нажимая кнопку, вы соглашаетесь с
    <a href="aml.php" class="text-cy hover:underline">AML политикой</a> и
    <a href="kyc.php" class="text-cy hover:underline">KYC процедурой</a>
  </p>

</main>

<?php require_once 'footer.php'; ?>

</body>
</html>