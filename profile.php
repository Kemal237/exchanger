<?php
// profile.php — Личный кабинет

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$isAdmin = isAdmin();

// Отмена заявки
if (isset($_GET['cancel_order'])) {
    $order_id = $_GET['cancel_order'];
    $stmt = $pdo->prepare("UPDATE orders SET status = 'canceled', canceled_at = NOW() WHERE id = ? AND user_id = ? AND status = 'new'");
    $stmt->execute([$order_id, $user_id]);
    $_SESSION['toast'] = ['type' => 'success', 'message' => "Заявка $order_id отменена"];
    header('Location: profile.php');
    exit;
}

// Получаем заявки
$orders = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Ошибка загрузки заявок: " . $e->getMessage();
}

// Подсветка новой заявки
$highlight_order = $_SESSION['highlight_order'] ?? null;
unset($_SESSION['highlight_order']);

// Форма профиля
$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name     = trim($_POST['username'] ?? '');
    $new_email    = trim($_POST['email'] ?? '');
    $new_telegram = trim($_POST['telegram'] ?? '');
    $new_pass     = $_POST['new_password'] ?? '';
    $confirm      = $_POST['confirm_password'] ?? '';

    if (empty($new_name) || empty($new_email)) {
        $error = 'Заполните имя и email';
    } elseif ($new_pass && $new_pass !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (!empty($new_telegram) && (!str_starts_with($new_telegram, '@') || strlen($new_telegram) < 5)) {
        $error = 'Telegram должен начинаться с @ и содержать минимум 5 символов';
    } else {
        try {
            $params = [$new_name, $new_email];
            $query = "UPDATE users SET username = ?, email = ?";

            if ($new_pass) {
                $hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $query .= ", password = ?";
                $params[] = $hash;
            }

            $query .= ", telegram = ?";
            $params[] = $new_telegram;
            $query .= " WHERE id = ?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($query);
            $stmt->execute($params);

            $_SESSION['username'] = $new_name;
            $_SESSION['email']    = $new_email;
            $_SESSION['toast'] = ['type' => 'success', 'message' => 'Профиль успешно обновлён'];
            header('Location: profile.php');
            exit;
        } catch (Exception $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}

$stmt = $pdo->prepare("SELECT telegram, created_at FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$userRow = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$current_telegram = $userRow['telegram'] ?? '';
$user_created = $userRow['created_at'] ?? null;

// Статистика
$total_orders = count($orders);
$success_orders = count(array_filter($orders, fn($o) => ($o['status'] ?? '') === 'success'));
$active_orders = count(array_filter($orders, fn($o) => in_array($o['status'] ?? '', ['new', 'in_process'])));

$page_title = 'Личный кабинет — ' . SITE_NAME;
$current_page = 'profile.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once 'theme.php'; ?>
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

<?php require_once 'header.php'; ?>

<main class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <!-- Hero -->
  <section class="mb-8 sm:mb-10 fade-in">
    <div class="flex items-center gap-2 sm:gap-3 text-[11px] sm:text-xs text-txt-muted mb-3 sm:mb-4">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">Личный кабинет</span>
    </div>
    <div class="flex flex-wrap items-end justify-between gap-3 sm:gap-4">
      <div class="min-w-0">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight mb-2 break-words">
          Добро пожаловать, <span class="shimmer-text"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></span>
        </h1>
        <p class="text-xs sm:text-sm text-txt-muted flex items-center gap-2">
          <i data-lucide="calendar" class="w-4 h-4"></i>
          <?php if ($user_created): ?>
            С нами с <?= date('d.m.Y', strtotime($user_created)) ?>
          <?php else: ?>
            Ваш личный кабинет
          <?php endif; ?>
        </p>
      </div>
      <a href="index.php" class="btn-cy px-4 sm:px-5 h-10 sm:h-11 rounded-lg text-xs sm:text-sm font-semibold flex items-center gap-2 whitespace-nowrap">
        <i data-lucide="arrow-left-right" class="w-4 h-4"></i>
        Новый обмен
      </a>
    </div>
  </section>

  <!-- Stats -->
  <section class="grid grid-cols-3 gap-2 sm:gap-4 mb-8 sm:mb-10">
    <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="1">
      <div class="flex items-center justify-between mb-2">
        <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Всего заявок</span>
        <i data-lucide="file-text" class="w-4 h-4 text-cy"></i>
      </div>
      <div class="text-lg sm:text-2xl font-bold count-up" data-target="<?= $total_orders ?>">0</div>
    </div>
    <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="2">
      <div class="flex items-center justify-between mb-2">
        <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">Успешных</span>
        <i data-lucide="check-circle-2" class="w-4 h-4 text-emr"></i>
      </div>
      <div class="text-lg sm:text-2xl font-bold text-emr count-up" data-target="<?= $success_orders ?>">0</div>
    </div>
    <div class="gborder spot rounded-xl bg-bg-card p-3 sm:p-5 reveal" data-d="3">
      <div class="flex items-center justify-between mb-2">
        <span class="text-[10px] sm:text-xs text-txt-muted uppercase tracking-wider">В работе</span>
        <i data-lucide="clock" class="w-4 h-4 text-warn"></i>
      </div>
      <div class="text-lg sm:text-2xl font-bold text-warn count-up" data-target="<?= $active_orders ?>">0</div>
    </div>
  </section>

  <div class="grid lg:grid-cols-[380px,1fr] gap-4 sm:gap-6">

    <!-- Settings card -->
    <aside class="reveal" data-d="1">
      <div class="gborder spot rounded-2xl bg-bg-card p-4 sm:p-6 shadow-card lg:sticky lg:top-24">
        <div class="flex items-center gap-2 mb-5">
          <div class="w-8 h-8 rounded-lg bg-cy-soft border border-cy-border flex items-center justify-center">
            <i data-lucide="settings" class="w-4 h-4 text-cy"></i>
          </div>
          <h2 class="text-lg font-bold">Настройки профиля</h2>
        </div>

        <?php if ($error): ?>
          <div class="mb-4 px-3.5 py-2.5 rounded-lg bg-danger/10 border border-danger/30 text-xs text-danger flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span><?= htmlspecialchars($error) ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Логин</label>
            <input type="text" name="username" required
                   value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>"
                   class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>

          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Email</label>
            <input type="email" name="email" required
                   value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>"
                   class="input-d w-full h-10 px-3 rounded-lg text-sm">
          </div>

          <div>
            <label class="block text-xs font-medium text-txt-secondary mb-1.5 uppercase tracking-wider">Telegram</label>
            <input type="text" name="telegram" placeholder="@username"
                   value="<?= htmlspecialchars($current_telegram) ?>"
                   class="input-d w-full h-10 px-3 rounded-lg text-sm">
            <p class="text-[11px] text-txt-muted mt-1">Для уведомлений по заявкам</p>
          </div>

          <div class="pt-3 border-t border-line">
            <p class="text-[11px] text-txt-muted mb-3 uppercase tracking-wider">Смена пароля</p>

            <div class="space-y-3">
              <div>
                <label class="block text-xs font-medium text-txt-secondary mb-1.5">Новый пароль</label>
                <input type="password" name="new_password"
                       class="input-d w-full h-10 px-3 rounded-lg text-sm"
                       placeholder="Оставьте пустым, если не меняете">
              </div>
              <div>
                <label class="block text-xs font-medium text-txt-secondary mb-1.5">Повторите пароль</label>
                <input type="password" name="confirm_password"
                       class="input-d w-full h-10 px-3 rounded-lg text-sm"
                       placeholder="••••••••">
              </div>
            </div>
          </div>

          <button type="submit" class="btn-cy w-full h-11 rounded-lg text-sm font-semibold flex items-center justify-center gap-2 mt-2">
            <i data-lucide="save" class="w-4 h-4"></i>
            Сохранить изменения
          </button>
        </form>

        <?php if ($isAdmin): ?>
          <a href="admin/index.php" class="btn-ghost mt-4 w-full h-10 rounded-lg text-sm font-medium flex items-center justify-center gap-2" style="border-color: rgba(167,139,250,0.35); color: #A78BFA;">
            <i data-lucide="shield-check" class="w-4 h-4"></i>
            Админ-панель
          </a>
        <?php endif; ?>
      </div>
    </aside>

    <!-- Orders -->
    <section class="reveal" data-d="2">
      <div class="gborder rounded-2xl bg-bg-card shadow-card overflow-hidden">
        <div class="flex items-center justify-between px-4 sm:px-6 py-4 sm:py-5 border-b border-line">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center">
              <i data-lucide="list-ordered" class="w-4 h-4 text-vi"></i>
            </div>
            <h2 class="text-base sm:text-lg font-bold">История обмена</h2>
          </div>
          <span class="text-xs text-txt-muted"><?= $total_orders ?> <?= $total_orders === 1 ? 'заявка' : ($total_orders >= 2 && $total_orders <= 4 ? 'заявки' : 'заявок') ?></span>
        </div>

        <?php if (empty($orders)): ?>
          <div class="p-8 sm:p-12 text-center">
            <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-bg-soft border border-line mx-auto mb-4 flex items-center justify-center">
              <i data-lucide="inbox" class="w-6 h-6 sm:w-7 sm:h-7 text-txt-muted"></i>
            </div>
            <p class="text-txt-secondary mb-1">У вас пока нет заявок</p>
            <p class="text-xs text-txt-muted mb-5">Создайте первую прямо сейчас</p>
            <a href="index.php" class="btn-cy inline-flex items-center gap-2 px-5 h-11 rounded-lg text-sm font-semibold">
              <i data-lucide="plus" class="w-4 h-4"></i>
              Создать обмен
            </a>
          </div>
        <?php else: ?>
          <!-- Desktop / tablet: table -->
          <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-sm" id="orders-table">
              <thead>
                <tr class="text-left text-xs text-txt-muted uppercase tracking-wider bg-bg-soft/40">
                  <th class="px-6 py-3 font-medium">№</th>
                  <th class="px-4 py-3 font-medium">Дата</th>
                  <th class="px-4 py-3 font-medium">Отдаёте</th>
                  <th class="px-4 py-3 font-medium">Получаете</th>
                  <th class="px-4 py-3 font-medium">Статус</th>
                  <th class="px-4 py-3 font-medium text-right">Действия</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-line">
                <?php foreach ($orders as $order):
                  $isNew = $highlight_order && $order['id'] === $highlight_order;
                  $status = $order['status'] ?? 'new';
                  $statuses = [
                      'new'        => ['text' => 'Новая',       'cls' => 'st-new',    'icon' => 'clock'],
                      'in_process' => ['text' => 'В обработке', 'cls' => 'st-proc',   'icon' => 'loader'],
                      'success'    => ['text' => 'Успешно',     'cls' => 'st-ok',     'icon' => 'check-circle-2'],
                      'canceled'   => ['text' => 'Отменено',    'cls' => 'st-cancel', 'icon' => 'x-circle'],
                  ];
                  $s = $statuses[$status] ?? $statuses['new'];
                ?>
                  <tr id="order-<?= htmlspecialchars($order['id']) ?>" class="row-h transition <?= $isNew ? 'highlight-row' : '' ?>">
                    <td class="px-6 py-4 font-mono text-xs text-txt-secondary">#<?= htmlspecialchars($order['id']) ?></td>
                    <td class="px-4 py-4 text-txt-secondary whitespace-nowrap"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td class="px-4 py-4 font-medium whitespace-nowrap">
                      <?= number_format($order['amount_give'] ?? 0, 2, '.', ' ') ?>
                      <span class="text-xs text-txt-muted ml-1"><?= htmlspecialchars($order['give_currency']) ?></span>
                    </td>
                    <td class="px-4 py-4 font-medium text-emr whitespace-nowrap">
                      <?= number_format($order['amount_get'] ?? 0, 2, '.', ' ') ?>
                      <span class="text-xs text-txt-muted ml-1"><?= htmlspecialchars($order['get_currency']) ?></span>
                    </td>
                    <td class="px-4 py-4">
                      <span class="st <?= $s['cls'] ?>">
                        <i data-lucide="<?= $s['icon'] ?>" class="w-3 h-3"></i>
                        <?= $s['text'] ?>
                      </span>
                    </td>
                    <td class="px-4 py-4 text-right">
                      <?php if ($status === 'new'): ?>
                        <a href="?cancel_order=<?= htmlspecialchars($order['id']) ?>"
                           onclick="return confirm('Отменить заявку #<?= htmlspecialchars($order['id']) ?>?');"
                           class="btn-danger inline-flex items-center gap-1 px-3 h-8 rounded-md text-xs">
                          <i data-lucide="x" class="w-3.5 h-3.5"></i>
                          Отменить
                        </a>
                      <?php else: ?>
                        <span class="text-xs text-txt-muted">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Mobile: card list -->
          <div class="md:hidden divide-y divide-line" id="orders-table">
            <?php foreach ($orders as $order):
              $isNew = $highlight_order && $order['id'] === $highlight_order;
              $status = $order['status'] ?? 'new';
              $statuses = [
                  'new'        => ['text' => 'Новая',       'cls' => 'st-new',    'icon' => 'clock'],
                  'in_process' => ['text' => 'В обработке', 'cls' => 'st-proc',   'icon' => 'loader'],
                  'success'    => ['text' => 'Успешно',     'cls' => 'st-ok',     'icon' => 'check-circle-2'],
                  'canceled'   => ['text' => 'Отменено',    'cls' => 'st-cancel', 'icon' => 'x-circle'],
              ];
              $s = $statuses[$status] ?? $statuses['new'];
            ?>
              <div id="order-m-<?= htmlspecialchars($order['id']) ?>" class="p-4 <?= $isNew ? 'highlight-row' : '' ?>">
                <div class="flex items-start justify-between gap-2 mb-3">
                  <div class="min-w-0">
                    <div class="font-mono text-[11px] text-txt-secondary truncate">#<?= htmlspecialchars($order['id']) ?></div>
                    <div class="text-[11px] text-txt-muted mt-0.5"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></div>
                  </div>
                  <span class="st <?= $s['cls'] ?> flex-shrink-0">
                    <i data-lucide="<?= $s['icon'] ?>" class="w-3 h-3"></i>
                    <?= $s['text'] ?>
                  </span>
                </div>
                <div class="flex items-center justify-between gap-3 mb-3">
                  <div class="flex-1 min-w-0">
                    <div class="text-[10px] text-txt-muted uppercase tracking-wider mb-0.5">Отдаёте</div>
                    <div class="font-medium text-sm truncate">
                      <?= number_format($order['amount_give'] ?? 0, 2, '.', ' ') ?>
                      <span class="text-[11px] text-txt-muted"><?= htmlspecialchars($order['give_currency']) ?></span>
                    </div>
                  </div>
                  <i data-lucide="arrow-right" class="w-4 h-4 text-txt-muted flex-shrink-0"></i>
                  <div class="flex-1 min-w-0 text-right">
                    <div class="text-[10px] text-txt-muted uppercase tracking-wider mb-0.5">Получаете</div>
                    <div class="font-medium text-emr text-sm truncate">
                      <?= number_format($order['amount_get'] ?? 0, 2, '.', ' ') ?>
                      <span class="text-[11px] text-txt-muted"><?= htmlspecialchars($order['get_currency']) ?></span>
                    </div>
                  </div>
                </div>
                <?php if ($status === 'new'): ?>
                  <a href="?cancel_order=<?= htmlspecialchars($order['id']) ?>"
                     onclick="return confirm('Отменить заявку #<?= htmlspecialchars($order['id']) ?>?');"
                     class="btn-danger w-full inline-flex items-center justify-center gap-1 px-3 h-9 rounded-md text-xs">
                    <i data-lucide="x" class="w-3.5 h-3.5"></i>
                    Отменить заявку
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>

  </div>
</main>

<?php require_once 'footer.php'; ?>

<script>
  <?php if (isset($highlight_order) && $highlight_order): ?>
  window.addEventListener('load', function() {
    const row = document.getElementById('order-<?= htmlspecialchars($highlight_order) ?>');
    if (row) row.scrollIntoView({ behavior: 'smooth', block: 'center' });
  });
  <?php endif; ?>
</script>

</body>
</html>