<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    if (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        echo json_encode(['error' => 'Forbidden']);
    }
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

$upload_dir = __DIR__ . '/../uploads/admin-notes/';

// ── File download ──────────────────────────────────────────────────
if (isset($_GET['file'])) {
    $file_id = (int)$_GET['file'];
    if (!$file_id) { http_response_code(404); exit; }

    $stmt = $pdo->prepare("SELECT filename, original_name FROM admin_note_files WHERE id = ?");
    $stmt->execute([$file_id]);
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$file) { http_response_code(404); exit; }

    $path = $upload_dir . $file['filename'];
    if (!file_exists($path)) { http_response_code(404); exit; }

    $mime = mime_content_type($path) ?: 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="' . rawurlencode($file['original_name']) . '"');
    header('Content-Length: ' . filesize($path));
    readfile($path);
    exit;
}

// ── JSON API ───────────────────────────────────────────────────────
if (isset($_GET['entity_type']) || isset($_POST['action'])) {
    header('Content-Type: application/json');
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $entity_type = $_GET['entity_type'] ?? '';
        $entity_id   = trim($_GET['entity_id'] ?? '');

        if (!in_array($entity_type, ['user', 'order']) || $entity_id === '') {
            echo json_encode(['error' => 'Invalid params']); exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM admin_notes WHERE entity_type = ? AND entity_id = ? ORDER BY created_at DESC");
        $stmt->execute([$entity_type, $entity_id]);
        $notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($notes as &$note) {
            $fs = $pdo->prepare("SELECT id, filename, original_name FROM admin_note_files WHERE note_id = ?");
            $fs->execute([$note['id']]);
            $note['files'] = $fs->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode($notes);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $note_id = (int)($_POST['note_id'] ?? 0);
            $fs = $pdo->prepare("SELECT filename FROM admin_note_files WHERE note_id = ?");
            $fs->execute([$note_id]);
            foreach ($fs->fetchAll(PDO::FETCH_ASSOC) as $f) {
                $p = $upload_dir . $f['filename'];
                if (file_exists($p)) unlink($p);
            }
            $pdo->prepare("DELETE FROM admin_notes WHERE id = ?")->execute([$note_id]);
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'add') {
            $entity_type = $_POST['entity_type'] ?? '';
            $entity_id   = trim($_POST['entity_id'] ?? '');
            $note_text   = trim($_POST['note_text'] ?? '');
            $admin_name  = $_SESSION['username'] ?? 'Администратор';

            if (!in_array($entity_type, ['user', 'order']) || $entity_id === '') {
                echo json_encode(['error' => 'Invalid params']); exit;
            }

            $has_text  = $note_text !== '';
            $has_files = !empty($_FILES['files']['name'][0]);

            if (!$has_text && !$has_files) {
                echo json_encode(['error' => 'Добавьте текст или прикрепите файл']); exit;
            }

            $stmt = $pdo->prepare("INSERT INTO admin_notes (entity_type, entity_id, admin_name, note_text) VALUES (?, ?, ?, ?)");
            $stmt->execute([$entity_type, $entity_id, $admin_name, $note_text]);
            $note_id = (int)$pdo->lastInsertId();

            $uploaded = [];
            if ($has_files) {
                foreach ($_FILES['files']['name'] as $i => $orig) {
                    if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK || !$orig) continue;
                    $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
                    $fn  = bin2hex(random_bytes(16)) . ($ext ? '.' . $ext : '');
                    if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $upload_dir . $fn)) {
                        $pdo->prepare("INSERT INTO admin_note_files (note_id, filename, original_name) VALUES (?, ?, ?)")
                            ->execute([$note_id, $fn, $orig]);
                        $fid = (int)$pdo->lastInsertId();
                        $uploaded[] = ['id' => $fid, 'filename' => $fn, 'original_name' => $orig];
                    }
                }
            }

            echo json_encode([
                'success' => true,
                'note'    => [
                    'id'         => $note_id,
                    'note_text'  => $note_text,
                    'created_at' => date('Y-m-d H:i:s'),
                    'admin_name' => $admin_name,
                    'files'      => $uploaded,
                ],
            ]);
            exit;
        }
    }

    echo json_encode(['error' => 'Unknown action']);
    exit;
}
?>
<!-- Notes Modal (shared: users + orders) -->
<div id="notes-modal" class="hidden fixed inset-0 z-[60] bg-black/75 backdrop-blur-sm flex items-center justify-center p-2 sm:p-4" onclick="closeNotesModal()">
  <div class="gborder rounded-2xl bg-bg-card shadow-card w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden" onclick="event.stopPropagation()">

      <!-- Header -->
      <div class="flex items-center justify-between px-4 sm:px-6 py-3 sm:py-4 border-b border-line flex-shrink-0">
        <div class="flex items-center gap-2 min-w-0">
          <div class="w-8 h-8 rounded-lg bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
            <i data-lucide="notebook-pen" class="w-4 h-4 text-vi"></i>
          </div>
          <h2 class="text-sm sm:text-base font-bold truncate" id="notes-modal-title">Заметки</h2>
        </div>
        <button onclick="closeNotesModal()" class="w-8 h-8 rounded-md hover:bg-bg-soft text-txt-muted hover:text-danger transition flex items-center justify-center flex-shrink-0 ml-2">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <!-- Notes list -->
      <div class="flex-1 overflow-y-auto px-4 sm:px-6 py-4 space-y-3 min-h-0" id="notes-list">
        <div class="flex flex-col items-center justify-center gap-2 py-10 text-txt-muted">
          <i data-lucide="loader" class="w-6 h-6 animate-spin opacity-50"></i>
        </div>
      </div>

      <!-- Add note -->
      <div class="border-t border-line px-4 sm:px-6 py-4 flex-shrink-0 bg-bg-soft/30">
        <div id="notes-error" class="hidden mb-3 px-3 py-2 rounded-lg bg-danger/10 border border-danger/30 text-xs text-danger flex items-center gap-1.5">
          <i data-lucide="alert-circle" class="w-3.5 h-3.5 flex-shrink-0"></i>
          <span id="notes-error-text"></span>
        </div>
        <textarea id="notes-textarea" rows="3"
                  placeholder="Введите заметку..."
                  class="input-d w-full px-3 py-2.5 rounded-lg text-sm resize-none mb-3"></textarea>
        <div class="flex items-center gap-2">
          <label class="flex-1 flex items-center gap-2 h-9 px-3 rounded-lg border border-dashed border-line hover:border-cy-border bg-bg-card cursor-pointer transition text-xs text-txt-muted hover:text-cy min-w-0">
            <i data-lucide="paperclip" class="w-3.5 h-3.5 flex-shrink-0"></i>
            <span id="notes-file-label" class="truncate">Прикрепить файлы</span>
            <input type="file" id="notes-files" multiple class="hidden">
          </label>
          <button onclick="submitNote()" id="notes-submit"
                  class="btn-cy h-9 px-4 rounded-lg text-sm font-medium flex items-center gap-2 flex-shrink-0">
            <i data-lucide="send" class="w-3.5 h-3.5"></i>
            Добавить
          </button>
        </div>
      </div>
    </div>
