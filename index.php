<?php
// ── Page cache: sirve HTML pre-renderizado para reducir el TTFB (~820ms → ~30ms).
//    Se invalida solo cuando cambian content.json, index.php, css o js.
//    Sólo aplica a peticiones GET normales (no admin, no query params especiales).
$__cacheable = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
    && empty($_SERVER['QUERY_STRING']);
$__cacheDir = __DIR__ . '/data/cache';
if ($__cacheable) {
    $__sig = @filemtime(__DIR__ . '/data/content.json') . '-'
           . @filemtime(__FILE__) . '-'
           . @filemtime(__DIR__ . '/css/style.css') . '-'
           . @filemtime(__DIR__ . '/js/app.js');
    $__cacheFile = $__cacheDir . '/page-' . md5($__sig) . '.html';
    if (is_file($__cacheFile)) {
        header('X-Cache: HIT');
        readfile($__cacheFile);
        exit;
    }
    ob_start(); // capturamos el render para guardarlo al final
}

// Render principal data-driven. Lee data/content.json y expone $C a las plantillas.
$contentPath = __DIR__ . '/data/content.json';
$C = json_decode(@file_get_contents($contentPath), true);
if (!is_array($C)) {
    // Fallback minimo si el JSON falla, para que la web nunca se caiga.
    $C = ['hero' => ['label' => 'Pop-Rock Alternativo / Punk — Madrid', 'tagline' => "Pop, rock y punk con melodías que pegan.\nMúsica sin gluten desde 2012.", 'videoId' => 'jxkv3uXwY_I', 'badge' => 'MUERTE AL PAN!'], 'bio' => ['image' => 'img/cec-promo2.jpg', 'paragraphs' => [], 'stats' => []], 'members' => [], 'discography' => ['featured' => [], 'past' => []], 'videos' => ['featured' => [], 'grid' => []], 'concerts' => ['upcoming' => [], 'past' => []], 'press' => ['quotes' => [], 'articles' => []], 'photos' => [], 'contact' => ['general' => 'celiaesceliaca@gmail.com', 'pressName' => '', 'pressEmail' => '', 'social' => []]];
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function hattr($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function nl2html($s) { return nl2br(h($s)); }
function rawhtml($s) { return (string)$s; } // contenido HTML confiado (editor de admin lo sanitiza)
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Celia es Celíaca — Pop-Rock Alternativo desde Madrid</title>
  <meta name="description" content="Celia es Celíaca. Pop-rock alternativo y punk desde Madrid. Música sin gluten desde 2012. Subterfuge Records.">
  <meta name="keywords" content="Celia es Celíaca, pop-rock, punk, Madrid, Subterfuge Records, música alternativa, Dover">
  <meta name="author" content="Celia es Celíaca">
  <meta name="robots" content="index, follow">
  <meta name="theme-color" content="#08080C">

  <!-- Open Graph -->
  <meta property="og:site_name" content="Celia es Celíaca">
  <meta property="og:title" content="Celia es Celíaca — Pop-Rock Alternativo desde Madrid">
  <meta property="og:description" content="Pop, rock y punk con melodías que pegan. Música sin gluten desde 2012. MUERTE AL PAN!">
  <meta property="og:type" content="music.musician">
  <meta property="og:url" content="https://celiaesceliaca.com/">
  <meta property="og:image" content="https://celiaesceliaca.com/img/og-cover.jpg">
  <meta property="og:image:width" content="1200">
  <meta property="og:image:height" content="630">
  <meta property="og:image:alt" content="Celia es Celíaca — banda de pop-rock de Madrid">
  <meta property="og:locale" content="es_ES">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Celia es Celíaca — Pop-Rock Alternativo desde Madrid">
  <meta name="twitter:description" content="Pop, rock y punk con melodías que pegan. Música sin gluten desde 2012. MUERTE AL PAN!">
  <meta name="twitter:image" content="https://celiaesceliaca.com/img/og-cover.jpg">

  <!-- Canonical -->
  <link rel="canonical" href="https://celiaesceliaca.com/">

  <!-- Favicon + PWA -->
  <link rel="icon" type="image/png" sizes="32x32" href="img/favicon-32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="img/icon-192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon.png">
  <link rel="manifest" href="manifest.webmanifest">
  <meta name="apple-mobile-web-app-title" content="Celia es Celíaca">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <!-- Fonts (carga no-bloqueante para mejorar FCP/LCP) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Archivo+Black&family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Archivo+Black&family=Space+Grotesk:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap"></noscript>

  <!-- Preconnects para terceros (vídeo/audio embebido) -->
  <link rel="preconnect" href="https://www.youtube-nocookie.com">
  <link rel="dns-prefetch" href="https://www.youtube-nocookie.com">
  <link rel="dns-prefetch" href="https://i.ytimg.com">

  <!-- Preload hero image (LCP) -->
  <link rel="preload" as="image" href="img/hero-bg.jpg" fetchpriority="high">

  <!-- Styles -->
  <link rel="stylesheet" href="css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?: '1' ?>">

  <!-- GSAP -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js" defer></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js" defer></script>

  <!-- Schema.org -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MusicGroup",
    "name": "Celia es Celíaca",
    "alternateName": "CEC",
    "url": "https://celiaesceliaca.com",
    "image": "https://celiaesceliaca.com/img/og-cover.jpg",
    "description": "Banda de pop-rock alternativo y punk de Madrid. Música sin gluten desde 2012.",
    "genre": ["Pop-Rock Alternativo", "Punk"],
    "foundingDate": "2012",
    "foundingLocation": {
      "@type": "Place",
      "name": "Madrid, Spain"
    },
    "member": [
      {"@type": "Person", "name": "Celia", "roleName": "Voz y letras"},
      {"@type": "Person", "name": "Jesús Antúnez", "roleName": "Batería y producción"},
      {"@type": "Person", "name": "Álvaro Gómez", "roleName": "Bajo"},
      {"@type": "Person", "name": "Borja", "roleName": "Guitarra"},
      {"@type": "Person", "name": "Miguel L. Garrido", "roleName": "Guitarra"}
    ],
    "album": [
      {"@type": "MusicAlbum", "name": "Kosmos", "datePublished": "2016"},
      {"@type": "MusicAlbum", "name": "Despechos de Autor", "datePublished": "2019"},
      {"@type": "MusicAlbum", "name": "Melodías EP", "datePublished": "2023"},
      {"@type": "MusicAlbum", "name": "Pretendientes Contundentes", "datePublished": "2026"}
    ],
    "sameAs": [
      "https://open.spotify.com/artist/2Sq78UsNGrY3Myqs1Tbu0d",
      "https://music.apple.com/us/artist/celia-es-cel%C3%ADaca/1128918022",
      "https://music.youtube.com/channel/UCw60jpCcry5FCtTD14shYbg",
      "https://www.deezer.com/es/artist/10622691",
      "https://soundcloud.com/celiaesceliaca",
      "https://celiaesceliaca.bandcamp.com",
      "https://www.instagram.com/celiaesceliaca",
      "https://www.youtube.com/channel/UCw60jpCcry5FCtTD14shYbg",
      "https://www.tiktok.com/@celiaesceliaca",
      "https://x.com/celiaesceliaca",
      "https://www.facebook.com/CeliaEsCeliaca"
    ]
  }
  </script>

<?php
// ── Structured data: conciertos próximos (MusicEvent → rich results de eventos) ──
$upcoming = $C['concerts']['upcoming'] ?? [];
if (!empty($upcoming)):
?>
  <script type="application/ld+json">
  [
<?php
  $events = [];
  foreach ($upcoming as $ev) {
    $date = $ev['date'] ?? '';
    if (!$date) continue;
    $venue = $ev['venue'] ?? '';
    $city  = $ev['city'] ?? '';
    $url   = $ev['ticketsUrl'] ?? 'https://celiaesceliaca.com/#conciertos';
    $events[] = json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'MusicEvent',
      'name' => 'Celia es Celíaca en ' . $venue,
      'startDate' => $date . 'T21:00:00+02:00',
      'eventStatus' => 'https://schema.org/EventScheduled',
      'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
      'location' => [
        '@type' => 'Place',
        'name' => $venue,
        'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'Madrid', 'addressCountry' => 'ES'],
      ],
      'image' => 'https://celiaesceliaca.com/img/og-cover.jpg',
      'performer' => ['@type' => 'MusicGroup', 'name' => 'Celia es Celíaca'],
      'organizer' => ['@type' => 'Organization', 'name' => 'Subterfuge Records', 'url' => 'https://www.subterfuge.com'],
      'offers' => ['@type' => 'Offer', 'url' => $url, 'availability' => 'https://schema.org/InStock'],
      'description' => $city,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
  }
  echo implode(",\n", $events);
?>

  ]
  </script>
<?php endif; ?>
</head>
<body>

  <!-- Skip nav -->
  <a href="#bio" class="skip-nav">Saltar al contenido</a>

  <!-- ═══════════════════════════════════════════ -->
  <!-- LOADING SCREEN                              -->
  <!-- ═══════════════════════════════════════════ -->
  <div id="loader" class="loader" aria-hidden="true">
    <div class="loader__content">
      <img src="img/logo-hd.png" alt="Celia es Celíaca" class="loader__logo" width="180" height="180">
      <div class="loader__bar">
        <div class="loader__progress"></div>
      </div>
      <div class="loader__motto easter-trigger" role="button" tabindex="0">MUERTE AL PAN</div>
    </div>
  </div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- NAVIGATION                                  -->
  <!-- ═══════════════════════════════════════════ -->
  <header class="nav" id="nav">
    <div class="nav__inner">
      <a href="#" class="nav__logo" aria-label="Inicio"><img src="img/logo.png" alt="Celia es Celíaca" class="nav__logo-img" width="57" height="57"></a>
      <nav class="nav__links" id="navLinks" aria-label="Navegación principal">
        <a href="#musica" class="nav__link">Música</a>
        <a href="#conciertos" class="nav__link">Conciertos</a>
        <a href="#bio" class="nav__link">Bio</a>
        <a href="#videos" class="nav__link">Vídeos</a>
        <a href="#prensa" class="nav__link">Prensa</a>
        <a href="#fotos" class="nav__link">Fotos</a>
        <a href="#contacto" class="nav__link">Contacto</a>
      </nav>
      <div class="nav__social">
        <a href="https://open.spotify.com/artist/2Sq78UsNGrY3Myqs1Tbu0d" target="_blank" rel="noopener noreferrer" aria-label="Spotify" class="nav__social-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
        </a>
        <a href="https://www.instagram.com/celiaesceliaca" target="_blank" rel="noopener noreferrer" aria-label="Instagram" class="nav__social-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
        </a>
        <a href="https://www.youtube.com/channel/UCw60jpCcry5FCtTD14shYbg" target="_blank" rel="noopener noreferrer" aria-label="YouTube" class="nav__social-link">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
        </a>
      </div>
      <button class="nav__hamburger" id="hamburger" aria-label="Abrir menú" aria-expanded="false">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>

  <!-- Mobile menu overlay -->
  <div class="mobile-menu" id="mobileMenu" aria-hidden="true">
    <nav class="mobile-menu__nav">
      <a href="#musica" class="mobile-menu__link">Música</a>
      <a href="#conciertos" class="mobile-menu__link">Conciertos</a>
      <a href="#bio" class="mobile-menu__link">Bio</a>
      <a href="#videos" class="mobile-menu__link">Vídeos</a>
      <a href="#prensa" class="mobile-menu__link">Prensa</a>
      <a href="#fotos" class="mobile-menu__link">Fotos</a>
      <a href="#contacto" class="mobile-menu__link">Contacto</a>
    </nav>
    <div class="mobile-menu__social">
      <a href="https://open.spotify.com/artist/2Sq78UsNGrY3Myqs1Tbu0d" target="_blank" rel="noopener noreferrer" aria-label="Spotify">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/></svg>
      </a>
      <a href="https://www.instagram.com/celiaesceliaca" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
      </a>
      <a href="https://www.youtube.com/channel/UCw60jpCcry5FCtTD14shYbg" target="_blank" rel="noopener noreferrer" aria-label="YouTube">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>
      </a>
      <a href="https://www.tiktok.com/@celiaesceliaca" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1v-3.5a6.37 6.37 0 00-.79-.05A6.34 6.34 0 003.15 15.2a6.34 6.34 0 0010.86 4.48v-7.15a8.16 8.16 0 005.58 2.17v-3.44a4.85 4.85 0 01-2-.57z"/></svg>
      </a>
    </div>
    <div class="mobile-menu__motto">MUERTE AL PAN</div>
  </div>

  <!-- Scroll progress -->
  <div class="scroll-progress" id="scrollProgress" aria-hidden="true"></div>

  <!-- ═══════════════════════════════════════════ -->
  <!-- HERO                                        -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="hero" id="hero">
    <div class="hero__bg">
<?php $vid = h($C['hero']['videoId'] ?? 'jxkv3uXwY_I'); ?>
      <!-- El iframe de YouTube se inyecta vía JS tras la carga (mejora LCP). La imagen es el fondo inmediato. -->
      <div class="hero__video-wrap" id="heroVideoWrap" data-video-id="<?= $vid ?>"></div>
      <img src="img/hero-bg.jpg" alt="Celia es Celíaca en directo" class="hero__bg-img" fetchpriority="high" decoding="async" width="1280" height="500">
      <div class="hero__noise"></div>
      <!-- Animated floating instruments -->
      <div class="hero__instruments" aria-hidden="true">
        <svg class="floating-icon floating-icon--1" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="var(--coral)" stroke-width="1.5" opacity="0.3"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
        <svg class="floating-icon floating-icon--2" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5" opacity="0.25"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
        <svg class="floating-icon floating-icon--3" width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="var(--magenta)" stroke-width="1.5" opacity="0.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="12" x2="15" y2="15"/><path d="M12 2a10 10 0 0 1 0 20"/></svg>
        <svg class="floating-icon floating-icon--4" width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="var(--coral)" stroke-width="1.5" opacity="0.2"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/></svg>
        <svg class="floating-icon floating-icon--5" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--purple)" stroke-width="1.5" opacity="0.25"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>
        <svg class="floating-icon floating-icon--6" width="42" height="42" viewBox="0 0 24 24" fill="none" stroke="var(--magenta)" stroke-width="1.5" opacity="0.15"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>
      </div>
    </div>
    <div class="hero__content">
      <h1 class="sr-only">Celia es Celíaca</h1>
      <img src="img/logo-hd@2x.png" alt="Celia es Celíaca" class="hero__logo" width="260" height="260" fetchpriority="high" decoding="async">
      <p class="hero__label"><?= h($C['hero']['label']) ?></p>
      <p class="hero__tagline"><?= nl2html($C['hero']['tagline']) ?></p>
      <div class="hero__ctas">
        <a href="#musica" class="btn btn--primary">Escuchar</a>
        <a href="#conciertos" class="btn btn--outline">Próximos conciertos</a>
      </div>
      <div class="hero__badge easter-trigger" role="button" tabindex="0"><?= h($C['hero']['badge']) ?></div>
    </div>
    <div class="hero__scroll-indicator" aria-hidden="true">
      <span>Scroll</span>
      <div class="hero__scroll-line"></div>
    </div>
  </section>

  <main>

  <!-- ═══════════════════════════════════════════ -->
  <!-- DISCOGRAFÍA                                 -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section discography" id="musica">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 001</span>
        <h2 class="section__title reveal">Discografía</h2>
      </div>

