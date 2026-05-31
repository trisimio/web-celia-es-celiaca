<?php
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

// Logos SVG oficiales de cada plataforma (24x24, fill=currentColor). Reconocibles.
function platform_icon($slug) {
    $p = [
        'spotify'      => '<path d="M12 0C5.4 0 0 5.4 0 12s5.4 12 12 12 12-5.4 12-12S18.66 0 12 0zm5.521 17.34c-.24.359-.66.48-1.021.24-2.82-1.74-6.36-2.101-10.561-1.141-.418.122-.779-.179-.899-.539-.12-.421.18-.78.54-.9 4.56-1.021 8.52-.6 11.64 1.32.42.18.479.659.301 1.02zm1.44-3.3c-.301.42-.841.6-1.262.3-3.239-1.98-8.159-2.58-11.939-1.38-.479.12-1.02-.12-1.14-.6-.12-.48.12-1.021.6-1.141C9.6 9.9 15 10.561 18.72 12.84c.361.181.54.78.241 1.2zm.12-3.36C15.24 8.4 8.82 8.16 5.16 9.301c-.6.179-1.2-.181-1.38-.721-.18-.601.18-1.2.72-1.381 4.26-1.26 11.28-1.02 15.721 1.621.539.3.719 1.02.419 1.56-.299.421-1.02.599-1.559.3z"/>',
        'apple'        => '<path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>',
        'youtube'      => '<path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>',
        'youtubemusic' => '<path d="M12 0C5.376 0 0 5.376 0 12s5.376 12 12 12 12-5.376 12-12S18.624 0 12 0zm0 19.104A7.104 7.104 0 1 1 19.104 12 7.11 7.11 0 0 1 12 19.104zM9.684 15.54 15.816 12 9.684 8.46z"/>',
        'amazon'       => '<path d="M.045 18.02c.072-.116.187-.124.348-.022 3.636 2.11 7.594 3.166 11.87 3.166 2.852 0 5.668-.533 8.447-1.595.135-.05.232-.018.29.098.058.116.04.232-.058.348-3.21 2.404-7.073 3.606-11.59 3.606-3.83 0-7.32-1.077-10.47-3.232-.18-.116-.252-.252-.216-.41l.001.001v-.36zm6.91-6.6c0-.97.24-1.8.72-2.49.48-.69 1.14-1.21 1.98-1.56.78-.32 1.74-.55 2.88-.69.39-.05.99-.11 1.8-.18v-.36c0-.9-.1-1.51-.3-1.83-.3-.42-.78-.63-1.44-.63h-.18c-.48.045-.9.195-1.26.45-.36.255-.585.615-.675 1.08-.06.27-.21.42-.45.45l-2.55-.32c-.24-.05-.36-.18-.36-.39 0-.045.007-.094.02-.147.25-1.31.86-2.29 1.83-2.94.97-.65 2.11-.99 3.42-1.02h.54c1.66 0 2.96.43 3.9 1.29.13.13.25.27.36.42.11.15.2.31.27.48.07.17.124.39.16.66.036.27.06.51.072.72.012.21.018.51.018.9v6.6c0 .47.07.91.21 1.32.14.41.28.7.42.87.21.27.21.51 0 .72-.24.21-.51.45-.81.72l-.02.02c-.36.3-.61.54-.75.72-.144.197-.144.43 0 .7.05.103.103.2.144.282l.06.1-2.16 1.88c-.174.137-.36.165-.56.084-.28-.24-.52-.47-.72-.69-.2-.227-.34-.395-.42-.504-.08-.11-.21-.315-.39-.615-1.12 1.16-2.224 1.74-3.314 1.74-1.522 0-2.773-.466-3.75-1.4-.976-.934-1.464-2.178-1.464-3.733l.001-.001zm4.52-.35c0 .605.154 1.08.46 1.426.306.347.713.52 1.22.52h.1c.054-.005.126-.018.214-.04.453-.12.8-.41 1.04-.88.124-.227.214-.473.27-.74.054-.267.083-.48.087-.64.005-.16.008-.427.008-.8v-.48c-.854 0-1.51.06-1.97.18-1.32.36-1.98 1.135-1.98 2.32l.001-.001v.135z"/>',
        'deezer'       => '<path d="M18.81 4.16v3.03H24V4.16h-5.19zM6.27 8.38v3.027h5.189V8.38h-5.19zm12.54 0v3.027H24V8.38h-5.19zM0 12.594v3.027h5.19v-3.027H0zm6.27 0v3.027h5.189v-3.027h-5.19zm6.271 0v3.027h5.19v-3.027h-5.19zm6.27 0v3.027H24v-3.027h-5.19zM0 16.81v3.029h5.19v-3.03H0zm6.27 0v3.029h5.189v-3.03h-5.19zm6.271 0v3.029h5.19v-3.03h-5.19zm6.27 0v3.029H24v-3.03h-5.19z"/>',
        'bandcamp'     => '<path d="M0 18.75l7.437-13.5H24l-7.438 13.5H0z"/>',
        'soundcloud'   => '<path d="M23.999 14.165c-.052 1.796-1.612 3.169-3.4 3.169h-8.18a.68.68 0 0 1-.675-.683V7.862a.747.747 0 0 1 .452-.724s.75-.513 2.333-.513a5.364 5.364 0 0 1 2.763.755 5.433 5.433 0 0 1 2.57 3.643c.282-.074.575-.112.869-.112.978 0 1.847.464 2.401 1.181a3.474 3.474 0 0 1 .267 2.073M10.564 8.485c.246 2.795.45 5.426 0 8.219a.249.249 0 0 1-.493 0c-.42-2.768-.218-5.45 0-8.219a.247.247 0 0 1 .493 0m-1.71.987c.302 2.36.232 4.435-.001 6.792a.255.255 0 0 1-.506 0c-.225-2.32-.279-4.477 0-6.792a.254.254 0 0 1 .507 0m-1.733-.439c.317 2.733.276 5.092-.003 7.823a.232.232 0 0 1-.464 0c-.273-2.69-.31-5.117 0-7.823a.234.234 0 0 1 .467 0m-1.717 1.997c.39 1.804.26 3.355-.006 5.171a.214.214 0 0 1-.425 0c-.245-1.79-.241-3.388-.001-5.17a.217.217 0 0 1 .432-.001m-1.73-.524c.355 1.819.27 3.39-.003 5.213a.21.21 0 0 1-.417 0c-.273-1.804-.293-3.4 0-5.214a.211.211 0 0 1 .42 0m-1.73 1.052c.348 1.252.233 2.262-.003 3.527a.2.2 0 0 1-.397 0c-.213-1.245-.252-2.276 0-3.528a.198.198 0 0 1 .398 0m-1.726-.107c.41 1.31.215 2.376.013 3.732l-.022.135a.165.165 0 0 1-.325 0l-.02-.124c-.225-1.374-.394-2.451.008-3.747a.187.187 0 0 1 .362.002z"/>',
        'instagram'    => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/>',
        'twitter'      => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
        'tiktok'       => '<path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/>',
        'facebook'     => '<path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>',
    ];
    if (!isset($p[$slug])) return '';
    return '<svg class="platform-icon" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">' . $p[$slug] . '</svg>';
}
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
  <meta name="mobile-web-app-capable" content="yes">
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
      <div class="hero__video-wrap">
