<?php
// admin/header.php — dark admin navbar
if (!defined('SITE_NAME')) require_once __DIR__ . '/../config.php';
$admin_page = $admin_page ?? basename($_SERVER['SCRIPT_NAME']);
?>
<header class="sticky top-0 z-40 backdrop-blur-md bg-bg-base/80 border-b border-line relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-3">
    <div class="flex items-center gap-4 lg:gap-8 min-w-0">
      <a href="index.php" class="flex items-center gap-2 flex-shrink-0">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vi to-cy flex items-center justify-center shadow-glow">
          <i data-lucide="shield-check" class="w-4 h-4 text-bg-base"></i>
        </div>
        <span class="text-base sm:text-lg font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?> <span class="text-vi text-xs font-medium ml-1">Admin</span></span>
      </a>
      <nav class="hidden md:flex items-center gap-4 lg:gap-5 text-sm">
        <a class="<?= $admin_page === 'index.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition flex items-center gap-1.5" href="index.php">
          <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Обзор
        </a>
        <a class="<?= $admin_page === 'orders.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition flex items-center gap-1.5" href="orders.php">
          <i data-lucide="file-text" class="w-4 h-4"></i> Заявки
        </a>
        <a class="<?= $admin_page === 'users.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition flex items-center gap-1.5" href="users.php">
          <i data-lucide="users" class="w-4 h-4"></i> Пользователи
        </a>
        <a class="<?= $admin_page === 'reserves.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition flex items-center gap-1.5" href="reserves.php">
          <i data-lucide="wallet" class="w-4 h-4"></i> Резервы
        </a>
        <a class="<?= $admin_page === 'support.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition flex items-center gap-1.5" href="support.php">
          <i data-lucide="message-circle" class="w-4 h-4"></i> Поддержка
        </a>
      </nav>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
      <a href="../index.php" class="hidden sm:flex px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-cy transition items-center gap-2">
        <i data-lucide="external-link" class="w-4 h-4"></i>
        <span class="hidden md:inline">На сайт</span>
      </a>
      <a href="logout.php" class="hidden sm:flex px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-danger transition items-center gap-2">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        <span class="hidden md:inline">Выйти</span>
      </a>

      <button type="button" id="admin-menu-btn" class="md:hidden w-10 h-10 rounded-lg border border-line bg-bg-soft flex items-center justify-center text-txt-primary hover:border-cy-border transition" aria-label="Меню">
        <i data-lucide="menu" class="w-5 h-5" id="admin-burger-icon"></i>
      </button>
    </div>
  </div>

  <div id="admin-mobile-menu" class="hidden md:hidden border-t border-line bg-bg-base/95 backdrop-blur-md">
    <nav class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm">
      <a class="<?= $admin_page === 'index.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="index.php">
        <i data-lucide="layout-dashboard" class="w-4 h-4"></i> Обзор
      </a>
      <a class="<?= $admin_page === 'orders.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="orders.php">
        <i data-lucide="file-text" class="w-4 h-4"></i> Заявки
      </a>
      <a class="<?= $admin_page === 'users.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="users.php">
        <i data-lucide="users" class="w-4 h-4"></i> Пользователи
      </a>
      <a class="<?= $admin_page === 'reserves.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="reserves.php">
        <i data-lucide="wallet" class="w-4 h-4"></i> Резервы
      </a>
      <a class="<?= $admin_page === 'support.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="support.php">
        <i data-lucide="message-circle" class="w-4 h-4"></i> Поддержка
      </a>
      <div class="h-px bg-line my-2"></div>
      <a href="../index.php" class="flex items-center gap-3 px-3 h-11 rounded-lg text-txt-secondary hover:text-cy hover:bg-bg-soft transition">
        <i data-lucide="external-link" class="w-4 h-4"></i> На сайт
      </a>
      <a href="logout.php" class="flex items-center gap-3 px-3 h-11 rounded-lg text-txt-secondary hover:text-danger hover:bg-bg-soft transition">
        <i data-lucide="log-out" class="w-4 h-4"></i> Выйти
      </a>
    </nav>
  </div>
</header>

<script>
  (function() {
    const btn = document.getElementById('admin-menu-btn');
    const menu = document.getElementById('admin-mobile-menu');
    const icon = document.getElementById('admin-burger-icon');
    if (!btn || !menu) return;
    btn.addEventListener('click', function() {
      const hidden = menu.classList.toggle('hidden');
      if (icon) {
        icon.setAttribute('data-lucide', hidden ? 'menu' : 'x');
        if (window.lucide) lucide.createIcons({ elements: [icon] });
      }
    });
  })();
</script>
