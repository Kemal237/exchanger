<?php
// activity_log.php — универсальная функция логирования действий

function logAction(
    PDO    $pdo,
    string $action,
    string $description  = '',
    string $result       = 'success',
    string $entity_type  = '',
    string $entity_id    = ''
): void {
    $user_id  = $_SESSION['user_id']  ?? null;
    $username = $_SESSION['username'] ?? null;

    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        $role = 'admin';
        if (!$username && !empty($_SESSION['user_id'])) {
            // имя админа уже в $_SESSION['username'] если он залогинен как user+admin
            $username = $_SESSION['username'] ?? null;
        }
    } elseif (isset($_SESSION['user_id'])) {
        $role = 'user';
    } else {
        $role = 'guest';
    }

    // Реальный IP с учётом прокси
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
       ?? $_SERVER['HTTP_X_REAL_IP']
       ?? $_SERVER['REMOTE_ADDR']
       ?? 'unknown';
    // Берём первый IP из цепочки прокси
    $ip = trim(explode(',', $ip)[0]);
    if (strlen($ip) > 45) $ip = substr($ip, 0, 45);

    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs
                (user_id, username, role, ip, user_agent, action, description, entity_type, entity_id, result, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $username,
            $role,
            $ip,
            $ua,
            $action,
            $description,
            $entity_type,
            $entity_id,
            $result,
        ]);
    } catch (Exception $e) {
        // логирование не должно ломать основной поток
    }
}
