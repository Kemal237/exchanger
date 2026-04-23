<?php
require_once 'config.php';
require_once 'auth.php';

$page_title = 'Правила обмена — ' . SITE_NAME;
$current_page = 'rules.php';
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

  <!-- Hero -->
  <section class="mb-8 sm:mb-10 fade-in">
    <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs text-txt-muted mb-3 sm:mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">Правила обмена</span>
    </div>
    <div class="flex items-center gap-3 mb-3">
      <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
        <i data-lucide="scroll-text" class="w-5 h-5 sm:w-6 sm:h-6 text-vi"></i>
      </div>
      <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight">
        Правила <span class="shimmer-text">обмена</span>
      </h1>
    </div>
    <p class="text-xs sm:text-sm text-txt-muted">Пожалуйста, внимательно ознакомьтесь с правилами перед совершением обмена</p>

    <!-- Quick nav -->
    <div class="mt-5 flex flex-wrap gap-2">
      <?php $sections = [
        ['#general',    'Общие положения'],
        ['#procedure',  'Порядок обмена'],
        ['#rates',      'Курсы и лимиты'],
        ['#liability',  'Ответственность'],
        ['#limits',     'Ограничения'],
        ['#refunds',    'Возвраты'],
        ['#disputes',   'Споры'],
        ['#privacy',    'Конфиденциальность'],
        ['#changes',    'Изменения правил'],
      ]; foreach ($sections as $i => [$href, $label]): ?>
        <a href="<?= $href ?>" class="inline-flex items-center gap-1.5 px-3 h-7 rounded-full text-[11px] sm:text-xs text-txt-secondary border border-line bg-bg-card hover:border-vi/40 hover:text-vi transition">
          <span class="text-vi font-semibold"><?= $i + 1 ?></span><?= $label ?>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <article class="space-y-4 sm:space-y-5">

    <!-- 1. Общие положения -->
    <div id="general" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="1">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="book-open" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">1. Общие положения</h2>
      </div>
      <div class="space-y-3 text-sm text-txt-secondary leading-relaxed">
        <p>Сервис <strong class="text-txt-primary"><?= htmlspecialchars(SITE_NAME) ?></strong> предоставляет услуги обмена криптовалют (USDT TRC20, BTC) и фиатных средств (рубли, переводы по СБП и на банковские карты). Настоящие Правила регулируют отношения между Сервисом и Пользователем.</p>
        <p>Используя сервис, пользователь подтверждает, что:</p>
        <ul class="space-y-2 mt-2">
          <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Ознакомлен с настоящими Правилами и согласен с ними в полном объёме</span></li>
          <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Достиг 18-летнего возраста и является дееспособным лицом</span></li>
          <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Обмениваемые средства получены законным путём и не связаны с незаконной деятельностью</span></li>
          <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Действует от своего имени, а не в интересах третьих лиц (если иное не согласовано с сервисом)</span></li>
        </ul>
      </div>
    </div>

    <!-- 2. Порядок обмена -->
    <div id="procedure" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="2">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="list-ordered" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">2. Порядок совершения обмена</h2>
      </div>
      <div class="grid sm:grid-cols-2 gap-3 mb-4">
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <div class="flex items-center gap-2 mb-2">
            <span class="w-6 h-6 rounded-full bg-cy-soft border border-cy-border text-cy text-xs font-bold flex items-center justify-center flex-shrink-0">1</span>
            <span class="font-semibold text-sm">Создание заявки</span>
          </div>
          <p class="text-xs text-txt-muted leading-relaxed">Укажите направление обмена и сумму. Авторизуйтесь, если ещё не зарегистрированы. Подтвердите заявку.</p>
        </div>
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <div class="flex items-center gap-2 mb-2">
            <span class="w-6 h-6 rounded-full bg-cy-soft border border-cy-border text-cy text-xs font-bold flex items-center justify-center flex-shrink-0">2</span>
            <span class="font-semibold text-sm">Получение реквизитов</span>
          </div>
          <p class="text-xs text-txt-muted leading-relaxed">Оператор свяжется с вами в Telegram и передаст реквизиты для оплаты. Время ответа — до 30 минут.</p>
        </div>
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <div class="flex items-center gap-2 mb-2">
            <span class="w-6 h-6 rounded-full bg-vi-soft border border-vi/30 text-vi text-xs font-bold flex items-center justify-center flex-shrink-0">3</span>
            <span class="font-semibold text-sm">Отправка средств</span>
          </div>
          <p class="text-xs text-txt-muted leading-relaxed">Переведите точную сумму на указанные реквизиты в течение 30 минут после получения инструкций.</p>
        </div>
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <div class="flex items-center gap-2 mb-2">
            <span class="w-6 h-6 rounded-full bg-emr/10 border border-emr/30 text-emr text-xs font-bold flex items-center justify-center flex-shrink-0">4</span>
            <span class="font-semibold text-sm">Получение обмена</span>
          </div>
          <p class="text-xs text-txt-muted leading-relaxed">После подтверждения поступления средств оператор выполняет обмен. Срок выплаты — до 30 минут.</p>
        </div>
      </div>
      <div class="rounded-xl p-4 bg-warn/5 border border-warn/25">
        <div class="flex items-start gap-2">
          <i data-lucide="alert-triangle" class="w-4 h-4 text-warn mt-0.5 flex-shrink-0"></i>
          <p class="text-xs text-txt-secondary leading-relaxed"><strong class="text-warn">Важно:</strong> заявки обрабатываются в ручном режиме операторами. Обмен не происходит автоматически сразу после подачи заявки.</p>
        </div>
      </div>
    </div>

    <!-- 3. Курсы и лимиты -->
    <div id="rates" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="3">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="trending-up" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">3. Курсы и лимиты</h2>
      </div>
      <div class="space-y-3 text-sm text-txt-secondary leading-relaxed">
        <p>Курсы обмена формируются на основе данных рыночного агрегатора CoinGecko с учётом спреда сервиса и обновляются каждые 15 секунд.</p>
        <ul class="space-y-2">
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Фиксация курса:</strong> курс фиксируется в момент создания заявки и действует 30 минут — до истечения срока оплаты.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Пересчёт курса:</strong> если оплата не поступила в течение 30 минут, заявка аннулируется. Новая заявка создаётся по актуальному курсу.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Лимиты:</strong> минимальные и максимальные суммы для каждой валюты указаны на странице <a href="rates.php" class="text-cy hover:underline">Курсы и резервы</a>. Заявки вне лимитов не принимаются.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Резервы:</strong> обмен возможен только при наличии достаточного резерва по выбранной валюте. Актуальные резервы отображаются в режиме реального времени.</span></li>
        </ul>
      </div>
    </div>

    <!-- 4. Ответственность сторон -->
    <div id="liability" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="4">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="scale" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">4. Ответственность сторон</h2>
      </div>
      <div class="space-y-4 text-sm text-txt-secondary leading-relaxed">
        <div>
          <p class="font-semibold text-txt-primary mb-2">Сервис не несёт ответственности за:</p>
          <ul class="space-y-1.5">
            <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Задержки, вызванные загруженностью блокчейна или банковской инфраструктуры</span></li>
            <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Ошибки пользователя при указании реквизитов (адрес кошелька, номер карты)</span></li>
            <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Убытки, возникшие вследствие курсовых колебаний после создания заявки</span></li>
            <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Действия третьих лиц, получивших доступ к аккаунту пользователя</span></li>
            <li class="flex items-start gap-2"><i data-lucide="x" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Временную недоступность сервиса в связи с техническими работами</span></li>
          </ul>
        </div>
        <div>
          <p class="font-semibold text-txt-primary mb-2">Пользователь несёт ответственность за:</p>
          <ul class="space-y-1.5">
            <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Корректность введённых реквизитов и данных аккаунта</span></li>
            <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Законность происхождения обмениваемых средств</span></li>
            <li class="flex items-start gap-2"><i data-lucide="check" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span>Сохранность учётных данных своего аккаунта</span></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 5. Ограничения и запреты -->
    <div id="limits" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="5">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-danger/10 border border-danger/25 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="ban" class="w-4 h-4 text-danger"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">5. Ограничения и запреты</h2>
      </div>
      <p class="text-sm text-txt-secondary leading-relaxed mb-3">Сервис вправе отказать в обслуживании без объяснения причин. Использование сервиса <strong class="text-danger">запрещено</strong> в следующих случаях:</p>
      <ul class="space-y-2 text-sm text-txt-secondary">
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Лицам, не достигшим 18 лет</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>Гражданам и резидентам юрисдикций, находящихся под международными санкциями</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>При попытке легализации (отмывания) денежных средств, полученных преступным путём</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>При финансировании террористической деятельности или организаций, признанных террористическими</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>При использовании средств, связанных с мошенничеством, вымогательством, скамом</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>При попытке обойти лимиты путём дробления операций (структурирование)</span></li>
        <li class="flex items-start gap-2"><i data-lucide="x-circle" class="w-4 h-4 text-danger mt-0.5 flex-shrink-0"></i><span>При предоставлении заведомо ложных сведений при регистрации или верификации</span></li>
      </ul>
    </div>

    <!-- 6. Возвраты и отмены -->
    <div id="refunds" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="1">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="undo-2" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">6. Возвраты и отмены</h2>
      </div>
      <div class="space-y-3">
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <p class="text-sm font-semibold text-txt-primary mb-1.5 flex items-center gap-2">
            <i data-lucide="check-circle-2" class="w-4 h-4 text-emr flex-shrink-0"></i>
            Отмена до отправки средств
          </p>
          <p class="text-xs text-txt-muted leading-relaxed">Пользователь может отменить заявку самостоятельно в личном кабинете до момента перевода оплаты. Никаких удержаний не производится.</p>
        </div>
        <div class="rounded-xl p-4 bg-bg-soft border border-line">
          <p class="text-sm font-semibold text-txt-primary mb-1.5 flex items-center gap-2">
            <i data-lucide="alert-triangle" class="w-4 h-4 text-warn flex-shrink-0"></i>
            Возврат после отправки средств
          </p>
          <p class="text-xs text-txt-muted leading-relaxed">Если оплата поступила, но обмен не был выполнен по вине сервиса — средства возвращаются в полном объёме. Если обмен не выполнен по причине несоответствия суммы или AML-проверки — возврат производится за вычетом сетевых комиссий.</p>
        </div>
        <div class="rounded-xl p-4 bg-danger/5 border border-danger/25">
          <p class="text-sm font-semibold text-danger mb-1.5 flex items-center gap-2">
            <i data-lucide="x-circle" class="w-4 h-4 flex-shrink-0"></i>
            Невозможность возврата
          </p>
          <p class="text-xs text-txt-muted leading-relaxed">Возврат невозможен, если пользователь указал ошибочный адрес кошелька или реквизиты, а также если средства уже выплачены и подтверждены в блокчейне или банке.</p>
        </div>
        <p class="text-xs text-txt-muted px-1">Для инициации возврата свяжитесь с оператором через Telegram в течение 24 часов с момента создания заявки.</p>
      </div>
    </div>

    <!-- 7. Разрешение споров -->
    <div id="disputes" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="2">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="message-circle" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">7. Разрешение споров</h2>
      </div>
      <div class="space-y-3 text-sm text-txt-secondary leading-relaxed">
        <p>В случае возникновения разногласий пользователь обязан в первую очередь обратиться в службу поддержки сервиса до направления жалоб в сторонние организации.</p>
        <ul class="space-y-2">
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Срок подачи претензии:</strong> не позднее 24 часов с момента совершения спорной операции.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Форма обращения:</strong> через Telegram-оператора или на электронную почту <a href="mailto:<?= htmlspecialchars(ADMIN_EMAIL) ?>" class="text-cy hover:underline"><?= htmlspecialchars(ADMIN_EMAIL) ?></a>.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Срок рассмотрения:</strong> сервис рассматривает претензии в течение 3 рабочих дней с момента получения.</span></li>
          <li class="flex items-start gap-2"><i data-lucide="arrow-right" class="w-4 h-4 text-vi mt-0.5 flex-shrink-0"></i><span><strong class="text-txt-primary">Доказательная база:</strong> пользователь обязан предоставить скриншоты, хеши транзакций и иные доказательства, подтверждающие его позицию.</span></li>
        </ul>
      </div>
    </div>

    <!-- 8. Конфиденциальность -->
    <div id="privacy" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="3">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="lock" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">8. Конфиденциальность данных</h2>
      </div>
      <div class="space-y-3 text-sm text-txt-secondary leading-relaxed">
        <p>Сервис собирает и обрабатывает персональные данные (имя, email, Telegram, история операций) исключительно в целях предоставления услуг и соблюдения требований законодательства.</p>
        <ul class="space-y-2">
          <li class="flex items-start gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Данные пользователей не передаются третьим лицам в коммерческих целях</span></li>
          <li class="flex items-start gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Информация может быть раскрыта уполномоченным государственным органам по законному запросу</span></li>
          <li class="flex items-start gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>Хранение паролей осуществляется в хешированном виде — сервис не имеет доступа к паролям в открытом виде</span></li>
          <li class="flex items-start gap-2"><i data-lucide="shield-check" class="w-4 h-4 text-cy mt-0.5 flex-shrink-0"></i><span>История обменов хранится в целях AML-комплаенса и технической поддержки</span></li>
        </ul>
      </div>
    </div>

    <!-- 9. Изменение правил -->
    <div id="changes" class="gborder spot rounded-2xl bg-bg-card p-5 sm:p-6 reveal" data-d="4">
      <div class="flex items-start gap-3 mb-4">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i data-lucide="file-edit" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-lg sm:text-xl font-bold">9. Изменение правил</h2>
      </div>
      <div class="space-y-2 text-sm text-txt-secondary leading-relaxed">
        <p>Сервис оставляет за собой право вносить изменения в настоящие Правила в одностороннем порядке без предварительного уведомления пользователей.</p>
        <p>Продолжение использования сервиса после внесения изменений означает согласие пользователя с обновлёнными Правилами. Рекомендуем периодически проверять актуальность данной страницы.</p>
        <p>Дата последнего обновления: <span class="text-txt-primary font-medium"><?= date('d.m.Y') ?></span></p>
      </div>
    </div>

    <!-- Contact -->
    <div class="gborder rounded-2xl p-5 sm:p-6 reveal" data-d="5" style="background: linear-gradient(135deg, rgba(167,139,250,0.08), rgba(34,211,238,0.06));">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
          <i data-lucide="mail" class="w-4 h-4 text-vi"></i>
        </div>
        <div class="min-w-0">
          <h3 class="font-semibold mb-1">Есть вопросы по правилам?</h3>
          <p class="text-sm text-txt-secondary">
            Свяжитесь с нами: <a href="mailto:<?= htmlspecialchars(ADMIN_EMAIL) ?>" class="text-cy hover:underline"><?= htmlspecialchars(ADMIN_EMAIL) ?></a>
          </p>
        </div>
      </div>
    </div>

  </article>

  <!-- CTA -->
  <div class="mt-8 sm:mt-10 text-center reveal" data-d="5">
    <a href="index.php" class="btn-cy inline-flex items-center gap-2 px-6 h-12 rounded-xl text-sm font-semibold">
      <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
      Перейти к обмену
    </a>
  </div>

</main>

<?php require_once 'footer.php'; ?>

</body>
</html>
