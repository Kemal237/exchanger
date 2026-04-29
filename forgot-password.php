<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'mailer.php';
require_once 'activity_log.php';

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logAction($pdo, 'password_reset_request', 'Некорректный email: ' . $email, 'error', 'user', '');
        $error = 'Введите корректный email';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_reset_sent_at FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Защита от спама: не чаще 1 раза в 2 минуты
            if ($user['password_reset_sent_at']) {
                $lastSent = strtotime($user['password_reset_sent_at']);
                if (time() - $lastSent < 120) {
                    $error = 'Письмо уже отправлено. Подождите 2 минуты перед повторным запросом.';
                }
            }

            if (!$error) {
                $token = bin2hex(random_bytes(32));
                $pdo->prepare("UPDATE users SET password_reset_token = ?, password_reset_sent_at = NOW() WHERE id = ?")
                    ->execute([$token, $user['id']]);
                sendPasswordResetEmail($email, $user['username'], $token);
            }
        }

        // Всегда показываем успех — не раскрываем существование email
        if (!$error) {
            $sent = true;
            logAction($pdo, 'password_reset_request', 'Запрос сброса пароля для email: ' . $email, 'success', 'user', '');
        }
    }
}

$page_title = 'Восстановление пароля — ' . SITE_NAME;
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

<div class="aurora"><div class="ab ab-1"></div><div class="ab ab-2"></div><div class="ab ab-3"></div></div>
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

      <?php if ($sent): ?>
        <!-- Успех -->
        <div class="text-center py-4">
          <div class="w-14 h-14 rounded-2xl bg-emr/10 border border-emr/30 flex items-center justify-center mx-auto mb-5">
            <i data-lucide="mail-check" class="w-7 h-7 text-emr"></i>
          </div>
          <h1 class="text-xl font-bold mb-2">Письмо отправлено</h1>
          <p class="text-sm text-txt-secondary leading-relaxed mb-6">
            Если аккаунт с таким email существует, мы отправили ссылку для сброса пароля.<br>
            Проверьте папку «Спам», если письмо не пришло в течение нескольких минут.
          </p>
          <a href="login.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
            <i data-lucide="log-in" class="w-4 h-4"></i> Войти
          </a>
        </div>

      <?php else: ?>
        <!-- Форма -->
        <div class="text-center mb-6">
          <div class="w-12 h-12 rounded-2xl bg-vi-soft border border-vi/30 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="key-round" class="w-6 h-6 text-vi"></i>
          </div>
          <h1 class="text-2xl font-bold mb-1.5">Забыли пароль?</h1>
          <p class="text-sm text-txt-muted">Укажите email — пришлём ссылку для сброса</p>
        </div>

        <?php if ($error): ?>
          <div class="mb-5 px-4 py-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger flex items-start gap-2.5">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Email</label>
            <div class="relative">
              <i data-lucide="mail" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input type="email" name="email" required autofocus
                     value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                     class="input-d w-full h-11 pl-10 pr-4 rounded-lg text-sm"
                     placeholder="your@email.com">
            </div>
          </div>
          <button type="submit" class="btn-cy w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
            <i data-lucide="send" class="w-4 h-4"></i>
            Отправить ссылку
          </button>
        </form>

        <div class="mt-6 text-center">
          <a href="login.php" class="text-xs text-txt-muted hover:text-cy transition inline-flex items-center gap-1">
            <i data-lucide="arrow-left" class="w-3 h-3"></i>
            Вернуться ко входу
          </a>
        </div>
      <?php endif; ?>

    </div>
  </div>
</main>

<?php require_once 'theme-scripts.php'; ?>
</body>
</html>
