<?php
header('Content-Type: application/json');
header('Cache-Control: public, max-age=60');
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) { echo json_encode(['results' => [], 'query' => $q]); exit; }

$like = "%$q%";

$packages = DB::rows(
    "SELECT id, title, slug, base_price, sale_price, hero_image, type, duration_days
     FROM packages
     WHERE is_active=1 AND (title LIKE ? OR tagline LIKE ? OR overview LIKE ?)
     ORDER BY is_featured DESC, (title LIKE ?) DESC
     LIMIT 6",
    [$like, $like, $like, $like]
);

$destinations = DB::rows(
    "SELECT d.id, d.name, d.slug, d.country, d.hero_image,
            (SELECT COUNT(*) FROM packages WHERE destination_id=d.id AND is_active=1) AS package_count
     FROM destinations d
     WHERE d.is_active=1 AND (d.name LIKE ? OR d.country LIKE ?)
     LIMIT 4",
    [$like, $like]
);

$blogs = DB::rows(
    "SELECT id, title, slug, featured_image, excerpt
     FROM blog_posts
     WHERE status='published' AND (title LIKE ? OR excerpt LIKE ?)
     ORDER BY is_featured DESC, published_at DESC
     LIMIT 3",
    [$like, $like]
);

$results = [];

foreach ($packages as $p) {
    $price = $p['sale_price'] ?? $p['base_price'];
    $results[] = [
        'type'  => 'package',
        'title' => $p['title'],
        'url'   => url('package-detail.php?slug=' . $p['slug']),
        'price' => 'From $' . number_format((float)$price, 0),
        'meta'  => ucfirst($p['type']) . ' - ' . $p['duration_days'] . ' days',
        'image' => $p['hero_image'],
    ];
}

foreach ($destinations as $d) {
    $results[] = [
        'type'  => 'destination',
        'title' => $d['name'],
        'url'   => url('destinations.php?slug=' . $d['slug']),
        'meta'  => $d['country'] . ' - ' . $d['package_count'] . ' packages',
        'image' => $d['hero_image'],
    ];
}

foreach ($blogs as $b) {
    $results[] = [
        'type'  => 'blog',
        'title' => $b['title'],
        'url'   => url('blog-detail.php?slug=' . $b['slug']),
        'meta'  => excerpt(strip_tags($b['excerpt'] ?? ''), 60),
        'image' => $b['featured_image'],
    ];
}

echo json_encode(['results' => $results, 'query' => $q], JSON_UNESCAPED_UNICODE);
