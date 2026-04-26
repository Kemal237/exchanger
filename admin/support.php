<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$status_filter = $_GET['status'] ?? 'open';
if (!in_array($status_filter, ['open', 'answered', 'closed', 'all'])) $status_filter = 'open';

$where  = $status_filter === 'all' ? '' : 'WHERE t.status = ?';
$params = $status_filter === 'all' ? [] : [$status_filter];

$tickets      = [];
$messages_map = [];
$counts       = ['open' => 0, 'answered' => 0, 'closed' => 0, 'all' => 0];
$db_error     = null;

try {
    $stmt = $pdo->prepare("
        SELECT t.*, u.username AS user_name, u.email AS user_email, u.telegram AS user_telegram,
               (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) AS msg_count,
               (SELECT sender  FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_sender
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id
        $where
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute($params);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($tickets) {
        $ids = array_column($tickets, 'id');
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $sm  = $pdo->prepare("SELECT * FROM support_messages WHERE ticket_id IN ($ph) ORDER BY created_at ASC");
        $sm->execute($ids);
        foreach ($sm->fetchAll(PDO::FETCH_ASSOC) as $m) {
            $messages_map[$m['ticket_id']][] = $m;
        }
    }

    // Счётчики для табов
    foreach (['open', 'answered', 'closed'] as $s) {
        $c = $pdo->prepare("SELECT COUNT(*) FROM support_tickets WHERE status = ?");
        $c->execute([$s]);
        $counts[$s] = (int)$c->fetchColumn();
    }
    $counts['all'] = array_sum($counts);
} catch (PDOException $e) {
    $db_error = 'Таблицы поддержки не найдены. Выполните файл <b>support_tables.sql</b> в phpMyAdmin.';
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

$openTicketId  = $_GET['ticket'] ?? null;
$statusLabel   = ['open' => 'Открыт', 'answered' => 'Ответили', 'closed' => 'Закрыт'];
$statusClass   = ['open' => 'st-warn', 'answered' => 'st-ok', 'closed' => 'st-cancel'];

$page_title = 'Поддержка — Админ';
$admin_page = 'support.php';
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

<div class="aurora"><div class="ab ab-1"></div><div class="ab ab-2"></div><div class="ab ab-3"></div></div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php require_once __DIR__ . '/header.php'; ?>

<?php if ($toast): ?>
<div id="toast" class="fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg border text-sm font-medium flex items-center gap-2
  <?= $toast['type'] === 'success' ? 'bg-emr/10 border-emr/30 text-emr' : 'bg-danger/10 border-danger/30 text-danger' ?>">
  <i data-lucide="<?= $toast['type'] === 'success' ? 'check-circle-2' : 'x-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
  <?= htmlspecialchars($toast['message']) ?>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('toast'); if(t) t.remove(); }, 4000);</script>
<?php endif; ?>

<main class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <!-- Title -->
  <section class="mb-6 fade-in">
    <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-1">Поддержка</h1>
    <p class="text-xs sm:text-sm text-txt-muted">Обращения пользователей</p>
  </section>

  <!-- Status tabs -->
  <div class="flex flex-wrap gap-1.5 mb-5">
    <?php foreach (['open' => 'Открытые', 'answered' => 'Отвечены', 'closed' => 'Закрытые', 'all' => 'Все'] as $s => $label): ?>
      <a href="?status=<?= $s ?>"
         class="flex items-center gap-1.5 px-3 h-8 rounded-lg text-xs font-medium transition whitespace-nowrap
           <?= $status_filter === $s ? 'bg-cy-soft text-cy border border-cy-border' : 'text-txt-secondary hover:text-cy hover:bg-bg-soft border border-transparent' ?>">
        <?= $label ?>
        <span class="text-[10px] px-1.5 py-0.5 rounded-full <?= $status_filter === $s ? 'bg-cy/20' : 'bg-bg-soft' ?>">
          <?= $counts[$s] ?? 0 ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- DB error -->
  <?php if ($db_error): ?>
  <div class="gborder rounded-2xl bg-danger/5 border border-danger/20 p-5 flex items-start gap-3">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-danger flex-shrink-0 mt-0.5"></i>
    <div class="text-sm text-danger"><?= $db_error ?></div>
  </div>
  <?php endif; ?>

  <!-- Ticket list -->
  <?php if (!$db_error && empty($tickets)): ?>
  <div class="gborder rounded-2xl bg-bg-card p-10 text-center">
    <i data-lucide="inbox" class="w-10 h-10 text-txt-muted mx-auto mb-3"></i>
    <p class="text-txt-muted text-sm">Нет обращений в этом разделе</p>
  </div>
  <?php elseif (!$db_error): ?>
  <div class="space-y-3">
    <?php foreach ($tickets as $ticket):
      $tid      = $ticket['id'];
      $msgs     = $messages_map[$tid] ?? [];
      $isClosed = $ticket['status'] === 'closed';
      $isOpen   = ($openTicketId == $tid);
    ?>
    <div class="gborder rounded-2xl bg-bg-card overflow-hidden reveal" id="ticket-<?= $tid ?>">

      <!-- Ticket header -->
      <button type="button" onclick="toggleTicket(<?= $tid ?>)"
              class="w-full flex items-start sm:items-center gap-3 px-4 sm:px-5 py-4 hover:bg-bg-soft/50 transition-colors text-left">
        <div class="w-9 h-9 rounded-xl bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0 mt-0.5 sm:mt-0">
          <i data-lucide="message-square" class="w-4 h-4 text-cy"></i>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <span class="font-semibold text-sm"><?= htmlspecialchars($ticket['subject']) ?></span>
            <span class="st <?= $statusClass[$ticket['status']] ?? 'st-warn' ?> text-[10px]">
              <?= $statusLabel[$ticket['status']] ?>
            </span>
            <?php if ($ticket['last_sender'] === 'user' && $ticket['status'] === 'open'): ?>
            <span class="st st-warn text-[10px]">Ждёт ответа</span>
            <?php endif; ?>
          </div>
          <div class="text-[10px] sm:text-xs text-txt-muted flex flex-wrap items-center gap-2">
            <span>#<?= $tid ?></span>
            <span>·</span>
            <span class="font-medium text-txt-secondary"><?= htmlspecialchars($ticket['user_name']) ?></span>
            <span>·</span>
            <span><?= htmlspecialchars($ticket['user_email']) ?></span>
            <?php if ($ticket['user_telegram']): ?>
              <span>·</span>
              <span class="text-cy"><?= htmlspecialchars($ticket['user_telegram']) ?></span>
            <?php endif; ?>
            <span>·</span>
            <span><?= $ticket['msg_count'] ?> сообщ.</span>
            <span>·</span>
            <span><?= date('d.m.Y H:i', strtotime($ticket['updated_at'])) ?></span>
          </div>
        </div>
        <i data-lucide="chevron-down" id="chevron-t-<?= $tid ?>"
           class="w-4 h-4 text-txt-muted flex-shrink-0 transition-transform duration-200 mt-1 sm:mt-0 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
      </button>

      <!-- Chat body -->
      <div id="tbody-<?= $tid ?>" class="<?= $isOpen ? '' : 'hidden' ?> border-t border-line/50">

        <!-- Messages -->
        <div class="px-4 sm:px-5 py-4 space-y-3 max-h-[450px] overflow-y-auto" id="msgs-<?= $tid ?>">
          <?php if (empty($msgs)): ?>
            <p class="text-xs text-txt-muted text-center py-4">Нет сообщений</p>
          <?php else: ?>
            <?php foreach ($msgs as $m): ?>
            <div class="flex <?= $m['sender'] === 'admin' ? 'justify-end' : 'justify-start' ?>">
              <div class="max-w-[80%]">
                <?php if ($m['sender'] === 'user'): ?>
                <div class="flex items-center gap-1.5 mb-1">
                  <div class="w-5 h-5 rounded-full bg-cy/20 flex items-center justify-center">
                    <i data-lucide="user" class="w-3 h-3 text-cy"></i>
                  </div>
                  <span class="text-[10px] text-cy font-medium"><?= htmlspecialchars($ticket['user_name']) ?></span>
                </div>
                <?php else: ?>
                <div class="flex items-center justify-end gap-1.5 mb-1">
                  <span class="text-[10px] text-vi font-medium">Поддержка</span>
                  <div class="w-5 h-5 rounded-full bg-vi/20 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-3 h-3 text-vi"></i>
                  </div>
                </div>
                <?php endif; ?>
                <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed
                  <?= $m['sender'] === 'user'
                      ? 'bg-bg-soft border border-line text-txt-primary rounded-tl-sm'
                      : 'bg-vi/10 border border-vi/20 text-txt-primary rounded-tr-sm' ?>">
                  <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
                <div class="text-[10px] text-txt-muted mt-1 <?= $m['sender'] === 'admin' ? 'text-right' : 'text-left' ?>">
                  <?= date('d.m.Y H:i', strtotime($m['created_at'])) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="border-t border-line/50 px-4 sm:px-5 py-3 space-y-3">

          <?php if (!$isClosed): ?>
          <!-- Reply form -->
          <form method="POST" action="support-action.php" class="flex gap-2 items-end">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="ticket_id" value="<?= $tid ?>">
            <textarea name="message" rows="2" required placeholder="Ответ пользователю…"
                      class="flex-1 bg-bg-soft border border-line rounded-xl px-3 py-2 text-sm text-txt-primary placeholder-txt-muted resize-none focus:outline-none focus:border-cy-border transition"></textarea>
            <button type="submit" class="btn-cy flex items-center gap-1.5 px-3 h-9 rounded-xl text-xs font-semibold flex-shrink-0">
              <i data-lucide="send" class="w-3.5 h-3.5"></i> Ответить
            </button>
          </form>
          <?php endif; ?>

          <!-- Status actions -->
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs text-txt-muted">Статус:</span>
            <?php if ($ticket['status'] !== 'open'): ?>
            <form method="POST" action="support-action.php" class="inline">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="ticket_id" value="<?= $tid ?>">
              <input type="hidden" name="new_status" value="open">
              <button type="submit" class="px-2.5 h-7 rounded-lg border border-line text-xs text-txt-secondary hover:text-cy hover:border-cy-border transition">
                Открыть заново
              </button>
            </form>
            <?php endif; ?>
            <?php if (!$isClosed): ?>
            <form method="POST" action="support-action.php" class="inline">
              <input type="hidden" name="action" value="status">
              <input type="hidden" name="ticket_id" value="<?= $tid ?>">
              <input type="hidden" name="new_status" value="closed">
              <button type="submit" class="px-2.5 h-7 rounded-lg border border-danger/30 text-xs text-danger hover:bg-danger/10 transition">
                <i data-lucide="lock" class="w-3 h-3 inline-block mr-1 align-middle"></i> Закрыть тикет
              </button>
            </form>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/footer.php'; ?>

<script>
function toggleTicket(id) {
  const body    = document.getElementById('tbody-' + id);
  const chevron = document.getElementById('chevron-t-' + id);
  const isHidden = body.classList.contains('hidden');
  body.classList.toggle('hidden', !isHidden);
  if (chevron) chevron.classList.toggle('rotate-180', isHidden);
  if (isHidden) {
    const msgs = document.getElementById('msgs-' + id);
    if (msgs) setTimeout(() => { msgs.scrollTop = msgs.scrollHeight; }, 50);
  }
}

<?php if ($openTicketId): ?>
(function() {
  const el = document.getElementById('tbody-<?= (int)$openTicketId ?>');
  if (el && el.classList.contains('hidden')) toggleTicket(<?= (int)$openTicketId ?>);
  const card = document.getElementById('ticket-<?= (int)$openTicketId ?>');
  if (card) setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
})();
<?php endif; ?>
</script>

</body>
</html>
