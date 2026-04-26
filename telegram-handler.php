<?php
// telegram-handler.php — проверка и сохранение Telegram в одном файле

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

header('Content-Type: application/json; charset=utf-8');

// Пользователь не авторизован → сразу нет Telegram
if (!isLoggedIn()) {
    echo json_encode(['hasTelegram' => false, 'message' => 'Не авторизован']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Проверяем наличие Telegram (работает для GET и POST)
$stmt = $pdo->prepare("SELECT telegram FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$telegram = $stmt->fetchColumn();

$hasTelegram = !empty($telegram);

// Если это POST → пытаемся сохранить новый Telegram
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_telegram = trim($_POST['telegram'] ?? '');

    if (empty($new_telegram)) {
        echo json_encode([
            'success' => false,
            'hasTelegram' => $hasTelegram,
            'message' => 'Telegram не указан'
        ]);
        exit;
    }

    if (!str_starts_with($new_telegram, '@') || strlen($new_telegram) < 5) {
        echo json_encode([
            'success' => false,
            'hasTelegram' => $hasTelegram,
            'message' => 'Telegram должен начинаться с @ и содержать минимум 5 символов'
        ]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("UPDATE users SET telegram = ? WHERE id = ?");
        $stmt->execute([$new_telegram, $user_id]);

        echo json_encode([
            'success' => true,
            'hasTelegram' => true,
            'message' => 'Telegram успешно сохранён',
            'telegram' => $new_telegram
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'hasTelegram' => $hasTelegram,
            'message' => 'Ошибка базы данных: ' . $e->getMessage()
        ]);
    }

    exit;
}

// Если просто GET (или любой другой запрос) → возвращаем статус
echo json_encode([
    'hasTelegram' => $hasTelegram,
    'telegram' => $hasTelegram ? $telegram : null
]);
exit;
?>