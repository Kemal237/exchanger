<?php
// mailer.php — отправка писем через PHPMailer + SMTP

define('MAIL_HOST',       'smtp.timeweb.ru');
define('MAIL_PORT',       465);
define('MAIL_USERNAME',   'noreply@cr873507.tw1.ru');
define('MAIL_PASSWORD',   'Bkm31072005&');
define('MAIL_FROM',       'noreply@cr873507.tw1.ru');
define('MAIL_FROM_NAME',  SITE_NAME);
define('MAIL_ENCRYPTION', 'ssl');

require_once __DIR__ . '/lib/phpmailer/Exception.php';
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USERNAME;
    $mail->Password   = MAIL_PASSWORD;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->Encoding   = 'base64';

    // Sender = Return-Path должен совпадать с From — иначе SPF фейлится
    $mail->Sender  = MAIL_FROM;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addReplyTo(MAIL_FROM, MAIL_FROM_NAME);

    // Скрываем "PHPMailer" из заголовков — спам-фильтры его знают
    $mail->XMailer = SITE_NAME . ' Mailer';

    // Сигнализируем что письмо транзакционное, не маркетинговое
    $mail->addCustomHeader('X-Priority', '3');
    $mail->addCustomHeader('X-Mailer-Type', 'Transactional');

    $mail->isHTML(true);
    return $mail;
}

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool {
    $verifyUrl = SITE_URL . '/verify-email.php?token=' . urlencode($token);

    $mail = createMailer();
    try {
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = '[' . SITE_NAME . '] Подтверждение адреса электронной почты';
        $mail->Body    = emailVerificationTemplate($toName, $verifyUrl);
        $mail->AltBody = "Здравствуйте, $toName!\n\nПодтвердите ваш email, перейдя по ссылке:\n$verifyUrl\n\nЕсли вы не регистрировались на " . SITE_NAME . " — проигнорируйте это письмо.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Mailer] Ошибка отправки verification: ' . $mail->ErrorInfo);
        return false;
    }
}

function sendPasswordResetEmail(string $toEmail, string $toName, string $token): bool {
    $resetUrl = SITE_URL . '/reset-password.php?token=' . urlencode($token);

    $mail = createMailer();
    try {
        $mail->addAddress($toEmail, $toName);
        $mail->Subject = '[' . SITE_NAME . '] Восстановление доступа к аккаунту';
        $mail->Body    = emailPasswordResetTemplate($toName, $resetUrl);
        $mail->AltBody = "Здравствуйте, $toName!\n\nВы запросили сброс пароля для аккаунта на " . SITE_NAME . ".\n\nДля установки нового пароля перейдите по ссылке:\n$resetUrl\n\nСсылка действительна 1 час.\n\nЕсли вы не запрашивали сброс пароля — проигнорируйте это письмо. Ваш пароль не изменится.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Mailer] Ошибка отправки reset: ' . $mail->ErrorInfo);
        return false;
    }
}