<?php $featured = $C['discography']['featured'] ?? []; $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($featured['title'] ?? 'album')); ?>
      <!-- Featured album -->
      <div class="album-hero reveal">
        <div class="album-hero__cover">
          <img src="<?= h($featured['image'] ?? '') ?>" alt="<?= h($featured['title'] ?? '') ?> - Celia es Celíaca" class="album-hero__img" loading="lazy" width="559" height="559">
        </div>
        <div class="album-hero__info">
          <span class="album-hero__year"><?= h($featured['year'] ?? '') ?></span>
          <h3 class="album-hero__title"><?= h($featured['title'] ?? '') ?></h3>
          <p class="album-hero__label"><?= h($featured['label'] ?? '') ?></p>
          <p class="album-hero__desc"><?= h($featured['description'] ?? '') ?></p>
<?php if (!empty($featured['tracklist'])): ?>
          <button class="tracklist-toggle" data-album="<?= h($slug) ?>" aria-expanded="false">
            Ver tracklist <span class="tracklist-toggle__icon">+</span>
          </button>
          <div class="tracklist" id="tracklist-<?= h($slug) ?>" hidden>
            <ol>
<?php foreach ($featured['tracklist'] as $t): ?>
              <li><?= h($t) ?></li>
<?php endforeach; ?>
            </ol>
          </div>
