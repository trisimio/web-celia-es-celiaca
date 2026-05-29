<?php
// Bootstrap comun del admin: sesion, auth helpers, CSRF, JSON IO, rate-limit, logging.

declare(strict_types=1);

// Defensa en profundidad: ocultar errores fatales al usuario final (manteniendo logs server-side).
ini_set('display_errors', '0');
ini_set('log_errors', '1');
@ini_set('session.use_strict_mode', '1');
@ini_set('session.cookie_httponly', '1');
@ini_set('session.cookie_samesite', 'Lax');
@ini_set('session.use_only_cookies', '1');

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
           || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('CECADMIN');
    session_start();
}

// Headers basicos (refuerzan los del .htaccess por si Apache no los aplica)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header_remove('X-Powered-By');
}

const DATA_DIR          = __DIR__ . '/../data';
const CONTENT_FILE      = DATA_DIR . '/content.json';
const AUTH_FILE         = DATA_DIR . '/auth.php';
const BACKUP_DIR        = DATA_DIR . '/backups';
const LOGINS_FILE       = DATA_DIR . '/login-attempts.json';
const ACTIVITY_LOG      = DATA_DIR . '/admin-activity.log';
const IMG_DIR           = __DIR__ . '/../img';
const SESSION_TTL       = 60 * 60;       // 1 hora de inactividad
const LOGIN_MAX_FAILS   = 5;             // 5 intentos por IP/ventana
const LOGIN_WINDOW      = 15 * 60;       // ventana de 15 minutos
const LOGIN_LOCK_TIME   = 30 * 60;       // bloqueo 30 min tras superar limite
const MAX_UPLOAD_BYTES  = 12 * 1024 * 1024;
const ALLOWED_IMG_EXTS  = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'];

/* ───── Helpers basicos ─────────────────────────────────────────── */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return $_SESSION['csrf'];
}

function csrf_check(?string $token): bool {
    return !empty($_SESSION['csrf']) && is_string($token) && hash_equals($_SESSION['csrf'], $token);
}

function is_logged_in(): bool {
    if (empty($_SESSION['auth']) || empty($_SESSION['authUser'])) return false;
    // Session timeout por inactividad
    $last = (int)($_SESSION['lastSeen'] ?? 0);
    if ($last && (time() - $last) > SESSION_TTL) {
        session_unset();
        return false;
    }
    $_SESSION['lastSeen'] = time();
    return true;
}

function require_auth(): void {
    if (!is_logged_in()) {
        $isApi = str_contains($_SERVER['REQUEST_URI'] ?? '', '/api.php')
              || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'auth_required']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}

function load_auth(): array {
    if (!is_file(AUTH_FILE)) return ['user' => 'miguel', 'pwHash' => '', 'totpSecret' => '', 'recovery' => []];
    $cfg = include AUTH_FILE;
    if (!is_array($cfg)) $cfg = [];
    return $cfg + ['user' => 'miguel', 'pwHash' => '', 'totpSecret' => '', 'recovery' => []];
}

function save_auth_full(array $cfg): bool {
    $allowed = ['user', 'pwHash', 'totpSecret', 'recovery'];
    $clean = [];
    foreach ($allowed as $k) if (array_key_exists($k, $cfg)) $clean[$k] = $cfg[$k];
    $body = "<?php\nreturn " . var_export($clean, true) . ";\n";
    return (bool) @file_put_contents(AUTH_FILE, $body, LOCK_EX);
}

function save_auth(string $user, string $pwHash): bool {
    $cur = load_auth();
    $cur['user'] = $user; $cur['pwHash'] = $pwHash;
    return save_auth_full($cur);
}

function load_content(): array {
    $raw = @file_get_contents(CONTENT_FILE);
    $data = json_decode($raw ?: 'null', true);
    return is_array($data) ? $data : [];
}