<?php $vid = h($C['hero']['videoId'] ?? 'jxkv3uXwY_I'); ?>
        <iframe class="hero__video" id="heroVideo" data-video-id="<?= $vid ?>" src="https://www.youtube-nocookie.com/embed/<?= $vid ?>?autoplay=1&mute=1&loop=1&playlist=<?= $vid ?>&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1&iv_load_policy=3&disablekb=1&enablejsapi=1" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="Hero video - Celia es Celíaca" loading="eager"></iframe>
      </div>
      <img src="img/hero-bg.jpg" alt="" class="hero__bg-img" loading="eager" width="1280" height="500">
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
      <img src="img/logo-hd.png" alt="Celia es Celíaca" class="hero__logo" width="180" height="180">
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
    ['key' => 'spotifyUrl',      'label' => 'Spotify',       'cls' => 'btn--spotify', 'icon' => 'spotify'],
    ['key' => 'appleUrl',        'label' => 'Apple Music',   'cls' => 'btn--outline', 'icon' => 'apple'],
    ['key' => 'youtubeMusicUrl', 'label' => 'YouTube Music', 'cls' => 'btn--outline', 'icon' => 'youtubemusic'],
    ['key' => 'amazonUrl',       'label' => 'Amazon Music',  'cls' => 'btn--outline', 'icon' => 'amazon'],
    ['key' => 'deezerUrl',       'label' => 'Deezer',        'cls' => 'btn--outline', 'icon' => 'deezer'],
    ['key' => 'bandcampUrl',     'label' => 'Bandcamp',      'cls' => 'btn--outline', 'icon' => 'bandcamp'],
    ['key' => 'soundcloudUrl',   'label' => 'SoundCloud',    'cls' => 'btn--outline', 'icon' => 'soundcloud'],
  ];
  foreach ($platforms as $pf):
    if (empty($featured[$pf['key']])) continue;
?>            <a href="<?= h($featured[$pf['key']]) ?>" target="_blank" rel="noopener noreferrer" class="btn btn--sm btn--platform <?= $pf['cls'] ?>"><?= platform_icon($pf['icon']) ?><span><?= $pf['label'] ?></span></a>
<?php endforeach; ?>          </div>
        </div>
      </div>

<?php if (!empty($featured['spotifyId'])): ?>
      <!-- Spotify embed -->
      <div class="spotify-embed reveal">
        <iframe title="Reproductor de Spotify — <?= h($featured['title'] ?? 'álbum') ?>" style="border-radius:12px" src="https://open.spotify.com/embed/album/<?= h($featured['spotifyId']) ?>?utm_source=generator&theme=0" width="100%" height="352" frameBorder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>
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
    'spotify'   => ['label' => 'Spotify',     'icon' => 'spotify'],
    'instagram' => ['label' => 'Instagram',   'icon' => 'instagram'],
    'youtube'   => ['label' => 'YouTube',     'icon' => 'youtube'],
    'twitter'   => ['label' => 'Twitter / X', 'icon' => 'twitter'],
    'bandcamp'  => ['label' => 'Bandcamp',    'icon' => 'bandcamp'],
    'tiktok'    => ['label' => 'TikTok',      'icon' => 'tiktok'],
    'facebook'  => ['label' => 'Facebook',    'icon' => 'facebook'],
  ];
  foreach ($socialMap as $key => $info):
    if (empty($soc[$key])) continue;
?>            <a href="<?= hattr($soc[$key]) ?>" target="_blank" rel="noopener noreferrer" class="social-pill social-pill--<?= h($key) ?>"><?= platform_icon($info['icon']) ?><span><?= h($info['label']) ?></span></a>
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
