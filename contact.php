<?php
// Handler del formulario de contacto. HTML email multipart + texto fallback.
// Envia a celiaesceliaca@gmail.com con BCC a miguel@trisimio.es por defecto.

declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Honeypot
if (!empty($_POST['hp_field'])) {
    http_response_code(200);
    echo json_encode(['ok' => true]); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']); exit;
}

// Rate limit por IP
$ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$ip = trim(explode(',', $ip)[0]);
$rateFile = __DIR__ . '/data/contact-rate.json';
$now = time(); $window = 600; $maxPerWindow = 5;
$rate = [];
if (is_file($rateFile)) {
    $rate = json_decode((string)@file_get_contents($rateFile), true) ?: [];
}
$rate[$ip] = array_values(array_filter($rate[$ip] ?? [], fn($t) => $t > $now - $window));
if (count($rate[$ip]) >= $maxPerWindow) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Demasiados envios. Inténtalo más tarde.']); exit;
}

function sanitize($s) {
    $s = (string)$s;
    $s = str_replace(["\r", "\n"], ' ', $s);
    return trim($s);
}

$name    = sanitize($_POST['name']    ?? '');
$email   = sanitize($_POST['email']   ?? '');
$subject = sanitize($_POST['subject'] ?? '');
$message = trim((string)($_POST['message'] ?? ''));

$errors = [];
if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Nombre invalido';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) $errors[] = 'Email invalido';
if ($subject === '' || mb_strlen($subject) > 60) $errors[] = 'Asunto invalido';
if ($message === '' || mb_strlen($message) > 5000) $errors[] = 'Mensaje invalido (1-5000 caracteres)';
if (preg_match('/(viagra|casino|loan|crypto[\\s\\-]?wallet|bitcoin)/i', $message . ' ' . $subject)) {
    $errors[] = 'Spam detectado';
}

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'errors' => $errors]); exit;
}

// Mapeo asunto -> etiqueta legible
$subjectLabels = [
    'booking'      => 'Booking / Contratación',
    'prensa'       => 'Prensa / Entrevista',
    'colaboracion' => 'Colaboración',
    'press-kit'    => 'Press Kit',
    'otro'         => 'Otro',
];
$subjectLabel = $subjectLabels[$subject] ?? $subject;

// Destinatarios
$C       = @json_decode(@file_get_contents(__DIR__ . '/data/content.json'), true) ?: [];
$to      = $C['form']['recipientTo']  ?? 'celiaesceliaca@gmail.com';
$bcc     = $C['form']['recipientBcc'] ?? 'miguel@trisimio.es';
if (!filter_var($to, FILTER_VALIDATE_EMAIL))  $to  = 'celiaesceliaca@gmail.com';
if (!filter_var($bcc, FILTER_VALIDATE_EMAIL)) $bcc = '';

// Credenciales SMTP de Hostinger (secret no commited en git)
$secrets = @include __DIR__ . '/data/secrets.php';
$smtpCfg = is_array($secrets) ? ($secrets['smtp'] ?? null) : null;
// Resend como fallback si SMTP falla
$resendKey  = is_array($secrets) ? ($secrets['resend_api_key'] ?? '') : '';
$resendFrom = is_array($secrets) ? ($secrets['resend_from'] ?? '') : '';

$subjLine = '🤘 [' . $subjectLabel . '] ' . $name;

/* ─── PLANTILLA HTML ─── */
$nameH    = htmlspecialchars($name,    ENT_QUOTES, 'UTF-8');
$emailH   = htmlspecialchars($email,   ENT_QUOTES, 'UTF-8');
$subjectH = htmlspecialchars($subjectLabel, ENT_QUOTES, 'UTF-8');
$messageH = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
$dateH    = date('d/m/Y · H:i');
$ipH      = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
$replyHref = 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode('Re: ' . $subjectLabel);

$htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Nuevo mensaje · Celia es Celíaca</title>
</head>
<body style="margin:0;padding:0;background-color:#08080C;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#F0F0F5;">
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color:#08080C;padding:24px 12px;">
  <tr>
    <td align="center">
      <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width:600px;width:100%;background:#111118;border-radius:18px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,0.45);">

        <!-- Header gradient -->
        <tr>
          <td style="background:linear-gradient(120deg,#FBA4A2 0%,#D946C8 50%,#8A59F8 100%);padding:36px 32px 32px;text-align:center;">
            <div style="font-family:'Bebas Neue','Archivo Black',Impact,sans-serif;font-size:28px;letter-spacing:0.06em;color:#08080C;font-weight:900;margin:0;">CELIA ES CELÍACA</div>
            <div style="font-family:'JetBrains Mono',Menlo,monospace;font-size:11px;color:#08080C;opacity:0.7;letter-spacing:0.15em;text-transform:uppercase;margin-top:6px;">Nuevo mensaje desde la web</div>
          </td>
        </tr>

        <!-- Asunto -->
        <tr>
          <td style="padding:28px 32px 8px;">
            <div style="font-family:'JetBrains Mono',Menlo,monospace;font-size:11px;letter-spacing:0.15em;color:#9D9DAF;text-transform:uppercase;margin-bottom:6px;">Asunto</div>
            <div style="font-size:22px;font-weight:700;color:#F0F0F5;letter-spacing:-0.01em;line-height:1.3;">{$subjectH}</div>
          </td>
        </tr>

        <!-- Datos del remitente -->
        <tr>
          <td style="padding:8px 32px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#17171F;border-radius:12px;border:1px solid rgba(255,255,255,0.08);">
              <tr>
                <td style="padding:18px 22px;">
                  <div style="font-family:'JetBrains Mono',Menlo,monospace;font-size:10px;letter-spacing:0.15em;color:#9D9DAF;text-transform:uppercase;margin-bottom:4px;">De</div>
                  <div style="font-size:16px;color:#F0F0F5;font-weight:600;line-height:1.4;">{$nameH}</div>
                  <div style="margin-top:4px;">
                    <a href="mailto:{$emailH}" style="color:#FBA4A2;text-decoration:none;font-size:14px;border-bottom:1px solid rgba(251,164,162,0.3);">{$emailH}</a>
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Mensaje -->
        <tr>
          <td style="padding:22px 32px 8px;">
            <div style="font-family:'JetBrains Mono',Menlo,monospace;font-size:11px;letter-spacing:0.15em;color:#9D9DAF;text-transform:uppercase;margin-bottom:10px;">Mensaje</div>
            <div style="background:#17171F;border-radius:12px;padding:24px;border-left:3px solid #FBA4A2;color:#F0F0F5;font-size:15px;line-height:1.6;">{$messageH}</div>
          </td>
        </tr>

        <!-- CTA Responder -->
        <tr>
          <td align="center" style="padding:28px 32px 8px;">
            <a href="{$replyHref}" style="display:inline-block;background:linear-gradient(120deg,#FBA4A2,#8A59F8);color:#08080C;text-decoration:none;padding:14px 32px;border-radius:999px;font-weight:700;font-size:14px;letter-spacing:0.02em;">Responder a {$nameH} ↗</a>
          </td>
        </tr>

        <!-- Metadata -->
        <tr>
          <td style="padding:24px 32px 28px;">
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px dashed rgba(255,255,255,0.1);padding-top:18px;">
              <tr>
                <td style="font-family:'JetBrains Mono',Menlo,monospace;font-size:11px;color:#6D6D80;line-height:1.8;">
                  Recibido: {$dateH}<br>
                  IP: {$ipH}<br>
                  Web: <a href="https://celiaesceliaca.com" style="color:#9D9DAF;text-decoration:none;">celiaesceliaca.com</a>
                </td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="background:#0A0A10;padding:22px 32px;text-align:center;border-top:1px solid rgba(255,255,255,0.05);">
            <div style="font-family:'Bebas Neue',Impact,sans-serif;color:#FBA4A2;font-size:14px;letter-spacing:0.2em;">MUERTE AL PAN!</div>
            <div style="font-family:'JetBrains Mono',Menlo,monospace;font-size:10px;color:#6D6D80;letter-spacing:0.1em;text-transform:uppercase;margin-top:6px;">Pop-Rock alternativo · Madrid · desde 2012</div>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