<?php endif; ?>
          <div class="album-hero__links">
<?php
  $platforms = [
    ['key' => 'spotifyUrl',      'label' => 'Spotify',       'cls' => 'btn--spotify'],
    ['key' => 'appleUrl',        'label' => 'Apple Music',   'cls' => 'btn--outline'],
    ['key' => 'youtubeMusicUrl', 'label' => 'YouTube Music', 'cls' => 'btn--outline'],
    ['key' => 'amazonUrl',       'label' => 'Amazon Music',  'cls' => 'btn--outline'],
    ['key' => 'deezerUrl',       'label' => 'Deezer',        'cls' => 'btn--outline'],
    ['key' => 'bandcampUrl',     'label' => 'Bandcamp',      'cls' => 'btn--outline'],
    ['key' => 'soundcloudUrl',   'label' => 'SoundCloud',    'cls' => 'btn--outline'],
  ];
  foreach ($platforms as $pf):
    if (empty($featured[$pf['key']])) continue;
?>            <a href="<?= h($featured[$pf['key']]) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--sm <?= $pf['cls'] ?>"><?= $pf['label'] ?></a>
<?php endforeach; ?>          </div>
        </div>
      </div>

<?php if (!empty($featured['spotifyId'])): ?>
      <!-- Spotify embed -->
      <div class="spotify-embed reveal">
        <iframe title="Reproductor de Spotify — <?= h($featured['title'] ?? 'álbum') ?>" style="border-radius:12px" src="https://open.spotify.com/embed/album/<?= h($featured['spotifyId']) ?>?utm_source=generator&theme=0" width="100%" height="352" frameBorder="0" allowfullscreen="" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
      </div>
