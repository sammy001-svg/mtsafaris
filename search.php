<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$q       = trim($_GET['q'] ?? '');
$type    = trim($_GET['type'] ?? '');   // package | destination | blog | ''
$sort    = trim($_GET['sort'] ?? 'relevance');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

$results   = [];
$totalHits = 0;

if (strlen($q) >= 2) {
    $like  = "%$q%";

    // ---- Packages ----
    if (!$type || $type === 'package') {
        $pkgWhere = "p.is_active=1 AND (p.title LIKE ? OR p.tagline LIKE ? OR p.overview LIKE ?)";
        $pkgParams = [$like,$like,$like];
        $orderBy = match($sort) {
            'price_asc'  => 'base_price ASC',
            'price_desc' => 'base_price DESC',
            'newest'     => 'created_at DESC',
            default      => 'is_featured DESC, (title LIKE ?) DESC, base_price ASC',
        };
        if ($sort === 'relevance') {
            $pkgParams[] = "%$q%";
        }
        $pkgTotal = (int)DB::value("SELECT COUNT(*) FROM packages p WHERE $pkgWhere", [$like,$like,$like]);
        if (!$type) {
            $pkgs = DB::rows("SELECT p.*, c.name AS category_name, d.name AS dest_name, d.country
                              FROM packages p
                              LEFT JOIN categories c ON p.category_id=c.id
                              LEFT JOIN destinations d ON p.destination_id=d.id
                              WHERE $pkgWhere ORDER BY $orderBy LIMIT 6", $pkgParams);
        } else {
            $offset = ($page-1)*$perPage;
            $pkgs = DB::rows("SELECT p.*, c.name AS category_name, d.name AS dest_name, d.country
                              FROM packages p
                              LEFT JOIN categories c ON p.category_id=c.id
                              LEFT JOIN destinations d ON p.destination_id=d.id
                              WHERE $pkgWhere ORDER BY $orderBy LIMIT $perPage OFFSET $offset", $pkgParams);
            $totalHits = $pkgTotal;
        }
    }

    // ---- Destinations ----
    if (!$type || $type === 'destination') {
        $dests = DB::rows("SELECT d.*, r.name AS region_name,
                           (SELECT COUNT(*) FROM packages WHERE destination_id=d.id AND is_active=1) AS package_count
                           FROM destinations d LEFT JOIN regions r ON d.region_id=r.id
                           WHERE d.is_active=1 AND (d.name LIKE ? OR d.country LIKE ? OR d.description LIKE ?)
                           ORDER BY d.sort_order ASC LIMIT " . ($type ? $perPage : 4),
                          [$like,$like,$like]);
        if ($type === 'destination') $totalHits = (int)DB::value("SELECT COUNT(*) FROM destinations WHERE is_active=1 AND (name LIKE ? OR country LIKE ?)", [$like,$like]);
    }

    // ---- Blog posts ----
    if (!$type || $type === 'blog') {
        $blogOrder = $sort === 'newest' ? 'bp.published_at DESC' : 'bp.is_featured DESC, bp.published_at DESC';
        $blogs = DB::rows("SELECT bp.*, bc.name AS category_name, u.first_name AS author_first, u.last_name AS author_last
                           FROM blog_posts bp
                           LEFT JOIN blog_categories bc ON bp.category_id=bc.id
                           LEFT JOIN users u ON bp.author_id=u.id
                           WHERE bp.status='published' AND (bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.body LIKE ?)
                           ORDER BY $blogOrder LIMIT " . ($type ? $perPage : 3),
                          [$like,$like,$like]);
        if ($type === 'blog') $totalHits = (int)DB::value("SELECT COUNT(*) FROM blog_posts WHERE status='published' AND (title LIKE ? OR excerpt LIKE ?)", [$like,$like]);
    }

    if (!$type) {
        $totalHits = ($pkgTotal ?? 0) + count($dests ?? []) + count($blogs ?? []);
    }
}

$pages = $totalHits > 0 ? max(1, ceil($totalHits / $perPage)) : 1;

$pageTitle       = $q ? 'Search: "'.h($q).'" | MT Safaris' : 'Search | MT Safaris';
$pageDescription = $q ? "Search results for \"$q\" — packages, destinations, and blog posts on MT Safaris." : 'Search tour packages, destinations, and travel articles on MT Safaris.';
$headerClass     = 'solid';
require_once 'includes/header.php';
?>

<section class="page-hero" style="padding:40px 0 0">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb"><a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Search</span></div>
      <h1>Search <span style="color:var(--clr-gold)">Results</span></h1>
    </div>
  </div>
</section>

<section class="section" style="padding-top:32px">
  <div class="container">

    <!-- Search Bar -->
    <form method="GET" action="<?= url('search.php') ?>" id="searchForm" style="margin-bottom:36px">
      <div style="display:flex;gap:12px;max-width:700px;margin:0 auto">
        <div style="position:relative;flex:1">
          <i class="fas fa-search" style="position:absolute;left:18px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          <input type="text" name="q" value="<?= h($q) ?>" class="form-control" placeholder="Search packages, destinations, articles…"
                 style="padding-left:48px;font-size:1rem;height:52px;border-radius:var(--radius-full)" autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-lg" style="border-radius:var(--radius-full);padding:0 28px"><i class="fas fa-search"></i> Search</button>
      </div>

      <?php if ($q): ?>
      <!-- Filters row -->
      <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;flex-wrap:wrap">
        <?php foreach ([''=> 'All', 'package'=>'Packages', 'destination'=>'Destinations', 'blog'=>'Blog'] as $v=>$l): ?>
        <a href="?q=<?= urlencode($q) ?>&type=<?= $v ?>&sort=<?= h($sort) ?>" class="btn btn-sm <?= $type===$v?'btn-primary':'btn-outline' ?>"><?= $l ?></a>
        <?php endforeach; ?>
        <div style="width:1px;background:var(--clr-border);margin:0 4px"></div>
        <?php foreach (['relevance'=>'Most Relevant','newest'=>'Newest','price_asc'=>'Price Low','price_desc'=>'Price High'] as $v=>$l): ?>
        <a href="?q=<?= urlencode($q) ?>&type=<?= h($type) ?>&sort=<?= $v ?>" class="btn btn-sm <?= $sort===$v?'btn-gold':'btn-outline' ?>"><?= $l ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </form>

    <?php if (!$q): ?>
    <!-- Empty state -->
    <div style="text-align:center;padding:80px 24px;max-width:560px;margin:0 auto">
      <i class="fas fa-search" style="font-size:4rem;color:var(--clr-border);margin-bottom:20px"></i>
      <h2 style="color:var(--clr-primary);margin-bottom:10px">What are you looking for?</h2>
      <p style="color:var(--clr-muted)">Search for tour packages, destinations, or travel articles. Try "Masai Mara", "Kenya Safari", or "Zanzibar".</p>
      <div style="display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:24px">
        <?php foreach (['Safari','Zanzibar','Kilimanjaro','Masai Mara','Serengeti','Nairobi'] as $suggestion): ?>
        <a href="?q=<?= urlencode($suggestion) ?>" class="btn btn-outline btn-sm"><?= $suggestion ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php elseif ($totalHits === 0): ?>
    <!-- No results -->
    <div style="text-align:center;padding:80px 24px;max-width:520px;margin:0 auto">
      <i class="fas fa-search-minus" style="font-size:4rem;color:var(--clr-border);margin-bottom:20px"></i>
      <h2 style="color:var(--clr-primary);margin-bottom:10px">No results for "<?= h($q) ?>"</h2>
      <p style="color:var(--clr-muted)">Try different keywords, check your spelling, or browse our categories below.</p>
      <div style="display:flex;gap:12px;justify-content:center;margin-top:24px;flex-wrap:wrap">
        <a href="<?= url('packages.php') ?>" class="btn btn-primary">Browse Packages</a>
        <a href="<?= url('destinations.php') ?>" class="btn btn-outline">All Destinations</a>
        <a href="<?= url('contact.php') ?>" class="btn btn-outline">Ask Us</a>
      </div>
    </div>

    <?php else: ?>
    <!-- Results header -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <p style="color:var(--clr-muted)">Found <strong style="color:var(--clr-primary)"><?= number_format($totalHits) ?></strong> result<?= $totalHits!=1?'s':'' ?> for "<strong><?= h($q) ?></strong>"<?= $type?' in '.ucfirst($type).'s':'' ?></p>
    </div>

    <?php if (!$type): ?>
    <!-- ===== OVERVIEW MODE (all types mixed) ===== -->

    <?php if (!empty($pkgs)): ?>
    <div style="margin-bottom:48px">
      <div class="flex-between" style="margin-bottom:20px">
        <h2 style="font-size:1.25rem;color:var(--clr-primary)"><i class="fas fa-suitcase" style="color:var(--clr-gold);margin-right:8px"></i>Tour Packages (<?= number_format($pkgTotal) ?>)</h2>
        <?php if ($pkgTotal > 6): ?><a href="?q=<?= urlencode($q) ?>&type=package" style="color:var(--clr-sky);font-size:.875rem">View all <?= $pkgTotal ?> <i class="fas fa-arrow-right"></i></a><?php endif; ?>
      </div>
      <div class="grid-3">
        <?php foreach ($pkgs as $pkg): ?>
        <article class="package-card">
          <div class="package-card-img">
            <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>">
              <img src="<?= h($pkg['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>" alt="<?= h($pkg['title']) ?>" loading="lazy">
            </a>
            <span class="package-badge"><?= ucfirst(h($pkg['type'])) ?></span>
          </div>
          <div class="package-card-body">
            <div class="package-meta">
              <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span>
              <?php if ($pkg['dest_name']): ?><span><i class="fas fa-map-marker-alt"></i> <?= h($pkg['dest_name']) ?></span><?php endif; ?>
            </div>
            <h3 class="package-title"><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a></h3>
            <div class="package-footer">
              <div class="package-price">
                <?php if ($pkg['sale_price']): ?><span class="original"><?= money($pkg['base_price']) ?></span><span class="amount"><?= money($pkg['sale_price']) ?></span>
                <?php else: ?><span class="from">From</span><span class="amount"><?= money($pkg['base_price']) ?></span><?php endif; ?>
              </div>
              <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">View</a>
            </div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($dests)): ?>
    <div style="margin-bottom:48px">
      <div class="flex-between" style="margin-bottom:20px">
        <h2 style="font-size:1.25rem;color:var(--clr-primary)"><i class="fas fa-map-marker-alt" style="color:var(--clr-gold);margin-right:8px"></i>Destinations</h2>
        <a href="?q=<?= urlencode($q) ?>&type=destination" style="color:var(--clr-sky);font-size:.875rem">See all <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="grid-4">
        <?php foreach ($dests as $dest): ?>
        <a href="<?= url('destinations.php?slug='.h($dest['slug'])) ?>" class="destination-card">
          <img src="<?= h($dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=400&q=70') ?>" alt="<?= h($dest['name']) ?>" loading="lazy">
          <div class="destination-card-info">
            <div class="destination-card-country"><?= h($dest['region_name']??$dest['continent']??'') ?></div>
            <div class="destination-card-name"><?= h($dest['name']) ?>, <?= h($dest['country']) ?></div>
            <div class="destination-card-count"><?= $dest['package_count'] ?> packages</div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($blogs)): ?>
    <div>
      <div class="flex-between" style="margin-bottom:20px">
        <h2 style="font-size:1.25rem;color:var(--clr-primary)"><i class="fas fa-blog" style="color:var(--clr-gold);margin-right:8px"></i>Articles</h2>
        <a href="?q=<?= urlencode($q) ?>&type=blog" style="color:var(--clr-sky);font-size:.875rem">See all <i class="fas fa-arrow-right"></i></a>
      </div>
      <div class="grid-3">
        <?php foreach ($blogs as $b): ?>
        <article class="blog-card">
          <div class="blog-card-img"><a href="<?= url('blog-detail.php?slug='.h($b['slug'])) ?>"><img src="<?= h($b['featured_image'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80') ?>" alt="<?= h($b['title']) ?>" loading="lazy"></a></div>
          <div class="blog-card-body">
            <?php if ($b['category_name']): ?><span class="blog-cat"><?= h($b['category_name']) ?></span><?php endif; ?>
            <h3 class="blog-title"><a href="<?= url('blog-detail.php?slug='.h($b['slug'])) ?>"><?= h($b['title']) ?></a></h3>
            <p style="font-size:.875rem;color:var(--clr-muted);line-height:1.6"><?= h(excerpt($b['excerpt']?:strip_tags($b['content']??''),120)) ?></p>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <!-- ===== SINGLE TYPE MODE ===== -->
    <?php if ($type === 'package'): ?>
    <div class="grid-3">
      <?php foreach ($pkgs ?? [] as $pkg): ?>
      <article class="package-card">
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>">
            <img src="<?= h($pkg['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>" alt="<?= h($pkg['title']) ?>" loading="lazy">
          </a>
          <span class="package-badge"><?= ucfirst(h($pkg['type'])) ?></span>
        </div>
        <div class="package-card-body">
          <div class="package-meta">
            <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span>
            <?php if ($pkg['dest_name']): ?><span><i class="fas fa-map-marker-alt"></i> <?= h($pkg['dest_name']) ?></span><?php endif; ?>
          </div>
          <h3 class="package-title"><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a></h3>
          <div class="package-footer">
            <div class="package-price">
              <?php if ($pkg['sale_price']): ?><span class="original"><?= money($pkg['base_price']) ?></span><span class="amount"><?= money($pkg['sale_price']) ?></span>
              <?php else: ?><span class="from">From</span><span class="amount"><?= money($pkg['base_price']) ?></span><?php endif; ?>
            </div>
            <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">View</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <?php elseif ($type === 'destination'): ?>
    <div class="grid-4">
      <?php foreach ($dests ?? [] as $dest): ?>
      <a href="<?= url('destinations.php?slug='.h($dest['slug'])) ?>" class="destination-card">
        <img src="<?= h($dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=400&q=70') ?>" alt="<?= h($dest['name']) ?>" loading="lazy">
        <div class="destination-card-info">
          <div class="destination-card-country"><?= h($dest['region_name']??'') ?></div>
          <div class="destination-card-name"><?= h($dest['name']) ?>, <?= h($dest['country']) ?></div>
          <div class="destination-card-count"><?= $dest['package_count'] ?> packages</div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <?php elseif ($type === 'blog'): ?>
    <div class="grid-3">
      <?php foreach ($blogs ?? [] as $b): ?>
      <article class="blog-card">
        <div class="blog-card-img"><a href="<?= url('blog-detail.php?slug='.h($b['slug'])) ?>"><img src="<?= h($b['featured_image'] ?: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80') ?>" alt="<?= h($b['title']) ?>" loading="lazy"></a></div>
        <div class="blog-card-body">
          <?php if ($b['category_name']): ?><span class="blog-cat"><?= h($b['category_name']) ?></span><?php endif; ?>
          <h3 class="blog-title"><a href="<?= url('blog-detail.php?slug='.h($b['slug'])) ?>"><?= h($b['title']) ?></a></h3>
          <p style="font-size:.875rem;color:var(--clr-muted);line-height:1.6"><?= h(excerpt($b['excerpt']?:strip_tags($b['content']??''),120)) ?></p>
          <div class="blog-meta"><span><i class="fas fa-calendar"></i> <?= formatDate($b['published_at']??$b['created_at'],'M j, Y') ?></span><span><i class="fas fa-eye"></i> <?= number_format($b['views']) ?></span></div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($pages > 1): ?>
    <div style="margin-top:40px"><?= paginationHtml($totalHits, $pages, $page, url("search.php?q=".urlencode($q)."&type=$type&sort=$sort")) ?></div>
    <?php endif; ?>
    <?php endif; // single type mode ?>
    <?php endif; // has results ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
