<?php
if (!defined('SITE_NAME')) require_once __DIR__ . '/config.php';
?>
<footer class="mt-12 sm:mt-20 border-t border-line relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 sm:py-10 grid sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-8 text-sm">
    <div>
      <div class="flex items-center gap-2 mb-3">
        <div class="w-7 h-7 rounded-md bg-gradient-to-br from-cy to-vi flex items-center justify-center">
          <i data-lucide="arrow-left-right" class="w-3.5 h-3.5 text-bg-base"></i>
        </div>
        <span class="font-bold"><?= htmlspecialchars(SITE_NAME) ?></span>
      </div>
      <p class="text-txt-muted leading-relaxed">Безопасный обмен криптовалют с проверкой AML и поддержкой 24/7.</p>
    </div>
    <div>
      <div class="text-txt-primary font-semibold mb-3">Сервис</div>
      <ul class="space-y-2 text-txt-muted">
        <li><a href="index.php" class="hover:text-cy transition">Обмен</a></li>
        <li><a href="rates.php" class="hover:text-cy transition">Курсы и резервы</a></li>
        <li><a href="rates.xml.php" target="_blank" class="hover:text-cy transition">Курсы для BestChange</a></li>
      </ul>
    </div>
    <div>
      <div class="text-txt-primary font-semibold mb-3">Правила</div>
      <ul class="space-y-2 text-txt-muted">
        <li><a href="rules.php" class="hover:text-cy transition">Правила обмена</a></li>
        <li><a href="aml.php" class="hover:text-cy transition">AML политика</a></li>
        <li><a href="kyc.php" class="hover:text-cy transition">KYC процедура</a></li>
      </ul>
    </div>
    <div>
      <div class="text-txt-primary font-semibold mb-3">Контакты</div>
      <ul class="space-y-2 text-txt-muted">
        <li class="flex items-center gap-2">
          <i data-lucide="mail" class="w-4 h-4 text-cy"></i>
          <?= htmlspecialchars(ADMIN_EMAIL) ?>
        </li>
        <li>
          <a href="support.php" class="flex items-center gap-2 hover:text-cy transition">
            <i data-lucide="message-circle" class="w-4 h-4 text-cy"></i>
            Техподдержка
          </a>
        </li>
      </ul>
    </div>
  </div>
  <div class="border-t border-line">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 sm:py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-txt-muted text-center md:text-left">
      <span>© <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?>. Все права защищены.</span>
      <div class="flex items-center gap-4">
        <span class="flex items-center gap-1.5"><span class="pdot"></span> Сервис онлайн</span>
      </div>
    </div>
  </div>
</footer>
<?php require_once __DIR__ . '/theme-scripts.php'; ?>