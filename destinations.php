<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$slug   = trim($_GET['slug'] ?? '');
$region = trim($_GET['region'] ?? '');

// ── Single destination detail ─────────────────────────────────────────────
if ($slug) {
    $dest = getDestinationBySlug($slug);
    if (!$dest) redirect(url('destinations.php'));

    $pkgs       = DB::rows("SELECT p.*, c.name AS category_name FROM packages p LEFT JOIN categories c ON p.category_id=c.id WHERE p.destination_id=? AND p.is_active=1 ORDER BY p.is_featured DESC LIMIT 6", [$dest['id']]);
    $highlights = jd($dest['highlights'] ?? '[]') ?: [];
    $gallery    = jd($dest['gallery']    ?? '[]') ?: [];
    $nearby     = DB::rows("SELECT id,name,slug,country,hero_image,(SELECT COUNT(*) FROM packages WHERE destination_id=destinations.id AND is_active=1) AS pkg_count FROM destinations WHERE id!=? AND is_active=1 ORDER BY is_featured DESC, RAND() LIMIT 4", [$dest['id']]);

    $pageTitle       = ($dest['meta_title']       ?: $dest['name'] . ' — MT Safaris');
    $pageDescription = ($dest['meta_description'] ?: 'Explore ' . $dest['name'] . ', ' . $dest['country'] . ' with MT Safaris. Book tours, safaris, and travel packages.');
    $pageImage       = $dest['hero_image'] ?? '';
    $headerClass     = 'transparent';
    $jsonLd = schemaDestination($dest)
            . schemaBreadcrumb([
                ['name' => 'Home',         'url' => url()],
                ['name' => 'Destinations', 'url' => url('destinations.php')],
                ['name' => $dest['name'],  'url' => url('destinations.php?slug=' . $dest['slug'])],
              ]);
    require_once 'includes/header.php';
?>

<!-- ── Full-height hero ──────────────────────────────────────────────── -->
<div class="dest-hero">
  <img src="<?= h($dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1600&q=85') ?>"
       alt="<?= h($dest['name']) ?>" class="dest-hero-img" fetchpriority="high" decoding="async">
  <div class="dest-hero-overlay"></div>
  <div class="dest-hero-content container">
    <div class="breadcrumb" style="margin-bottom:20px">
      <a href="<?= url() ?>" style="color:rgba(255,255,255,.7)">Home</a>
      <i class="fas fa-chevron-right" style="color:rgba(255,255,255,.4)"></i>
      <a href="<?= url('destinations.php') ?>" style="color:rgba(255,255,255,.7)">Destinations</a>
      <i class="fas fa-chevron-right" style="color:rgba(255,255,255,.4)"></i>
      <span style="color:#fff"><?= h($dest['name']) ?></span>
    </div>
    <div style="display:inline-flex;align-items:center;gap:6px;background:var(--clr-gold);color:#fff;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;padding:4px 12px;border-radius:20px;margin-bottom:14px">
      <i class="fas fa-map-marker-alt"></i> <?= h($dest['continent'] ?: $dest['country']) ?>
    </div>
    <h1 class="dest-hero-title"><?= h($dest['name']) ?><span style="color:var(--clr-gold)">,</span> <?= h($dest['country']) ?></h1>
    <!-- Stat pills -->
    <div class="dest-hero-stats">
      <?php if ($dest['best_time']): ?>
      <div class="dest-stat-pill"><i class="fas fa-sun"></i><div><span class="dsp-label">Best Time</span><span class="dsp-val"><?= h($dest['best_time']) ?></span></div></div>
      <?php endif; ?>
      <div class="dest-stat-pill"><i class="fas fa-suitcase-rolling"></i><div><span class="dsp-label">Packages</span><span class="dsp-val"><?= count($pkgs) ?> Available</span></div></div>
      <?php if ($dest['latitude']): ?>
      <div class="dest-stat-pill"><i class="fas fa-map-marker-alt"></i><div><span class="dsp-label">Coordinates</span><span class="dsp-val"><?= round($dest['latitude'],2) ?>°, <?= round($dest['longitude'],2) ?>°</span></div></div>
      <?php endif; ?>
    </div>
  </div>
  <!-- Scroll cue -->
  <div style="position:absolute;bottom:28px;left:50%;transform:translateX(-50%);text-align:center;animation:bounce 2s infinite">
    <i class="fas fa-chevron-down" style="color:rgba(255,255,255,.6);font-size:1.3rem"></i>
  </div>
</div>

<!-- ── Tab nav ───────────────────────────────────────────────────────── -->
<div class="dest-tab-bar" id="destTabBar">
  <div class="container" style="display:flex;gap:0;overflow-x:auto">
    <?php foreach ([
      ['overview',   'fas fa-info-circle', 'Overview'],
      ['highlights', 'fas fa-star',        'Highlights'],
      ['climate',    'fas fa-cloud-sun',   'When to Visit'],
      ['packages',   'fas fa-suitcase',    'Packages ('.count($pkgs).')'],
      ['map',        'fas fa-map',         'Map'],
    ] as [$id, $icon, $label]): ?>
    <a href="#<?= $id ?>" class="dest-tab" data-tab="<?= $id ?>"><?= "<i class='$icon'></i>" ?> <?= $label ?></a>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── Overview ─────────────────────────────────────────────────────── -->
<section class="section" id="overview" style="padding-top:56px">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:48px;align-items:start">

      <div>
        <h2 style="color:var(--clr-primary);margin-bottom:20px">About <?= h($dest['name']) ?></h2>
        <p style="font-size:1.05rem;color:var(--clr-muted);line-height:1.9"><?= nl2br(h($dest['description'])) ?></p>

        <?php if ($gallery): ?>
        <h3 style="color:var(--clr-primary);margin:40px 0 16px">Gallery</h3>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;border-radius:12px;overflow:hidden">
          <?php foreach (array_slice($gallery, 0, 6) as $img): ?>
          <a href="<?= h($img) ?>" target="_blank" style="display:block;aspect-ratio:4/3;overflow:hidden">
            <img src="<?= h($img) ?>" loading="lazy" decoding="async" alt="" style="width:100%;height:100%;object-fit:cover;transition:transform .4s" onmouseover="this.style.transform='scale(1.06)'" onmouseout="this.style.transform=''">
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div style="position:sticky;top:calc(var(--header-h, 80px) + 60px)">
        <!-- Quick info card -->
        <div class="card" style="margin-bottom:16px">
          <div class="card-header"><h3 style="font-size:.95rem"><i class="fas fa-info-circle" style="color:var(--clr-gold)"></i> Quick Info</h3></div>
          <div class="card-body" style="padding:0">
            <?php foreach ([
              ['fas fa-globe',        'Country',        $dest['country']],
              ['fas fa-layer-group',  'Continent',      $dest['continent'] ?: '—'],
              ['fas fa-sun',          'Best Time',      $dest['best_time'] ?: 'Year-round'],
              ['fas fa-suitcase',     'Packages',       count($pkgs).' available'],
            ] as [$icon, $lbl, $val]): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:12px 20px;border-bottom:1px solid var(--clr-border)">
              <div style="width:34px;height:34px;background:var(--clr-light);border-radius:8px;display:grid;place-items:center;flex-shrink:0">
                <i class="<?= $icon ?>" style="color:var(--clr-gold);font-size:.8rem"></i>
              </div>
              <div>
                <div style="font-size:.68rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.06em"><?= $lbl ?></div>
                <div style="font-size:.875rem;font-weight:600;color:var(--clr-primary)"><?= h($val) ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- CTA card -->
        <div style="background:var(--clr-primary);border-radius:16px;padding:24px;text-align:center;margin-bottom:16px">
          <div style="font-size:2rem;margin-bottom:10px">🌍</div>
          <h4 style="color:#fff;margin-bottom:8px">Ready to Explore?</h4>
          <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin-bottom:18px">Let our experts plan your perfect <?= h($dest['name']) ?> experience.</p>
          <a href="<?= url('contact.php') ?>" class="btn btn-gold btn-block" style="margin-bottom:8px"><i class="fas fa-paper-plane"></i> Request a Quote</a>
          <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>?text=Hi!+I%27m+interested+in+visiting+<?= urlencode($dest['name']) ?>" target="_blank" class="btn btn-block" style="background:#25D366;color:#fff"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
        </div>

        <!-- Price from -->
        <?php if ($pkgs): $minPrice = min(array_column($pkgs, 'base_price')); ?>
        <div style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:18px;text-align:center">
          <div style="font-size:.72rem;color:#92400e;text-transform:uppercase;letter-spacing:.08em;margin-bottom:4px">Packages from</div>
          <div style="font-size:2rem;font-weight:800;color:var(--clr-primary)"><?= money($minPrice) ?></div>
          <div style="font-size:.75rem;color:#92400e">per person</div>
          <a href="#packages" class="btn btn-primary btn-sm" style="margin-top:12px">View Packages</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<!-- ── Highlights ────────────────────────────────────────────────────── -->
<?php if ($highlights): ?>
<section class="section" id="highlights" style="background:var(--clr-light)">
  <div class="container">
    <div class="section-header">
      <h2>Highlights</h2>
      <p>What makes <?= h($dest['name']) ?> unforgettable</p>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px;margin-top:40px">
      <?php
      $hlIcons = ['fas fa-star','fas fa-binoculars','fas fa-camera','fas fa-hiking','fas fa-water','fas fa-leaf','fas fa-sun','fas fa-mountain'];
      foreach ($highlights as $i => $hl): ?>
      <div class="dest-highlight-card" style="animation-delay:<?= $i * 60 ?>ms">
        <div class="dhc-icon"><i class="<?= $hlIcons[$i % count($hlIcons)] ?>"></i></div>
        <span><?= h($hl) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Climate / When to visit ──────────────────────────────────────── -->
<?php if ($dest['climate_info'] || $dest['best_time']): ?>
<section class="section" id="climate">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:center">
      <div>
        <div style="display:inline-flex;align-items:center;gap:8px;background:#ebf8ff;color:#2b6cb0;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;padding:5px 14px;border-radius:20px;margin-bottom:16px">
          <i class="fas fa-cloud-sun"></i> Climate & Seasons
        </div>
        <h2 style="color:var(--clr-primary);margin-bottom:16px">When to Visit <?= h($dest['name']) ?></h2>
        <?php if ($dest['climate_info']): ?>
        <p style="color:var(--clr-muted);line-height:1.9;margin-bottom:24px"><?= nl2br(h($dest['climate_info'])) ?></p>
        <?php endif; ?>
        <?php if ($dest['best_time']): ?>
        <div style="display:flex;align-items:center;gap:14px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:16px 20px">
          <div style="font-size:2rem">☀️</div>
          <div>
            <div style="font-size:.72rem;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:3px">Peak Season</div>
            <div style="font-size:1.05rem;font-weight:800;color:var(--clr-primary)"><?= h($dest['best_time']) ?></div>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <!-- Visual season wheel placeholder -->
      <div style="background:var(--clr-light);border-radius:20px;padding:32px;text-align:center">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px">
          <?php
          $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
          $best   = strtolower($dest['best_time'] ?? '');
          $peakMonths = [];
          // rough heuristic: mark months mentioned in best_time string
          foreach ($months as $m) { if (stripos($best, $m) !== false || stripos($best, date('F', mktime(0,0,0,array_search($m,$months)+1,1))) !== false) $peakMonths[] = $m; }
          foreach ($months as $m):
            $isPeak = count($peakMonths) === 0 || in_array($m, $peakMonths);
          ?>
          <div style="background:<?= $isPeak ? 'var(--clr-gold)' : 'var(--clr-border)' ?>;color:<?= $isPeak ? '#fff' : 'var(--clr-muted)' ?>;border-radius:8px;padding:10px 6px;font-size:.75rem;font-weight:700">
            <?= $m ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:16px;justify-content:center;margin-top:16px;font-size:.75rem">
          <span style="display:flex;align-items:center;gap:5px"><span style="width:12px;height:12px;background:var(--clr-gold);border-radius:3px;display:inline-block"></span> Peak season</span>
          <span style="display:flex;align-items:center;gap:5px"><span style="width:12px;height:12px;background:var(--clr-border);border-radius:3px;display:inline-block"></span> Off-peak</span>
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Packages ──────────────────────────────────────────────────────── -->
<?php if ($pkgs): ?>
<section class="section" id="packages" style="background:var(--clr-light)">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;margin-bottom:40px">
      <div>
        <h2 style="color:var(--clr-primary)">Packages in <?= h($dest['name']) ?></h2>
        <p style="color:var(--clr-muted);"><?= count($pkgs) ?> curated trip<?= count($pkgs) > 1 ? 's' : '' ?> available</p>
      </div>
      <a href="<?= url('packages.php?destination='.$dest['id']) ?>" class="btn btn-outline"><i class="fas fa-th-large"></i> View All</a>
    </div>
    <div class="grid-3">
      <?php foreach ($pkgs as $pkg): ?>
      <article class="package-card">
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>">
            <img src="<?= h($pkg['hero_image'] ?: $dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>" alt="<?= h($pkg['title']) ?>" loading="lazy" decoding="async">
          </a>
          <?php if ($pkg['is_featured']): ?><span class="package-badge package-badge-featured">Featured</span><?php endif; ?>
        </div>
        <div class="package-card-body">
          <div class="package-meta">
            <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span>
            <span><i class="fas fa-users"></i> <?= $pkg['min_pax'] ?>+ Pax</span>
            <?php if ($pkg['category_name']): ?><span><i class="fas fa-tag"></i> <?= h($pkg['category_name']) ?></span><?php endif; ?>
          </div>
          <h3 class="package-title"><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a></h3>
          <div class="package-footer">
            <div class="package-price"><span class="from">From</span><span class="amount"><?= money($pkg['base_price']) ?></span></div>
            <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">View Trip</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ── Map ──────────────────────────────────────────────────────────── -->
<?php if ($dest['latitude'] && $dest['longitude']): ?>
<section class="section" id="map" style="padding-bottom:0">
  <div class="container" style="margin-bottom:40px">
    <h2 style="color:var(--clr-primary);margin-bottom:8px">Location</h2>
    <p style="color:var(--clr-muted)"><?= h($dest['name']) ?>, <?= h($dest['country']) ?></p>
  </div>
  <div id="destMap" style="height:420px;width:100%"></div>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="">
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    var lat = <?= (float)$dest['latitude'] ?>, lng = <?= (float)$dest['longitude'] ?>;
    var map = L.map('destMap', {scrollWheelZoom: false}).setView([lat, lng], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    var icon = L.divIcon({
      html: '<div style="background:var(--clr-gold,#F6A229);width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.3)"></div>',
      iconSize: [36,36], iconAnchor:[18,36], className:''
    });
    L.marker([lat, lng], {icon: icon}).addTo(map)
      .bindPopup('<strong><?= h($dest['name']) ?></strong><br><?= h($dest['country']) ?>').openPopup();
  });
  </script>
