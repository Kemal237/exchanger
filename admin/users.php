<?php
// admin/users.php — Управление пользователями + история обмена

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

// AJAX: обновление статуса заявки из модалки истории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    header('Content-Type: application/json');
    $order_id   = trim($_POST['order_id'] ?? '');
    $new_status = $_POST['new_status'] ?? '';
    $valid      = ['new', 'in_process', 'success', 'canceled'];
    if ($order_id && in_array($new_status, $valid)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid']);
    }
    exit;
}

// AJAX: удаление заявки из модалки истории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order_ajax'])) {
    header('Content-Type: application/json');
    $order_id = trim($_POST['order_id'] ?? '');
    if ($order_id) {
        $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$order_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Invalid']);
    }
    exit;
}

// AJAX-запрос истории пользователя
if (isset($_GET['get_history'])) {
    $user_id = (int)$_GET['get_history'];
    $stmt = $pdo->prepare("
        SELECT id, created_at, give_currency, amount_give, get_currency, amount_get, status
        FROM orders
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($orders);
    exit;
}

$allowed_columns = ['id', 'username', 'email', 'telegram', 'role', 'created_at'];
$sort_column = isset($_GET['sort']) && in_array($_GET['sort'], $allowed_columns) ? $_GET['sort'] : 'id';
$sort_order  = (isset($_GET['order']) && $_GET['order'] === 'desc') ? 'DESC' : 'ASC';

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => 'Пользователь удалён'];
    header('Location: users.php');
    exit;
}


