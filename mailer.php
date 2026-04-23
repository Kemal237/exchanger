<?php
// mailer.php — отправка писем через PHPMailer + SMTP

require_once __DIR__ . '/mail_config.php';
require_once __DIR__ . '/lib/phpmailer/Exception.php';
require_once __DIR__ . '/lib/phpmailer/PHPMailer.php';
require_once __DIR__ . '/lib/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendVerificationEmail(string $toEmail, string $toName, string $token): bool {
    $verifyUrl = SITE_URL . '/verify-email.php?token=' . urlencode($token);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USERNAME;
        $mail->Password   = MAIL_PASSWORD;
        $mail->SMTPSecure = MAIL_ENCRYPTION;
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Подтверждение email — ' . SITE_NAME;
        $mail->Body    = emailVerificationTemplate($toName, $verifyUrl);
        $mail->AltBody = "Подтвердите email, перейдя по ссылке:\n$verifyUrl";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('[Mailer] Ошибка отправки: ' . $mail->ErrorInfo);
        return false;
    }
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