<?php endif; ?>

      <!-- Previous albums -->
      <div class="albums-grid">
<?php foreach (($C['discography']['past'] ?? []) as $a):
        $aslug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($a['title'] ?? 'album'));
?>        <div class="album-card reveal">
          <div class="album-card__cover">
            <img src="<?= h($a['image'] ?? '') ?>" alt="<?= h($a['title'] ?? '') ?> - Celia es Celíaca" class="album-card__img" loading="lazy" width="400" height="400">
          </div>
          <div class="album-card__info">
            <span class="album-card__year"><?= h($a['year'] ?? '') ?></span>
            <h4 class="album-card__title"><?= h($a['title'] ?? '') ?></h4>
<?php if (!empty($a['type'])): ?>            <span class="album-card__type"><?= h($a['type']) ?></span>
<?php elseif (!empty($a['tracklist'])): ?>            <button class="tracklist-toggle" data-album="<?= h($aslug) ?>" aria-expanded="false">
              Tracklist <span class="tracklist-toggle__icon">+</span>
            </button>
            <div class="tracklist" id="tracklist-<?= h($aslug) ?>" hidden>
              <ol>
<?php foreach ($a['tracklist'] as $t): ?>                <li><?= h($t) ?></li>
<?php endforeach; ?>              </ol>
            </div>