$edit_error = $edit_success = '';
$edit_user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $user_id      = (int)($_POST['user_id'] ?? 0);
    $new_username = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_telegram = trim($_POST['telegram'] ?? '');
    $new_role         = $_POST['role'] ?? 'user';
    $new_password     = $_POST['new_password'] ?? '';
    $email_verified   = isset($_POST['email_verified']) ? 1 : 0;

    if (empty($new_username) || empty($new_email)) {
        $edit_error = 'Заполните имя и email';
    } else {
        try {
            if ($new_password) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, telegram = ?, role = ?, password = ?, email_verified = ?, email_verification_token = IF(? = 1, NULL, email_verification_token) WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_telegram, $new_role, $hash, $email_verified, $email_verified, $user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, telegram = ?, role = ?, email_verified = ?, email_verification_token = IF(? = 1, NULL, email_verification_token) WHERE id = ?");
                $stmt->execute([$new_username, $new_email, $new_telegram, $new_role, $email_verified, $email_verified, $user_id]);
            }
            $edit_success = 'Пользователь успешно обновлён';
            // Reload edit user after update
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $edit_error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['edit']) && !$edit_user) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("
    SELECT id, username, email, telegram, role, created_at, email_verified
    FROM users
    ORDER BY $sort_column $sort_order
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

function sortIcon($col, $current, $order) {
    if ($col !== $current) return 'chevrons-up-down';
    return $order === 'DESC' ? 'chevron-down' : 'chevron-up';
}

$page_title = 'Пользователи — Админ-панель';
$admin_page = 'users.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once __DIR__ . '/../theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora">
  <div class="ab ab-1"></div>
  <div class="ab ab-2"></div>
  <div class="ab ab-3"></div>
</div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php if (isset($_SESSION['toast'])): ?>
  <div id="toast" class="toast-w <?= htmlspecialchars($_SESSION['toast']['type']) ?>">
    <?= htmlspecialchars($_SESSION['toast']['message']) ?>
  </div>
  <?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<?php require_once __DIR__ . '/header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <section class="mb-6 sm:mb-8 fade-in">
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-1">Пользователи</h1>
    <p class="text-xs sm:text-sm text-txt-muted"><?= count($users) ?> зарегистрированных пользователей</p>
  </section>

  <?php if ($edit_success): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-emr/10 border border-emr/30 text-sm text-emr flex items-center gap-2.5">
      <i data-lucide="check-circle-2" class="w-4 h-4 flex-shrink-0"></i>
      <span><?= htmlspecialchars($edit_success) ?></span>
    </div>
  <?php endif; ?>
  <?php if ($edit_error): ?>
    <div class="mb-5 px-4 py-3 rounded-lg bg-danger/10 border border-danger/30 text-sm text-danger flex items-center gap-2.5">
      <i data-lucide="alert-circle" class="w-4 h-4 flex-shrink-0"></i>
      <span><?= htmlspecialchars($edit_error) ?></span>
    </div>
  <?php endif; ?>

  <?php if ($edit_user): ?>
    <section class="gborder spot rounded-2xl bg-bg-card p-4 sm:p-6 mb-6 sm:mb-8 reveal" data-d="1">
      <div class="flex items-center gap-2 mb-4 sm:mb-5">
        <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
          <i data-lucide="user-cog" class="w-4 h-4 text-vi"></i>
        </div>
        <h2 class="text-base sm:text-lg font-bold min-w-0 truncate">Редактирование: <span class="text-cy"><?= htmlspecialchars($edit_user['username']) ?></span></h2>
      </div>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
        <div class="grid md:grid-cols-2 gap-4">
          <!-- Логин -->
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Логин</label>
            <input type="text" name="username" required value="<?= htmlspecialchars($edit_user['username']) ?>" class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>
          <!-- Email -->
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($edit_user['email']) ?>" class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>
          <!-- Telegram -->
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Telegram</label>
            <input type="text" name="telegram" placeholder="@username" value="<?= htmlspecialchars($edit_user['telegram'] ?? '') ?>" class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>
          <!-- Новый пароль -->
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Новый пароль</label>
            <input type="password" name="new_password" placeholder="Оставьте пустым, если не меняете" class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>
          <!-- Роль (тумблер) -->
          <div class="flex items-end pb-0.5">
            <label class="flex items-center justify-between gap-3 cursor-pointer select-none w-full h-10 px-3 rounded-lg bg-bg-soft border border-line">
              <div class="flex items-center gap-2">
                <div class="text-xs font-medium text-txt-secondary uppercase tracking-wider">Роль:</div>
                <div class="text-sm font-medium" id="role-label"><?= $edit_user['role'] === 'admin' ? 'Администратор' : 'Пользователь' ?></div>
              </div>
              <div class="relative flex-shrink-0">
                <input type="checkbox" name="role_admin" id="role_toggle" class="sr-only peer"
                       <?= $edit_user['role'] === 'admin' ? 'checked' : '' ?>>
                <div class="w-11 h-6 rounded-full bg-line peer-checked:bg-vi transition-colors duration-200"></div>
                <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></div>
              </div>
            </label>
            <input type="hidden" name="role" id="role_hidden" value="<?= $edit_user['role'] === 'admin' ? 'admin' : 'user' ?>">
          </div>
          <!-- Email верифицирован (тумблер) -->
          <div class="flex items-end pb-0.5">
            <label class="flex items-center justify-between gap-3 cursor-pointer select-none w-full h-10 px-3 rounded-lg bg-bg-soft border border-line">
              <div class="flex items-center gap-2">
                <div class="text-xs font-medium text-txt-secondary uppercase tracking-wider">Email:</div>
                <div class="text-sm font-medium" id="email-verified-label"><?= !empty($edit_user['email_verified']) ? 'Подтверждён' : 'Не подтверждён' ?></div>
              </div>
              <div class="relative flex-shrink-0">
                <input type="checkbox" name="email_verified" id="email_verified_toggle" class="sr-only peer"
                       <?= !empty($edit_user['email_verified']) ? 'checked' : '' ?>>
                <div class="w-11 h-6 rounded-full bg-line peer-checked:bg-emr transition-colors duration-200"></div>
                <div class="absolute top-0.5 left-0.5 w-5 h-5 rounded-full bg-white shadow transition-transform duration-200 peer-checked:translate-x-5"></div>
              </div>
            </label>
          </div>
        </div>
        <div class="flex gap-2 pt-2">
          <button type="submit" class="btn-cy h-11 px-6 rounded-lg text-sm font-semibold flex items-center gap-2">
            <i data-lucide="save" class="w-4 h-4"></i>
            Сохранить
          </button>
          <a href="users.php" class="btn-ghost h-11 px-5 rounded-lg text-sm font-medium flex items-center gap-2">
            <i data-lucide="x" class="w-4 h-4"></i>
            Отмена
          </a>
        </div>
      </form>
    </section>
  <?php endif; ?>

  <!-- Список -->
  <section class="reveal" data-d="2">
    <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
      <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
              <?php
              $cols = [
                ['id',         '№'],
                ['username',   'Логин'],
                ['email',      'Email'],
                ['telegram',   'Telegram'],
                ['role',       'Роль'],
                ['created_at', 'Регистрация'],
                ['email_verified', 'Email верифицирован'],
              ];
              foreach ($cols as [$key, $label]):
                $newOrder = ($sort_column === $key && $sort_order === 'ASC') ? 'desc' : 'asc';
              ?>
                <th class="px-5 py-3 font-medium">
                  <a href="?sort=<?= $key ?>&order=<?= $newOrder ?>" class="flex items-center gap-1.5 hover:text-cy transition">
                    <?= $label ?>
                    <i data-lucide="<?= sortIcon($key, $sort_column, $sort_order) ?>" class="w-3 h-3 <?= $sort_column === $key ? 'text-cy' : 'opacity-50' ?>"></i>
                  </a>
                </th>
              <?php endforeach; ?>
              <th class="px-5 py-3 font-medium text-right">Действия</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-line">
            <?php foreach ($users as $user): ?>
              <tr class="row-h transition">
                <td class="px-5 py-4 font-mono text-xs text-txt-secondary">#<?= $user['id'] ?></td>
                <td class="px-5 py-4 font-medium"><?= htmlspecialchars($user['username']) ?></td>
                <td class="px-5 py-4 text-txt-secondary"><?= htmlspecialchars($user['email']) ?></td>
                <td class="px-5 py-4 text-cy font-mono text-xs"><?= htmlspecialchars($user['telegram'] ?: '—') ?></td>
                <td class="px-5 py-4">
                  <?php if ($user['role'] === 'admin'): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-vi-soft border border-vi/30 text-vi">
                      <i data-lucide="shield-check" class="w-3 h-3"></i>
                      Админ
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-cy-soft border border-cy-border text-cy">
                      <i data-lucide="user" class="w-3 h-3"></i>
                      Пользователь
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-xs text-txt-muted whitespace-nowrap"><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                <td class="px-5 py-4 whitespace-nowrap">
                  <?php if ($user['email_verified']): ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-emr/10 border border-emr/30 text-emr">
                      <i data-lucide="check-circle-2" class="w-3 h-3"></i> Подтверждён
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs bg-warn/10 border border-warn/30 text-warn">
                      <i data-lucide="alert-circle" class="w-3 h-3"></i> Не подтверждён
                    </span>
                  <?php endif; ?>
                </td>
                <td class="px-5 py-4 text-right whitespace-nowrap">
                  <div class="inline-flex items-center gap-1.5">
                    <a href="?edit=<?= $user['id'] ?>" class="btn-ghost h-8 px-2.5 rounded-md text-xs inline-flex items-center gap-1" title="Редактировать">
                      <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                    </a>
                    <button onclick="showUserHistory(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" class="btn-ghost h-8 px-2.5 rounded-md text-xs inline-flex items-center gap-1" title="История">
                      <i data-lucide="history" class="w-3.5 h-3.5"></i>
                    </button>
                    <button onclick="showNotes('user', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" class="btn-ghost h-8 px-2.5 rounded-md text-xs inline-flex items-center gap-1 text-vi" title="Заметки">
                      <i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i>
                    </button>
                    <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')" class="btn-danger h-8 px-2.5 rounded-md text-xs inline-flex items-center gap-1" title="Удалить">
                      <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    </a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="md:hidden divide-y divide-line">
        <?php foreach ($users as $user): ?>
          <div class="p-4">
            <div class="flex items-start justify-between gap-2 mb-3">
              <div class="min-w-0">
                <div class="font-medium text-sm truncate"><?= htmlspecialchars($user['username']) ?></div>
                <div class="font-mono text-[11px] text-txt-secondary mt-0.5">#<?= $user['id'] ?></div>
              </div>
              <?php if ($user['role'] === 'admin'): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-vi-soft border border-vi/30 text-vi flex-shrink-0">
                  <i data-lucide="shield-check" class="w-3 h-3"></i> Админ
                </span>
              <?php else: ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-cy-soft border border-cy-border text-cy flex-shrink-0">
                  <i data-lucide="user" class="w-3 h-3"></i> User
                </span>
              <?php endif; ?>
            </div>
            <div class="space-y-1 mb-3 text-[12px]">
              <div class="flex items-center gap-2 text-txt-secondary"><i data-lucide="mail" class="w-3.5 h-3.5 flex-shrink-0"></i><span class="truncate"><?= htmlspecialchars($user['email']) ?></span></div>
              <?php if (!empty($user['telegram'])): ?>
                <div class="flex items-center gap-2 text-cy"><i data-lucide="send" class="w-3.5 h-3.5 flex-shrink-0"></i><span class="truncate font-mono"><?= htmlspecialchars($user['telegram']) ?></span></div>
              <?php endif; ?>
              <div class="flex items-center gap-2 text-txt-muted"><i data-lucide="calendar" class="w-3.5 h-3.5 flex-shrink-0"></i><span><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></span></div>
              <div class="flex items-center gap-2">
                <?php if ($user['email_verified']): ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-emr/10 border border-emr/30 text-emr">
                    <i data-lucide="check-circle-2" class="w-3 h-3"></i> Email подтверждён
                  </span>
                <?php else: ?>
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] bg-warn/10 border border-warn/30 text-warn">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i> Email не подтверждён
                  </span>
                <?php endif; ?>
              </div>
            </div>
            <div class="flex items-center gap-1.5">
              <a href="?edit=<?= $user['id'] ?>" class="btn-ghost flex-1 h-9 rounded-md text-xs inline-flex items-center justify-center gap-1">
                <i data-lucide="pencil" class="w-3.5 h-3.5"></i> Изменить
              </a>
              <button onclick="showUserHistory(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" class="btn-ghost flex-1 h-9 rounded-md text-xs inline-flex items-center justify-center gap-1">
                <i data-lucide="history" class="w-3.5 h-3.5"></i> История
              </button>
              <button onclick="showNotes('user', <?= $user['id'] ?>, '<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>')" class="btn-ghost h-9 px-3 rounded-md text-xs inline-flex items-center gap-1 text-vi" title="Заметки">
                <i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i>
              </button>
              <a href="?delete=<?= $user['id'] ?>" onclick="return confirm('Удалить пользователя <?= htmlspecialchars($user['username']) ?>?')" class="btn-danger h-9 px-3 rounded-md text-xs inline-flex items-center gap-1">
                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<!-- History modal -->
