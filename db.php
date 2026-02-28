<?php
// db.php — подключение к базе

define('DB_HOST', 'localhost');
define('DB_NAME', 'cr873507_swap');     // ← полное имя твоей базы
define('DB_USER', 'cr873507_swap');     // ← то же самое
define('DB_PASS', '14751475');

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к базе: " . $e->getMessage());
}