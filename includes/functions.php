<?php
require_once __DIR__ . '/db.php';

// =============================================================
// STRING & SANITIZATION
// =============================================================

function h(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function slug(string $text): string {
    $text = mb_strtolower(trim($text));
    $text = preg_replace('/[^\w\s-]/u', '', $text);
    $text = preg_replace('/[\s_]+/', '-', $text);
    return preg_replace('/-+/', '-', $text);
}

function excerpt(string $text, int $length = 160): string {
    $text = strip_tags($text);
    if (mb_strlen($text) <= $length) return $text;
    return mb_substr($text, 0, $length) . '…';
}

function money(float $amount, string $currency = 'USD'): string {
    return '$' . number_format($amount, 2);
}

function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'm ago';
    return 'just now';
}

function formatDate(string $date, string $format = 'M j, Y'): string {
    return (new DateTime($date))->format($format);
}

function stars(float $rating, int $max = 5): string {
    $html = '<span class="stars">';
    for ($i = 1; $i <= $max; $i++) {
        if ($i <= $rating) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    return $html . '</span>';
}

function generateReference(): string {
    return 'MTS-' . strtoupper(substr(uniqid(), -6)) . '-' . rand(100, 999);
}

function generateToken(int $length = 64): string {
    return bin2hex(random_bytes($length / 2));
}

// =============================================================
// PACKAGES
// =============================================================

function getFeaturedPackages(int $limit = 6): array {
    return DB::rows("SELECT p.*, d.name AS destination_name, d.country, c.name AS category_name
                     FROM packages p
                     LEFT JOIN destinations d ON p.destination_id = d.id
                     LEFT JOIN categories c   ON p.category_id = c.id
                     WHERE p.is_featured = 1 AND p.is_active = 1
                     ORDER BY p.sort_order ASC, p.booking_count DESC
                     LIMIT ?", [$limit]);
}

function getPackageBySlug(string $slug): ?array {
    return DB::row("SELECT p.*, d.name AS destination_name, d.country, d.latitude, d.longitude,
                           c.name AS category_name, c.slug AS category_slug
                    FROM packages p
                    LEFT JOIN destinations d ON p.destination_id = d.id
                    LEFT JOIN categories c   ON p.category_id = c.id
                    WHERE p.slug = ? AND p.is_active = 1", [$slug]);
}

function getPackages(array $filters = [], int $page = 1): array {
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($filters['category'])) {
        $where[]  = 'c.slug = ?';
        $params[] = $filters['category'];
    }
    if (!empty($filters['destination'])) {
        $where[]  = 'd.id = ?';
        $params[] = (int) $filters['destination'];
    }
    if (!empty($filters['type'])) {
        $where[]  = 'p.type = ?';
        $params[] = $filters['type'];
    }
    if (!empty($filters['min_price'])) {
        $where[]  = 'p.base_price >= ?';
        $params[] = (float) $filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $where[]  = 'p.base_price <= ?';
        $params[] = (float) $filters['max_price'];
    }
    if (!empty($filters['duration'])) {
        $where[]  = 'p.duration_days <= ?';
        $params[] = (int) $filters['duration'];
    }
    if (!empty($filters['search'])) {
        $where[]  = 'MATCH(p.title, p.tagline, p.description) AGAINST(? IN BOOLEAN MODE)';
        $params[] = $filters['search'] . '*';
    }

    $order = match ($filters['sort'] ?? '') {
        'price_asc'  => 'p.base_price ASC',
        'price_desc' => 'p.base_price DESC',
        'rating'     => 'p.rating DESC',
        'popular'    => 'p.booking_count DESC',
        default      => 'p.is_featured DESC, p.sort_order ASC',
    };

    $sql = "SELECT p.*, d.name AS destination_name, d.country,
                   c.name AS category_name, c.slug AS category_slug
            FROM packages p
            LEFT JOIN destinations d ON p.destination_id = d.id
            LEFT JOIN categories c   ON p.category_id   = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $order";

    return DB::paginate($sql, $params, $page, PER_PAGE);
}

// =============================================================
// DESTINATIONS
// =============================================================

function getFeaturedDestinations(int $limit = 8): array {
    return DB::rows("SELECT * FROM destinations WHERE is_featured = 1 AND is_active = 1
                     ORDER BY sort_order ASC LIMIT ?", [$limit]);
}

function getDestinationBySlug(string $slug): ?array {
    return DB::row("SELECT d.*, r.name AS region_name FROM destinations d
                    LEFT JOIN regions r ON d.region_id = r.id
                    WHERE d.slug = ? AND d.is_active = 1", [$slug]);
}

// =============================================================
// BLOG
// =============================================================

function getBlogPosts(int $page = 1, ?int $categoryId = null): array {
    $where  = ["bp.status = 'published' AND bp.published_at <= NOW()"];
    $params = [];
    if ($categoryId) {
        $where[]  = 'bp.category_id = ?';
        $params[] = $categoryId;
    }
    $sql = "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug,
                   CONCAT(u.first_name, ' ', u.last_name) AS author_name, u.avatar AS author_avatar
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bp.category_id = bc.id
            LEFT JOIN users u            ON bp.author_id   = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY bp.published_at DESC";
    return DB::paginate($sql, $params, $page, BLOG_PER_PAGE);
}

function getBlogPostBySlug(string $slug): ?array {
    DB::query("UPDATE blog_posts SET view_count = view_count + 1 WHERE slug = ?", [$slug]);
    return DB::row("SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug,
                           CONCAT(u.first_name, ' ', u.last_name) AS author_name, u.avatar AS author_avatar
                    FROM blog_posts bp
                    LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                    LEFT JOIN users u            ON bp.author_id   = u.id
                    WHERE bp.slug = ? AND bp.status = 'published'", [$slug]);
}

function getRecentPosts(int $limit = 4): array {
    return DB::rows("SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
                     FROM blog_posts bp
                     LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                     WHERE bp.status = 'published'
                     ORDER BY bp.published_at DESC LIMIT ?", [$limit]);
}

// =============================================================
// REVIEWS
// =============================================================

function getPackageReviews(int $packageId, int $limit = 10): array {
    return DB::rows("SELECT * FROM reviews WHERE package_id = ? AND is_approved = 1
                     ORDER BY created_at DESC LIMIT ?", [$packageId, $limit]);
}

// =============================================================
// TESTIMONIALS
// =============================================================

function getTestimonials(int $limit = 6): array {
    return DB::rows("SELECT * FROM testimonials WHERE is_active = 1 AND is_featured = 1
                     ORDER BY sort_order ASC LIMIT ?", [$limit]);
}

// =============================================================
// SETTINGS
// =============================================================

function setting(string $key, string $default = ''): string {
    static $cache = [];
    if (!isset($cache[$key])) {
        $val = DB::value("SELECT `value` FROM settings WHERE `key` = ?", [$key]);
        $cache[$key] = $val !== false ? $val : $default;
    }
    return $cache[$key] ?? $default;
}

// =============================================================
// CATEGORIES
// =============================================================

function getCategories(): array {
    return DB::rows("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC");
}

// =============================================================
// UPLOAD HELPERS
// =============================================================

function uploadImage(array $file, string $subdir = 'general'): string|false {
    if ($file['error'] !== UPLOAD_ERR_OK) return false;
    if ($file['size'] > MAX_UPLOAD_SIZE)  return false;
    if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) return false;

    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = uniqid('img_') . '.' . strtolower($ext);
    $dir  = UPLOAD_PATH . $subdir . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $dest = $dir . $name;
    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;
    return UPLOAD_URL . $subdir . '/' . $name;
}

// =============================================================
// COUPONS
// =============================================================

function validateCoupon(string $code, float $amount): array {
    $coupon = DB::row("SELECT * FROM coupons
                       WHERE code = ? AND is_active = 1
                         AND (valid_from IS NULL OR valid_from <= CURDATE())
                         AND (valid_to   IS NULL OR valid_to   >= CURDATE())
                         AND (usage_limit IS NULL OR used_count < usage_limit)", [$code]);
    if (!$coupon) return ['valid' => false, 'message' => 'Invalid or expired coupon code.'];
    if ($amount < $coupon['min_order'])
        return ['valid' => false, 'message' => 'Minimum order amount not met.'];

    $discount = $coupon['type'] === 'percentage'
        ? min($amount * $coupon['value'] / 100, $coupon['max_discount'] ?? PHP_FLOAT_MAX)
        : $coupon['value'];
    $discount = min($discount, $amount);

    return ['valid' => true, 'discount' => round($discount, 2), 'coupon' => $coupon];
}

// =============================================================
// FLASH MESSAGES
// =============================================================

function flash(string $type, string $message): void {
    $_SESSION['flash'] = compact('type', 'message');
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    $icons = ['success' => 'check-circle', 'error' => 'exclamation-circle', 'warning' => 'exclamation-triangle', 'info' => 'info-circle'];
    $icon  = $icons[$flash['type']] ?? 'info-circle';
    return '<div class="flash-msg flash-' . h($flash['type']) . '">
                <i class="fas fa-' . $icon . '"></i>
                <span>' . h($flash['message']) . '</span>
                <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
            </div>';
}

// =============================================================
// SEO HELPERS
// =============================================================

function seoMeta(string $title, string $description = '', string $image = '', string $ogType = 'website'): string {
    $siteName  = APP_NAME;
    $fullTitle = $title ? "$title | $siteName" : "$siteName — " . APP_TAGLINE;
    $desc      = $description ?: APP_TAGLINE;
    $img       = $image ?: APP_URL . '/assets/images/og-default.jpg';
    $canonical = currentUrl();
    // Strip tracking params from canonical
    $cleanUrl = preg_replace('/[?&](utm_[^&]+|fbclid|gclid)(&|$)/', '$2', $canonical);
    $cleanUrl = rtrim($cleanUrl, '?&');

    return '<title>' . h($fullTitle) . '</title>
<meta name="description" content="' . h(mb_substr($desc, 0, 160)) . '">
<link rel="canonical" href="' . h($cleanUrl) . '">
<meta property="og:site_name" content="' . h($siteName) . '">
<meta property="og:url" content="' . h($canonical) . '">
<meta property="og:title" content="' . h($fullTitle) . '">
<meta property="og:description" content="' . h(mb_substr($desc, 0, 200)) . '">
<meta property="og:image" content="' . h($img) . '">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:type" content="' . h($ogType) . '">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@MTSafaris">
<meta name="twitter:title" content="' . h($fullTitle) . '">
<meta name="twitter:description" content="' . h(mb_substr($desc, 0, 200)) . '">
<meta name="twitter:image" content="' . h($img) . '">';
}

/**
 * Emit a JSON-LD <script> block. Pass structured data as a PHP array.
 */
function jsonLd(array $data): string {
    return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
}

/**
 * Organization + WebSite schema for the homepage.
 */
function schemaOrganization(): string {
    return jsonLd([
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'       => 'TravelAgency',
                '@id'         => APP_URL . '/#organization',
                'name'        => APP_NAME,
                'url'         => APP_URL,
                'logo'        => APP_URL . '/assets/images/logo.png',
                'description' => APP_TAGLINE,
                'address'     => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => 'Nairobi',
                    'addressCountry'  => 'KE',
                    'streetAddress'   => CONTACT_ADDRESS,
                ],
                'telephone'   => CONTACT_PHONE,
                'email'       => CONTACT_EMAIL,
                'sameAs'      => array_values(array_filter([
                    setting('social_facebook'),
                    setting('social_instagram'),
                    setting('social_twitter'),
                ])),
                'openingHours' => setting('office_hours', 'Mo-Sa 08:00-18:00'),
            ],
            [
                '@type'           => 'WebSite',
                '@id'             => APP_URL . '/#website',
                'url'             => APP_URL,
                'name'            => APP_NAME,
                'publisher'       => ['@id' => APP_URL . '/#organization'],
                'potentialAction' => [
                    '@type'       => 'SearchAction',
                    'target'      => [
                        '@type'       => 'EntryPoint',
                        'urlTemplate' => APP_URL . '/search.php?q={search_term_string}',
                    ],
                    'query-input' => 'required name=search_term_string',
                ],
            ],
        ],
    ]);
}

/**
 * TouristTrip (package) schema for package-detail pages.
 */
function schemaPackage(array $pkg, array $reviews = []): string {
    $price = $pkg['sale_price'] ?? $pkg['base_price'];
    $dest  = $pkg['dest_name'] ?? ($pkg['destination'] ?? '');

    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristTrip',
        'name'        => $pkg['title'],
        'description' => excerpt(strip_tags($pkg['overview'] ?? $pkg['tagline'] ?? ''), 300),
        'url'         => APP_URL . '/package-detail.php?slug=' . $pkg['slug'],
        'image'       => $pkg['hero_image'] ?? '',
        'touristType' => ucfirst($pkg['type'] ?? 'group'),
        'provider'    => ['@id' => APP_URL . '/#organization'],
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => number_format((float)$price, 2, '.', ''),
            'priceCurrency' => 'USD',
            'availability'  => 'https://schema.org/InStock',
            'url'           => APP_URL . '/booking.php?package=' . $pkg['slug'],
        ],
    ];

    if ($dest) {
        $data['itinerary'] = ['@type' => 'ItemList', 'name' => $dest];
    }

    if (!empty($reviews)) {
        $total  = count($reviews);
        $avgRaw = array_sum(array_column($reviews, 'rating')) / $total;
        $data['aggregateRating'] = [
            '@type'       => 'AggregateRating',
            'ratingValue' => round($avgRaw, 1),
            'reviewCount' => $total,
            'bestRating'  => 5,
        ];
        $data['review'] = array_map(fn($r) => [
            '@type'        => 'Review',
            'reviewRating' => ['@type'=>'Rating','ratingValue'=>(int)$r['rating'],'bestRating'=>5],
            'author'       => ['@type'=>'Person','name'=>h($r['reviewer_name']??'Traveler')],
            'reviewBody'   => h(excerpt($r['comment']??'', 400)),
            'datePublished'=> date('Y-m-d', strtotime($r['created_at'])),
        ], array_slice($reviews, 0, 5));
    }

    return jsonLd($data);
}

/**
 * Article schema for blog posts.
 */
function schemaBlogPost(array $post): string {
    return jsonLd([
        '@context'         => 'https://schema.org',
        '@type'            => 'Article',
        'headline'         => $post['title'],
        'description'      => excerpt(strip_tags($post['excerpt'] ?? $post['body'] ?? ''), 200),
        'image'            => $post['featured_image'] ?? APP_URL . '/assets/images/og-default.jpg',
        'url'              => APP_URL . '/blog-detail.php?slug=' . $post['slug'],
        'datePublished'    => date('c', strtotime($post['published_at'] ?? $post['created_at'])),
        'dateModified'     => date('c', strtotime($post['updated_at'] ?? $post['created_at'])),
        'author'           => ['@type'=>'Person','name'=>$post['author_name']??APP_NAME],
        'publisher'        => ['@id' => APP_URL . '/#organization'],
        'mainEntityOfPage' => APP_URL . '/blog-detail.php?slug=' . $post['slug'],
    ]);
}

/**
 * BreadcrumbList schema.
 * Pass array of ['name'=>'...','url'=>'...'] items.
 */
function schemaBreadcrumb(array $items): string {
    $list = [];
    foreach ($items as $i => $item) {
        $list[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['name'],
            'item'     => $item['url'],
        ];
    }
    return jsonLd(['@context'=>'https://schema.org','@type'=>'BreadcrumbList','itemListElement'=>$list]);
}

/**
 * TouristAttraction schema for destination detail pages.
 */
function schemaDestination(array $dest): string {
    $data = [
        '@context'    => 'https://schema.org',
        '@type'       => 'TouristAttraction',
        'name'        => $dest['name'],
        'description' => excerpt(strip_tags($dest['description'] ?? ''), 300) ?: 'Discover ' . $dest['name'] . ' with MT Safaris.',
        'url'         => APP_URL . '/destinations.php?slug=' . $dest['slug'],
        'provider'    => ['@id' => APP_URL . '/#organization'],
    ];
    if (!empty($dest['hero_image'])) {
        $data['image'] = $dest['hero_image'];
    }
    if (!empty($dest['latitude']) && !empty($dest['longitude'])) {
        $data['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float)$dest['latitude'],
            'longitude' => (float)$dest['longitude'],
        ];
        $data['hasMap'] = 'https://www.google.com/maps?q=' . $dest['latitude'] . ',' . $dest['longitude'];
    }
    if (!empty($dest['country'])) {
        $data['containedInPlace'] = ['@type' => 'Country', 'name' => $dest['country']];
    }
    if (!empty($dest['best_time'])) {
        $data['tourBookingPage'] = APP_URL . '/packages.php';
        $data['additionalProperty'] = [
            '@type' => 'PropertyValue',
            'name'  => 'Best Time to Visit',
            'value' => $dest['best_time'],
        ];
    }
    return jsonLd($data);
}

/**
 * FAQPage schema — pass array of rows with 'question' and 'answer' keys.
 */
function schemaFaqPage(array $faqs): string {
    $items = array_map(fn($f) => [
        '@type'          => 'Question',
        'name'           => $f['question'],
        'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['answer'])],
    ], $faqs);
    return jsonLd(['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items]);
}

/**
 * ItemList schema for package/destination listing pages.
 */
function schemaItemList(array $items, string $pageUrl, string $listName = 'Tour Packages'): string {
    $elements = [];
    foreach ($items as $i => $item) {
        $elements[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $item['title'] ?? $item['name'] ?? '',
            'url'      => isset($item['slug']) ? APP_URL . '/package-detail.php?slug=' . $item['slug'] : '',
        ];
    }
    return jsonLd([
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $listName,
        'url'             => $pageUrl,
        'numberOfItems'   => count($items),
        'itemListElement' => $elements,
    ]);
}

// =============================================================
// URL HELPERS
// =============================================================

function url(string $path = ''): string {
    return APP_URL . '/' . ltrim($path, '/');
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function currentUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function isActive(string $page): string {
    return (basename($_SERVER['PHP_SELF']) === $page) ? 'active' : '';
}

// =============================================================
// CSRF
// =============================================================

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . csrfToken() . '">';
}

function verifyCsrf(): bool {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals(csrfToken(), $token);
}

// =============================================================
// JSON DECODE HELPER
// =============================================================

function jd(string|null $json, mixed $default = []): mixed {
    if (!$json) return $default;
    return json_decode($json, true) ?? $default;
}

// =============================================================
// PAGINATION HTML
// =============================================================

function paginationHtml(int $total, int $pages, int $current, string $baseUrl): string {
    if ($pages <= 1) return '';
    $html = '<nav class="pagination"><ul>';
    if ($current > 1) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($current - 1) . '" class="prev"><i class="fas fa-chevron-left"></i></a></li>';
    }
    for ($i = 1; $i <= $pages; $i++) {
        if ($i == $current) {
            $html .= '<li><span class="current">' . $i . '</span></li>';
        } elseif ($i <= 2 || $i >= $pages - 1 || abs($i - $current) <= 1) {
            $html .= '<li><a href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
        } elseif (abs($i - $current) == 2) {
            $html .= '<li><span>…</span></li>';
        }
    }
    if ($current < $pages) {
        $html .= '<li><a href="' . $baseUrl . '?page=' . ($current + 1) . '" class="next"><i class="fas fa-chevron-right"></i></a></li>';
    }
    return $html . '</ul></nav>';
}
