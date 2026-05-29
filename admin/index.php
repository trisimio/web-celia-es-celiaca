<?php
require __DIR__ . '/_bootstrap.php';
require_auth();
$csrf = csrf_token();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin · Celia es Celíaca</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="admin">
<header class="admin-header">
  <div class="admin-header__brand">
    <span class="admin-header__logo">●</span>
    <strong>Celia es Celíaca</strong>
    <span class="admin-header__tag">CMS</span>
  </div>
  <div class="admin-header__actions">
    <span class="admin-header__dirty" id="dirtyDot" hidden title="Hay cambios sin guardar">●</span>
    <button class="btn btn--primary btn--sm" id="saveAllBtn">Guardar todo</button>
    <button class="btn btn--ghost btn--sm" id="historyBtn" title="Historial de cambios">⟲ Historial</button>
    <button class="btn btn--ghost btn--sm" id="previewBtn" title="Vista previa en vivo">⊞ Preview</button>
    <a href="/" target="_blank" rel="noopener" class="btn btn--ghost btn--sm">Ver web ↗</a>
    <button class="btn btn--ghost btn--sm" id="changePwBtn">Contraseña</button>
    <button class="btn btn--ghost btn--sm" id="twoFaBtn">2FA</button>
    <a href="logout.php" class="btn btn--outline btn--sm">Salir</a>
  </div>
</header>

<div class="admin-layout">
  <aside class="admin-nav" id="adminNav">
    <button class="admin-nav__item is-active" data-section="hero">
      <span class="admin-nav__icon">🏠</span>
      <span>Portada</span>
    </button>
    <button class="admin-nav__item" data-section="bio">
      <span class="admin-nav__icon">📝</span>
      <span>Biografía</span>
    </button>
    <button class="admin-nav__item" data-section="members">
      <span class="admin-nav__icon">👥</span>
      <span>Miembros</span>
    </button>
    <button class="admin-nav__item" data-section="discography">
      <span class="admin-nav__icon">💿</span>
      <span>Discografía</span>
    </button>
    <button class="admin-nav__item" data-section="videos">
      <span class="admin-nav__icon">🎬</span>
      <span>Vídeos</span>
    </button>
    <button class="admin-nav__item" data-section="concerts">
      <span class="admin-nav__icon">🎤</span>
      <span>Conciertos</span>
    </button>
    <button class="admin-nav__item" data-section="press">
      <span class="admin-nav__icon">📰</span>
      <span>Prensa</span>
    </button>
    <button class="admin-nav__item" data-section="instagram">
      <span class="admin-nav__icon">📷</span>
      <span>Instagram</span>
    </button>
    <button class="admin-nav__item" data-section="photos">
      <span class="admin-nav__icon">🖼️</span>
      <span>Fotos</span>
    </button>
    <button class="admin-nav__item" data-section="contact">
      <span class="admin-nav__icon">✉️</span>
      <span>Contacto</span>
    </button>
    <button class="admin-nav__item" data-section="form">
      <span class="admin-nav__icon">📨</span>
      <span>Formulario</span>
    </button>
    <div class="admin-nav__spacer"></div>
    <p class="admin-nav__hint">💡 Los cambios se guardan al pulsar <strong>Guardar</strong>. Hay copias de seguridad automáticas en <strong>Historial</strong>.</p>
  </aside>

  <main class="admin-main" id="adminMain">
    <div class="admin-loading">Cargando contenido…</div>
  </main>

  <aside class="admin-preview" id="adminPreview" hidden>
    <div class="admin-preview__bar">
      <span>Vista previa en vivo</span>
      <select id="previewScope" class="select" style="max-width:130px">
        <option value="auto">Auto</option>
        <option value="hero">Hero</option>
        <option value="musica">Música</option>
        <option value="conciertos">Conciertos</option>
        <option value="bio">Bio</option>
        <option value="videos">Vídeos</option>
        <option value="prensa">Prensa</option>
        <option value="instagram">Instagram</option>
        <option value="fotos">Fotos</option>
        <option value="contacto">Contacto</option>
      </select>
      <button class="btn btn--ghost btn--sm" id="previewReload" title="Recargar">↻</button>
      <button class="btn btn--ghost btn--sm" id="previewClose" title="Cerrar">✕</button>
    </div>
    <iframe id="previewFrame" src="about:blank" loading="lazy" referrerpolicy="no-referrer"></iframe>
  </aside>
</div>

<div class="admin-toast" id="toast" hidden></div>

<dialog class="admin-modal" id="pwDialog">
  <form method="dialog" class="admin-modal__form" id="pwForm">
    <h2>Cambiar contraseña</h2>
    <label class="field"><span>Contraseña actual</span><input type="password" id="pwCurrent" required autocomplete="current-password"></label>
    <label class="field"><span>Nueva contraseña (min. 8)</span><input type="password" id="pwNew" required minlength="8" autocomplete="new-password"></label>
    <div class="admin-modal__actions">
      <button type="button" class="btn btn--ghost" value="cancel" id="pwCancel">Cancelar</button>
      <button type="submit" class="btn btn--primary">Guardar</button>
    </div>
  </form>
</dialog>

<script>window.CSRF = <?= json_encode($csrf) ?>;</script>
<script src="assets/admin.js" defer></script>
</body>
</html>
