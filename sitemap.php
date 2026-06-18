<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';

$base = rtrim(APP_URL, '/');

// Static pages
$static = [
    [''              , '1.0', 'daily'  ],
    ['packages.php'  , '0.9', 'daily'  ],
    ['destinations.php','0.8','weekly' ],
    ['blog.php'      , '0.8', 'daily'  ],
    ['about.php'     , '0.6', 'monthly'],
    ['corporate.php' , '0.7', 'monthly'],
    ['contact.php'   , '0.6', 'monthly'],
    ['terms.php'     , '0.3', 'yearly' ],
    ['privacy.php'   , '0.3', 'yearly' ],
];

// Dynamic — packages
$packages = DB::rows("SELECT slug, updated_at FROM packages WHERE is_active=1 ORDER BY updated_at DESC");

// Dynamic — destinations
$destinations = DB::rows("SELECT slug, updated_at FROM destinations WHERE is_active=1 ORDER BY sort_order");

// Dynamic — blog posts
$posts = DB::rows("SELECT slug, published_at, updated_at FROM blog_posts WHERE status='published' ORDER BY published_at DESC");
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
<?php foreach ($static as [$path, $priority, $freq]): ?>
  <url>
    <loc><?= $base . '/' . $path ?></loc>
    <changefreq><?= $freq ?></changefreq>
    <priority><?= $priority ?></priority>
  </url>
<?php endforeach; ?>

<?php foreach ($packages as $pkg): ?>
  <url>
    <loc><?= $base ?>/package-detail.php?slug=<?= urlencode($pkg['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($pkg['updated_at'])) ?></lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
<?php endforeach; ?>

<?php foreach ($destinations as $dest): ?>
  <url>
    <loc><?= $base ?>/destinations.php?slug=<?= urlencode($dest['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($dest['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
<?php endforeach; ?>

<?php foreach ($posts as $post): ?>
  <url>
    <loc><?= $base ?>/blog-detail.php?slug=<?= urlencode($post['slug']) ?></loc>
    <lastmod><?= date('Y-m-d', strtotime($post['published_at'] ?? $post['updated_at'])) ?></lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.6</priority>
  </url>
<?php endforeach; ?>
</urlset>
