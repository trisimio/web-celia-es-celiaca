<?php
// Sitemap dinamico. Servimos como application/xml.
header('Content-Type: application/xml; charset=UTF-8');
$base = 'https://celiaesceliaca.com';
$today = date('Y-m-d');
$paths = ['/', '/#musica', '/#conciertos', '/#bio', '/#videos', '/#prensa', '/#fotos', '/#contacto'];
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($paths as $p): ?>
  <url>
    <loc><?= htmlspecialchars($base . $p, ENT_QUOTES) ?></loc>
    <lastmod><?= $today ?></lastmod>
    <changefreq>weekly</changefreq>
  </url>
<?php endforeach; ?>
</urlset>
