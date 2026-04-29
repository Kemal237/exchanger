<?php
require_once 'config.php';
require_once 'db.php';
require_once 'activity_log.php';
session_start();
// Захватываем данные до уничтожения сессии
$logUsername = $_SESSION['username'] ?? null;
$logUserId   = $_SESSION['user_id']  ?? null;
logAction($pdo, 'user_logout', 'Выход из аккаунта' . ($logUsername ? ': ' . $logUsername : ''), 'success', 'user', (string)($logUserId ?? ''));
session_destroy();
header('Location: index.php');
exit;