</section>
<?php endif; ?>

<!-- ── Nearby destinations ───────────────────────────────────────────── -->
<?php if ($nearby): ?>
<section class="section">
  <div class="container">
    <div class="section-header">
      <h2>Explore More Destinations</h2>
      <p>Other places you might love</p>
    </div>
    <div class="grid-4" style="margin-top:40px">
      <?php foreach ($nearby as $nd): ?>
      <a href="<?= url('destinations.php?slug='.h($nd['slug'])) ?>" class="destination-card">
        <img src="<?= h($nd['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=500&q=75') ?>" alt="<?= h($nd['name']) ?>" loading="lazy" decoding="async">
        <div class="destination-card-info">
          <div class="destination-card-country"><?= h($nd['country']) ?></div>
          <div class="destination-card-name"><?= h($nd['name']) ?></div>
          <div class="destination-card-count"><?= $nd['pkg_count'] ?> package<?= $nd['pkg_count'] != 1 ? 's' : '' ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<style>
/* ── Destination hero ── */
.dest-hero { position:relative; height:92vh; min-height:580px; display:flex; align-items:flex-end; overflow:hidden; }
.dest-hero-img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; }
.dest-hero-overlay { position:absolute; inset:0; background:linear-gradient(to top, rgba(5,20,40,.92) 0%, rgba(5,20,40,.45) 55%, rgba(5,20,40,.1) 100%); }
.dest-hero-content { position:relative; z-index:1; padding-bottom:60px; }
.dest-hero-title { font-size:clamp(2.2rem,5vw,4rem); color:#fff; font-weight:900; line-height:1.1; margin-bottom:28px; }
.dest-hero-stats { display:flex; flex-wrap:wrap; gap:12px; }
.dest-stat-pill { display:flex; align-items:center; gap:10px; background:rgba(255,255,255,.12); -webkit-backdrop-filter:blur(12px); backdrop-filter:blur(12px); border:1px solid rgba(255,255,255,.2); border-radius:50px; padding:10px 18px; color:#fff; }
.dest-stat-pill i { color:var(--clr-gold); font-size:.85rem; flex-shrink:0; }
.dest-stat-pill .dsp-label { display:block; font-size:.62rem; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,.6); line-height:1; margin-bottom:2px; }
.dest-stat-pill .dsp-val { display:block; font-size:.85rem; font-weight:700; line-height:1; }

/* ── Tab bar ── */
.dest-tab-bar { background:#fff; border-bottom:2px solid var(--clr-border); position:sticky; top:var(--header-h,80px); z-index:90; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.dest-tab { display:inline-flex; align-items:center; gap:7px; padding:16px 22px; font-size:.85rem; font-weight:600; color:var(--clr-muted); text-decoration:none; border-bottom:2px solid transparent; margin-bottom:-2px; white-space:nowrap; transition:color .2s,border-color .2s; }
.dest-tab:hover, .dest-tab.active { color:var(--clr-primary); border-bottom-color:var(--clr-gold); }

/* ── Highlight cards ── */
.dest-highlight-card { display:flex; align-items:center; gap:14px; background:#fff; border:1px solid var(--clr-border); border-radius:12px; padding:16px 18px; transition:box-shadow .2s,transform .2s; }
.dest-highlight-card:hover { box-shadow:var(--shadow-md); transform:translateY(-2px); }
.dhc-icon { width:40px; height:40px; background:linear-gradient(135deg,var(--clr-gold),#e8c56c); border-radius:10px; display:grid; place-items:center; flex-shrink:0; }
.dhc-icon i { color:#fff; font-size:.9rem; }
.dest-highlight-card span { font-size:.875rem; font-weight:600; color:var(--clr-primary); line-height:1.4; }

@keyframes bounce { 0%,100%{transform:translateX(-50%) translateY(0)} 50%{transform:translateX(-50%) translateY(8px)} }
@media(max-width:768px) {
  .dest-hero { height:75vh; min-height:480px; }
  .dest-hero-content { padding-bottom:40px; }
}
</style>

<script>
// Highlight active tab on scroll
(function() {
  const tabs = document.querySelectorAll('.dest-tab');
  const sections = ['overview','highlights','climate','packages','map'].map(id => document.getElementById(id)).filter(Boolean);
  const bar = document.getElementById('destTabBar');
  function onScroll() {
    const scrollY = window.scrollY + (bar ? bar.offsetHeight : 0) + 90;
    let active = sections[0];
    sections.forEach(s => { if (s && s.offsetTop <= scrollY) active = s; });
    tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === (active && active.id)));
  }
  window.addEventListener('scroll', onScroll, {passive:true});
  onScroll();
  // Smooth-scroll on tab click
  tabs.forEach(t => t.addEventListener('click', function(e) {
    e.preventDefault();
    const target = document.getElementById(this.dataset.tab);
    if (target) target.scrollIntoView({behavior:'smooth', block:'start'});
  }));
})();
</script>

<?php
    // Clean up and jump to footer
    require_once 'includes/footer.php';
    exit;

} // end single destination

// ── Destinations Listing ──────────────────────────────────────────────────
$pageTitle       = 'Destinations — Explore Africa & Beyond | MT Safaris';
$pageDescription = 'Explore breathtaking destinations across Africa, the Indian Ocean, Middle East, and Europe with MT Safaris premium travel packages.';
$headerClass     = 'solid';
require_once 'includes/header.php';

$regions  = DB::rows("SELECT * FROM regions ORDER BY sort_order ASC");
$allDests = DB::rows("SELECT d.*, r.name AS region_name, (SELECT COUNT(*) FROM packages p WHERE p.destination_id=d.id AND p.is_active=1) AS package_count
                      FROM destinations d LEFT JOIN regions r ON d.region_id=r.id
                      WHERE d.is_active=1
                      " . ($region ? "AND r.slug=?" : "") . "
                      ORDER BY d.sort_order ASC, d.name ASC",
                      $region ? [$region] : []);
?>
<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb"><a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Destinations</span></div>
      <h1>Explore <span style="color:var(--clr-gold)">Destinations</span></h1>
      <p>Discover <?= count($allDests) ?>+ stunning destinations across Africa and the world.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <!-- Region Filter -->
    <div class="category-tabs" style="margin-bottom:40px">
      <a href="<?= url('destinations.php') ?>" class="cat-tab <?= !$region?'active':'' ?>"><i class="fas fa-globe"></i> All Regions</a>
      <?php foreach ($regions as $r): ?>
      <a href="?region=<?= h($r['slug']) ?>" class="cat-tab <?= $region===$r['slug']?'active':'' ?>">
        <i class="fas fa-map-marker-alt"></i> <?= h($r['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div class="grid-4">
      <?php foreach ($allDests as $i => $dest): ?>
      <a href="<?= url('destinations.php?slug='.h($dest['slug'])) ?>" class="destination-card" data-animate data-delay="<?= ($i%4)*80 ?>">
        <img src="<?= h($dest['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=500&q=75') ?>" alt="<?= h($dest['name']) ?>" loading="lazy" decoding="async">
        <div class="destination-card-info">
          <div class="destination-card-country"><?= h($dest['region_name']??$dest['continent']??'') ?></div>
          <div class="destination-card-name"><?= h($dest['name']) ?>, <?= h($dest['country']) ?></div>
          <div class="destination-card-count"><?= $dest['package_count'] ?> package<?= $dest['package_count']!=1?'s':'' ?> available</div>
        </div>
      </a>
      <?php endforeach; ?>
      <?php if (!$allDests): ?>
      <div style="grid-column:span 4;text-align:center;padding:60px;color:var(--clr-muted)">No destinations found for this region.</div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php require_once 'includes/footer.php'; ?>
