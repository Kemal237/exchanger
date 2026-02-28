<?php
// auth.php — ВАЖНО: session_start() только здесь!

session_start();  // ← вызывается один раз на всю сессию

require_once 'db.php';  // ← подключаем PDO

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function login($username, $password) {
    global $pdo;

    // Проверка, что $pdo существует
    if (!$pdo) {
        die("Ошибка: подключение к базе данных не удалось");
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email']    = $user['email'];
        return true;
    }
    return false;
}

function register($username, $email, $password) {
    global $pdo;

    if (!$pdo) {
        die("Ошибка: подключение к базе данных не удалось");
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);
        return true;
    } catch (PDOException $e) {
        // Если логин или email уже занят — возвращаем false
        return false;
    }
}

// Функция обновления профиля (если понадобится позже)
function updateProfile($user_id, $new_name, $new_email, $new_password = null) {
    global $pdo;

    if (!$pdo) return false;

    if ($new_password) {
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, password = ? WHERE id = ?");
        return $stmt->execute([$new_name, $new_email, $hash, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
        return $stmt->execute([$new_name, $new_email, $user_id]);
    }
}