<?php endif; ?>          </div>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- CONCIERTOS                                  -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section concerts" id="conciertos">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 002</span>
        <h2 class="section__title reveal">Conciertos</h2>
      </div>

<?php $upcoming = $C['concerts']['upcoming'] ?? []; $past = $C['concerts']['past'] ?? []; ?>
<?php if (!empty($upcoming)): ?>
      <h3 class="concerts-list__heading reveal" style="margin-bottom: 1rem;">Próximos shows</h3>
      <div class="concerts-list" style="margin-bottom: 3rem;">
<?php foreach ($upcoming as $c):
  $hasLink = !empty($c['ticketsUrl']);
  $Tag = $hasLink ? 'a' : 'div';
  $linkAttrs = $hasLink ? ' href="' . hattr($c['ticketsUrl']) . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex;"' : '';
?>        <<?= $Tag ?> class="concert-item concert-item--next reveal"<?= $linkAttrs ?>>
          <div class="concert-item__date">
            <time datetime="<?= h($c['date'] ?? '') ?>">
              <span class="concert-item__day"><?= h($c['day'] ?? '') ?></span>
              <span class="concert-item__month"><?= h($c['month'] ?? '') ?></span>
            </time>
          </div>
          <div class="concert-item__info">
            <h4 class="concert-item__venue"><?= h($c['venue'] ?? '') ?></h4>
            <span class="concert-item__city"><?= h($c['city'] ?? '') ?></span>
          </div>
          <span class="concert-item__sold"><?= h($c['status'] ?? 'Entradas') ?></span>
        </<?= $Tag ?>>
<?php endforeach; ?>      </div>
<?php endif; ?>
<?php if (!empty($past)): ?>
      <div class="concerts-list">
        <h3 class="concerts-list__heading reveal">Últimos shows</h3>
<?php foreach ($past as $c):
  $hasLink = !empty($c['url']);
  $Tag = $hasLink ? 'a' : 'div';
  $linkAttrs = $hasLink ? ' href="' . hattr($c['url']) . '" target="_blank" rel="noopener noreferrer" style="text-decoration: none; color: inherit; display: flex;"' : '';
?>        <<?= $Tag ?> class="concert-item concert-item--past reveal"<?= $linkAttrs ?>>
          <div class="concert-item__date">
            <time datetime="<?= h($c['date'] ?? '') ?>">
              <span class="concert-item__day"><?= h($c['day'] ?? '') ?></span>
              <span class="concert-item__month"><?= h($c['month'] ?? '') ?></span>
            </time>
          </div>
          <div class="concert-item__info">
            <h4 class="concert-item__venue"><?= h($c['venue'] ?? '') ?></h4>
            <span class="concert-item__city"><?= h($c['city'] ?? '') ?></span>
          </div>
          <span class="concert-item__sold"><?= h($c['status'] ?? '') ?></span>
        </<?= $Tag ?>>
<?php endforeach; ?>      </div>
<?php endif; ?>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- BIO                                         -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section bio" id="bio">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 003</span>
        <h2 class="section__title reveal">Biografía</h2>
      </div>
      <div class="bio__image bio__image--hero reveal">
        <img src="<?= h($C['bio']['image'] ?? 'img/cec-promo2.jpg') ?>" alt="Celia es Celíaca" class="bio__img" loading="lazy" width="1600" height="1066">
      </div>
      <div class="bio__grid">
        <div class="bio__text reveal">
<?php foreach (($C['bio']['paragraphs'] ?? []) as $i => $p): ?>
          <p<?= $i === 0 ? ' class="bio__intro"' : '' ?>><?= rawhtml($p) ?></p>
<?php endforeach; ?>
          <div class="bio__stats">
<?php foreach (($C['bio']['stats'] ?? []) as $s): ?>
            <div class="stat reveal">
              <span class="stat__number" data-count="<?= (int)($s['number'] ?? 0) ?>">0</span>
              <span class="stat__label"><?= h($s['label'] ?? '') ?></span>
            </div>
<?php endforeach; ?>
          </div>
        </div>
        <div class="bio__members">
<?php foreach (($C['members'] ?? []) as $m): ?>
          <div class="member-card reveal">
            <div class="member-card__info">
              <h3 class="member-card__name"><?= h($m['name'] ?? '') ?></h3>
              <span class="member-card__role"><?= h($m['role'] ?? '') ?></span>
            </div>
          </div>
<?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- VÍDEOS                                      -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section videos" id="videos">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 004</span>
        <h2 class="section__title reveal">Vídeos</h2>
      </div>

<?php $vf = $C['videos']['featured'] ?? []; ?>
      <!-- Featured video -->
      <div class="video-featured reveal">
        <div class="video-thumb" data-youtube="<?= h($vf['id'] ?? '') ?>" role="button" tabindex="0" aria-label="Reproducir vídeo: <?= h($vf['title'] ?? '') ?>">
          <div class="video-thumb__overlay">
            <div class="video-thumb__play">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </div>
          </div>
          <img src="<?= h($vf['thumb'] ?? '') ?>" alt="<?= h($vf['title'] ?? '') ?> - Celia es Celíaca" class="video-thumb__img" loading="lazy" width="480" height="360">
        </div>
        <h3 class="video-featured__title"><?= h($vf['title'] ?? '') ?></h3>
      </div>

      <!-- Video grid -->
      <div class="videos-grid">