function save_content(array $data): bool {
    if (!is_dir(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0775, true);
    if (is_file(CONTENT_FILE)) {
        @copy(CONTENT_FILE, BACKUP_DIR . '/content-' . date('Ymd-His') . '.json');
        $backups = glob(BACKUP_DIR . '/content-*.json') ?: [];
        if (count($backups) > 20) {
            usort($backups, fn($a, $b) => filemtime($a) <=> filemtime($b));
            foreach (array_slice($backups, 0, count($backups) - 20) as $old) @unlink($old);
        }
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    return (bool) @file_put_contents(CONTENT_FILE, $json, LOCK_EX);
}

function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function input_json(): array {
    $raw = file_get_contents('php://input');
    if (strlen((string)$raw) > 2 * 1024 * 1024) return []; // hard limit 2MB
    $d = json_decode($raw ?: 'null', true);
    return is_array($d) ? $d : [];
}

function clean_text($v): string { return trim((string)$v); }
function clean_html($v): string { return trim((string)$v); }

/* ───── Path-traversal guard para archivos en /img ────────────────── */

function safe_img_path(string $name): ?string {
    // Solo basename, sin slashes
    $name = basename($name);
    if ($name === '' || $name === '.' || $name === '..') return null;
    // Sin caracteres raros
    if (!preg_match('/^[a-zA-Z0-9._-]+$/', $name)) return null;
    $real = realpath(IMG_DIR . '/' . $name);
    if ($real === false) return null;
    // Confinado a IMG_DIR
    if (strpos($real, realpath(IMG_DIR) . DIRECTORY_SEPARATOR) !== 0
        && $real !== realpath(IMG_DIR)) return null;
    return $real;
}

/* ───── Optimizacion de imagenes en upload ─────────────────────────── */

/**
 * Optimiza una imagen JPEG/PNG/WebP en disco: max 1600px lado largo, quality 82.
 * Usa GD si esta disponible. SVG y GIF se dejan tal cual.
 */
function optimize_image(string $path, string $kind): void {
    if (!function_exists('imagecreatefromjpeg')) return; // GD no instalado
    if ($kind === 'svg' || $kind === 'gif') return;
    [$w, $h] = @getimagesize($path) ?: [0, 0];
    if (!$w || !$h) return;
    $maxSide = 1600;
    if (max($w, $h) <= $maxSide && filesize($path) < 600 * 1024) return; // ya pequena

    $src = null;
    switch ($kind) {
        case 'jpg': case 'jpeg': $src = @imagecreatefromjpeg($path); break;
        case 'png':              $src = @imagecreatefrompng($path);  break;
        case 'webp':             if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($path); break;
    }
    if (!$src) return;

    $ratio = min(1, $maxSide / max($w, $h));
    $nw = (int) round($w * $ratio);
    $nh = (int) round($h * $ratio);
    $dst = imagecreatetruecolor($nw, $nh);
    if ($kind === 'png') {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

    switch ($kind) {
        case 'jpg': case 'jpeg': imagejpeg($dst, $path, 82); break;
        case 'png':              imagepng($dst, $path, 6);   break;
        case 'webp':             if (function_exists('imagewebp')) imagewebp($dst, $path, 82); break;
    }
    imagedestroy($src); imagedestroy($dst);
}

/* ───── Magic-bytes para uploads ──────────────────────────────────── */

function detect_image_kind(string $tmpPath): ?string {
    if (!is_readable($tmpPath)) return null;
    $f = @fopen($tmpPath, 'rb');
    if (!$f) return null;
    $head = fread($f, 16);
    fclose($f);
    if ($head === false || strlen($head) < 4) return null;

    if (substr($head, 0, 3) === "\xFF\xD8\xFF") return 'jpg';
    if (substr($head, 0, 8) === "\x89PNG\r\n\x1A\n") return 'png';
    if (substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') return 'webp';
    if (substr($head, 0, 6) === 'GIF87a' || substr($head, 0, 6) === 'GIF89a') return 'gif';

    // SVG: text-based — verificamos que el XML empiece con <?xml o <svg
    $t = ltrim($head);
    if (str_starts_with($t, '<?xml') || stripos($t, '<svg') !== false) {
        // Lectura adicional para chequear que no contenga <script
        $first = file_get_contents($tmpPath, false, null, 0, 16384);
        if (stripos($first, '<script') !== false) return null;
        if (preg_match('/on[a-z]+\s*=/i', $first)) return null; // event handlers
        return 'svg';
    }
    return null;
}

/* ───── Rate-limit del login ──────────────────────────────────────── */

function client_ip(): string {
    $h = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
    if ($h) return trim(explode(',', $h)[0]);
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function load_attempts(): array {
    $j = @file_get_contents(LOGINS_FILE);
    $d = json_decode($j ?: 'null', true);
    return is_array($d) ? $d : [];
}

function save_attempts(array $a): void {
    @file_put_contents(LOGINS_FILE, json_encode($a), LOCK_EX);
}

/**
 * Devuelve null si el login esta permitido, o un mensaje de error si esta bloqueado.
 */
function login_throttle_check(string $ip): ?string {
    $all = load_attempts();
    $row = $all[$ip] ?? ['count' => 0, 'firstAt' => time(), 'lockedUntil' => 0];
    // Si esta bloqueado y aun no expiro el lock
    if (!empty($row['lockedUntil']) && time() < $row['lockedUntil']) {
        $sec = $row['lockedUntil'] - time();
        return 'Demasiados intentos. Vuelve a probar en ' . max(1, (int)ceil($sec/60)) . ' min.';
    }
    return null;
}

function login_record_fail(string $ip): void {
    $all = load_attempts();
    $row = $all[$ip] ?? ['count' => 0, 'firstAt' => time(), 'lockedUntil' => 0];
    // Reset ventana si paso el limite
    if ((time() - (int)$row['firstAt']) > LOGIN_WINDOW) {
        $row = ['count' => 0, 'firstAt' => time(), 'lockedUntil' => 0];
    }
    $row['count']++;
    if ($row['count'] >= LOGIN_MAX_FAILS) {
        $row['lockedUntil'] = time() + LOGIN_LOCK_TIME;
    }
    $all[$ip] = $row;
    // GC entradas viejas
    foreach ($all as $k => $v) {
        if ((time() - (int)($v['firstAt'] ?? 0)) > 7 * 24 * 3600 && empty($v['lockedUntil'])) unset($all[$k]);
    }
    save_attempts($all);
}

function login_record_ok(string $ip): void {
    $all = load_attempts();
    unset($all[$ip]);
    save_attempts($all);
}

/* ───── Audit log ─────────────────────────────────────────────────── */

function admin_log(string $event, array $ctx = []): void {
    $line = sprintf(
        "[%s] %s ip=%s ua=%s user=%s ctx=%s\n",
        date('c'),
        $event,
        client_ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 200),
        $_SESSION['authUser'] ?? '-',
        json_encode($ctx, JSON_UNESCAPED_UNICODE)
    );
    @file_put_contents(ACTIVITY_LOG, $line, FILE_APPEND | LOCK_EX);
}
