<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_totp.php';
require_auth();

// Session pinning: invalidamos si IP o UA cambian de golpe (defensa basica anti-hijacking).
$ip = client_ip();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
if (!empty($_SESSION['ip']) && $_SESSION['ip'] !== $ip) {
    session_unset(); session_destroy();
    json_response(['ok' => false, 'error' => 'session_invalid'], 401);
}
if (!empty($_SESSION['ua']) && $_SESSION['ua'] !== $ua) {
    session_unset(); session_destroy();
    json_response(['ok' => false, 'error' => 'session_invalid'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// CSRF para mutaciones
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['_csrf'] ?? null);
    if (!csrf_check($token)) {
        admin_log('csrf_fail', ['action' => $action]);
        json_response(['ok' => false, 'error' => 'csrf'], 419);
    }
}

$content = load_content();

// Whitelist de secciones editables — no se permite anadir/renombrar claves arbitrarias.
$ALLOWED_SECTIONS = ['hero','bio','members','discography','videos','concerts','press','photos','contact','form','instagram'];

switch ($action) {

    case 'all':
        json_response(['ok' => true, 'data' => $content, 'csrf' => csrf_token()]);

    case 'save_section': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $section = $in['section'] ?? null;
        $data    = $in['data'] ?? null;
        if (!is_string($section) || !in_array($section, $ALLOWED_SECTIONS, true)) {
            json_response(['ok' => false, 'error' => 'unknown_section'], 400);
        }
        // Limite duro de tamaño por seccion: 512KB serializado
        if (strlen(json_encode($data) ?: '') > 512 * 1024) {
            json_response(['ok' => false, 'error' => 'section_too_big'], 413);
        }
        $content[$section] = $data;
        if (!save_content($content)) json_response(['ok' => false, 'error' => 'save_failed'], 500);
        admin_log('save_section', ['section' => $section]);
        json_response(['ok' => true]);
    }

    case 'save_all': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $data = $in['data'] ?? null;
        if (!is_array($data)) json_response(['ok' => false, 'error' => 'bad_data'], 400);
        // Solo conservamos claves permitidas para evitar inyeccion de secciones
        $filtered = [];
        foreach ($ALLOWED_SECTIONS as $k) if (array_key_exists($k, $data)) $filtered[$k] = $data[$k];
        if (!save_content($filtered)) json_response(['ok' => false, 'error' => 'save_failed'], 500);
        admin_log('save_all');
        json_response(['ok' => true]);
    }

    case 'upload_image': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            json_response(['ok' => false, 'error' => 'no_file'], 400);
        }
        $f = $_FILES['file'];
        if ($f['size'] <= 0 || $f['size'] > MAX_UPLOAD_BYTES) {
            json_response(['ok' => false, 'error' => 'bad_size'], 400);
        }

        // Magic bytes -> tipo real
        $kind = detect_image_kind($f['tmp_name']);
        if ($kind === null) json_response(['ok' => false, 'error' => 'bad_image_or_unsafe_svg'], 400);
        $ext = $kind === 'jpeg' ? 'jpg' : $kind;
        if (!in_array($ext, ALLOWED_IMG_EXTS, true)) json_response(['ok' => false, 'error' => 'bad_ext'], 400);

        // Sanitizar nombre
        $baseRaw = pathinfo($f['name'] ?? 'upload', PATHINFO_FILENAME);
        $base = preg_replace('/[^a-z0-9\-_]+/i', '-', strtolower((string)$baseRaw));
        $base = trim((string)$base, '-') ?: 'upload';
        $base = substr($base, 0, 60);
        $name = $base . '.' . $ext;
        $dest = IMG_DIR . '/' . $name;
        $i = 1;
        while (is_file($dest)) {
            $name = $base . '-' . $i . '.' . $ext;
            $dest = IMG_DIR . '/' . $name;
            $i++;
            if ($i > 9999) json_response(['ok' => false, 'error' => 'name_collision'], 500);
        }
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
            json_response(['ok' => false, 'error' => 'move_failed'], 500);
        }
        @chmod($dest, 0644);
        // Optimiza in-place
        optimize_image($dest, $kind);
        admin_log('upload', ['name' => $name, 'origBytes' => $f['size'], 'finalBytes' => filesize($dest)]);
        json_response(['ok' => true, 'src' => 'img/' . $name, 'name' => $name, 'bytes' => filesize($dest)]);
    }

    case 'list_images': {
        $files = [];
        foreach (glob(IMG_DIR . '/*') ?: [] as $path) {
            if (!is_file($path)) continue;
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_IMG_EXTS, true)) continue;
            $files[] = ['src' => 'img/' . basename($path), 'size' => filesize($path), 'mtime' => filemtime($path)];
        }
        usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        json_response(['ok' => true, 'images' => $files]);
    }

    case 'delete_image': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $name = (string)($in['name'] ?? '');
        $safe = safe_img_path($name);
        if (!$safe) json_response(['ok' => false, 'error' => 'not_found_or_unsafe'], 400);
        @unlink($safe);
        admin_log('delete_image', ['name' => basename($safe)]);
        json_response(['ok' => true]);
    }

    case 'change_password': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $current = (string)($in['current'] ?? '');
        $new     = (string)($in['new'] ?? '');
        if (strlen($new) < 10) json_response(['ok' => false, 'error' => 'password_min_10'], 400);
        if (strlen($new) > 128) json_response(['ok' => false, 'error' => 'password_too_long'], 400);
        $cfg = load_auth();
        if (!password_verify($current, $cfg['pwHash'] ?? '')) {
            usleep(800000);
            admin_log('pw_change_wrong_current');
            json_response(['ok' => false, 'error' => 'wrong_current'], 401);
        }
        $hash = password_hash($new, PASSWORD_BCRYPT);
        if (!save_auth($cfg['user'] ?? 'miguel', $hash)) {
            json_response(['ok' => false, 'error' => 'save_failed'], 500);
        }
        admin_log('pw_change_ok');
        json_response(['ok' => true]);
    }

    case 'fetch_url_meta': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $url = trim((string)($in['url'] ?? ''));
        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i', $url)) {
            json_response(['ok' => false, 'error' => 'bad_url'], 400);
        }
        // Bloquear esquemas/hosts privados (SSRF defense)
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        if ($host === '' || $host === 'localhost' || preg_match('/^(127\.|10\.|192\.168\.|169\.254\.|0\.)/', $host)) {
            json_response(['ok' => false, 'error' => 'blocked_host'], 400);
        }
        // Fetch con curl (limite 5MB, timeout 8s)
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; CeliaBot/1.0; +https://celiaesceliaca.com)',
            CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS=> CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_BUFFERSIZE     => 65536,
        ]);
        $maxBytes = 5 * 1024 * 1024;
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$buf, $maxBytes) {
            static $size = 0; $size += strlen($chunk); $buf .= $chunk;
            return $size > $maxBytes ? 0 : strlen($chunk);
        });
        $buf = '';
        curl_exec($ch);
        $err = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) ?: $url;
        curl_close($ch);
        if ($err || $code < 200 || $code >= 400) {
            json_response(['ok' => false, 'error' => 'fetch_failed', 'http' => $code], 502);
        }
        $html = $buf;
        // Parse OpenGraph / meta basicos
        $meta = [];
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $m)) $meta['title'] = trim(html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8'));
        $patterns = [
            'og_title'    => '/<meta\s+(?:property|name)=[\'"]og:title[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
            'og_desc'     => '/<meta\s+(?:property|name)=[\'"]og:description[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
            'og_image'    => '/<meta\s+(?:property|name)=[\'"]og:image[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
            'og_site'     => '/<meta\s+(?:property|name)=[\'"]og:site_name[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
            'twitter_t'   => '/<meta\s+(?:property|name)=[\'"]twitter:title[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
            'date_meta'   => '/<meta\s+(?:property|name)=[\'"](?:article:published_time|date|pubdate)[\'"][^>]*content=[\'"]([^\'"]+)[\'"]/i',
        ];
        foreach ($patterns as $k => $pat) {
            if (preg_match($pat, $html, $m)) $meta[$k] = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
        }
        // Heuristica: source = host limpio (sin www.)
        $cleanHost = preg_replace('/^www\./', '', $host);
        // Logo = mayuscula del nombre del dominio
        $domainParts = explode('.', $cleanHost);
        $logoGuess = strtoupper(preg_replace('/[^a-z0-9]/i', '', $domainParts[0] ?? ''));
        if (strlen($logoGuess) > 10) $logoGuess = substr($logoGuess, 0, 10);

        $title = $meta['og_title'] ?? $meta['twitter_t'] ?? $meta['title'] ?? '';
        $date = $meta['date_meta'] ?? '';
        $dateLabel = '';
        if ($date) {
            $ts = strtotime($date);
            if ($ts) {
                $months = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
                $dateLabel = (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts) - 1] . ' ' . date('Y', $ts);
                $date = date('Y-m-d', $ts);
            }
        }
        json_response(['ok' => true, 'data' => [
            'url'       => $finalUrl,
            'title'     => $title,
            'source'    => $meta['og_site'] ?? $cleanHost,
            'logo'      => $logoGuess,
            'date'      => $date,
            'dateLabel' => $dateLabel,
            'image'     => $meta['og_image'] ?? '',
        ]]);
    }

    case 'list_backups': {
        $files = [];
        foreach (glob(BACKUP_DIR . '/content-*.json') ?: [] as $path) {
            $files[] = ['name' => basename($path), 'size' => filesize($path), 'mtime' => filemtime($path)];
        }
        usort($files, fn($a, $b) => $b['mtime'] <=> $a['mtime']);
        json_response(['ok' => true, 'backups' => $files]);
    }

    case 'restore_backup': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $name = basename((string)($in['name'] ?? ''));
        if (!preg_match('/^content-\d{8}-\d{6}\.json$/', $name)) json_response(['ok' => false, 'error' => 'bad_name'], 400);
        $src = BACKUP_DIR . '/' . $name;
        if (!is_file($src)) json_response(['ok' => false, 'error' => 'not_found'], 404);
        $raw = file_get_contents($src);
        $data = json_decode($raw ?: 'null', true);
        if (!is_array($data)) json_response(['ok' => false, 'error' => 'bad_json'], 400);
        if (!save_content($data)) json_response(['ok' => false, 'error' => 'save_failed'], 500);
        admin_log('restore_backup', ['name' => $name]);
        json_response(['ok' => true, 'data' => $data]);
    }

    case 'session': {
        // Util para refrescar TTL desde el frontend
        $cfg = load_auth();
        json_response(['ok' => true, 'user' => $_SESSION['authUser'] ?? null, 'expiresIn' => SESSION_TTL, 'twoFactorEnabled' => !empty($cfg['totpSecret'])]);
    }

    case '2fa_setup': {
        // Genera un secret temporal (no se guarda hasta confirmar)
        $secret = totp_generate_secret();
        $_SESSION['totp_pending'] = $secret;
        $user = $_SESSION['authUser'] ?? 'admin';
        $uri = totp_uri($secret, $user);
        json_response(['ok' => true, 'secret' => $secret, 'uri' => $uri]);
    }

    case '2fa_confirm': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $code = trim((string)($in['code'] ?? ''));
        $secret = $_SESSION['totp_pending'] ?? '';
        if (!$secret) json_response(['ok' => false, 'error' => 'no_pending'], 400);
        if (!totp_verify($secret, $code, 1)) {
            usleep(500000);
            json_response(['ok' => false, 'error' => 'bad_code'], 400);
        }
        $cfg = load_auth();
        $cfg['totpSecret'] = $secret;
        $rec = totp_generate_recovery_codes();
        $cfg['recovery'] = $rec['hashes'];
        if (!save_auth_full($cfg)) json_response(['ok' => false, 'error' => 'save_failed'], 500);
        unset($_SESSION['totp_pending']);
        admin_log('2fa_enabled');
        json_response(['ok' => true, 'recoveryCodes' => $rec['plain']]);
    }

    case '2fa_disable': {
        if ($method !== 'POST') json_response(['ok' => false, 'error' => 'method'], 405);
        $in = input_json();
        $code = trim((string)($in['code'] ?? ''));
        $cfg = load_auth();
        if (empty($cfg['totpSecret'])) json_response(['ok' => false, 'error' => 'not_enabled'], 400);
        if (!totp_verify($cfg['totpSecret'], $code, 1)) {
            usleep(500000);
            json_response(['ok' => false, 'error' => 'bad_code'], 400);
        }
        $cfg['totpSecret'] = '';
        $cfg['recovery'] = [];
        if (!save_auth_full($cfg)) json_response(['ok' => false, 'error' => 'save_failed'], 500);
        admin_log('2fa_disabled');
        json_response(['ok' => true]);
    }

    default:
        json_response(['ok' => false, 'error' => 'unknown_action'], 404);
}