<?php foreach (($C['videos']['grid'] ?? []) as $v): ?>
        <div class="video-card reveal">
          <div class="video-thumb" data-youtube="<?= h($v['id'] ?? '') ?>" role="button" tabindex="0" aria-label="Reproducir vídeo: <?= h($v['title'] ?? '') ?>">
            <div class="video-thumb__overlay">
              <div class="video-thumb__play">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
            </div>
            <img src="<?= h($v['thumb'] ?? '') ?>" alt="<?= h($v['title'] ?? '') ?>" class="video-thumb__img" loading="lazy" width="480" height="360">
          </div>
          <h4 class="video-card__title"><?= h($v['title'] ?? '') ?></h4>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- INSTAGRAM                                   -->
  <!-- ═══════════════════════════════════════════ -->
<?php
$ig = $C['instagram'] ?? null;
$igHandle = $ig['handle'] ?? 'celiaesceliaca';

// Sanitizar widgetHtml: solo permitir iframes de proveedores conocidos
function safe_widget_html(string $html): string {
    if ($html === '') return '';
    // Hosts permitidos para el iframe del widget (modulos de terceros oficiales)
    $allowed = ['cdn.lightwidget.com', 'lightwidget.com', 'snapwidget.com', 'www.snapwidget.com', 'apps.elfsight.com', 'static.elfsight.com', 'embedsocial.com', 'www.embedsocial.com', 'curator.io', 'behold.so'];
    if (!preg_match('~<iframe[^>]+src=["\']([^"\']+)["\']~i', $html, $m)) return '';
    $host = parse_url($m[1], PHP_URL_HOST);
    if (!$host || !in_array(strtolower($host), $allowed, true)) return '';
    // Devuelve solo el primer iframe encontrado, limpio
    if (preg_match('~<iframe[^>]*>.*?</iframe>|<iframe[^>]*/>~is', $html, $m2)) return $m2[0];
    return '';
}

$igPosts = [];
if ($ig && !empty($ig['posts'])) {
    foreach ($ig['posts'] as $u) {
        $u = trim((string)$u);
        if ($u === '') continue;
        if (preg_match('~^https?://(?:www\.)?instagram\.com/(p|reel|tv)/([\w-]+)~i', $u, $m)) {
            $igPosts[] = 'https://www.instagram.com/' . $m[1] . '/' . $m[2] . '/';
        }
    }
}
$igWidget = $ig ? safe_widget_html($ig['widgetHtml'] ?? '') : '';

if ($ig): ?>
  <section class="section instagram" id="instagram">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// IG</span>
        <h2 class="section__title reveal"><?= h($ig['title'] ?? 'Instagram') ?></h2>
        <?php if (!empty($ig['subtitle'])): ?><p class="section__subtitle"><?= h($ig['subtitle']) ?></p><?php endif; ?>
      </div>
<?php if ($igWidget): ?>
      <div class="instagram-widget reveal">
        <?= $igWidget /* ya saneado por safe_widget_html() */ ?>
      </div>
<?php elseif (!empty($igPosts)): ?>
      <div class="instagram-grid">
<?php foreach ($igPosts as $url): ?>
        <blockquote class="instagram-media reveal" data-instgrm-captioned data-instgrm-permalink="<?= h($url) ?>" data-instgrm-version="14" style="background:#000;border:0;border-radius:12px;margin:0;padding:0;width:100%;min-height:540px;"></blockquote>
<?php endforeach; ?>
      </div>
<?php else: ?>
      <div class="instagram-placeholder reveal">
        <div class="instagram-placeholder__icon">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <rect x="3" y="3" width="18" height="18" rx="5"/>
            <circle cx="12" cy="12" r="4"/>
            <circle cx="17.5" cy="6.5" r=".75" fill="currentColor"/>
          </svg>
        </div>
        <p>Sigue a la banda en Instagram para no perderte nada</p>
      </div>
<?php endif; ?>
      <div class="instagram-cta reveal">
        <a class="btn btn--outline" href="https://www.instagram.com/<?= h($igHandle) ?>" target="_blank" rel="noopener noreferrer">@<?= h($igHandle) ?> en Instagram ↗</a>
      </div>
    </div>
  </section>
<?php if (!empty($igPosts) && !$igWidget): ?>
  <script async src="https://www.instagram.com/embed.js"></script>
  <script>
    window.addEventListener('load', function() {
      if (window.instgrm && window.instgrm.Embeds) window.instgrm.Embeds.process();
    });
  </script>
<?php endif; ?>
<?php endif; ?>
<!-- ═══════════════════════════════════════════ -->
  <!-- PRENSA                                      -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section press" id="prensa">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 005</span>
        <h2 class="section__title reveal">Prensa</h2>
      </div>

      <!-- Citas destacadas -->
      <div class="press-quotes">
