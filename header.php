<?php
// header.php — dark navbar for public pages
if (!defined('SITE_NAME')) require_once __DIR__ . '/config.php';
if (!function_exists('isLoggedIn')) require_once __DIR__ . '/auth.php';
$current_page = $current_page ?? basename($_SERVER['SCRIPT_NAME']);

$_header_badge = 0;
if (isLoggedIn() && isset($pdo)) {
    try {
        $_uid = $_SESSION['user_id'] ?? 0;
        if ($_uid) {
            $_hstmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status IN ('new','in_process')");
            $_hstmt->execute([$_uid]);
            $_header_badge = (int)$_hstmt->fetchColumn();
            unset($_hstmt, $_uid);
        }
    } catch (\Throwable $e) {
        // не ломаем страницу если запрос не удался
    }
}
?>
<header class="sticky top-0 z-40 backdrop-blur-md bg-bg-base/80 border-b border-line relative">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 h-14 sm:h-16 flex items-center justify-between gap-3">
    <div class="flex items-center gap-4 lg:gap-8 min-w-0">
      <a href="index.php" class="flex items-center gap-2 flex-shrink-0">
        <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-cy to-vi flex items-center justify-center shadow-glow">
          <i data-lucide="arrow-left-right" class="w-4 h-4 text-bg-base"></i>
        </div>
        <span class="text-lg font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?></span>
      </a>
      <nav class="hidden md:flex items-center gap-4 lg:gap-6 text-sm">
        <a class="<?= $current_page === 'index.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition whitespace-nowrap" href="index.php">Обмен</a>
        <a class="<?= $current_page === 'rates.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition whitespace-nowrap" href="rates.php">Курсы</a>
        <a class="<?= $current_page === 'rules.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition whitespace-nowrap" href="rules.php">Правила</a>
        <a class="<?= $current_page === 'aml.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="aml.php">AML</a>
        <a class="<?= $current_page === 'kyc.php' ? 'text-cy' : 'text-txt-secondary' ?> hover:text-cy transition" href="kyc.php">KYC</a>
      </nav>
    </div>
    <div class="flex items-center gap-2 sm:gap-3">
      <a href="rates.xml.php" target="_blank" class="hidden lg:flex items-center gap-2 px-3 h-9 rounded-lg text-xs text-cy border border-cy-border bg-cy-soft hover:bg-cy/20 transition whitespace-nowrap">
        <i data-lucide="code-xml" class="w-3.5 h-3.5"></i>
        BestChange XML
      </a>
      <?php if (isLoggedIn()): ?>
        <a href="profile.php" class="<?= $current_page === 'profile.php' ? 'text-cy bg-bg-soft' : 'text-txt-primary' ?> flex items-center gap-2 px-3 h-9 rounded-lg text-sm hover:bg-bg-soft transition">
          <span class="relative inline-flex">
            <i data-lucide="user-round" class="w-4 h-4"></i>
            <?php if ($_header_badge > 0): ?>
              <span class="absolute -top-1 -right-1 min-w-[10px] h-[10px] rounded-full bg-danger text-white text-[7px] font-bold flex items-center justify-center px-[1px] leading-none pointer-events-none"><?= $_header_badge > 9 ? '9+' : $_header_badge ?></span>
            <?php endif; ?>
          </span>
          <span class="hidden md:inline">Профиль</span>
        </a>
        <?php if (isAdmin()): ?>
          <a href="admin/index.php" class="hidden sm:flex items-center gap-2 px-3 h-9 rounded-lg text-sm text-vi hover:bg-vi-soft transition border border-transparent hover:border-vi/30">
            <i data-lucide="shield-check" class="w-4 h-4"></i> <span class="hidden md:inline">Админ</span>
          </a>
        <?php endif; ?>
        <a href="logout.php" class="hidden sm:flex px-3 h-9 rounded-lg text-sm text-txt-secondary hover:text-danger transition items-center gap-2">
          <i data-lucide="log-out" class="w-4 h-4"></i>
          <span class="hidden md:inline">Выйти</span>
        </a>
      <?php else: ?>
        <a href="login.php" class="hidden sm:flex px-3 sm:px-4 h-9 rounded-lg text-sm text-txt-primary hover:bg-bg-soft transition items-center">Войти</a>
        <a href="register.php" class="hidden sm:flex btn-cy px-3 sm:px-4 h-9 rounded-lg text-sm font-semibold items-center">Регистрация</a>
      <?php endif; ?>

      <!-- Mobile burger -->
      <button type="button" id="mobile-menu-btn" class="md:hidden w-10 h-10 rounded-lg border border-line bg-bg-soft flex items-center justify-center text-txt-primary hover:border-cy-border transition" aria-label="Меню">
        <i data-lucide="menu" class="w-5 h-5" id="burger-icon"></i>
      </button>
    </div>
  </div>

  <!-- Mobile menu drawer -->
  <div id="mobile-menu" class="hidden md:hidden border-t border-line bg-bg-base/95 backdrop-blur-md">
    <nav class="max-w-7xl mx-auto px-4 py-3 flex flex-col gap-1 text-sm">
      <a class="<?= $current_page === 'index.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="index.php">
        <i data-lucide="arrow-left-right" class="w-4 h-4"></i> Обмен
      </a>
      <a class="<?= $current_page === 'rates.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="rates.php">
        <i data-lucide="trending-up" class="w-4 h-4"></i> Курсы и резервы
      </a>
      <a class="<?= $current_page === 'rules.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="rules.php">
        <i data-lucide="scroll-text" class="w-4 h-4"></i> Правила обмена
      </a>
      <a class="<?= $current_page === 'aml.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="aml.php">
        <i data-lucide="shield-check" class="w-4 h-4"></i> AML политика
      </a>
      <a class="<?= $current_page === 'kyc.php' ? 'text-cy bg-cy-soft' : 'text-txt-secondary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="kyc.php">
        <i data-lucide="user-check" class="w-4 h-4"></i> KYC процедура
      </a>

      <div class="h-px bg-line my-2"></div>

      <?php if (isLoggedIn()): ?>
        <a class="<?= $current_page === 'profile.php' ? 'text-cy bg-cy-soft' : 'text-txt-primary' ?> flex items-center gap-3 px-3 h-11 rounded-lg hover:bg-bg-soft transition" href="profile.php">
          <i data-lucide="user-round" class="w-4 h-4"></i> Профиль
        </a>
        <?php if (isAdmin()): ?>
          <a class="flex items-center gap-3 px-3 h-11 rounded-lg text-vi hover:bg-vi-soft transition" href="admin/index.php">
            <i data-lucide="shield-check" class="w-4 h-4"></i> Админ-панель
          </a>
        <?php endif; ?>
        <a class="flex items-center gap-3 px-3 h-11 rounded-lg text-txt-secondary hover:text-danger hover:bg-bg-soft transition" href="logout.php">
          <i data-lucide="log-out" class="w-4 h-4"></i> Выйти
        </a>
      <?php else: ?>
        <a href="login.php" class="flex items-center gap-3 px-3 h-11 rounded-lg text-txt-primary hover:bg-bg-soft transition">
          <i data-lucide="log-in" class="w-4 h-4"></i> Войти
        </a>
        <a href="register.php" class="btn-cy flex items-center justify-center gap-2 h-11 rounded-lg text-sm font-semibold mt-1">
          <i data-lucide="user-plus" class="w-4 h-4"></i> Регистрация
        </a>
      <?php endif; ?>

      <a href="rates.xml.php" target="_blank" class="flex items-center gap-3 px-3 h-10 rounded-lg text-xs text-cy mt-1">
        <i data-lucide="code-xml" class="w-3.5 h-3.5"></i> BestChange XML
      </a>
    </nav>
  </div>
</header>

<script>
  (function() {
    const btn = document.getElementById('mobile-menu-btn');
    const menu = document.getElementById('mobile-menu');
    const icon = document.getElementById('burger-icon');
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
