<?php
$pageTitle       = 'Tour Packages — Safaris, Holidays, Honeymoon & More';
$pageDescription = 'Browse MT Safaris premium tour packages — African safaris, holiday packages, luxury tours, honeymoon escapes, corporate travel and custom adventures.';
$headerClass     = 'solid';

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Fetch featured packages for structured data before the header include
$_featuredForSchema = DB::rows("SELECT title, slug FROM packages WHERE is_active=1 AND is_featured=1 ORDER BY sort_order LIMIT 12");
$jsonLd = schemaItemList($_featuredForSchema, url('packages.php'), 'Tour Packages — MT Safaris');
unset($_featuredForSchema);

require_once 'includes/header.php';

$categories = getCategories();
$destinations = DB::rows("SELECT id, name, country FROM destinations WHERE is_active = 1 ORDER BY name ASC");

// Filters
$filters = [
  'category'  => $_GET['category']  ?? '',
  'type'      => $_GET['type']      ?? '',
  'destination'=> (int)($_GET['destination'] ?? 0),
  'min_price' => (float)($_GET['min_price'] ?? 0),
  'max_price' => (float)($_GET['max_price'] ?? 0),
  'duration'  => (int)($_GET['duration']  ?? 0),
  'search'    => trim($_GET['search'] ?? ''),
  'sort'      => $_GET['sort'] ?? '',
];
$page   = max(1, (int)($_GET['page'] ?? 1));
$result = getPackages($filters, $page);
$packages = $result['rows'];
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a>
        <i class="fas fa-chevron-right"></i>
        <span>Tour Packages</span>
      </div>
      <h1>Our Tour <span style="color:var(--clr-gold)">Packages</span></h1>
      <p>Discover <?= $result['total'] ?> handcrafted travel experiences designed for every adventurer.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:280px 1fr;gap:32px;align-items:start">

      <!-- FILTERS SIDEBAR -->
      <aside>
        <form method="GET" action="">
          <div class="filters-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
              <h3 style="font-size:1rem;color:var(--clr-primary)"><i class="fas fa-sliders-h" style="margin-right:8px;color:var(--clr-gold)"></i>Filters</h3>
              <a href="<?= url('packages.php') ?>" style="font-size:.78rem;color:var(--clr-danger)">Clear All</a>
            </div>

            <!-- Search -->
            <div class="filter-group">
              <h4>Search</h4>
              <div style="position:relative">
                <input type="text" name="search" value="<?= h($filters['search']) ?>" placeholder="Search packages…"
                       class="form-control" style="padding-left:36px;font-size:.85rem">
                <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted);font-size:.85rem"></i>
              </div>
            </div>

            <!-- Category -->
            <div class="filter-group">
              <h4>Tour Category</h4>
              <?php foreach ($categories as $cat): ?>
              <label class="filter-option">
                <input type="radio" name="category" value="<?= h($cat['slug']) ?>"
                       <?= $filters['category'] === $cat['slug'] ? 'checked' : '' ?>>
                <?= h($cat['name']) ?>
              </label>
              <?php endforeach; ?>
              <label class="filter-option">
                <input type="radio" name="category" value="" <?= !$filters['category'] ? 'checked' : '' ?>> All Categories
              </label>
            </div>

            <!-- Price Range Slider -->
            <div class="filter-group">
              <h4>Price Range (USD) <span id="priceLabel" style="float:right;font-weight:700;color:var(--clr-primary);font-size:.82rem"></span></h4>
              <div class="price-slider-wrap">
                <div class="price-slider-track" id="priceTrack">
                  <div class="price-slider-fill" id="priceFill"></div>
                  <input type="range" class="price-slider-input" id="sliderMin"
                         min="0" max="10000" step="50"
                         value="<?= $filters['min_price'] ?: 0 ?>">
                  <input type="range" class="price-slider-input" id="sliderMax"
                         min="0" max="10000" step="50"
                         value="<?= $filters['max_price'] ?: 10000 ?>">
                </div>
                <div class="price-slider-labels">
                  <span>$0</span><span>$10,000+</span>
                </div>
              </div>
              <input type="hidden" name="min_price" id="minPriceInput" value="<?= $filters['min_price'] ?: '' ?>">
              <input type="hidden" name="max_price" id="maxPriceInput" value="<?= $filters['max_price'] ?: '' ?>">
            </div>

            <!-- Duration -->
            <div class="filter-group">
              <h4>Duration</h4>
              <?php foreach ([['3','Up to 3 Days'],['7','Up to 1 Week'],['14','Up to 2 Weeks'],['21','Up to 3 Weeks']] as $d): ?>
              <label class="filter-option">
                <input type="radio" name="duration" value="<?= $d[0] ?>" <?= $filters['duration'] == $d[0] ? 'checked' : '' ?>>
                <?= $d[1] ?>
              </label>
              <?php endforeach; ?>
              <label class="filter-option">
                <input type="radio" name="duration" value="" <?= !$filters['duration'] ? 'checked' : '' ?>> Any Duration
              </label>
            </div>

            <!-- Destination -->
            <div class="filter-group">
              <h4>Destination</h4>
              <select name="destination" class="form-control" style="font-size:.85rem">
                <option value="">All Destinations</option>
                <?php foreach ($destinations as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $filters['destination'] == $d['id'] ? 'selected' : '' ?>>
                  <?= h($d['name']) ?>, <?= h($d['country']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block">
              <i class="fas fa-search"></i> Apply Filters
            </button>
          </div>
        </form>

        <!-- Quick Links -->
        <div class="filters-card" style="margin-top:16px">
          <h4 style="font-size:.875rem;color:var(--clr-primary);margin-bottom:14px">Quick Picks</h4>
          <div style="display:flex;flex-direction:column;gap:6px">
            <a href="?type=safari" class="btn btn-outline btn-sm"><i class="fas fa-paw"></i> Safari Tours</a>
            <a href="?type=honeymoon" class="btn btn-outline btn-sm"><i class="fas fa-heart"></i> Honeymoon</a>
            <a href="?type=luxury" class="btn btn-outline btn-sm"><i class="fas fa-gem"></i> Luxury Tours</a>
            <a href="?type=corporate" class="btn btn-outline btn-sm"><i class="fas fa-briefcase"></i> Corporate</a>
          </div>
        </div>
      </aside>

      <!-- PACKAGES GRID -->
      <div>
        <!-- Results Bar -->
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
          <p style="color:var(--clr-muted);font-size:.875rem">
            Showing <strong><?= count($packages) ?></strong> of <strong><?= $result['total'] ?></strong> packages
            <?= $filters['search'] ? ' for "' . h($filters['search']) . '"' : '' ?>
          </p>
          <form method="GET" style="display:flex;gap:8px;align-items:center">
            <?php foreach ($filters as $k => $v): if ($k !== 'sort' && $v): ?>
            <input type="hidden" name="<?= h($k) ?>" value="<?= h($v) ?>">
            <?php endif; endforeach; ?>
            <label style="font-size:.82rem;color:var(--clr-muted)">Sort:</label>
            <select name="sort" class="form-control" style="width:auto;padding:8px 12px;font-size:.82rem" onchange="this.form.submit()">
              <option value="" <?= !$filters['sort'] ? 'selected' : '' ?>>Featured</option>
              <option value="price_asc"  <?= $filters['sort']==='price_asc'  ? 'selected' : '' ?>>Price: Low to High</option>
              <option value="price_desc" <?= $filters['sort']==='price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              <option value="rating"     <?= $filters['sort']==='rating'     ? 'selected' : '' ?>>Top Rated</option>
              <option value="popular"    <?= $filters['sort']==='popular'    ? 'selected' : '' ?>>Most Popular</option>
            </select>
          </form>
        </div>

        <?php if ($packages): ?>
        <div class="grid-auto" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
          <?php foreach ($packages as $pkg): ?>
          <article class="package-card" data-animate
                   data-cmp-id="<?= $pkg['id'] ?>"
                   data-cmp-title="<?= h($pkg['title']) ?>"
                   data-cmp-price="<?= money($pkg['sale_price'] ?? $pkg['base_price']) ?>"
                   data-cmp-duration="<?= $pkg['duration_days'] ?>D/<?= $pkg['duration_nights'] ?>N"
                   data-cmp-type="<?= h($pkg['type']) ?>"
                   data-cmp-rating="<?= $pkg['rating'] ? number_format($pkg['rating'],1) : 'N/A' ?>"
                   data-cmp-img="<?= h($pkg['hero_image'] ?: '') ?>"
                   data-cmp-url="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>"
                   data-cmp-dest="<?= h($pkg['destination_name'] ?? '') ?>">
            <div class="package-card-img">
              <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>">
                <img src="<?= h($pkg['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>"
                     alt="<?= h($pkg['title']) ?>" loading="lazy" decoding="async">
              </a>
              <span class="package-badge <?= $pkg['type']==='luxury'?'package-badge-blue':'' ?>"><?= ucfirst(h($pkg['type'])) ?></span>
              <?php if ($pkg['sale_price'] && $pkg['sale_price'] < $pkg['base_price']): ?>
              <span class="package-badge" style="top:auto;bottom:14px;left:14px;background:var(--clr-danger)">
                <?= round((1-$pkg['sale_price']/$pkg['base_price'])*100) ?>% OFF
              </span>
              <?php endif; ?>
              <!-- Compare checkbox -->
              <div class="compare-check-wrap">
                <input type="checkbox" id="cmp<?= $pkg['id'] ?>" class="cmp-check" data-id="<?= $pkg['id'] ?>">
                <label for="cmp<?= $pkg['id'] ?>" class="compare-check-label">
                  <i class="fas fa-check-square"></i> Compare
                </label>
              </div>
              <button class="package-wishlist" data-id="<?= $pkg['id'] ?>"><i class="far fa-heart"></i></button>
            </div>
            <div class="package-card-body">
              <div class="package-meta">
                <span><i class="fas fa-map-marker-alt"></i> <?= h($pkg['destination_name'] ?? '') ?></span>
                <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?>D/<?= $pkg['duration_nights'] ?>N</span>
                <?php if ($pkg['rating']): ?>
                <span><i class="fas fa-star" style="color:var(--clr-gold)"></i> <?= number_format($pkg['rating'],1) ?> (<?= $pkg['review_count'] ?>)</span>
                <?php endif; ?>
              </div>
              <h3 class="package-title">
                <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a>
              </h3>
              <p class="package-excerpt"><?= h(excerpt($pkg['tagline'] ?: $pkg['description'], 100)) ?></p>
              <div class="package-footer">
                <div class="package-price">
                  <?php if ($pkg['sale_price'] && $pkg['sale_price'] < $pkg['base_price']): ?>
                  <span style="font-size:.75rem;text-decoration:line-through;color:var(--clr-muted)"><?= money($pkg['base_price']) ?></span>
                  <div><span class="amount" style="color:var(--clr-danger)"><?= money($pkg['sale_price']) ?></span> <span class="per">/ person</span></div>
                  <?php else: ?>
                  <span class="from">From</span>
                  <div><span class="amount"><?= money($pkg['base_price']) ?></span> <span class="per">/ person</span></div>
                  <?php endif; ?>
                </div>
                <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">View</a>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php
        $baseUrl = url('packages.php?' . http_build_query(array_filter($filters)));
        echo paginationHtml($result['total'], $result['pages'], $result['page'], $baseUrl);
        ?>

        <?php else: ?>
        <div class="text-center" style="padding:80px 20px">
          <i class="fas fa-search" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
          <h3 style="color:var(--clr-primary);margin-bottom:8px">No packages found</h3>
          <p class="text-muted" style="margin-bottom:24px">Try adjusting your filters or <a href="<?= url('contact.php#quote') ?>" style="color:var(--clr-gold)">request a custom package</a>.</p>
          <a href="<?= url('packages.php') ?>" class="btn btn-primary">Clear Filters</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section style="background:var(--clr-primary);padding:72px 0">
  <div class="container text-center">
    <h2 style="color:#fff;margin-bottom:12px">Can't Find What You're Looking For?</h2>
    <p style="color:rgba(255,255,255,.8);margin-bottom:28px">We create custom travel itineraries tailored precisely to your needs and budget.</p>
    <a href="<?= url('contact.php#quote') ?>" class="btn btn-gold btn-lg"><i class="fas fa-magic"></i> Request a Custom Package</a>
  </div>
</section>

<!-- Compare floating bar -->
<div id="compareBar">
  <div style="font-size:.8rem;font-weight:700;color:rgba(255,255,255,.7);white-space:nowrap">
    Compare <span class="compare-count-badge" id="compareBadge">0</span>
  </div>
  <div class="compare-bar-slots" id="compareSlots">
    <div class="compare-slot" id="cmpSlot0"><i class="fas fa-plus"></i></div>
    <div class="compare-slot" id="cmpSlot1"><i class="fas fa-plus"></i></div>
    <div class="compare-slot" id="cmpSlot2"><i class="fas fa-plus"></i></div>
  </div>
  <button id="compareNow" disabled><i class="fas fa-columns"></i> Compare Now</button>
  <button id="compareClear"><i class="fas fa-times"></i> Clear</button>
</div>

<!-- Compare modal -->
<div id="compareModal">
  <div class="compare-modal-box">
    <div class="compare-modal-header">
      <h3><i class="fas fa-columns" style="margin-right:8px;color:var(--clr-gold)"></i>Package Comparison</h3>
      <button id="compareModalClose"><i class="fas fa-times"></i></button>
    </div>
    <div style="overflow-x:auto">
      <table class="compare-table" id="compareTable"></table>
    </div>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