<div id="history-modal" class="hidden fixed inset-0 z-[60] bg-black/75 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4" onclick="closeHistoryModal()">
  <div class="gborder rounded-2xl bg-bg-card shadow-card w-full max-w-5xl max-h-[90vh] sm:max-h-[85vh] flex flex-col overflow-hidden" onclick="event.stopPropagation()">
    <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-line">
      <div class="flex items-center gap-2 min-w-0">
        <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
          <i data-lucide="history" class="w-4 h-4 text-cy"></i>
        </div>
        <h2 class="text-sm sm:text-lg font-bold truncate" id="modal-user-name">История обменов</h2>
      </div>
      <button onclick="closeHistoryModal()" class="w-8 h-8 rounded-md hover:bg-bg-soft text-txt-secondary hover:text-danger transition flex items-center justify-center flex-shrink-0 ml-2">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <div class="flex-1 overflow-auto">
      <table class="w-full text-sm" id="history-table">
        <thead class="sticky top-0 z-[1] bg-bg-card">
          <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
            <th class="px-5 py-3 font-medium">№ заявки</th>
            <th class="px-4 py-3 font-medium">Дата</th>
            <th class="px-4 py-3 font-medium">Отдаёт</th>
            <th class="px-4 py-3 font-medium">Получает</th>
            <th class="px-4 py-3 font-medium">Статус</th>
            <th class="px-4 py-3 font-medium text-right">Действие</th>
          </tr>
        </thead>
        <tbody id="history-body" class="divide-y divide-line"></tbody>
      </table>
    </div>
    <div class="px-4 sm:px-6 py-3 sm:py-4 border-t border-line flex justify-end">
      <button onclick="closeHistoryModal()" class="btn-ghost h-10 px-4 sm:px-5 rounded-lg text-sm font-medium flex items-center gap-2">
        <i data-lucide="x" class="w-4 h-4"></i>
        Закрыть
      </button>
    </div>
  </div>
