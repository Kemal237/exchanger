<?php
// verify-email.php — подтверждение email по токену из письма
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'activity_log.php';

$token = trim($_GET['token'] ?? '');
$status = 'invalid'; // invalid | expired | already | success

if (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT id, email_verified, email_verification_sent_at
        FROM users
        WHERE email_verification_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $status = 'invalid';
    } elseif ($user['email_verified']) {
        $status = 'already';
    } elseif ($user['email_verification_sent_at']) {
        $sent = strtotime($user['email_verification_sent_at']);
        if (time() - $sent > 86400) { // 24 часа
            $status = 'expired';
        } else {
            $pdo->prepare("
                UPDATE users
                SET email_verified = 1, email_verification_token = NULL
                WHERE id = ?
            ")->execute([$user['id']]);
            logAction($pdo, 'email_verified', 'Email подтверждён для пользователя #' . $user['id'], 'success', 'user', (string)$user['id']);
            $status = 'success';
        }
    }
}

$page_title = 'Подтверждение email — ' . SITE_NAME;
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

<?php require_once 'header.php'; ?>

<main class="relative z-10 min-h-[70vh] flex items-center justify-center px-4 sm:px-6 py-10">
  <div class="w-full max-w-md fade-in text-center">

    <?php if ($status === 'success'): ?>
      <div class="w-16 h-16 rounded-2xl bg-emr/10 border border-emr/30 flex items-center justify-center mx-auto mb-6">
        <i data-lucide="check-circle-2" class="w-8 h-8 text-emr"></i>
      </div>
      <h1 class="text-2xl font-bold mb-2">Email подтверждён!</h1>
      <p class="text-txt-secondary text-sm mb-8">Ваш адрес электронной почты успешно подтверждён. Спасибо!</p>
      <a href="profile.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
        <i data-lucide="user-round" class="w-4 h-4"></i> Перейти в профиль
      </a>

    <?php elseif ($status === 'already'): ?>
      <div class="w-16 h-16 rounded-2xl bg-cy-soft border border-cy-border flex items-center justify-center mx-auto mb-6">
        <i data-lucide="info" class="w-8 h-8 text-cy"></i>
      </div>
      <h1 class="text-2xl font-bold mb-2">Уже подтверждён</h1>
      <p class="text-txt-secondary text-sm mb-8">Этот email уже был подтверждён ранее.</p>
      <a href="profile.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
        <i data-lucide="user-round" class="w-4 h-4"></i> Перейти в профиль
      </a>

    <?php elseif ($status === 'expired'): ?>
      <div class="w-16 h-16 rounded-2xl bg-warn/10 border border-warn/30 flex items-center justify-center mx-auto mb-6">
        <i data-lucide="clock" class="w-8 h-8 text-warn"></i>
      </div>
      <h1 class="text-2xl font-bold mb-2">Ссылка устарела</h1>
      <p class="text-txt-secondary text-sm mb-8">Ссылка для подтверждения действительна 24 часа. Запросите новую.</p>
      <?php if (isLoggedIn()): ?>
        <a href="resend-verification.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
          <i data-lucide="send" class="w-4 h-4"></i> Отправить новую ссылку
        </a>
      <?php else: ?>
        <a href="login.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
          <i data-lucide="log-in" class="w-4 h-4"></i> Войти и повторить
        </a>
      <?php endif; ?>

    <?php else: ?>
      <div class="w-16 h-16 rounded-2xl bg-danger/10 border border-danger/30 flex items-center justify-center mx-auto mb-6">
        <i data-lucide="x-circle" class="w-8 h-8 text-danger"></i>
      </div>
      <h1 class="text-2xl font-bold mb-2">Недействительная ссылка</h1>
      <p class="text-txt-secondary text-sm mb-8">Ссылка для подтверждения неверна или уже использована.</p>
      <a href="index.php" class="btn-ghost inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-medium">
        <i data-lucide="home" class="w-4 h-4"></i> На главную
      </a>
    <?php endif; ?>

  </div>
</main>

<?php require_once 'footer.php'; ?>
</body>
</html>
