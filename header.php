<?php
// header.php — dark navbar for public pages
if (!defined('SITE_NAME')) require_once __DIR__ . '/config.php';
if (!function_exists('isLoggedIn')) require_once __DIR__ . '/auth.php';
$current_page = $current_page ?? basename($_SERVER['SCRIPT_NAME']);
?>
<header class="sticky top-0 z-40 backdrop-blur-md bg-bg-base/80 border-b border-line relative">
  <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
    <div class="flex items-center gap-8">
      <a href="index.php" class="flex items-center gap-2">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cy to-vi flex items-center justify-center shadow-glow">
          <i data-lucide="arrow-left-right" class="w-4 h-4 text-bg-base"></i>
        </div>
        <span class="text-lg font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-6 text-sm">
        <a class="<?= $current_page === 'index.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="index.php">Обмен</a>
        <a class="<?= $current_page === 'rates.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="rates.php">Курсы и резервы</a>
        <a class="<?= $current_page === 'aml.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="aml.php">AML</a>
        <a class="<?= $current_page === 'kyc.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="kyc.php">KYC</a>
      </nav>
    </div>
    <div class="flex items-center gap-3">
      <a href="rates.xml.php" target="_blank" class="hidden lg:flex items-center gap-2 px-3 h-9 rounded-lg text-xs text-cy border border-cy-border bg-cy-soft hover:bg-cy/20 transition">
        <i data-lucide="code-xml" class="w-3.5 h-3.5"></i>
        BestChange XML
      </a>
      <?php if (isLoggedIn()): ?>
        <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'text-cy bg-bg-soft' : 'text-txt-primary' ?> hidden sm:flex items-center gap-2 px-3 h-9 rounded-lg text-sm hover:bg-bg-soft transition">
          <i data-lucide="user-round" class="w-4 h-4"></i>
          Профиль
        </a>
        <?php if (isAdmin()): ?>
          <a href="admin/index.php" class="hidden sm:flex items-center gap-2 px-3 h-9 rounded-lg text-sm text-vi hover:bg-vi-soft transition border border-transparent hover:border-vi/30">
            <i data-lucide="shield-check" class="w-4 h-4"></i> Админ
          </a>
        <?php endif; ?>
        <a href="logout.php" class="px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-danger transition flex items-center gap-2">
          <i data-lucide="log-out" class="w-4 h-4"></i>
          <span class="hidden sm:inline">Выйти</span>
        </a>
      <?php else: ?>
        <a href="login.php" class="px-4 h-9 rounded-lg text-sm text-txt-primary hover:bg-bg-soft transition flex items-center">Войти</a>
        <a href="register.php" class="btn-cy px-4 h-9 rounded-lg text-sm font-semibold flex items-center">Регистрация</a>
      <?php endif; ?>
    </div>
  </div>
</header>