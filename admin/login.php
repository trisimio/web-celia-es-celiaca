<?php
require __DIR__ . '/_bootstrap.php';
require __DIR__ . '/_totp.php';

if (is_logged_in()) { header('Location: index.php'); exit; }

$err = '';
$step = 'password';
$ip = client_ip();

if (!empty($_SESSION['needs_2fa']) && empty($_POST['user'])) {
    $step = '2fa';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($lockMsg = login_throttle_check($ip)) {
        admin_log('login_locked', ['msg' => $lockMsg]);
        $err = $lockMsg;
    } elseif (!empty($_POST['user'])) {
        // STEP 1: usuario + password
        $u = clean_text($_POST['user'] ?? '');
        $p = (string)($_POST['pass'] ?? '');
        $cfg = load_auth();
        $okUser = hash_equals((string)($cfg['user'] ?? ''), $u);
        $okPass = !empty($cfg['pwHash']) && password_verify($p, $cfg['pwHash']);
        if (!$cfg['pwHash']) password_verify($p, '$2y$10$' . str_repeat('a', 53));

        if ($okUser && $okPass) {
            // Si tiene 2FA, pedir codigo
            if (!empty($cfg['totpSecret'])) {
                $_SESSION['needs_2fa'] = true;
                $_SESSION['pending_user'] = $u;
                $step = '2fa';
            } else {
                session_regenerate_id(true);
                $_SESSION['auth'] = true;
                $_SESSION['authUser'] = $u;
                $_SESSION['lastSeen'] = time();
                $_SESSION['ip'] = $ip;
                $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                if (password_needs_rehash($cfg['pwHash'], PASSWORD_BCRYPT)) save_auth($u, password_hash($p, PASSWORD_BCRYPT));
                login_record_ok($ip);
                admin_log('login_ok');
                header('Location: index.php');
                exit;
            }
        } else {
            usleep(700000);
            login_record_fail($ip);
            admin_log('login_fail', ['user' => $u]);
            $err = 'Credenciales incorrectas';
        }
    } elseif (!empty($_POST['totp']) || !empty($_POST['recovery'])) {
        // STEP 2: codigo TOTP o recovery
        $cfg = load_auth();
        $u = $_SESSION['pending_user'] ?? '';
        if (!$u || empty($cfg['totpSecret'])) {
            unset($_SESSION['needs_2fa'], $_SESSION['pending_user']);
            $err = 'Sesión expirada';
        } else {
            $code = trim($_POST['totp'] ?? '');
            $rec  = trim($_POST['recovery'] ?? '');
            $ok = false;
            if ($code) $ok = totp_verify($cfg['totpSecret'], $code);
            elseif ($rec) {
                $rh = $cfg['recovery'] ?? [];
                if (totp_consume_recovery($rec, $rh)) {
                    $cfg['recovery'] = $rh;
                    save_auth_full($cfg);
                    $ok = true;
                }
            }
            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['auth'] = true;
                $_SESSION['authUser'] = $u;
                $_SESSION['lastSeen'] = time();
                $_SESSION['ip'] = $ip;
                $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
                unset($_SESSION['needs_2fa'], $_SESSION['pending_user']);
                login_record_ok($ip);
                admin_log('login_2fa_ok');
                header('Location: index.php');
                exit;
            } else {
                usleep(700000);
                login_record_fail($ip);
                admin_log('login_2fa_fail');
                $err = 'Código incorrecto';
                $step = '2fa';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<title>Admin · Celia es Celíaca</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin admin--login">
<main class="login-card" role="main">
  <div class="login-card__brand">
    <h1 class="login-card__title">Celia es Celíaca</h1>
    <p class="login-card__sub">Panel de administración</p>
  </div>
  <?php if ($err): ?><div class="alert alert--err" role="alert"><?= htmlspecialchars($err, ENT_QUOTES) ?></div><?php endif; ?>

  <?php if ($step === 'password'): ?>
    <form method="post" class="login-form" autocomplete="off">
      <label class="field"><span>Usuario</span><input name="user" type="text" required autofocus autocomplete="username" maxlength="60"></label>
      <label class="field"><span>Contraseña</span><input name="pass" type="password" required autocomplete="current-password" maxlength="128"></label>
      <button type="submit" class="btn btn--primary btn--full">Entrar</button>
    </form>
  <?php else: ?>
    <form method="post" class="login-form" autocomplete="off">
      <label class="field"><span>Código de 6 dígitos (app autenticadora)</span><input name="totp" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" autofocus placeholder="123456"></label>
      <button type="submit" class="btn btn--primary btn--full">Verificar</button>
      <details style="margin-top:14px">
        <summary style="cursor:pointer;color:var(--gray-300);font-size:13px">¿No tienes acceso al código?</summary>
        <label class="field" style="margin-top:14px"><span>Código de recuperación (XXXXX-XXXXX)</span><input name="recovery" type="text" maxlength="11" autocomplete="off" placeholder="ABCDE-12345"></label>
        <button type="submit" class="btn btn--outline btn--full">Usar recuperación</button>
      </details>
    </form>
  <?php endif; ?>

  <a href="/" class="login-card__back">← Volver a la web</a>
</main>
</body>
</html>