</div>

<script>
  document.getElementById('role_toggle')?.addEventListener('change', function() {
    const isAdmin = this.checked;
    document.getElementById('role_hidden').value = isAdmin ? 'admin' : 'user';
    document.getElementById('role-label').textContent = isAdmin ? 'Администратор' : 'Пользователь';
  });

  document.getElementById('email_verified_toggle')?.addEventListener('change', function() {
    document.getElementById('email-verified-label').textContent = this.checked ? 'Подтверждён' : 'Не подтверждён';
  });

  const currencyLabelMap = {
    'USDT_TRC20':'USDT · TRC20','USDT_ERC20':'USDT · ERC20','USDT_BEP20':'USDT · BEP20',
    'USDC_TRC20':'USDC · TRC20','USDC_ERC20':'USDC · ERC20',
    'ETH':'ETH · ERC20','SOL':'SOL','BTC':'BTC',
    'RUB_SBP':'RUB · СБП','RUB_CASH':'RUB · Нал.','RUB_CARD':'RUB · Карта',
    'USD':'USD','RUB':'RUB','USDC':'USDC',
  };
  function curLabel(key) { return currencyLabelMap[key] || key.replace(/_/g,' · '); }

  const statusMap = {
    'new':        { cls: 'st-new',    text: 'Новая',       icon: 'clock' },
    'in_process': { cls: 'st-proc',   text: 'В обработке', icon: 'loader' },
    'success':    { cls: 'st-ok',     text: 'Успешно',     icon: 'check-circle-2' },
    'canceled':   { cls: 'st-cancel', text: 'Отменено',    icon: 'x-circle' }
  };

  async function showUserHistory(userId, username) {
    const modal = document.getElementById('history-modal');
    const tbody = document.getElementById('history-body');

    document.getElementById('modal-user-name').textContent = 'История обменов: ' + username;
    tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-txt-muted">Загрузка...</td></tr>';
    modal.classList.remove('hidden');

    try {
      const res    = await fetch('users.php?get_history=' + userId);
      const orders = await res.json();

      if (!orders.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-txt-muted">Заявок пока нет</td></tr>';
      } else {
        let html = '';
        orders.forEach(o => {
          const s = statusMap[o.status] || statusMap['new'];
          const d = new Date(o.created_at).toLocaleString('ru-RU');
          html += `
            <tr class="row-h transition" id="hrow-${o.id}">
              <td class="px-5 py-3 font-mono text-xs text-txt-secondary">${o.id}</td>
              <td class="px-4 py-3 text-txt-secondary whitespace-nowrap">${d}</td>
              <td class="px-4 py-3 whitespace-nowrap"><span class="font-medium">${parseFloat(o.amount_give).toLocaleString('ru-RU')}</span> <span class="text-xs text-txt-muted">${curLabel(o.give_currency)}</span></td>
              <td class="px-4 py-3 whitespace-nowrap"><span class="font-medium text-emr">${parseFloat(o.amount_get).toLocaleString('ru-RU')}</span> <span class="text-xs text-txt-muted">${curLabel(o.get_currency)}</span></td>
              <td class="px-4 py-3"><span class="st ${s.cls}" id="hbadge-${o.id}"><i data-lucide="${s.icon}" class="w-3 h-3"></i>${s.text}</span></td>
              <td class="px-4 py-3 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5">
                  <select id="hsel-${o.id}" class="input-d h-8 px-2 pr-7 rounded-md text-xs">
                    <option value="new"        ${o.status==='new'?'selected':''}>Новая</option>
                    <option value="in_process" ${o.status==='in_process'?'selected':''}>В обработке</option>
                    <option value="success"    ${o.status==='success'?'selected':''}>Успешно</option>
                    <option value="canceled"   ${o.status==='canceled'?'selected':''}>Отменено</option>
                  </select>
                  <button onclick="saveOrderStatus('${o.id}')" class="btn-cy h-8 px-2.5 rounded-md text-xs flex items-center gap-1" title="Сохранить статус">
                    <i data-lucide="check" class="w-3 h-3"></i>
                  </button>
                  <button onclick="showNotes('order','${o.id}','${o.id}')" class="btn-ghost h-8 px-2.5 rounded-md text-xs flex items-center gap-1 text-vi" title="Заметки">
                    <i data-lucide="notebook-pen" class="w-3 h-3"></i>
                  </button>
                  <button onclick="deleteHistoryOrder('${o.id}')" class="btn-danger h-8 px-2.5 rounded-md text-xs flex items-center gap-1" title="Удалить заявку">
                    <i data-lucide="trash-2" class="w-3 h-3"></i>
                  </button>
                </div>
              </td>
            </tr>`;
        });
        tbody.innerHTML = html;
      }
      lucide.createIcons();
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="6" class="p-10 text-center text-danger">Ошибка загрузки</td></tr>';
    }
  }

  async function deleteHistoryOrder(orderId) {
    if (!confirm('Удалить заявку ' + orderId + '? Это действие необратимо.')) return;
    const fd = new FormData();
    fd.append('delete_order_ajax', '1');
    fd.append('order_id', orderId);
    try {
      const res  = await fetch('users.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        const row = document.getElementById('hrow-' + orderId);
        if (row) row.remove();
      }
    } catch(e) {}
  }

  async function saveOrderStatus(orderId) {
    const sel    = document.getElementById('hsel-' + orderId);
    const badge  = document.getElementById('hbadge-' + orderId);
    const newSt  = sel.value;
    const fd     = new FormData();
    fd.append('update_order_status', '1');
    fd.append('order_id', orderId);
    fd.append('new_status', newSt);
    try {
      const res  = await fetch('users.php', { method: 'POST', body: fd });
      const data = await res.json();
      if (data.success) {
        const s = statusMap[newSt];
        badge.className = 'st ' + s.cls;
        badge.innerHTML = `<i data-lucide="${s.icon}" class="w-3 h-3"></i>${s.text}`;
        lucide.createIcons({ elements: [badge] });
      }
    } catch(e) {}
  }

  function closeHistoryModal() {
    document.getElementById('history-modal').classList.add('hidden');
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      closeHistoryModal();
      closeNotesModal();
    }
  });
</script>

<?php require_once __DIR__ . '/notes.php'; ?>

<?php require_once __DIR__ . '/footer.php'; ?>

</body>
</html>