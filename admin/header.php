<?php
// admin/header.php — dark admin navbar
if (!defined('SITE_NAME')) require_once __DIR__ . '/../config.php';
$admin_page = $admin_page ?? basename($_SERVER['SCRIPT_NAME']);
?>
<header class="sticky top-0 z-40 backdrop-blur-md bg-bg-base/80 border-b border-line relative">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-8">
      <a href="index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vi to-cy flex items-center justify-center shadow-glow">
          <i data-lucide="shield-check" class="w-4 h-4 text-bg-base"></i>
        </div>
        <span class="text-lg font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?> <span class="text-vi text-xs font-medium ml-1">Admin</span></span>
      </a>
      <nav class="hidden md:flex items-center gap-5 text-sm">
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
      </nav>
    </div>
    <div class="flex items-center gap-3">
      <a href="../index.php" class="px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-cy transition flex items-center gap-2">
        <i data-lucide="external-link" class="w-4 h-4"></i>
        <span class="hidden sm:inline">На сайт</span>
      </a>
      <a href="logout.php" class="px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-danger transition flex items-center gap-2">
        <i data-lucide="log-out" class="w-4 h-4"></i>
        <span class="hidden sm:inline">Выйти</span>
      </a>
    </div>
  </div>
</header>