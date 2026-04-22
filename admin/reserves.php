<?php
// admin/reserves.php — Управление резервами и лимитами

if (session_status() === PHP_SESSION_NONE) session_start();

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
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Резерв по валюте $currency обновлён"];
    }
    header('Location: reserves.php');
    exit;
}

// 2. Обновление лимитов
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
        $_SESSION['toast'] = ['type' => 'success', 'message' => "Лимиты для $currency сохранены"];
    }
    header('Location: reserves.php');
    exit;
}

$stmt = $pdo->query("SELECT currency, amount, min, max, updated_at FROM reserves ORDER BY currency");
$reservesList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$js_limits = [];
foreach ($reservesList as $r) {
    $js_limits[$r['currency']] = ['min' => $r['min'], 'max' => $r['max']];
}

$currencyMeta = [
    'USDT_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981'],
    'USDT_BEP20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981'],
    'BTC'        => ['icon' => 'bitcoin',            'color' => '#F7931A'],
    'RUB'        => ['icon' => 'banknote',          'color' => '#22D3EE'],
    'RUB_SBER'   => ['icon' => 'banknote',          'color' => '#22D3EE'],
    'RUB_TINK'   => ['icon' => 'banknote',          'color' => '#22D3EE'],
];

$page_title = 'Резервы — Админ-панель';
$admin_page = 'reserves.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once __DIR__ . '/../theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php if (isset($_SESSION['toast'])): ?>
  <div id="toast" class="toast-w <?= htmlspecialchars($_SESSION['toast']['type']) ?>">
    <?= htmlspecialchars($_SESSION['toast']['message']) ?>
  </div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <section class="mb-6 sm:mb-8 fade-in">
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-1">Управление резервами</h1>
    <p class="text-xs sm:text-sm text-txt-muted">Пополнение, списание и установка лимитов обмена</p>
  </section>

  <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 mb-6 sm:mb-8">

    <!-- Изменить резерв -->
    <div class="gborder spot rounded-2xl bg-bg-card p-4 sm:p-6 reveal" data-d="1">
      <div class="flex items-center gap-2 mb-4 sm:mb-5">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
          <i data-lucide="wallet" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-base sm:text-lg font-bold">Изменить резерв</h2>
      </div>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Валюта</label>
          <select name="currency" required class="input-d w-full h-10 px-3 rounded-lg text-sm">
            <option value="">Выберите валюту</option>
            <option value="USDT_TRC20">USDT TRC20</option>
            <option value="RUB">RUB</option>
            <option value="BTC">BTC</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Сумма</label>
            <input type="number" name="amount" step="0.00000001" value="0" class="input-d w-full h-10 px-3 rounded-lg text-sm font-mono">
          </div>
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Действие</label>
            <select name="action" class="input-d w-full h-10 px-3 rounded-lg text-sm">
              <option value="add">Добавить</option>
              <option value="subtract">Вычесть</option>
            </select>
          </div>
        </div>
        <button type="submit" name="update_reserve" class="btn-cy w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
          <i data-lucide="check" class="w-4 h-4"></i>
          Применить
        </button>
      </form>
    </div>

    <!-- Лимиты -->
    <div class="gborder spot rounded-2xl bg-bg-card p-4 sm:p-6 reveal" data-d="2">
      <div class="flex items-center gap-2 mb-4 sm:mb-5">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
          <i data-lucide="sliders-horizontal" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-base sm:text-lg font-bold">Лимиты обмена</h2>
      </div>
      <form method="POST" id="limits-form" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Валюта</label>
          <select name="currency" id="limit-currency" required class="input-d w-full h-10 px-3 rounded-lg text-sm">
            <option value="">Выберите валюту</option>
            <option value="USDT_TRC20">USDT TRC20</option>
            <option value="RUB">RUB</option>
            <option value="BTC">BTC</option>
          </select>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Минимум</label>
            <input type="number" name="min" id="min-input" step="0.00000001" class="input-d w-full h-10 px-3 rounded-lg text-sm font-mono">
          </div>
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Максимум</label>
            <input type="number" name="max" id="max-input" step="0.00000001" class="input-d w-full h-10 px-3 rounded-lg text-sm font-mono">
          </div>
        </div>
        <button type="submit" name="update_limits" class="btn-vi w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
          <i data-lucide="save" class="w-4 h-4"></i>
          Сохранить лимиты
        </button>
      </form>
    </div>
  </div>

  <!-- Таблица резервов -->
  <section class="reveal" data-d="3">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
      <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-line">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-emr/10 border border-emr/30 flex items-center justify-center flex-shrink-0">
            <i data-lucide="database" class="w-4 h-4 text-emr"></i>
          </div>
          <h2 class="text-base sm:text-lg font-bold truncate">Текущие резервы</h2>
        </div>
        <span class="text-xs text-txt-muted flex-shrink-0 ml-2"><?= count($reservesList) ?> валют</span>
      </div>

      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
              <th class="px-6 py-3 font-medium">Валюта</th>
              <th class="px-4 py-3 font-medium text-right">Резерв</th>
              <th class="px-4 py-3 font-medium text-right">Минимум</th>
              <th class="px-4 py-3 font-medium text-right">Максимум</th>
              <th class="px-4 py-3 font-medium text-right">Обновлено</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($reservesList as $r):
              $meta = $currencyMeta[$r['currency']] ?? ['icon' => 'coins', 'color' => '#A1A1AA'];
            ?>
              <tr class="row-h transition">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?= $meta['color'] ?>1A; border: 1px solid <?= $meta['color'] ?>33;">
                      <i data-lucide="<?= $meta['icon'] ?>" class="w-3.5 h-3.5" style="color: <?= $meta['color'] ?>"></i>
                    </div>
                    <span class="font-medium"><?= htmlspecialchars(str_replace('_', ' ', $r['currency'])) ?></span>
                  </div>
                </td>
                <td class="px-4 py-4 font-mono text-right text-emr whitespace-nowrap"><?= number_format($r['amount'], 8, '.', ' ') ?></td>
                <td class="px-4 py-4 font-mono text-right text-txt-secondary whitespace-nowrap"><?= number_format($r['min'], 8, '.', ' ') ?></td>
                <td class="px-4 py-4 font-mono text-right text-txt-secondary whitespace-nowrap"><?= number_format($r['max'], 8, '.', ' ') ?></td>
                <td class="px-4 py-4 text-right text-xs text-txt-muted whitespace-nowrap">
                  <?= $r['updated_at'] ? date('d.m.Y H:i', strtotime($r['updated_at'])) : '—' ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="md:hidden divide-y divide-line">
        <?php foreach ($reservesList as $r):
          $meta = $currencyMeta[$r['currency']] ?? ['icon' => 'coins', 'color' => '#A1A1AA'];
        ?>
          <div class="p-4">
            <div class="flex items-center gap-2.5 mb-3">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?= $meta['color'] ?>1A; border: 1px solid <?= $meta['color'] ?>33;">
                <i data-lucide="<?= $meta['icon'] ?>" class="w-4 h-4" style="color: <?= $meta['color'] ?>"></i>
              </div>
              <span class="font-medium text-sm"><?= htmlspecialchars(str_replace('_', ' ', $r['currency'])) ?></span>
              <span class="ml-auto text-[11px] text-txt-muted"><?= $r['updated_at'] ? date('d.m.Y H:i', strtotime($r['updated_at'])) : '—' ?></span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-xs">
              <div>
                <div class="text-[10px] text-txt-muted uppercase tracking-wider mb-0.5">Резерв</div>
                <div class="font-mono text-emr break-all"><?= number_format($r['amount'], 4, '.', ' ') ?></div>
              </div>
              <div>
                <div class="text-[10px] text-txt-muted uppercase tracking-wider mb-0.5">Мин</div>
                <div class="font-mono text-txt-secondary break-all"><?= number_format($r['min'], 4, '.', ' ') ?></div>
              </div>
              <div>
                <div class="text-[10px] text-txt-muted uppercase tracking-wider mb-0.5">Макс</div>
                <div class="font-mono text-txt-secondary break-all"><?= number_format($r['max'], 4, '.', ' ') ?></div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
  const limitsData = <?= json_encode($js_limits) ?>;
  document.getElementById('limit-currency')?.addEventListener('change', function() {
    const cur = this.value;
    const minEl = document.getElementById('min-input');
    const maxEl = document.getElementById('max-input');
    if (cur && limitsData[cur]) {
      minEl.value = limitsData[cur].min;
      maxEl.value = limitsData[cur].max;
    } else {
      minEl.value = '';
      maxEl.value = '';
    }
  });
</script>

</body>
</html>