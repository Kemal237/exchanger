<?php
require_once '../config.php';
require_once '../auth.php';

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if (!isAdmin()) {
    $error = 'Доступ запрещён. Только администратор может войти в панель.';
}

define('ADMIN_PASSWORD', '14751475');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $pass = $_POST['password'] ?? '';
    if ($pass === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Неверный пароль';
    }
}

$page_title = 'Админ-панель — Вход';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once '../theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<main class="relative z-10 min-h-screen flex items-center justify-center px-6 py-10">
  <div class="w-full max-w-md fade-in">

    <a href="../index.php" class="flex items-center justify-center gap-2 mb-8 group">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-vi to-cy flex items-center justify-center shadow-glow group-hover:scale-105 transition">
        <i data-lucide="shield-check" class="w-5 h-5 text-bg-base"></i>
      </div>
      <span class="text-xl font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?>
        <span class="text-vi text-sm font-medium ml-1">Admin</span>
      </span>
    </a>

    <div class="gborder spot rounded-2xl bg-bg-card/85 backdrop-blur-md p-8 shadow-card">
      <div class="text-center mb-6">
        <div class="w-14 h-14 rounded-2xl bg-vi-soft border border-vi/30 mx-auto mb-4 flex items-center justify-center">
          <i data-lucide="lock-keyhole" class="w-6 h-6 text-vi"></i>
        </div>
        <h1 class="text-2xl font-bold mb-1.5">Админ-панель</h1>
        <p class="text-sm text-txt-muted">Закрытая зона. Требуется авторизация</p>
      </div>

      <?php if ($error): ?>
        <div class="mb-5 px-4 py-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger flex items-start gap-2.5">
          <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <?php if (isAdmin()): ?>
        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Пароль администратора</label>
            <div class="relative">
              <i data-lucide="key-round" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input type="password" name="password" id="password" required autofocus
                     class="input-d w-full h-11 pl-10 pr-11 rounded-lg text-sm"
                     placeholder="••••••••">
              <button type="button" id="toggle-pwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-txt-muted hover:text-vi transition" tabindex="-1">
                <i data-lucide="eye" class="w-4 h-4" id="pwd-icon"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn-vi w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            Войти в панель
          </button>
        </form>
      <?php else: ?>
        <div class="text-center py-4">
          <a href="../login.php" class="btn-ghost inline-flex items-center gap-2 h-10 px-4 rounded-lg text-sm">
            <i data-lucide="log-in" class="w-4 h-4"></i>
            Войти под админ-аккаунтом
          </a>
        </div>
      <?php endif; ?>

      <div class="mt-6 text-center">
        <a href="../index.php" class="text-xs text-txt-muted hover:text-cy transition inline-flex items-center gap-1">
          <i data-lucide="arrow-left" class="w-3 h-3"></i>
          На главную страницу сайта
        </a>
      </div>
    </div>
  </div>
</main>

<script>
  document.getElementById('toggle-pwd')?.addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('pwd-icon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.setAttribute('data-lucide', 'eye-off');
    } else {
      pwd.type = 'password';
      icon.setAttribute('data-lucide', 'eye');
    }
    lucide.createIcons();
  });
</script>

<?php require_once '../theme-scripts.php'; ?>
</body>
</html>