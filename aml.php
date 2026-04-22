<?php
require_once 'config.php';
require_once 'auth.php';

$page_title = 'AML политика — ' . SITE_NAME;
$current_page = 'aml.php';
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

<main class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <section class="mb-8 sm:mb-10 fade-in">
    <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs text-txt-muted mb-3 sm:mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">AML политика</span>
    </div>
    <div class="flex items-center gap-3 mb-3">
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
        <i data-lucide="shield-check" class="w-5 h-5 sm:w-6 sm:h-6 text-cy"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight">
        <span class="shimmer-text">AML</span> политика
      </h1>
    </div>
    <p class="text-txt-muted">Anti-Money Laundering — меры по предотвращению отмывания денег</p>
  </section>

  <article class="space-y-5">

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="1">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="info" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-xl font-bold">1. Общие положения</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed">
        <?= htmlspecialchars(SITE_NAME) ?> строго следует международным стандартам AML (Anti-Money Laundering) и CFT (Counter-Terrorism Financing). Наша политика направлена на предотвращение использования сервиса в целях отмывания денег, финансирования терроризма и иной противоправной деятельности.
      </p>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="2">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="search" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-xl font-bold">2. Проверка транзакций</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed mb-3">
        Мы проверяем каждую входящую криптотранзакцию с использованием специализированных сервисов анализа блокчейна. Оцениваются:
      </p>
      <ul class="space-y-2 text-sm text-txt-secondary">
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-emr mt-0.5 flex-shrink-0"></i>
          <span>Происхождение средств и связь с высокорисковыми адресами</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-emr mt-0.5 flex-shrink-0"></i>
          <span>Взаимодействие с миксерами и сервисами анонимизации</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-emr mt-0.5 flex-shrink-0"></i>
          <span>Связь с санкционными списками (OFAC, EU, UN)</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="check" class="w-4 h-4 text-emr mt-0.5 flex-shrink-0"></i>
          <span>Участие в схемах даркнета, вымогательства, скама</span>
        </li>
      </ul>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="3">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-warn/10 border border-warn/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="alert-triangle" class="w-4 h-4 text-warn"></i>
        </div>
        <h2 class="text-xl font-bold">3. Уровни риска</h2>
      </div>
      <div class="grid sm:grid-cols-3 gap-3 mt-4">
        <div class="rounded-lg p-4 bg-emr/5 border border-emr/30">
          <div class="flex items-center gap-2 mb-1.5">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emr"></i>
            <span class="font-semibold text-emr text-sm">0–25%</span>
          </div>
          <p class="text-xs text-txt-muted">Низкий риск — транзакция проходит автоматически</p>
        </div>
        <div class="rounded-lg p-4 bg-warn/5 border border-warn/30">
          <div class="flex items-center gap-2 mb-1.5">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-warn"></i>
            <span class="font-semibold text-warn text-sm">26–75%</span>
          </div>
          <p class="text-xs text-txt-muted">Средний риск — возможен запрос документов</p>
        </div>
        <div class="rounded-lg p-4 bg-danger/5 border border-danger/30">
          <div class="flex items-center gap-2 mb-1.5">
            <i data-lucide="x-circle" class="w-4 h-4 text-danger"></i>
            <span class="font-semibold text-danger text-sm">76–100%</span>
          </div>
          <p class="text-xs text-txt-muted">Высокий риск — заявка может быть отклонена</p>
        </div>
      </div>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="4">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="file-lock-2" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-xl font-bold">4. Действия при выявлении рисков</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed mb-3">При обнаружении подозрительной активности сервис оставляет за собой право:</p>
      <ul class="space-y-2 text-sm text-txt-secondary">
        <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Запросить прохождение процедуры KYC (верификация личности)</span></li>
        <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Приостановить обработку заявки до выяснения обстоятельств</span></li>
        <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Отказать в обмене с возвратом средств за вычетом комиссии</span></li>
        <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Передать информацию уполномоченным органам при наличии оснований</span></li>
      </ul>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="5">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="user-check" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-xl font-bold">5. Обязательства пользователя</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed">
        Используя сервис, пользователь подтверждает, что обмениваемые средства получены законным путём и не связаны с преступной деятельностью. Пользователь обязуется предоставлять достоверную информацию и документы по запросу службы безопасности.
      </p>
    </div>

    <div class="gborder rounded-2xl p-6 reveal" data-d="5" style="background: linear-gradient(135deg, rgba(34,211,238,0.08), rgba(167,139,250,0.06));">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
          <i data-lucide="mail" class="w-4 h-4 text-cy"></i>
        </div>
        <div>
          <h3 class="font-semibold mb-1">Связь с комплаенс-отделом</h3>
          <p class="text-sm text-txt-secondary">
            Вопросы по AML-политике направляйте на
            <a href="mailto:<?= htmlspecialchars(ADMIN_EMAIL) ?>" class="text-cy hover:underline"><?= htmlspecialchars(ADMIN_EMAIL) ?></a>
          </p>
        </div>
      </div>
    </div>

  </article>

</main>

<?php require_once 'footer.php'; ?>

</body>
</html>