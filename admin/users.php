<?php
// admin/users.php — Управление пользователями

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// Список всех пользователей
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка редактирования пользователя
$edit_error = $edit_success = '';
$edit_user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $new_username = trim($_POST['username'] ?? '');
    $new_email = trim($_POST['email'] ?? '');
    $new_role = $_POST['role'] ?? 'user';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($new_username) || empty($new_email)) {
        $edit_error = 'Заполните имя и email';
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $edit_error = 'Некорректный email';
    } else {
        try {
            if ($new_password) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_role, $hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_role, $user_id]);
            }
            $edit_success = 'Пользователь успешно обновлён';
        } catch (PDOException $e) {
            $edit_error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Если нажали "Редактировать" — загружаем данные пользователя
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Пользователи — Админ-панель</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

  <header class="bg-gray-900 text-white py-4">
    <div class="container mx-auto px-4 flex justify-between items-center">
      <h1 class="text-2xl font-bold">Админ-панель</h1>
      <nav class="space-x-6">
        <a href="orders.php" class="hover:underline">Заявки</a>
        <a href="users.php" class="text-yellow-300 font-bold hover:underline">Пользователи</a>
        <a href="logout.php" class="hover:underline">Выйти</a>
      </nav>
    </div>
  </header>

  <main class="container mx-auto px-4 py-10">

    <h1 class="text-3xl font-bold mb-8">Управление пользователями</h1>

    <?php if ($edit_success): ?>
      <p class="text-green-600 mb-6 bg-green-50 p-4 rounded border border-green-200">
        <?= htmlspecialchars($edit_success) ?>
      </p>
    <?php endif; ?>

    <?php if ($edit_error): ?>
      <p class="text-red-600 mb-6 bg-red-50 p-4 rounded border border-red-200">
        <?= htmlspecialchars($edit_error) ?>
      </p>
    <?php endif; ?>

    <!-- Форма редактирования (появляется при нажатии "Редактировать") -->
    <?php if ($edit_user): ?>
      <div class="bg-white rounded-xl shadow p-8 mb-10">
        <h2 class="text-2xl font-bold mb-6">Редактирование пользователя: <?= htmlspecialchars($edit_user['username']) ?></h2>

        <form method="POST" class="space-y-6">
          <input type="hidden" name="action" value="edit">
          <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">

          <div>
            <label class="block text-gray-700 mb-2">Логин</label>
            <input type="text" name="username" value="<?= htmlspecialchars($edit_user['username']) ?>" required class="w-full p-3 border rounded-lg">
          </div>

          <div>
            <label class="block text-gray-700 mb-2">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($edit_user['email']) ?>" required class="w-full p-3 border rounded-lg">
          </div>

          <div>
            <label class="block text-gray-700 mb-2">Роль</label>
            <select name="role" class="w-full p-3 border rounded-lg">
              <option value="user"  <?= $edit_user['role'] === 'user' ? 'selected' : '' ?>>Пользователь</option>
              <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Администратор</option>
            </select>
          </div>

          <div>
            <label class="block text-gray-700 mb-2">Новый пароль (оставьте пустым, если не меняете)</label>
            <input type="password" name="new_password" class="w-full p-3 border rounded-lg">
          </div>

          <div class="flex space-x-4">
            <button type="submit" class="bg-blue-600 text-white py-3 px-8 rounded-lg hover:bg-blue-700">
              Сохранить
            </button>
            <a href="users.php" class="bg-gray-500 text-white py-3 px-8 rounded-lg hover:bg-gray-600">
              Отмена
            </a>
          </div>
        </form>
      </div>
    <?php endif; ?>

    <!-- Список пользователей -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-4">ID</th>
            <th class="p-4">Логин</th>
            <th class="p-4">Email</th>
            <th class="p-4">Роль</th>
            <th class="p-4">Дата регистрации</th>
            <th class="p-4">Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4"><?= $user['id'] ?></td>
              <td class="p-4"><?= htmlspecialchars($user['username']) ?></td>
              <td class="p-4"><?= htmlspecialchars($user['email']) ?></td>
              <td class="p-4">
                <span class="px-3 py-1 rounded-full text-sm
                  <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                  <?= $user['role'] === 'admin' ? 'Админ' : 'Пользователь' ?>
                </span>
              </td>
              <td class="p-4"><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
              <td class="p-4">
                <a href="?edit=<?= $user['id'] ?>" class="text-blue-600 hover:underline">Редактировать</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

</body>
</html>