<?php foreach (($C['press']['quotes'] ?? []) as $q): ?>
        <div class="press-quote reveal">
          <blockquote>
            <span class="press-quote__mark">&ldquo;</span>
            <p><?= h($q['text'] ?? '') ?></p>
            <cite>— <?php if (!empty($q['url'])): ?><a href="<?= hattr($q['url']) ?>" target="_blank" rel="noopener noreferrer"><?= h($q['source'] ?? '') ?></a><?php else: ?><?= h($q['source'] ?? '') ?><?php endif; ?></cite>
          </blockquote>
        </div>
<?php endforeach; ?>
      </div>

      <!-- Noticias y artículos -->
      <div class="press-articles reveal">
        <h3 class="press-articles__title">En los medios</h3>
        <div class="press-articles__grid">
<?php foreach (($C['press']['articles'] ?? []) as $a): ?>
          <article>
            <a href="<?= hattr($a['url'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="press-article" data-logo="<?= hattr($a['logo'] ?? '') ?>">
              <time class="press-article__date"<?= !empty($a['date']) ? ' datetime="' . hattr($a['date']) . '"' : '' ?>><?= h($a['dateLabel'] ?? '') ?></time>
              <span class="press-article__source"><?= h($a['source'] ?? '') ?></span>
              <h4 class="press-article__title"><?= h($a['title'] ?? '') ?></h4>
            </a>
          </article>
<?php endforeach; ?>
        </div>
      </div>

      <div class="press-kit reveal">
        <div class="press-kit__content">
          <h3>Press Kit</h3>
          <p>Fotos de prensa en alta resolución, bio, rider técnico y logos.</p>
          <a href="#contacto" class="btn btn--outline">Solicitar Press Kit</a>
        </div>
      </div>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- FOTOS                                       -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section photos" id="fotos">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 006</span>
        <h2 class="section__title reveal">Fotos</h2>
      </div>
      <div class="photos-grid">
<?php foreach (($C['photos'] ?? []) as $i => $photo):
  $sizeCls = ($photo['size'] ?? '') === 'wide' ? ' photo-item--wide' : (($photo['size'] ?? '') === 'tall' ? ' photo-item--tall' : '');
?>        <figure class="photo-item<?= $sizeCls ?> reveal" role="button" tabindex="0" data-index="<?= (int)$i ?>" data-src="<?= hattr($photo['src'] ?? '') ?>" aria-label="<?= hattr($photo['alt'] ?? '') ?>">
          <img src="<?= h($photo['src'] ?? '') ?>" alt="<?= h($photo['alt'] ?? '') ?>" class="photo-item__img" loading="lazy" width="1280" height="720">
          <div class="photo-item__overlay">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/></svg>
          </div>
          <figcaption class="sr-only"><?= h($photo['caption'] ?? '') ?></figcaption>
        </figure>
<?php endforeach; ?>
      </div>
      <div class="photos__cta reveal">
        <a href="https://www.instagram.com/celiaesceliaca" target="_blank" rel="noopener noreferrer" class="btn btn--outline">Más fotos en @celiaesceliaca</a>
      </div>
    </div>
  </section>
<!-- ═══════════════════════════════════════════ -->
  <!-- CONTACTO                                    -->
  <!-- ═══════════════════════════════════════════ -->
  <section class="section contact" id="contacto">
    <div class="container">
      <div class="section__header">
        <span class="section__number">// 007</span>
        <h2 class="section__title reveal">Contacto</h2>
      </div>
      <div class="contact__grid">
        <div class="contact__info reveal">
          <h3 class="contact__subtitle">Habla con la banda</h3>
          <p>Para colaboraciones, prensa o cualquier consulta.</p>
<?php $ct = $C['contact'] ?? []; $soc = $ct['social'] ?? []; ?>
          <div class="contact__details">
            <div class="contact__detail">
              <span class="contact__detail-label">Contacto</span>
              <address><a href="mailto:<?= hattr($ct['general'] ?? '') ?>"><?= h($ct['general'] ?? '') ?></a></address>
            </div>
<?php if (!empty($ct['pressEmail'])): ?>            <div class="contact__detail">
              <span class="contact__detail-label">Prensa</span>
              <address><?= h($ct['pressName'] ?? '') ?><br><a href="mailto:<?= hattr($ct['pressEmail']) ?>"><?= h($ct['pressEmail']) ?></a></address>
            </div>
<?php endif; ?>
<?php if (!empty($ct['labelUrl'])): ?>            <div class="contact__detail">
              <span class="contact__detail-label">Sello</span>
              <a href="<?= hattr($ct['labelUrl']) ?>" target="_blank" rel="noopener noreferrer">Subterfuge Records</a>
            </div>
<?php endif; ?>          </div>
          <div class="contact__social-links">
<?php
  $socialMap = [
    'spotify' => 'Spotify',
    'instagram' => 'Instagram',
    'youtube' => 'YouTube',
    'twitter' => 'Twitter / X',
    'bandcamp' => 'Bandcamp',
    'tiktok' => 'TikTok',
    'facebook' => 'Facebook',
  ];
  foreach ($socialMap as $key => $label):
    if (empty($soc[$key])) continue;
