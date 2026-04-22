<?php
require_once 'config.php';
require_once 'auth.php';

$page_title = 'KYC процедура — ' . SITE_NAME;
$current_page = 'kyc.php';
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

<main class="relative z-10 max-w-4xl mx-auto px-6 py-10">

  <section class="mb-10 fade-in">
    <div class="flex items-center gap-3 text-xs text-txt-muted mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">KYC процедура</span>
    </div>
    <div class="flex items-center gap-3 mb-3">
      <div class="w-12 h-12 rounded-xl bg-vi-soft border border-vi/30 flex items-center justify-center">
        <i data-lucide="user-check" class="w-6 h-6 text-vi"></i>
      </div>
      <h1 class="text-3xl md:text-4xl font-bold tracking-tight">
        <span class="shimmer-text">KYC</span> процедура
      </h1>
    </div>
    <p class="text-txt-muted">Know Your Customer — верификация личности клиента</p>
  </section>

  <article class="space-y-5">

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="1">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="info" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-xl font-bold">Когда требуется KYC</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed mb-3">
        В большинстве случаев обмен проходит без верификации. Однако процедура KYC может быть запрошена в следующих случаях:
      </p>
      <ul class="space-y-2 text-sm text-txt-secondary">
        <li class="flex items-start gap-2">
          <i data-lucide="circle" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i>
          <span>Транзакция получила высокую оценку AML-риска</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="circle" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i>
          <span>Сумма обмена превышает установленный порог</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="circle" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i>
          <span>Заявка вызывает подозрения у службы комплаенса</span>
        </li>
        <li class="flex items-start gap-2">
          <i data-lucide="circle" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i>
          <span>Поступил запрос от уполномоченных органов</span>
        </li>
      </ul>
    </div>

    <!-- Шаги процедуры -->
    <div class="gborder rounded-2xl bg-bg-card p-6 reveal" data-d="2">
      <h2 class="text-xl font-bold mb-6">Шаги верификации</h2>

      <div class="space-y-5">
        <div class="flex items-start gap-4">
          <div class="step-num flex-shrink-0" style="margin-bottom:0;">1</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1">Документ, удостоверяющий личность</h3>
            <p class="text-sm text-txt-secondary">
              Фото или скан паспорта (развороты с фото и пропиской), ID-карты или водительского удостоверения.
            </p>
          </div>
        </div>

        <div class="flex items-start gap-4">
          <div class="step-num flex-shrink-0" style="margin-bottom:0;">2</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1">Селфи с документом</h3>
            <p class="text-sm text-txt-secondary">
              Фотография, где вы держите документ рядом с лицом. Должны быть видны лицо, документ и лист бумаги с датой и названием сервиса.
            </p>
          </div>
        </div>

        <div class="flex items-start gap-4">
          <div class="step-num flex-shrink-0" style="margin-bottom:0;">3</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1">Подтверждение источника средств</h3>
            <p class="text-sm text-txt-secondary">
              Выписки с биржи, скриншоты депозитов, договор или иной документ, подтверждающий легальное происхождение средств.
            </p>
          </div>
        </div>

        <div class="flex items-start gap-4">
          <div class="step-num flex-shrink-0" style="margin-bottom:0;">4</div>
          <div class="flex-1">
            <h3 class="font-semibold mb-1">Рассмотрение заявки</h3>
            <p class="text-sm text-txt-secondary">
              Мы проверяем документы в течение 1–24 часов. После одобрения заявка обрабатывается в обычном порядке.
            </p>
          </div>
        </div>
      </div>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="3">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-emr/10 border border-emr/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="lock" class="w-4 h-4 text-emr"></i>
        </div>
        <h2 class="text-xl font-bold">Защита данных</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed">
        Все предоставленные документы хранятся в зашифрованном виде на защищённых серверах с ограниченным доступом.
        Данные используются исключительно в целях AML/KYC проверки и не передаются третьим лицам, за исключением случаев,
        предусмотренных законодательством. Срок хранения — 5 лет с момента последней транзакции.
      </p>
    </div>

    <div class="gborder spot rounded-2xl bg-bg-card p-6 reveal" data-d="4">
      <div class="flex items-start gap-3 mb-3">
        <div class="w-8 h-8 rounded-lg bg-warn/10 border border-warn/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="alert-triangle" class="w-4 h-4 text-warn"></i>
        </div>
        <h2 class="text-xl font-bold">Отказ от верификации</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed">
        Если пользователь отказывается проходить процедуру KYC, заявка отклоняется, а средства возвращаются
        на адрес отправителя за вычетом сетевой комиссии и 5% за ручную обработку.
      </p>
    </div>

    <div class="gborder rounded-2xl p-6 reveal" data-d="5" style="background: linear-gradient(135deg, rgba(167,139,250,0.08), rgba(34,211,238,0.06));">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
          <i data-lucide="send" class="w-4 h-4 text-vi"></i>
        </div>
        <div>
          <h3 class="font-semibold mb-1">Загрузка документов</h3>
          <p class="text-sm text-txt-secondary">
            Документы отправляются оператору в Telegram после индивидуального запроса.
            По вопросам KYC пишите на
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