</div>

<script>
let _nType = '', _nId = 0;

function showNotes(type, id, name) {
  _nType = type; _nId = id;
  document.getElementById('notes-modal-title').textContent = 'Заметки: ' + name;
  document.getElementById('notes-textarea').value = '';
  document.getElementById('notes-files').value = '';
  document.getElementById('notes-file-label').textContent = 'Прикрепить файлы';
  document.getElementById('notes-error').classList.add('hidden');
  document.getElementById('notes-modal').classList.remove('hidden');
  loadNotes();
}

function closeNotesModal() {
  document.getElementById('notes-modal').classList.add('hidden');
}

async function loadNotes() {
  const list = document.getElementById('notes-list');
  list.innerHTML = '<div class="flex flex-col items-center justify-center gap-2 py-10 text-txt-muted"><i data-lucide="loader" class="w-6 h-6 opacity-50"></i></div>';
  lucide.createIcons();
  try {
    const res  = await fetch('notes.php?entity_type=' + _nType + '&entity_id=' + _nId);
    const data = await res.json();
    renderNotes(data);
  } catch(e) {
    list.innerHTML = '<div class="text-center text-danger py-8 text-sm">Ошибка загрузки</div>';
  }
}

function renderNotes(notes) {
  const list = document.getElementById('notes-list');
  if (!Array.isArray(notes) || !notes.length) {
    list.innerHTML = `
      <div class="flex flex-col items-center justify-center gap-2 py-10">
        <i data-lucide="notebook" class="w-10 h-10 text-txt-muted opacity-30"></i>
        <p class="text-sm text-txt-muted">Заметок пока нет</p>
      </div>`;
    lucide.createIcons();
    return;
  }

  list.innerHTML = notes.map(n => {
    const d     = new Date(n.created_at).toLocaleString('ru-RU');
    const text  = n.note_text
      ? `<p class="text-sm text-txt-primary whitespace-pre-wrap mt-2">${esc(n.note_text)}</p>`
      : '';
    const files = n.files && n.files.length
      ? `<div class="mt-2.5 flex flex-wrap gap-1.5">
           ${n.files.map(f => `
             <a href="notes.php?file=${f.id}" target="_blank"
                class="inline-flex items-center gap-1 px-2 h-7 rounded-md bg-bg-soft border border-line text-xs text-txt-secondary hover:text-cy hover:border-cy-border transition" title="${esc(f.original_name)}">
               <i data-lucide="paperclip" class="w-3 h-3 flex-shrink-0"></i>
               <span class="max-w-[140px] truncate">${esc(f.original_name)}</span>
             </a>`).join('')}
         </div>`
      : '';

    return `
      <div class="rounded-xl p-3 sm:p-4 bg-bg-soft border border-line" data-note-id="${n.id}">
        <div class="flex items-center justify-between gap-2">
          <div class="flex items-center gap-2 min-w-0">
            <div class="w-6 h-6 rounded-full bg-vi-soft border border-vi/30 flex items-center justify-center flex-shrink-0">
              <i data-lucide="user" class="w-3 h-3 text-vi"></i>
            </div>
            <span class="text-xs font-medium text-txt-secondary truncate">${esc(n.admin_name || 'Администратор')}</span>
            <span class="text-[11px] text-txt-muted whitespace-nowrap">${d}</span>
          </div>
          <button onclick="deleteNote(${n.id})"
                  class="w-6 h-6 rounded-md hover:bg-danger/10 text-txt-muted hover:text-danger transition flex items-center justify-center flex-shrink-0">
            <i data-lucide="trash-2" class="w-3 h-3"></i>
          </button>
        </div>
        ${text}
        ${files}
      </div>`;
  }).join('');
  lucide.createIcons();
}