function emailPasswordResetTemplate(string $name, string $url): string {
    $siteName = SITE_NAME;
    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Сброс пароля</title></head>
<body style="margin:0;padding:0;background:#060810;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#060810;padding:40px 20px;">
    <tr><td align="center">
      <table width="100%" style="max-width:520px;background:#0D0F1A;border-radius:16px;border:1px solid #1D2236;overflow:hidden;">

        <!-- Header -->
        <tr><td style="padding:32px 32px 24px;text-align:center;border-bottom:1px solid #1D2236;">
          <table cellpadding="0" cellspacing="0" style="display:inline-table;">
            <tr>
              <td valign="middle" style="padding-right:10px;">
                <table cellpadding="0" cellspacing="0">
                  <tr><td align="center" valign="middle" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#22D3EE,#A78BFA);">
                    <span style="color:#060810;font-weight:900;font-size:18px;line-height:1;">⇄</span>
                  </td></tr>
                </table>
              </td>
              <td valign="middle">
                <span style="color:#F4F4F5;font-size:20px;font-weight:700;letter-spacing:-0.5px;">$siteName</span>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- Body -->
        <tr><td style="padding:32px;">
          <h1 style="color:#F4F4F5;font-size:22px;font-weight:700;margin:0 0 8px;">Сброс пароля</h1>
          <p style="color:#A1A1AA;font-size:14px;line-height:1.6;margin:0 0 24px;">
            Привет, <strong style="color:#F4F4F5;">$name</strong>!<br>
            Мы получили запрос на сброс пароля для вашего аккаунта. Нажмите кнопку ниже, чтобы задать новый пароль.
          </p>

          <div style="text-align:center;margin:28px 0;">
            <a href="$url" style="display:inline-block;background:linear-gradient(135deg,#A78BFA,#8B5CF6);color:#060810;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:12px;letter-spacing:-0.2px;">
              Сбросить пароль
            </a>
          </div>

          <div style="background:#1C1F2E;border-radius:10px;padding:14px 18px;margin:0 0 20px;">
            <p style="color:#F59E0B;font-size:13px;margin:0;font-weight:600;">⚠ Важно</p>
            <p style="color:#A1A1AA;font-size:12px;margin:6px 0 0;line-height:1.5;">Ссылка действительна <strong style="color:#F4F4F5;">1 час</strong>. Если вы не запрашивали сброс пароля — просто проигнорируйте это письмо. Ваш пароль останется прежним.</p>
          </div>

          <p style="color:#71717A;font-size:12px;line-height:1.6;margin:24px 0 0;padding-top:24px;border-top:1px solid #1D2236;">
            Если кнопка не работает, скопируйте ссылку:<br>
            <a href="$url" style="color:#A78BFA;word-break:break-all;">$url</a>
          </p>
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:16px 32px;text-align:center;border-top:1px solid #1D2236;">
          <p style="color:#52525B;font-size:11px;margin:0;">© {$siteName}. Это автоматическое письмо, не отвечайте на него.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}

function emailVerificationTemplate(string $name, string $url): string {
    $siteName = SITE_NAME;
    return <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Подтверждение email</title></head>
<body style="margin:0;padding:0;background:#060810;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#060810;padding:40px 20px;">
    <tr><td align="center">
      <table width="100%" style="max-width:520px;background:#0D0F1A;border-radius:16px;border:1px solid #1D2236;overflow:hidden;">

        <!-- Header -->
        <tr><td style="padding:32px 32px 24px;text-align:center;border-bottom:1px solid #1D2236;">
          <table cellpadding="0" cellspacing="0" style="display:inline-table;">
            <tr>
              <td valign="middle" style="padding-right:10px;">
                <table cellpadding="0" cellspacing="0" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#22D3EE,#A78BFA);">
                  <tr><td align="center" valign="middle" style="width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,#22D3EE,#A78BFA);">
                    <span style="color:#060810;font-weight:900;font-size:18px;line-height:1;">⇄</span>
                  </td></tr>
                </table>
              </td>
              <td valign="middle">
                <span style="color:#F4F4F5;font-size:20px;font-weight:700;letter-spacing:-0.5px;">$siteName</span>
              </td>
            </tr>
          </table>
        </td></tr>

        <!-- Body -->
        <tr><td style="padding:32px;">
          <h1 style="color:#F4F4F5;font-size:22px;font-weight:700;margin:0 0 8px;">Подтвердите email</h1>
          <p style="color:#A1A1AA;font-size:14px;line-height:1.6;margin:0 0 24px;">
            Привет, <strong style="color:#F4F4F5;">$name</strong>!<br>
            Нажмите кнопку ниже, чтобы подтвердить ваш адрес электронной почты.
          </p>

          <div style="text-align:center;margin:28px 0;">
            <a href="$url" style="display:inline-block;background:linear-gradient(135deg,#22D3EE,#06B6D4);color:#060810;text-decoration:none;font-weight:700;font-size:15px;padding:14px 36px;border-radius:12px;letter-spacing:-0.2px;">
              Подтвердить email
            </a>
          </div>

          <p style="color:#71717A;font-size:12px;line-height:1.6;margin:24px 0 0;padding-top:24px;border-top:1px solid #1D2236;">
            Ссылка действительна 24 часа. Если вы не регистрировались на $siteName — просто проигнорируйте это письмо.<br><br>
            Если кнопка не работает, скопируйте ссылку:<br>
            <a href="$url" style="color:#22D3EE;word-break:break-all;">$url</a>
          </p>
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:16px 32px;text-align:center;border-top:1px solid #1D2236;">
          <p style="color:#52525B;font-size:11px;margin:0;">© {$siteName}. Это автоматическое письмо, не отвечайте на него.</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
