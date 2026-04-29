<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../activity_log.php';
session_start();
$logUsername = $_SESSION['username'] ?? null;
logAction($pdo, 'admin_logout', 'Выход из админ-панели' . ($logUsername ? ': ' . $logUsername : ''), 'success', 'admin', '');
unset($_SESSION['admin_logged_in']);
header('Location: login.php');
exit;
