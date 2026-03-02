<?php
// auth.php — session_start ТОЛЬКО здесь!

session_start();

require_once 'db.php';  // подключаем $pdo

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function login($username, $password) {
    global $pdo;

    if (!$pdo) {
        die("Ошибка: подключение к базе данных не удалось");
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];  // ← добавляем роль в сессию!
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
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hash]);
        return true;
    } catch (PDOException $e) {
        return false; // логин или email занят
    }
}

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