<?php
// admin/users.php — Управление пользователями с сортировкой

session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// === Обработка сортировки ===
$allowed_columns = ['id', 'username', 'email', 'telegram', 'role', 'created_at'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id';
$sort_order  = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'DESC' : 'ASC';

// Переключение направления сортировки
$next_order = ($sort_order === 'ASC') ? 'desc' : 'asc';

// Обработка удаления
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    $_SESSION['admin_message'] = "Пользователь успешно удалён";
    header('Location: users.php');
    exit;
}

// Обработка редактирования
$edit_error = $edit_success = '';
$edit_user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $new_username = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_telegram = trim($_POST['telegram'] ?? '');
    $new_role     = $_POST['role'] ?? 'user';
    $new_password = $_POST['new_password'] ?? '';

    if (empty($new_username) || empty($new_email)) {
        $edit_error = 'Заполните имя и email';
    } else {
        try {
            if ($new_password) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, telegram = ?, role = ?, password = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_telegram, $new_role, $hash, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, telegram = ?, role = ? WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_telegram, $new_role, $user_id]);
            }
            $edit_success = 'Пользователь успешно обновлён';
        } catch (PDOException $e) {
            $edit_error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

// Загрузка данных пользователя для редактирования
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получение списка пользователей с сортировкой
$stmt = $pdo->prepare("
    SELECT id, username, email, telegram, role, created_at 
    FROM users 
    ORDER BY $sort_column $sort_order
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <a href="index.php" class="hover:underline">Главная</a>
        <a href="orders.php" class="hover:underline">Заявки</a>
        <a href="users.php" class="text-yellow-300 font-bold hover:underline">Пользователи</a>
        <a href="reserves.php" class="hover:underline">Резервы</a>
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

    <!-- Форма редактирования -->
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
            <label class="block text-gray-700 mb-2">Telegram</label>
            <input type="text" name="telegram" value="<?= htmlspecialchars($edit_user['telegram'] ?? '') ?>" placeholder="@username" class="w-full p-3 border rounded-lg">
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

    <!-- Список пользователей с сортировкой -->
    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="w-full text-left">
        <thead class="bg-gray-100">
          <tr>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('id')">ID <?= $sort_column === 'id' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('username')">Логин <?= $sort_column === 'username' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('email')">Email <?= $sort_column === 'email' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('telegram')">Telegram <?= $sort_column === 'telegram' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('role')">Роль <?= $sort_column === 'role' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4 cursor-pointer hover:bg-gray-200" onclick="sortTable('created_at')">Дата регистрации <?= $sort_column === 'created_at' ? ($sort_order === 'ASC' ? '↑' : '↓') : '' ?></th>
            <th class="p-4">Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="p-4"><?= $user['id'] ?></td>
              <td class="p-4"><?= htmlspecialchars($user['username']) ?></td>
              <td class="p-4"><?= htmlspecialchars($user['email']) ?></td>
              <td class="p-4"><?= htmlspecialchars($user['telegram'] ?? '—') ?></td>
              <td class="p-4">
                <span class="px-3 py-1 rounded-full text-sm <?= $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' ?>">
                  <?= $user['role'] === 'admin' ? 'Админ' : 'Пользователь' ?>
                </span>
              </td>
              <td class="p-4"><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
              <td class="p-4 space-x-4">
                <a href="?edit=<?= $user['id'] ?>" class="text-blue-600 hover:underline">Редактировать</a>
                <a href="?delete=<?= $user['id'] ?>" 
                   onclick="return confirm('Вы уверены, что хотите удалить пользователя <?= htmlspecialchars($user['username']) ?>?');"
                   class="text-red-600 hover:underline">Удалить</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </main>

  <script>
    function sortTable(column) {
      let order = 'asc';
      const url = new URL(window.location.href);
      
      if (url.searchParams.get('sort') === column && url.searchParams.get('order') === 'asc') {
        order = 'desc';
      }
      
      url.searchParams.set('sort', column);
      url.searchParams.set('order', order);
      window.location.href = url.toString();
    }
  </script>

</body>
</html>