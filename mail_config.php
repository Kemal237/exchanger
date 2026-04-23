<?php
// mail_config.php — SMTP настройки (заполните своими данными из TimeWeb)

define('MAIL_HOST',     'smtp.timeweb.ru');   // SMTP сервер TimeWeb
define('MAIL_PORT',     465);                  // Порт (465 = SSL)
define('MAIL_USERNAME', 'noreply@cr873507.tw1.ru'); // ← ваш email на хостинге
define('MAIL_PASSWORD', 'Bkm31072005&');   // ← пароль от ящика
define('MAIL_FROM',     'noreply@cr873507.tw1.ru'); // ← тот же адрес
define('MAIL_FROM_NAME', SITE_NAME);
define('MAIL_ENCRYPTION', 'ssl');              // ssl или tls
