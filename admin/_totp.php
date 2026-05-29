<?php
// Implementacion TOTP (RFC 6238) + HOTP (RFC 4226) en PHP puro.
// Compatible con Google Authenticator, Authy, 1Password, etc.

declare(strict_types=1);

/**
 * Genera un secret base32 aleatorio (160 bits / 32 chars).
 */
function totp_generate_secret(int $length = 32): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $out = '';
    $rand = random_bytes($length);
    for ($i = 0; $i < $length; $i++) {
        $out .= $alphabet[ord($rand[$i]) & 31];
    }
    return $out;
}

/**
 * Decode base32 (RFC 4648) sin padding.
 */
function totp_b32_decode(string $b32): string {
    $b32 = strtoupper(preg_replace('/[^A-Z2-7]/', '', $b32));
    $bits = '';
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    for ($i = 0, $n = strlen($b32); $i < $n; $i++) {
        $v = strpos($alphabet, $b32[$i]);
        if ($v === false) continue;
        $bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
    }
    $bytes = '';
    for ($i = 0, $n = strlen($bits) - 7; $i <= $n; $i += 8) {
        $bytes .= chr(bindec(substr($bits, $i, 8)));
    }
    return $bytes;
}

/**
 * Calcula codigo TOTP para un secret y timestamp dados.
 * Devuelve 6 digitos zero-padded.
 */
function totp_code(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string {
    $timestamp = $timestamp ?? time();
    $counter = (int) floor($timestamp / $period);
    $bin = pack('N*', 0, $counter); // 8 bytes big-endian
    $key = totp_b32_decode($secret);
    $hash = hash_hmac('sha1', $bin, $key, true);
    $offset = ord(substr($hash, -1)) & 0x0F;
    $truncated = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        ( ord($hash[$offset + 3]) & 0xFF)
    );
    $code = $truncated % (10 ** $digits);
    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
}

/**
 * Verifica un codigo permitiendo +/- N ventanas de tiempo (clock drift).
 */
function totp_verify(string $secret, string $code, int $window = 1, int $period = 30): bool {
    $code = preg_replace('/\D/', '', $code);
    if (strlen($code) !== 6) return false;
    $ts = time();
    for ($i = -$window; $i <= $window; $i++) {
        $expected = totp_code($secret, $ts + ($i * $period), $period);
        if (hash_equals($expected, $code)) return true;
    }
    return false;
}

/**
 * URI otpauth:// para mostrar en QR o pegar manualmente.
 */
function totp_uri(string $secret, string $account, string $issuer = 'Celia es Celíaca'): string {
    $issuer = rawurlencode($issuer);
    $account = rawurlencode($account);
    return "otpauth://totp/{$issuer}:{$account}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
}

/**
 * Codigos de recuperacion: 8 codigos de 10 chars, hashed con SHA-256.
 */
function totp_generate_recovery_codes(int $count = 8): array {
    $codes = []; $hashes = [];
    for ($i = 0; $i < $count; $i++) {
        $bytes = random_bytes(8);
        $code = strtoupper(substr(bin2hex($bytes), 0, 10));
        $code = substr($code, 0, 5) . '-' . substr($code, 5);
        $codes[] = $code;
        $hashes[] = hash('sha256', $code);
    }
    return ['plain' => $codes, 'hashes' => $hashes];
}

/**
 * Consume un codigo de recuperacion: si valida, lo borra del array.
 * Modifica $hashes por referencia. Devuelve true si OK.
 */
function totp_consume_recovery(string $code, array &$hashes): bool {
    $code = strtoupper(trim($code));
    $h = hash('sha256', $code);
    foreach ($hashes as $i => $stored) {
        if (hash_equals($stored, $h)) {
            array_splice($hashes, $i, 1);
            return true;
        }
    }
    return false;
}
