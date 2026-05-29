<?php
// Cliente SMTP minimal (RFC 5321 + 4954 AUTH PLAIN/LOGIN) sin dependencias.
// Soporta SSL implicito (465) y STARTTLS (587). Subject MIME-encoded.

declare(strict_types=1);

class SmtpException extends RuntimeException {}

function smtp_send(array $cfg, string $rawMessage): void {
    $host = $cfg['host'];
    $port = (int)$cfg['port'];
    $user = $cfg['user'];
    $pass = $cfg['pass'];
    $secure = $cfg['secure'] ?? 'tls'; // 'ssl' | 'tls' | ''
    $fromEnv = $cfg['from_envelope'] ?? $user;
    $rcpts = $cfg['rcpts'] ?? [];
    $timeout = (int)($cfg['timeout'] ?? 15);

    if (!$rcpts) throw new SmtpException('no rcpts');

    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $ctx = stream_context_create([
        'ssl' => [
            'verify_peer'       => true,
            'verify_peer_name'  => true,
            'allow_self_signed' => false,
        ]
    ]);
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) throw new SmtpException("connect: $errstr ($errno)");
    stream_set_timeout($fp, $timeout);

    $read = function() use ($fp): array {
        $code = null; $lines = [];
        while (!feof($fp)) {
            $line = fgets($fp, 4096);
            if ($line === false) throw new SmtpException('read timeout');
            $lines[] = rtrim($line);
            $code = (int)substr($line, 0, 3);
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return [$code, $lines];
    };
    $send = function(string $cmd) use ($fp) {
        if (fwrite($fp, $cmd . "\r\n") === false) throw new SmtpException("write fail: $cmd");
    };
    $expect = function(int $code, array $lines, int $want) {
        if ($code !== $want) throw new SmtpException("SMTP $code expected $want: " . implode(' | ', $lines));
    };

    // Banner
    [$c, $l] = $read(); $expect($c, $l, 220);
    // EHLO
    $send('EHLO ' . ($cfg['ehlo'] ?? 'celiaesceliaca.com'));
    [$c, $l] = $read(); $expect($c, $l, 250);

    // STARTTLS si toca
    if ($secure === 'tls') {
        $send('STARTTLS');
        [$c, $l] = $read(); $expect($c, $l, 220);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new SmtpException('STARTTLS failed');
        }
        // re-EHLO
        $send('EHLO ' . ($cfg['ehlo'] ?? 'celiaesceliaca.com'));
        [$c, $l] = $read(); $expect($c, $l, 250);
    }

    // AUTH LOGIN
    $send('AUTH LOGIN');
    [$c, $l] = $read(); $expect($c, $l, 334);
    $send(base64_encode($user));
    [$c, $l] = $read(); $expect($c, $l, 334);
    $send(base64_encode($pass));
    [$c, $l] = $read();
    if ($c !== 235) throw new SmtpException("auth fail $c: " . implode(' | ', $l));

    // MAIL FROM
    $send('MAIL FROM:<' . $fromEnv . '>');
    [$c, $l] = $read(); $expect($c, $l, 250);
    // RCPT TO (puede ser varios)
    foreach ($rcpts as $r) {
        $send('RCPT TO:<' . $r . '>');
        [$c, $l] = $read();
        if ($c !== 250 && $c !== 251) throw new SmtpException("rcpt $r: $c " . implode(' | ', $l));
    }
    // DATA
    $send('DATA');
    [$c, $l] = $read(); $expect($c, $l, 354);
    // Dot-stuffing y CRLF
    $rawMessage = preg_replace('/(^|\r\n)\./', '$1..', $rawMessage);
    if (substr($rawMessage, -2) !== "\r\n") $rawMessage .= "\r\n";
    $send($rawMessage . '.');
    [$c, $l] = $read(); $expect($c, $l, 250);
    // QUIT
    $send('QUIT');
    @fclose($fp);
}
