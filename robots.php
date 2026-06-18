<?php
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain; charset=utf-8');
$base = rtrim(APP_URL, '/');
?>
User-agent: *
Allow: /

# Block admin and customer portal from indexing
Disallow: /admin/
Disallow: /portal/
Disallow: /api/
Disallow: /includes/
Disallow: /assets/uploads/

# Allow CSS, JS and images for rendering
Allow: /assets/css/
Allow: /assets/js/
Allow: /assets/images/

# Block query-string pages that duplicate content
Disallow: /search.php?*type=
Disallow: /packages.php?page=

Sitemap: <?= $base ?>/sitemap.php
