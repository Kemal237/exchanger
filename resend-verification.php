<?php
// resend-verification.php — повторная отправка письма верификации
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';
require_once 'mailer.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT email, username, email_verified, email_verification_sent_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['email_verified']) {
    $_SESSION['toast'] = ['type' => 'info', 'message' => 'Email уже подтверждён.'];
    header('Location: profile.php');
    exit;
}

// Защита от спама: не чаще раза в 2 минуты
if ($user['email_verification_sent_at']) {
    $lastSent = strtotime($user['email_verification_sent_at']);
    if (time() - $lastSent < 120) {
        $_SESSION['toast'] = ['type' => 'error', 'message' => 'Письмо уже отправлено. Подождите 2 минуты перед повторной отправкой.'];
        header('Location: profile.php');
        exit;
    }
}

// Генерируем новый токен
$token = bin2hex(random_bytes(32));
$pdo->prepare("UPDATE users SET email_verification_token = ?, email_verification_sent_at = NOW() WHERE id = ?")
    ->execute([$token, $user_id]);

$sent = sendVerificationEmail($user['email'], $user['username'], $token);

if ($sent) {
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Письмо с подтверждением отправлено на ' . $user['email']];
} else {
    $_SESSION['toast'] = ['type' => 'error', 'message' => 'Не удалось отправить письмо. Проверьте настройки SMTP.'];
}

header('Location: profile.php');
exit;
