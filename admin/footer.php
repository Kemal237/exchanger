<?php
if (!defined('SITE_NAME')) require_once __DIR__ . '/../config.php';
?>
<footer class="mt-20 border-t border-line relative">
  <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-txt-muted">
    <span>© <?= date('Y') ?> <?= htmlspecialchars(SITE_NAME) ?> · Админ-панель</span>
    <span class="flex items-center gap-1.5"><span class="pdot"></span> Сервис онлайн</span>
  </div>
</footer>
<?php require_once __DIR__ . '/../theme-scripts.php'; ?>