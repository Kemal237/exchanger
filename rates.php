<?php
require_once 'config.php';
require_once 'auth.php';

$allowed = ['USDT_TRC20', 'RUB', 'BTC'];

$currencyMeta = [
    'USDT_TRC20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'name' => 'USDT TRC20', 'dec' => 2],
    'BTC'        => ['icon' => 'bitcoin',            'color' => '#F7931A', 'name' => 'BTC',         'dec' => 8],
    'RUB'        => ['icon' => 'banknote',          'color' => '#22D3EE', 'name' => 'RUB',         'dec' => 2],
    'RUB_SBER'   => ['icon' => 'banknote',          'color' => '#22D3EE', 'name' => 'RUB Сбер',    'dec' => 2],
    'RUB_TINK'   => ['icon' => 'banknote',          'color' => '#22D3EE', 'name' => 'RUB Тинь.',   'dec' => 2],
    'USDT_BEP20' => ['icon' => 'circle-dollar-sign', 'color' => '#10B981', 'name' => 'USDT BEP20', 'dec' => 2],
];

$page_title = 'Резервы и курсы — ' . SITE_NAME;
$current_page = 'rates.php';
?>
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
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

<main class="relative z-10 max-w-7xl mx-auto px-6 py-10">

  <section class="mb-10 fade-in">
    <div class="flex items-center gap-3 text-xs text-txt-muted mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">Курсы и резервы</span>
    </div>
    <h1 class="text-3xl md:text-4xl font-bold tracking-tight mb-2">
      Резервы и <span class="shimmer-text">актуальные курсы</span>
    </h1>
    <p class="text-txt-muted flex items-center gap-2">
      <span class="pdot"></span>
      Обновлено только что · источник CoinGecko
    </p>
  </section>

  <!-- Reserve cards -->
  <section class="grid md:grid-cols-3 gap-4 mb-10">
    <?php foreach ($allowed as $idx => $cur):
      $meta = $currencyMeta[$cur];
      $reserve = $reserves[$cur] ?? 0;
    ?>
      <div class="gborder spot rounded-xl bg-bg-card p-5 reveal" data-d="<?= $idx + 1 ?>">
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background: <?= $meta['color'] ?>1A; border: 1px solid <?= $meta['color'] ?>33;">
              <i data-lucide="<?= $meta['icon'] ?>" class="w-5 h-5" style="color: <?= $meta['color'] ?>"></i>
            </div>
            <div>
              <div class="font-semibold text-sm"><?= htmlspecialchars($meta['name']) ?></div>
              <div class="text-xs text-txt-muted">Резерв</div>
            </div>
          </div>
          <span class="st st-ok">
            <i data-lucide="check" class="w-3 h-3"></i>
            В наличии
          </span>
        </div>
        <div class="text-2xl font-bold tracking-tight mb-1">
          <?= number_format($reserve, $meta['dec'], '.', ' ') ?>
          <span class="text-sm text-txt-muted font-normal ml-1"><?= htmlspecialchars($meta['name']) ?></span>
        </div>
      </div>
    <?php endforeach; ?>
  </section>

  <!-- Rates table -->
  <section class="reveal" data-d="4">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
      <div class="flex items-center justify-between px-6 py-5 border-b border-line">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center">
            <i data-lucide="trending-up" class="w-4 h-4 text-cy"></i>
          </div>
          <h2 class="text-lg font-bold">Таблица курсов</h2>
        </div>
        <a href="rates.xml.php" target="_blank" class="text-xs text-cy hover:underline flex items-center gap-1.5">
          <i data-lucide="code-xml" class="w-3.5 h-3.5"></i>
          BestChange XML
        </a>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
              <th class="px-6 py-3 font-medium">Отдаёте</th>
              <th class="px-4 py-3 font-medium">Получаете</th>
              <th class="px-4 py-3 font-medium">Курс</th>
              <th class="px-4 py-3 font-medium text-right">Резерв</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php
            foreach ($rates as $from => $to_list) {
                if (!in_array($from, $allowed)) continue;
                foreach ($to_list as $to => $rate) {
                    if (!in_array($to, $allowed)) continue;
                    $fromMeta = $currencyMeta[$from] ?? ['icon' => 'coins', 'color' => '#A1A1AA', 'name' => $from, 'dec' => 2];
                    $toMeta   = $currencyMeta[$to]   ?? ['icon' => 'coins', 'color' => '#A1A1AA', 'name' => $to,   'dec' => 2];
                    $reserve = $reserves[$to] ?? 0;
            ?>
              <tr class="row-h transition">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?= $fromMeta['color'] ?>1A; border: 1px solid <?= $fromMeta['color'] ?>33;">
                      <i data-lucide="<?= $fromMeta['icon'] ?>" class="w-3.5 h-3.5" style="color: <?= $fromMeta['color'] ?>"></i>
                    </div>
                    <span class="font-medium"><?= htmlspecialchars($fromMeta['name']) ?></span>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0" style="background: <?= $toMeta['color'] ?>1A; border: 1px solid <?= $toMeta['color'] ?>33;">
                      <i data-lucide="<?= $toMeta['icon'] ?>" class="w-3.5 h-3.5" style="color: <?= $toMeta['color'] ?>"></i>
                    </div>
                    <span class="font-medium"><?= htmlspecialchars($toMeta['name']) ?></span>
                  </div>
                </td>
                <td class="px-4 py-4 font-mono text-cy whitespace-nowrap">
                  <?= number_format($rate, ($to === 'BTC' ? 8 : 4), '.', ' ') ?>
                </td>
                <td class="px-4 py-4 text-right font-medium text-emr whitespace-nowrap">
                  <?= number_format($reserve, $toMeta['dec'], '.', ' ') ?>
                </td>
              </tr>
            <?php
                }
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <div class="mt-10 text-center reveal" data-d="5">
    <a href="index.php" class="btn-cy inline-flex items-center gap-2 px-6 h-12 rounded-xl text-sm font-semibold">
      <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
      Перейти к обмену
    </a>
  </div>

</main>

<?php require_once 'footer.php'; ?>

</body>
</html>