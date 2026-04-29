<?php
require_once 'config.php';
require_once 'db.php';
require_once 'auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$tickets      = [];
$messages_map = [];
$db_error     = null;

try {
    $stmt = $pdo->prepare("
        SELECT t.*,
            (SELECT COUNT(*) FROM support_messages WHERE ticket_id = t.id) AS msg_count,
            (SELECT message FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_msg,
            (SELECT sender  FROM support_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) AS last_sender
        FROM support_tickets t
        WHERE t.user_id = ?
        ORDER BY t.updated_at DESC
    ");
    $stmt->execute([$user_id]);
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
} catch (PDOException $e) {
    $db_error = 'Таблицы поддержки не найдены. Выполните файл <b>support_tables.sql</b> в phpMyAdmin.';
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);

$openTicketId = $_GET['ticket'] ?? null;

$statusLabel = ['open' => 'Открыт', 'answered' => 'Ответили', 'closed' => 'Закрыт'];
$statusClass  = ['open' => 'st-warn', 'answered' => 'st-ok', 'closed' => 'st-cancel'];

$page_title   = 'Поддержка — ' . SITE_NAME;
$current_page = 'support.php';
?>
<!DOCTYPE html>
<html lang="ru" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title) ?></title>
  <?php require_once 'theme.php'; ?>
</head>
<body class="bg-bg-base text-txt-primary min-h-screen relative overflow-x-hidden">

<div class="aurora"><div class="ab ab-1"></div><div class="ab ab-2"></div><div class="ab ab-3"></div></div>
<div class="grid-bg"></div>
<canvas id="particles" class="fixed inset-0 z-0 pointer-events-none"></canvas>

<?php require_once 'header.php'; ?>

<?php if ($toast): ?>
<div id="toast" class="fixed top-4 right-4 z-50 max-w-sm px-4 py-3 rounded-xl shadow-lg border text-sm font-medium flex items-center gap-2
  <?= $toast['type'] === 'success' ? 'bg-emr/10 border-emr/30 text-emr' : 'bg-danger/10 border-danger/30 text-danger' ?>">
  <i data-lucide="<?= $toast['type'] === 'success' ? 'check-circle-2' : 'x-circle' ?>" class="w-4 h-4 flex-shrink-0"></i>
  <?= htmlspecialchars($toast['message']) ?>
</div>
<script>setTimeout(()=>{ const t=document.getElementById('toast'); if(t) t.remove(); }, 4000);</script>
<?php endif; ?>

<main class="relative z-10 max-w-4xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

  <!-- Breadcrumb + title -->
  <section class="mb-6 sm:mb-8 fade-in">
    <div class="flex items-center gap-2 text-[11px] sm:text-xs text-txt-muted mb-3">
      <a href="index.php" class="hover:text-cy transition">Главная</a>
      <i data-lucide="chevron-right" class="w-3 h-3"></i>
      <span class="text-txt-secondary">Поддержка</span>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mb-1">
          Техническая <span class="shimmer-text">поддержка</span>
        </h1>
        <p class="text-xs sm:text-sm text-txt-muted">Ответим в течение нескольких часов</p>
      </div>
      <button onclick="openCreateModal()" class="btn-cy flex items-center gap-2 px-4 h-10 rounded-xl text-sm font-semibold flex-shrink-0">
        <i data-lucide="plus" class="w-4 h-4"></i>
        Новое обращение
      </button>
    </div>
  </section>

  <!-- DB error -->
  <?php if ($db_error): ?>
  <div class="gborder rounded-2xl bg-danger/5 border border-danger/20 p-5 flex items-start gap-3 reveal">
    <i data-lucide="alert-triangle" class="w-5 h-5 text-danger flex-shrink-0 mt-0.5"></i>
    <div class="text-sm text-danger"><?= $db_error ?></div>
  </div>
  <?php endif; ?>

  <!-- Ticket list -->
  <?php if (!$db_error && empty($tickets)): ?>
  <div class="gborder rounded-2xl bg-bg-card p-10 text-center reveal">
    <div class="w-14 h-14 rounded-2xl bg-cy-soft border border-cy-border flex items-center justify-center mx-auto mb-4">
      <i data-lucide="message-circle" class="w-7 h-7 text-cy"></i>
    </div>
    <p class="font-semibold mb-1">Обращений пока нет</p>
    <p class="text-sm text-txt-muted mb-5">Создайте первое обращение — мы поможем разобраться</p>
    <button onclick="openCreateModal()" class="btn-cy inline-flex items-center gap-2 px-5 h-10 rounded-xl text-sm font-semibold">
      <i data-lucide="plus" class="w-4 h-4"></i> Создать обращение
    </button>
  </div>
  <?php elseif (!$db_error): ?>
  <div class="space-y-3 reveal">
    <?php foreach ($tickets as $ticket):
      $tid      = $ticket['id'];
      $msgs     = $messages_map[$tid] ?? [];
      $isClosed = $ticket['status'] === 'closed';
      $isOpen   = ($openTicketId == $tid);
    ?>
    <div class="gborder rounded-2xl bg-bg-card overflow-hidden" id="ticket-<?= $tid ?>">

      <!-- Ticket header -->
      <button type="button" onclick="toggleTicket(<?= $tid ?>)"
              class="w-full flex items-center gap-3 px-4 sm:px-5 py-4 hover:bg-bg-soft/50 transition-colors text-left">
        <div class="w-9 h-9 rounded-xl bg-cy-soft border border-cy-border flex items-center justify-center flex-shrink-0">
          <i data-lucide="message-square" class="w-4 h-4 text-cy"></i>
        </div>
        <div class="min-w-0 flex-1">
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-semibold text-sm truncate"><?= htmlspecialchars($ticket['subject']) ?></span>
            <span class="st <?= $statusClass[$ticket['status']] ?? 'st-warn' ?> flex-shrink-0 text-[10px]" id="status-badge-<?= $tid ?>">
              <?= $statusLabel[$ticket['status']] ?? $ticket['status'] ?>
            </span>
          </div>
          <div class="text-[10px] sm:text-xs text-txt-muted mt-0.5 flex items-center gap-2">
            <span>#<?= $tid ?></span>
            <span>·</span>
            <span><?= $ticket['msg_count'] ?> сообщ.</span>
            <span>·</span>
            <span><?= date('d.m.Y H:i', strtotime($ticket['updated_at'])) ?></span>
          </div>
        </div>
        <i data-lucide="chevron-down" id="chevron-t-<?= $tid ?>"
           class="w-4 h-4 text-txt-muted flex-shrink-0 transition-transform duration-200 <?= $isOpen ? 'rotate-180' : '' ?>"></i>
      </button>

      <!-- Chat body -->
      <div id="tbody-<?= $tid ?>" class="<?= $isOpen ? '' : 'hidden' ?> border-t border-line/50">

        <!-- Messages -->
        <div class="px-4 sm:px-5 py-4 space-y-3 max-h-[400px] overflow-y-auto" id="msgs-<?= $tid ?>">
          <?php if (empty($msgs)): ?>
            <p class="text-xs text-txt-muted text-center py-4">Нет сообщений</p>
          <?php else: ?>
            <?php foreach ($msgs as $m): ?>
            <div class="flex <?= $m['sender'] === 'user' ? 'justify-end' : 'justify-start' ?>">
              <div class="max-w-[80%]">
                <?php if ($m['sender'] === 'admin'): ?>
                <div class="flex items-center gap-1.5 mb-1">
                  <div class="w-5 h-5 rounded-full bg-vi/20 flex items-center justify-center">
                    <i data-lucide="shield-check" class="w-3 h-3 text-vi"></i>
                  </div>
                  <span class="text-[10px] text-vi font-medium">Поддержка</span>
                </div>
                <?php endif; ?>
                <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed
                  <?= $m['sender'] === 'user'
                      ? 'bg-cy/15 border border-cy/20 text-txt-primary rounded-tr-sm'
                      : 'bg-bg-soft border border-line text-txt-primary rounded-tl-sm' ?>">
                  <?= nl2br(htmlspecialchars($m['message'])) ?>
                </div>
                <div class="text-[10px] text-txt-muted mt-1 <?= $m['sender'] === 'user' ? 'text-right' : 'text-left' ?>">
                  <?= date('d.m H:i', strtotime($m['created_at'])) ?>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Reply form -->
        <div id="reply-form-<?= $tid ?>" class="border-t border-line/50 px-4 sm:px-5 py-3<?= $isClosed ? ' hidden' : '' ?>">
          <form method="POST" action="support-action.php" class="flex gap-2 items-end reply-form">
            <input type="hidden" name="action" value="reply">
            <input type="hidden" name="ticket_id" value="<?= $tid ?>">
            <textarea name="message" rows="2" required placeholder="Ваш ответ…"
                      class="flex-1 bg-bg-soft border border-line rounded-xl px-3 py-2 text-sm text-txt-primary placeholder-txt-muted resize-none focus:outline-none focus:border-cy-border transition"></textarea>
            <button type="submit" class="btn-cy flex items-center gap-1.5 px-3 h-9 rounded-xl text-xs font-semibold flex-shrink-0">
              <i data-lucide="send" class="w-3.5 h-3.5"></i> Отправить
            </button>
          </form>
        </div>
        <div id="reply-closed-<?= $tid ?>" class="border-t border-line/50 px-4 sm:px-5 py-3 text-center text-xs text-txt-muted<?= $isClosed ? '' : ' hidden' ?>">
          <i data-lucide="lock" class="w-3.5 h-3.5 inline-block mr-1 align-middle"></i>
          Обращение закрыто
        </div>

      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

</main>

<!-- Create ticket modal -->
<div id="create-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCreateModal()"></div>
  <div class="relative gborder rounded-2xl bg-bg-card w-full max-w-lg shadow-2xl">
    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
      <h2 class="font-bold text-base">Новое обращение</h2>
      <button onclick="closeCreateModal()" class="w-8 h-8 rounded-lg hover:bg-bg-soft flex items-center justify-center text-txt-muted hover:text-txt-primary transition">
        <i data-lucide="x" class="w-4 h-4"></i>
      </button>
    </div>
    <form method="POST" action="support-action.php" class="p-5">
      <input type="hidden" name="action" value="create">
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5">Тема обращения</label>
          <input type="text" name="subject" required maxlength="255" placeholder="Кратко опишите проблему"
                 class="w-full bg-bg-soft border border-line rounded-xl px-3.5 h-11 text-sm text-txt-primary placeholder-txt-muted focus:outline-none focus:border-cy-border transition">
        </div>
        <div>
          <label class="block text-xs font-medium text-txt-secondary mb-1.5">Сообщение</label>
          <textarea name="message" required rows="7" placeholder="Подробно опишите вашу проблему или вопрос…"
                    class="w-full bg-bg-soft border border-line rounded-xl px-3.5 py-3 text-sm text-txt-primary placeholder-txt-muted resize-none focus:outline-none focus:border-cy-border transition"></textarea>
        </div>
      </div>
      <div class="flex gap-3 mt-4">
        <button type="button" onclick="closeCreateModal()"
                class="flex-1 h-11 rounded-xl border border-line text-sm font-medium text-txt-secondary hover:bg-bg-soft transition">
          Отмена
        </button>
        <button type="submit" class="flex-1 btn-cy h-11 rounded-xl text-sm font-semibold flex items-center justify-center gap-2">
          <i data-lucide="send" class="w-4 h-4"></i> Отправить
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once 'footer.php'; ?>

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

function openCreateModal() {
  document.getElementById('create-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeCreateModal() {
  document.getElementById('create-modal').classList.add('hidden');
  document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeCreateModal(); });

<?php if ($openTicketId): ?>
(function() {
  const el = document.getElementById('tbody-<?= (int)$openTicketId ?>');
  if (el && el.classList.contains('hidden')) toggleTicket(<?= (int)$openTicketId ?>);
  const card = document.getElementById('ticket-<?= (int)$openTicketId ?>');
  if (card) setTimeout(() => card.scrollIntoView({ behavior: 'smooth', block: 'start' }), 100);
})();
<?php endif; ?>

// ── Auto-polling ──────────────────────────────────────────────
(function() {
  const lastId = {};
  <?php foreach ($tickets as $t):
    $tid = $t['id'];
    $msgs = $messages_map[$tid] ?? [];
    $lid  = !empty($msgs) ? (int)end($msgs)['id'] : 0;
  ?>
  lastId[<?= $tid ?>] = <?= $lid ?>;
  <?php endforeach; ?>

  const statusLabels  = {open:'Открыт', answered:'Ответили', closed:'Закрыт'};
  const statusClasses = {open:'st-warn', answered:'st-ok',    closed:'st-cancel'};

  function fmtTime(s) {
    if (!s) return '';
    const p = s.split(/[\s\-:]/);
    return p[2] + '.' + p[1] + ' ' + p[3] + ':' + p[4];
  }
  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }
  function buildMsg(m) {
    const isUser = m.sender === 'user';
    const w = document.createElement('div');
    w.className = 'flex ' + (isUser ? 'justify-end' : 'justify-start');
    let h = '<div class="max-w-[80%]">';
    if (!isUser) h += '<div class="flex items-center gap-1.5 mb-1"><div class="w-5 h-5 rounded-full bg-vi/20 flex items-center justify-center"><i data-lucide="shield-check" class="w-3 h-3 text-vi"></i></div><span class="text-[10px] text-vi font-medium">Поддержка</span></div>';
    h += '<div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed '
       + (isUser ? 'bg-cy/15 border border-cy/20 text-txt-primary rounded-tr-sm'
                 : 'bg-bg-soft border border-line text-txt-primary rounded-tl-sm')
       + '">' + esc(m.message).replace(/\n/g, '<br>') + '</div>';
    h += '<div class="text-[10px] text-txt-muted mt-1 ' + (isUser ? 'text-right' : 'text-left') + '">' + fmtTime(m.created_at) + '</div></div>';
    w.innerHTML = h;
    return w;
  }

  function applyStatus(tid, status) {
    const badge = document.getElementById('status-badge-' + tid);
    if (badge) {
      badge.textContent = statusLabels[status] || status;
      badge.className   = 'st ' + (statusClasses[status] || 'st-warn') + ' flex-shrink-0 text-[10px]';
    }
    if (status === 'closed') {
      const fEl = document.getElementById('reply-form-'   + tid);
      const cEl = document.getElementById('reply-closed-' + tid);
      if (fEl) fEl.classList.add('hidden');
      if (cEl) cEl.classList.remove('hidden');
    }
  }

  function poll(tid) {
    fetch('support-poll.php?ticket_id=' + tid + '&last_id=' + (lastId[tid] || 0))
      .then(r => r.ok ? r.json() : null)
      .then(data => {
        if (!data || data.error) return;
        const c = document.getElementById('msgs-' + tid);
        if (c && data.messages && data.messages.length) {
          const atBottom = c.scrollHeight - c.scrollTop <= c.clientHeight + 60;
          data.messages.forEach(m => {
            c.appendChild(buildMsg(m));
            if (parseInt(m.id) > (lastId[tid] || 0)) lastId[tid] = parseInt(m.id);
          });
          if (atBottom) c.scrollTop = c.scrollHeight;
          if (window.lucide) lucide.createIcons();
        }
        if (data.status) applyStatus(tid, data.status);
      })
      .catch(() => {});
  }

  function getOpen() {
    return Array.from(document.querySelectorAll('[id^="tbody-"]'))
      .filter(el => !el.classList.contains('hidden'))
      .map(el => parseInt(el.id.slice(6)));
  }

  setInterval(() => getOpen().forEach(id => poll(id)), 5000);

  // AJAX reply
  document.querySelectorAll('.reply-form').forEach(form => {
    form.addEventListener('submit', e => {
      e.preventDefault();
      const ta  = form.querySelector('textarea[name="message"]');
      const btn = form.querySelector('button[type="submit"]');
      const tid = parseInt(form.querySelector('input[name="ticket_id"]').value);
      if (!ta.value.trim()) return;
      btn.disabled = true;
      fetch('support-action.php', {
        method: 'POST',
        body: new FormData(form),
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(d => { if (d.success) { ta.value = ''; poll(tid); } })
      .catch(() => {})
      .finally(() => { btn.disabled = false; });
    });
  });
})();
</script>

</body>
</html>
