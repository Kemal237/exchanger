<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$token = trim($_GET['token'] ?? '');
$error = '';
$done  = false;

// Ищем пользователя по токену
$user = null;
if (!empty($token)) {
    $stmt = $pdo->prepare("
        SELECT id, username, password_reset_sent_at
        FROM users
        WHERE password_reset_token = ?
        LIMIT 1
    ");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Токен не найден или истёк
$tokenValid = false;
if ($user && $user['password_reset_sent_at']) {
    $sent = strtotime($user['password_reset_sent_at']);
    $tokenValid = (time() - $sent) <= 3600; // 1 час
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm']  ?? '';

    if (strlen($password) < 8) {
        $error = 'Пароль должен быть минимум 8 символов';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну заглавную букву';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну строчную букву';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Пароль должен содержать хотя бы одну цифру';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $pdo->prepare("
            UPDATE users
            SET password = ?, password_reset_token = NULL, password_reset_sent_at = NULL
            WHERE id = ?
        ")->execute([$hash, $user['id']]);
        $done = true;
    }
}

$page_title = 'Новый пароль — ' . SITE_NAME;
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

      <?php if ($done): ?>
        <!-- Успех -->
        <div class="text-center py-4">
          <div class="w-14 h-14 rounded-2xl bg-emr/10 border border-emr/30 flex items-center justify-center mx-auto mb-5">
            <i data-lucide="check-circle-2" class="w-7 h-7 text-emr"></i>
          </div>
          <h1 class="text-xl font-bold mb-2">Пароль изменён!</h1>
          <p class="text-sm text-txt-secondary leading-relaxed mb-6">
            Ваш пароль успешно обновлён. Теперь вы можете войти с новым паролем.
          </p>
          <a href="login.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
            <i data-lucide="log-in" class="w-4 h-4"></i> Войти
          </a>
        </div>

      <?php elseif (!$tokenValid): ?>
        <!-- Невалидный или истёкший токен -->
        <div class="text-center py-4">
          <div class="w-14 h-14 rounded-2xl bg-danger/10 border border-danger/30 flex items-center justify-center mx-auto mb-5">
            <i data-lucide="x-circle" class="w-7 h-7 text-danger"></i>
          </div>
          <h1 class="text-xl font-bold mb-2">
            <?= $user ? 'Ссылка устарела' : 'Недействительная ссылка' ?>
          </h1>
          <p class="text-sm text-txt-secondary leading-relaxed mb-6">
            <?= $user
                ? 'Ссылка для сброса пароля действительна 1 час. Запросите новую.'
                : 'Ссылка для сброса пароля неверна или уже использована.' ?>
          </p>
          <a href="forgot-password.php" class="btn-cy inline-flex items-center gap-2 px-6 h-11 rounded-xl text-sm font-semibold">
            <i data-lucide="key-round" class="w-4 h-4"></i> Запросить снова
          </a>
        </div>

      <?php else: ?>
        <!-- Форма нового пароля -->
        <div class="text-center mb-6">
          <div class="w-12 h-12 rounded-2xl bg-vi-soft border border-vi/30 flex items-center justify-center mx-auto mb-4">
            <i data-lucide="lock-keyhole" class="w-6 h-6 text-vi"></i>
          </div>
          <h1 class="text-2xl font-bold mb-1.5">Новый пароль</h1>
          <p class="text-sm text-txt-muted">Придумайте надёжный пароль для аккаунта<br>
            <strong class="text-txt-primary"><?= htmlspecialchars($user['username']) ?></strong>
          </p>
        </div>

        <?php if ($error): ?>
          <div class="mb-5 px-4 py-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger flex items-start gap-2.5">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Новый пароль</label>
            <div class="relative">
              <i data-lucide="lock" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input type="password" name="password" id="password" required autofocus
                     class="input-d w-full h-11 pl-10 pr-11 rounded-lg text-sm"
                     placeholder="Минимум 8 символов">
              <button type="button" id="toggle-pwd" class="absolute right-3 top-1/2 -translate-y-1/2 text-txt-muted hover:text-cy transition" tabindex="-1">
                <i data-lucide="eye" class="w-4 h-4" id="pwd-icon"></i>
              </button>
            </div>
            <!-- Password strength -->
            <div id="pwd-strength" class="mt-2 hidden">
              <div class="flex gap-1 mb-1.5">
                <div class="h-1 flex-1 rounded-full bg-line strength-bar"></div>
                <div class="h-1 flex-1 rounded-full bg-line strength-bar"></div>
                <div class="h-1 flex-1 rounded-full bg-line strength-bar"></div>
                <div class="h-1 flex-1 rounded-full bg-line strength-bar"></div>
              </div>
              <div class="text-xs text-txt-muted space-y-0.5" id="pwd-reqs">
                <div class="flex items-center gap-1.5" data-req="len"><i data-lucide="circle" class="w-3 h-3"></i> минимум 8 символов</div>
                <div class="flex items-center gap-1.5" data-req="up"><i data-lucide="circle" class="w-3 h-3"></i> заглавная буква</div>
                <div class="flex items-center gap-1.5" data-req="low"><i data-lucide="circle" class="w-3 h-3"></i> строчная буква</div>
                <div class="flex items-center gap-1.5" data-req="num"><i data-lucide="circle" class="w-3 h-3"></i> цифра</div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Повторите пароль</label>
            <div class="relative">
              <i data-lucide="lock" class="w-4 h-4 text-txt-muted absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none"></i>
              <input type="password" name="confirm" id="confirm" required
                     class="input-d w-full h-11 pl-10 pr-4 rounded-lg text-sm"
                     placeholder="••••••••">
            </div>
            <div id="confirm-msg" class="text-xs mt-1.5 hidden"></div>
          </div>

          <button type="submit" class="btn-cy w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
            <i data-lucide="check" class="w-4 h-4"></i>
            Сохранить новый пароль
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

<script>
  document.getElementById('toggle-pwd')?.addEventListener('click', function () {
    const pwd = document.getElementById('password');
    const icon = document.getElementById('pwd-icon');
    pwd.type = pwd.type === 'password' ? 'text' : 'password';
    icon.setAttribute('data-lucide', pwd.type === 'password' ? 'eye' : 'eye-off');
    lucide.createIcons();
  });

  const pwdInput = document.getElementById('password');
  const strengthBox = document.getElementById('pwd-strength');
  const bars = document.querySelectorAll('.strength-bar');
  const reqs = document.querySelectorAll('#pwd-reqs [data-req]');

  pwdInput?.addEventListener('focus', () => strengthBox?.classList.remove('hidden'));
  pwdInput?.addEventListener('input', (e) => {
    const v = e.target.value;
    const checks = { len: v.length >= 8, up: /[A-Z]/.test(v), low: /[a-z]/.test(v), num: /[0-9]/.test(v) };
    let score = 0;
    reqs.forEach(r => {
      const ok = checks[r.dataset.req];
      if (ok) score++;
      const i = r.querySelector('i');
      i.setAttribute('data-lucide', ok ? 'check-circle-2' : 'circle');
      r.classList.toggle('text-emr', ok);
      r.classList.toggle('text-txt-muted', !ok);
    });
    const colors = ['bg-line', 'bg-danger', 'bg-warn', 'bg-cy', 'bg-emr'];
    bars.forEach((bar, i) => {
      bar.className = 'h-1 flex-1 rounded-full strength-bar ' + (i < score ? colors[score] : 'bg-line');
    });
    lucide.createIcons();
  });

  const confirmInput = document.getElementById('confirm');
  const confirmMsg   = document.getElementById('confirm-msg');
  confirmInput?.addEventListener('input', () => {
    const v = confirmInput.value;
    if (!v) { confirmMsg.classList.add('hidden'); return; }
    confirmMsg.classList.remove('hidden');
    if (v === pwdInput.value) {
      confirmMsg.className = 'text-xs mt-1.5 text-emr flex items-center gap-1';
      confirmMsg.innerHTML = '<i data-lucide="check" class="w-3 h-3"></i> Пароли совпадают';
    } else {
      confirmMsg.className = 'text-xs mt-1.5 text-danger flex items-center gap-1';
      confirmMsg.innerHTML = '<i data-lucide="x" class="w-3 h-3"></i> Пароли не совпадают';
    }
    lucide.createIcons();
  });
</script>

<?php require_once 'theme-scripts.php'; ?>
</body>
</html>
