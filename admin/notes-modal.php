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
    const res  = await fetch('notes-handler.php?entity_type=' + _nType + '&entity_id=' + _nId);
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
             <a href="notes-file.php?id=${f.id}" target="_blank"
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
  await fetch('notes-handler.php', { method: 'POST', body: fd });
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
    const res  = await fetch('notes-handler.php', { method: 'POST', body: fd });
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