// Plain text fallback
$textBody = "Nuevo mensaje desde celiaesceliaca.com\n";
$textBody .= str_repeat('=', 50) . "\n\n";
$textBody .= "Asunto:  $subjectLabel\n";
$textBody .= "De:      $name <$email>\n";
$textBody .= "Fecha:   $dateH\n";
$textBody .= "IP:      $ip\n\n";
$textBody .= str_repeat('-', 50) . "\n\n";
$textBody .= "$message\n\n";
$textBody .= str_repeat('-', 50) . "\n";
$textBody .= "Responder: $email\n";
$textBody .= "MUERTE AL PAN!\n";

/* ─── Construir mensaje raw (MIME multipart) + envio SMTP autenticado ─── */
require_once __DIR__ . '/_smtp.php';

$boundary = '_celia_' . bin2hex(random_bytes(12));
$msgId = '<' . bin2hex(random_bytes(8)) . '.' . time() . '@celiaesceliaca.com>';

$headerLines = [];
$headerLines[] = 'From: =?UTF-8?B?' . base64_encode($smtpCfg['from_name'] ?? 'Celia es Celíaca · Web') . '?= <' . $smtpCfg['from_email'] . '>';
$headerLines[] = 'To: ' . $to;
if ($bcc) $headerLines[] = 'Bcc: ' . $bcc;
$headerLines[] = 'Reply-To: =?UTF-8?B?' . base64_encode($name) . '?= <' . $email . '>';
$headerLines[] = 'Subject: =?UTF-8?B?' . base64_encode($subjLine) . '?=';
$headerLines[] = 'Date: ' . date('r');
$headerLines[] = 'Message-ID: ' . $msgId;
$headerLines[] = 'MIME-Version: 1.0';
$headerLines[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
$headerLines[] = 'X-Mailer: celiaesceliaca-web/1.0';

$rawBody  = "--{$boundary}\r\n";
$rawBody .= "Content-Type: text/plain; charset=UTF-8\r\n";
$rawBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$rawBody .= $textBody . "\r\n\r\n";
$rawBody .= "--{$boundary}\r\n";
$rawBody .= "Content-Type: text/html; charset=UTF-8\r\n";
$rawBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
$rawBody .= $htmlBody . "\r\n\r\n";
$rawBody .= "--{$boundary}--\r\n";

$rawMessage = implode("\r\n", $headerLines) . "\r\n\r\n" . $rawBody;

$rcpts = [$to];
if ($bcc) $rcpts[] = $bcc;

$sent = false;
$lastError = '';

// 1) Intento SMTP Hostinger
if (is_array($smtpCfg)) {
    try {
        smtp_send([
            'host'          => $smtpCfg['host'],
            'port'          => $smtpCfg['port'],
            'secure'        => $smtpCfg['secure'],
            'user'          => $smtpCfg['user'],
            'pass'          => $smtpCfg['pass'],
            'from_envelope' => $smtpCfg['from_email'],
            'rcpts'         => $rcpts,
            'ehlo'          => 'celiaesceliaca.com',
        ], $rawMessage);
        $sent = true;
    } catch (Throwable $e) {
        $lastError = 'SMTP: ' . $e->getMessage();
        error_log('contact.php SMTP error: ' . $lastError);
    }
}

// 2) Fallback Resend si SMTP falla
if (!$sent && $resendKey) {
    $payload = [
        'from'     => $resendFrom,
        'to'       => [$to],
        'subject'  => $subjLine,
        'html'     => $htmlBody,
        'text'     => $textBody,
        'reply_to' => [$email],
    ];
    if ($bcc) $payload['bcc'] = [$bcc];
    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $resendKey, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_TIMEOUT        => 12,
    ]);
    $resp = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($http >= 200 && $http < 300) {
        $sent = true;
    } else {
        $lastError .= ' | Resend HTTP ' . $http . ' ' . substr((string)$resp, 0, 200);
    }
}

if (!$sent) {
    error_log('contact.php final FAIL: ' . $lastError);
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo enviar el mensaje. Escribe directamente a ' . $to]); exit;
}

$rate[$ip][] = $now;
@file_put_contents($rateFile, json_encode($rate), LOCK_EX);

$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$xhr    = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
if ($xhr || str_contains($accept, 'application/json')) {
    echo json_encode(['ok' => true]); exit;
}
header('Location: /?sent=1#contacto');
exit;