function esc(s) {
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function deleteNote(noteId) {
  if (!confirm('Удалить заметку?')) return;
  const fd = new FormData();
  fd.append('action', 'delete');
  fd.append('note_id', noteId);
  await fetch('notes.php', { method: 'POST', body: fd });
  loadNotes();
}

async function submitNote() {
  const text  = document.getElementById('notes-textarea').value.trim();
  const files = document.getElementById('notes-files').files;
  const errEl = document.getElementById('notes-error');

  if (!text && !files.length) {
    document.getElementById('notes-error-text').textContent = 'Добавьте текст или прикрепите файл';
    errEl.classList.remove('hidden');
    return;
  }
  errEl.classList.add('hidden');

  const btn = document.getElementById('notes-submit');
  btn.disabled = true;

  const fd = new FormData();
  fd.append('action', 'add');
  fd.append('entity_type', _nType);
  fd.append('entity_id', _nId);
  fd.append('note_text', text);
  for (const f of files) fd.append('files[]', f);

  try {
    const res  = await fetch('notes.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      document.getElementById('notes-textarea').value = '';
      document.getElementById('notes-files').value = '';
      document.getElementById('notes-file-label').textContent = 'Прикрепить файлы';
      loadNotes();
    } else {
      document.getElementById('notes-error-text').textContent = data.error || 'Ошибка';
      errEl.classList.remove('hidden');
    }
  } catch(e) {
    document.getElementById('notes-error-text').textContent = 'Ошибка сети';
    errEl.classList.remove('hidden');
  }
  btn.disabled = false;
}

document.getElementById('notes-files').addEventListener('change', function() {
  document.getElementById('notes-file-label').textContent =
    this.files.length ? this.files.length + ' файл(ов) выбрано' : 'Прикрепить файлы';
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') closeNotesModal();
});
</script>