?>            <a href="<?= hattr($soc[$key]) ?>" target="_blank" rel="noopener noreferrer" class="social-pill social-pill--<?= h($key) ?>"><?= h($label) ?></a>
<?php endforeach; ?>          </div>
        </div>
        <div class="contact__form-wrap reveal">
          <form name="contact" method="POST" action="contact.php" class="contact-form" id="contactForm">
            <p class="hidden" aria-hidden="true" style="position:absolute;left:-9999px;">
              <label>No rellenar: <input name="hp_field" tabindex="-1" autocomplete="off"></label>
            </p>
            <div class="form-group">
              <input type="text" name="name" id="formName" required autocomplete="name" placeholder=" ">
              <label for="formName">Nombre</label>
            </div>
            <div class="form-group">
              <input type="email" name="email" id="formEmail" required autocomplete="email" placeholder=" ">
              <label for="formEmail">Email</label>
            </div>
            <div class="form-group">
              <select name="subject" id="formSubject" required>
                <option value="" disabled selected></option>
                <option value="booking">Booking / Contratación</option>
                <option value="prensa">Prensa / Entrevista</option>
                <option value="colaboracion">Colaboración</option>
                <option value="press-kit">Press Kit</option>
                <option value="otro">Otro</option>
              </select>
              <label for="formSubject">Asunto</label>
            </div>
            <div class="form-group">
              <textarea name="message" id="formMessage" rows="4" required placeholder=" "></textarea>
              <label for="formMessage">Mensaje</label>
            </div>
            <button type="submit" class="btn btn--primary btn--full">Enviar mensaje</button>
          </form>
          <div class="contact-form__success" id="formSuccess" hidden>
            <h3>Mensaje enviado</h3>
            <p>Te respondemos en cuanto podamos. MUERTE AL PAN!</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  </main>

  <!-- ═══════════════════════════════════════════ -->
  <!-- FOOTER                                      -->
  <!-- ═══════════════════════════════════════════ -->
  <footer class="footer">
    <div class="container">
      <div class="footer__inner">
        <div class="footer__brand">
          <img src="img/logo.png" alt="Celia es Celíaca" class="footer__logo-img" width="57" height="57">
          <p class="footer__tagline">Rock sin gluten desde Madrid</p>
        </div>
        <nav class="footer__links" aria-label="Enlaces rápidos">
          <a href="#musica">Música</a>
          <a href="#conciertos">Conciertos</a>
          <a href="#bio">Bio</a>
          <a href="#videos">Vídeos</a>
          <a href="#contacto">Contacto</a>
        </nav>
        <div class="footer__bottom">
          <p>&copy; 2026 Celia es Celíaca. Subterfuge Records.</p>
          <p class="footer__motto" id="easterEgg" role="button" tabindex="0" title="???">MUERTE AL PAN</p>
        </div>
      </div>
    </div>
  </footer>

  <!-- Lightbox -->
  <div class="lightbox" id="lightbox" hidden aria-hidden="true" role="dialog" aria-label="Visor de fotos">
    <button class="lightbox__close" aria-label="Cerrar">&times;</button>
    <button class="lightbox__prev" aria-label="Anterior">&lsaquo;</button>
    <button class="lightbox__next" aria-label="Siguiente">&rsaquo;</button>
    <div class="lightbox__content"></div>
    <div class="lightbox__counter"></div>
  </div>

  <!-- Easter Egg: Muerte al Pan Runner -->
  <div id="gameOverlay" class="game-overlay" hidden>
    <div class="game-overlay__inner">
      <button class="game-overlay__close" id="gameClose" aria-label="Cerrar">&times;</button>
      <h3 class="game-overlay__title">MUERTE AL PAN!</h3>
      <canvas id="gameCanvas" width="600" height="200"></canvas>
      <p class="game-overlay__hint" id="gameHint">Espacio o toca para saltar</p>
      <p class="game-overlay__score">Trigos esquivados: <span id="gameScore">0</span></p>
    </div>
  </div>

  <script src="js/app.js?v=<?= @filemtime(__DIR__ . '/js/app.js') ?: '1' ?>" defer></script>
  <script src="js/game.js?v=<?= @filemtime(__DIR__ . '/js/game.js') ?: '1' ?>" defer></script>
</body>
</html>
<?php
// ── Guardar el render en cache (si procede) ──
if (!empty($__cacheable) && isset($__cacheFile)) {
    $__html = ob_get_clean();
    echo $__html;
    if (!is_dir($__cacheDir)) @mkdir($__cacheDir, 0775, true);
    // Limpiar caches antiguos (de versiones previas de content/css/js)
    foreach (glob($__cacheDir . '/page-*.html') ?: [] as $__old) {
        if ($__old !== $__cacheFile) @unlink($__old);
    }
    @file_put_contents($__cacheFile, $__html, LOCK_EX);
}
?>
