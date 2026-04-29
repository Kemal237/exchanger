<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'activity_log.php';

$error = '';
$message = $_SESSION['auth_message'] ?? '';
unset($_SESSION['auth_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } elseif (login($username, $password)) {
        $username_display = htmlspecialchars($_SESSION['username'] ?? 'пользователь');
        logAction($pdo, 'user_login', 'Успешный вход: ' . $username, 'success', 'user', (string)($_SESSION['user_id'] ?? ''));

        if (isset($_SESSION['pending_exchange'])) {
            $_SESSION['toast'] = [
                'type'    => 'info',
                'message' => 'Добро пожаловать! Продолжите оформление заявки.',
            ];
            $_SESSION['auto_exchange'] = true;
        } else {
            $_SESSION['toast'] = [
                'type'    => 'success',
                'message' => 'Добро пожаловать, ' . $username_display . '!',
            ];
        }

        header('Location: index.php');
        exit;
    } else {
        logAction($pdo, 'user_login_fail', 'Неудачная попытка входа: ' . $username, 'error', 'user', '');
        $error = 'Неверный логин или пароль';
    }
}

$page_title = 'Вход — ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="ru">
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

<main class="relative z-10 min-h-screen flex items-center justify-center px-4 sm:px-6 py-8 sm:py-10">
  <div class="w-full max-w-md fade-in">

    <a href="index.php" class="flex items-center justify-center gap-2 mb-6 sm:mb-8 group">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-cy to-vi flex items-center justify-center shadow-glow group-hover:scale-105 transition">
        <i data-lucide="arrow-left-right" class="w-5 h-5 text-bg-base"></i>
      </div>
      <span class="text-xl font-bold tracking-tight"><?= htmlspecialchars(SITE_NAME) ?></span>
    </a>

    <div class="gborder spot rounded-2xl bg-bg-card/85 backdrop-blur-md p-6 sm:p-8 shadow-card">
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold mb-1.5">С возвращением</h1>
        <p class="text-sm text-txt-muted">Войдите в свой аккаунт</p>
      </div>

      <?php if ($message): ?>
        <div class="mb-5 px-4 py-3 rounded-lg bg-cy-soft border border-cy-border text-sm text-cy flex items-start gap-2.5">
          <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
          <span><?= htmlspecialchars($message) ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="mb-5 px-4 py-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger flex items-start gap-2.5">
          <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Логин</label>
          <div class="relative">
            <i data-lucide="user" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input type="text" name="username" required autofocus
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                   class="input-d w-full h-11 pl-10 pr-4 rounded-lg text-sm"
                   placeholder="Ваш логин">
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Пароль</label>
          <div class="relative">
            <i data-lucide="lock" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
            <input type="password" name="password" id="password" required
                   class="input-d w-full h-11 pl-10 pr-11 rounded-lg text-sm"
                   placeholder="••••••••">
            <button type="button" id="toggle-pwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-txt-muted hover:text-cy transition" tabindex="-1">
              <i data-lucide="eye" class="w-4 h-4" id="pwd-icon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-cy w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
          <i data-lucide="log-in" class="w-4 h-4"></i>
          Войти
        </button>

        <div class="text-center mt-4">
          <a href="forgot-password.php" class="text-xs text-txt-muted hover:text-cy transition inline-flex items-center gap-1">
            <i data-lucide="key-round" class="w-3 h-3"></i>
            Забыли пароль?
          </a>
        </div>
      </form>

      <div class="flex items-center gap-3 my-6">
        <div class="flex-1 h-px bg-line"></div>
        <span class="text-xs text-txt-muted">или</span>
        <div class="flex-1 h-px bg-line"></div>
      </div>

      <a href="register.php" class="btn-ghost w-full h-11 rounded-lg text-sm font-medium flex items-center justify-center gap-2">
        <i data-lucide="user-plus" class="w-4 h-4"></i>
        Создать аккаунт
      </a>
    </div>

    <p class="text-center text-xs text-txt-muted mt-6">
      <a href="index.php" class="hover:text-cy transition inline-flex items-center gap-1">
        <i data-lucide="arrow-left" class="w-3 h-3"></i>
        Вернуться на главную
      </a>
    </p>
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

<?php require_once 'theme-scripts.php'; ?>
</body>
</html>