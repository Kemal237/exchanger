<?php
// setup-webhook.php — управление webhook/polling
require_once __DIR__ . '/config.php';

$token = TG_BOT_TOKEN;
header('Content-Type: text/html; charset=utf-8');

// Удаляем webhook (переходим на polling)
$del = file_get_contents("https://api.telegram.org/bot{$token}/deleteWebhook");
$delData = json_decode($del, true);

// Проверяем статус
$info = file_get_contents("https://api.telegram.org/bot{$token}/getWebhookInfo");
$infoData = json_decode($info, true);
?>
<!DOCTYPE html><html lang="ru">
<head><meta charset="UTF-8"><title>Bot Setup</title>
<style>body{font-family:monospace;background:#111;color:#eee;padding:30px} .ok{color:#4ade80} .err{color:#f87171} pre{background:#1a1a1a;padding:15px;border-radius:8px}</style>
</head><body>
<h2>Telegram Bot — переход на Polling</h2>

<p><b>Удаление webhook:</b>
<?= ($delData['ok'] ?? false) ? '<span class="ok">✓ Webhook удалён</span>' : '<span class="err">Ошибка: ' . htmlspecialchars($delData['description'] ?? '') . '</span>' ?>
</p>

<p><b>Текущий webhook URL:</b> <code><?= htmlspecialchars($infoData['result']['url'] ?? 'пусто') ?></code></p>

<?php if (empty($infoData['result']['url'])): ?>
<p class="ok"><b>✓ Webhook отключён. Теперь работает polling через cron.</b></p>
<?php endif; ?>

<h3>Что сделать в панели Timeweb:</h3>
<ol>
  <li>Зайди в <b>Панель управления → Cron</b></li>
  <li>Добавь задачу с командой:<br>
    <pre>php /home/c/cr873507/exchanger/public_html/bot-cron.php</pre>
  </li>
  <li>Частота: <b>каждую минуту</b> (*/1 * * * *)</li>
  <li>Сохрани</li>
</ol>

<p>После добавления cron — напиши боту <b>/start</b>. Ответ придёт в течение минуты.</p>

<p style="color:#888;font-size:12px">После настройки удали этот файл с сервера.</p>
</body></html>
