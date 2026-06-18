<?php
$pageTitle       = 'Discover Exceptional Travel Experiences Worldwide';
$pageDescription = 'MT Safaris — East Africa\'s premier travel company. Corporate travel, luxury safaris, holiday packages, honeymoon escapes, and adventure tours.';
$headerClass     = 'transparent';

require_once 'includes/header.php';

$featuredPackages    = getFeaturedPackages(6);
$featuredDestinations = getFeaturedDestinations(8);
$testimonials        = getTestimonials(6);
$recentPosts         = getRecentPosts(3);
$categories          = getCategories();
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero">
  <!-- Slideshow -->
  <div class="hero-media" style="position:absolute;inset:0;overflow:hidden">
    <?php
    $heroSlides = [
      ['https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1920&q=85', 'Masai Mara Safari'],
      ['https://images.unsplash.com/photo-1547981609-4b6bfe67ca0b?w=1920&q=85', 'Serengeti Wildlife'],
      ['https://images.unsplash.com/photo-1504432842672-1a79f78e4084?w=1920&q=85', 'Zanzibar Beach'],
      ['https://images.unsplash.com/photo-1589553416260-f586c8f1514f?w=1920&q=85', 'Mount Kilimanjaro'],
    ];
    foreach ($heroSlides as $i => $slide): ?>
    <div class="hero-slide<?= $i === 0 ? ' active' : '' ?>" data-index="<?= $i ?>">
      <img src="<?= $slide[0] ?>" alt="<?= $slide[1] ?>" <?= $i === 0 ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"' ?>>
    </div>
    <?php endforeach; ?>
  </div>
  <div class="hero-overlay"></div>
  <!-- Slide dots -->
  <div class="hero-dots" id="heroDots">
    <?php foreach ($heroSlides as $i => $_): ?>
    <button class="hero-dot<?= $i === 0 ? ' active' : '' ?>" data-slide="<?= $i ?>" aria-label="Slide <?= $i+1 ?>"></button>
    <?php endforeach; ?>
  </div>

  <div class="container" style="position:relative;z-index:1;padding-top:calc(var(--header-h) + 40px);padding-bottom:60px">
    <div style="max-width:720px">

      <div class="hero-badge anim-down">
        <i class="fas fa-star"></i> #1 Rated Travel Company in East Africa
      </div>

      <h1 class="hero-title anim-up" style="animation-delay:.1s">
        Discover <span>Exceptional</span><br>Travel Experiences<br>Worldwide
      </h1>

      <p class="hero-subtitle anim-up" style="animation-delay:.2s">
        From iconic African safaris to luxury island retreats, corporate travel solutions, and bespoke adventures — we craft journeys that inspire and endure.
      </p>

      <div class="hero-actions anim-up" style="animation-delay:.3s">
        <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-lg">
          <i class="fas fa-compass"></i> Explore Packages
        </a>
        <a href="<?= url('contact.php#quote') ?>" class="btn btn-outline-white btn-lg">
          <i class="fas fa-paper-plane"></i> Request a Quote
        </a>
      </div>

      <!-- Hero Stats -->
      <div class="hero-stats anim-up" style="animation-delay:.4s">
        <div>
          <div class="hero-stat-num"><span data-counter="5000">5000</span>+</div>
          <div class="hero-stat-label">Happy Travelers</div>
        </div>
        <div>
          <div class="hero-stat-num"><span data-counter="150">150</span>+</div>
          <div class="hero-stat-label">Destinations</div>
        </div>
        <div>
          <div class="hero-stat-num"><span data-counter="18">18</span></div>
          <div class="hero-stat-label">Years Experience</div>
        </div>
        <div>
          <div class="hero-stat-num"><span data-counter="98">98</span>%</div>
          <div class="hero-stat-label">Client Satisfaction</div>
        </div>
      </div>

    </div>

    <!-- Compact Search Bar -->
    <form class="hero-search-bar anim-up" id="heroSearchForm" style="animation-delay:.5s" action="<?= url('packages.php') ?>" method="GET">
      <div class="hsb-field">
        <i class="fas fa-map-marker-alt hsb-icon"></i>
        <div class="hsb-inner">
          <span class="hsb-label">Destination</span>
          <input type="text" name="destination" placeholder="Where to?">
        </div>
      </div>
      <div class="hsb-divider"></div>
      <div class="hsb-field">
        <i class="fas fa-calendar-alt hsb-icon"></i>
        <div class="hsb-inner">
          <span class="hsb-label">Travel Date</span>
          <input type="date" name="travel_date" min="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="hsb-divider"></div>
      <div class="hsb-field">
        <i class="fas fa-users hsb-icon"></i>
        <div class="hsb-inner">
          <span class="hsb-label">Travelers</span>
          <select name="travelers">
            <option>1 Person</option>
            <option>2 People</option>
            <option>3–5 People</option>
            <option>6–10 People</option>
            <option>10+ People</option>
          </select>
        </div>
      </div>
      <div class="hsb-divider"></div>
      <div class="hsb-field">
        <i class="fas fa-binoculars hsb-icon"></i>
        <div class="hsb-inner">
          <span class="hsb-label">Tour Type</span>
          <select name="type">
            <option value="">Any Type</option>
            <option value="safari">Safari</option>
            <option value="holiday">Holiday</option>
            <option value="honeymoon">Honeymoon</option>
            <option value="corporate">Corporate</option>
            <option value="adventure">Adventure</option>
            <option value="luxury">Luxury</option>
          </select>
        </div>
      </div>
      <button type="submit" class="hsb-btn">
        <i class="fas fa-search"></i>
        <span>Search</span>
      </button>
    </form>

  </div>

  <div class="scroll-indicator">
    <span>Scroll to Explore</span>
    <i class="fas fa-chevron-down"></i>
  </div>
</section>

<!-- ============================================================
     CATEGORY STRIP
     ============================================================ -->
<section class="section-sm" style="background:var(--clr-light);border-bottom:1px solid var(--clr-border)">
  <div class="container">
    <div class="category-tabs">
      <?php foreach ($categories as $cat): ?>
      <a href="<?= url('packages.php?category=' . h($cat['slug'])) ?>" class="cat-tab">
        <i class="fas <?= h($cat['icon']) ?>"></i> <?= h($cat['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     FEATURED PACKAGES
     ============================================================ -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><i class="fas fa-fire" style="margin-right:5px"></i>Top Picks</span>
      <h2 class="section-title">Our <span>Featured</span> Travel Packages</h2>
      <p class="section-subtitle">Handpicked journeys designed by our expert travel consultants — extraordinary experiences at exceptional value.</p>
    </div>

    <!-- Category filter tabs -->
    <?php
    $pkgTypes = array_unique(array_filter(array_column($featuredPackages, 'type')));
    ?>
    <div class="pkg-filter-tabs" id="pkgTabs" data-animate>
      <button class="pkg-filter-tab active" data-filter="all">
        All <span class="tab-count"><?= count($featuredPackages) ?></span>
      </button>
      <?php foreach ($pkgTypes as $t): $cnt = count(array_filter($featuredPackages, fn($p) => $p['type'] === $t)); ?>
      <button class="pkg-filter-tab" data-filter="<?= h($t) ?>">
        <?= ucfirst(h($t)) ?> <span class="tab-count"><?= $cnt ?></span>
      </button>
      <?php endforeach; ?>
    </div>

    <div class="grid-3 grid-auto" id="packagesGrid">
      <?php foreach ($featuredPackages as $i => $pkg): ?>
      <article class="package-card" data-animate data-delay="<?= $i * 80 ?>" data-cat="<?= h($pkg['type']) ?>">
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>">
            <img src="<?= h($pkg['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>"
                 alt="<?= h($pkg['title']) ?>" loading="lazy">
          </a>
          <span class="package-badge"><?= h($pkg['type']) ?></span>
          <button class="package-wishlist <?= isLoggedIn() ? '' : '' ?>"
                  data-id="<?= $pkg['id'] ?>" title="Add to wishlist">
            <i class="far fa-heart"></i>
          </button>
        </div>
        <div class="package-card-body">
          <div class="package-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?= h($pkg['destination_name'] ?? $pkg['country'] ?? '') ?></span>
            <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span>
            <?php if ($pkg['rating']): ?>
            <span><i class="fas fa-star" style="color:var(--clr-gold)"></i> <?= number_format($pkg['rating'], 1) ?></span>
            <?php endif; ?>
          </div>
          <h3 class="package-title">
            <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a>
          </h3>
          <p class="package-excerpt"><?= h(excerpt($pkg['tagline'] ?: $pkg['description'], 110)) ?></p>
          <div class="package-footer">
            <div class="package-price">
              <span class="from">From</span>
              <span class="amount"><?= money($pkg['base_price']) ?></span>
              <span class="per">/ person</span>
            </div>
            <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">
              View Details
            </a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>

    <div class="text-center" style="margin-top:48px" data-animate>
      <a href="<?= url('packages.php') ?>" class="btn btn-outline btn-lg">
        <i class="fas fa-th-large"></i> View All Packages
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     CORPORATE TRAVEL BANNER
     ============================================================ -->
<section style="background:linear-gradient(135deg,var(--clr-primary) 0%,var(--clr-primary-l) 100%);padding:80px 0;position:relative;overflow:hidden">
  <div style="position:absolute;right:-80px;top:-80px;width:400px;height:400px;border-radius:50%;background:rgba(255,255,255,.04)"></div>
  <div style="position:absolute;left:-60px;bottom:-60px;width:300px;height:300px;border-radius:50%;background:rgba(212,160,23,.1)"></div>
  <div class="container" style="position:relative;z-index:1">
    <div class="grid-2" style="align-items:center;gap:64px">
      <div data-animate>
        <span class="section-badge" style="background:rgba(212,160,23,.15);border-color:rgba(212,160,23,.3)">
          <i class="fas fa-briefcase" style="margin-right:5px"></i> Corporate Solutions
        </span>
        <h2 style="color:#fff;font-size:2.4rem;margin:16px 0 20px;line-height:1.2">
          Elevate Your <span style="color:var(--clr-gold)">Business Travel</span> Experience
        </h2>
        <p style="color:rgba(255,255,255,.8);margin-bottom:32px;font-size:1.05rem;line-height:1.7">
          Comprehensive corporate travel management, conference planning, executive transfers, and team retreat packages — tailored for your organization's needs.
        </p>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:36px">
          <?php foreach (['Business Travel','Conference Management','Team Retreats','Airport Transfers','Visa Support','Travel Insurance'] as $s): ?>
          <div style="display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.85);font-size:.875rem">
            <i class="fas fa-check-circle" style="color:var(--clr-gold)"></i> <?= $s ?>
          </div>
          <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <a href="<?= url('corporate.php') ?>" class="btn btn-gold btn-lg">Explore Corporate Travel</a>
          <a href="<?= url('contact.php#quote') ?>" class="btn btn-outline-white">Request Proposal</a>
        </div>
      </div>
      <div data-animate data-delay="150">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
          <?php
          $stats = [
            ['200+','Corporate Clients','fas fa-building'],
            ['98%','Satisfaction Rate','fas fa-star'],
            ['24/7','Support Available','fas fa-headset'],
            ['50+','Destinations Served','fas fa-globe'],
          ];
          foreach ($stats as $s): ?>
          <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);border-radius:16px;padding:24px 20px;text-align:center">
            <i class="<?= $s[2] ?>" style="font-size:1.8rem;color:var(--clr-gold);margin-bottom:12px;display:block"></i>
            <div style="font-size:1.8rem;font-weight:800;color:#fff;font-family:var(--ff-head)"><?= $s[0] ?></div>
            <div style="font-size:.78rem;color:rgba(255,255,255,.65);margin-top:4px"><?= $s[1] ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============================================================
     POPULAR DESTINATIONS
     ============================================================ -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><i class="fas fa-map-marker-alt" style="margin-right:5px"></i>Top Destinations</span>
      <h2 class="section-title">Popular <span>Destinations</span></h2>
      <p class="section-subtitle">From the vast savannas of East Africa to pristine island paradises — explore our most sought-after destinations.</p>
    </div>

    <?php if ($featuredDestinations): ?>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);grid-template-rows:repeat(2,220px);gap:16px" data-animate>
      <?php foreach ($featuredDestinations as $i => $dest):
        $span = $i === 0 ? 'grid-row:span 2;grid-column:span 2' : '';
        $img  = $dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800&q=80';
      ?>
      <a href="<?= url('destinations.php?slug=' . h($dest['slug'])) ?>"
         class="destination-card" style="<?= $span ?>;border-radius:16px;overflow:hidden;display:block;position:relative">
        <img src="<?= h($img) ?>" alt="<?= h($dest['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;transition:transform .5s ease">
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(13,59,102,.85) 0%,transparent 55%)"></div>
        <div style="position:absolute;bottom:0;left:0;right:0;padding:20px">
          <div style="font-size:.72rem;font-weight:700;color:var(--clr-gold);letter-spacing:.1em;text-transform:uppercase;margin-bottom:4px"><?= h($dest['country']) ?></div>
          <div style="font-size:<?= $i===0?'1.4rem':'1.1rem' ?>;font-weight:700;color:#fff"><?= h($dest['name']) ?></div>
        </div>
      </a>
      <?php if ($i === 0): break; endif; ?>
      <?php endforeach; ?>
      <?php foreach (array_slice($featuredDestinations, 1, 6) as $i => $dest):
        $img = $dest['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80';
      ?>
      <a href="<?= url('destinations.php?slug=' . h($dest['slug'])) ?>"
         style="border-radius:16px;overflow:hidden;display:block;position:relative">
        <img src="<?= h($img) ?>" alt="<?= h($dest['name']) ?>" loading="lazy" style="width:100%;height:100%;object-fit:cover;transition:transform .5s ease">
        <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(13,59,102,.85) 0%,transparent 55%)"></div>
        <div style="position:absolute;bottom:0;left:0;right:0;padding:16px">
          <div style="font-size:.68rem;font-weight:700;color:var(--clr-gold);letter-spacing:.1em;text-transform:uppercase"><?= h($dest['country']) ?></div>
          <div style="font-size:1rem;font-weight:700;color:#fff"><?= h($dest['name']) ?></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="text-center" style="margin-top:40px">
      <a href="<?= url('destinations.php') ?>" class="btn btn-outline btn-lg">
        <i class="fas fa-globe"></i> Explore All Destinations
      </a>
    </div>
  </div>
</section>

<!-- ============================================================
     WHY CHOOSE US
     ============================================================ -->
<section class="section why-bg">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge" style="background:rgba(212,160,23,.15);border-color:rgba(212,160,23,.3)">
        <i class="fas fa-award" style="margin-right:5px"></i> Our Difference
      </span>
      <h2 class="section-title" style="color:#fff">Why <span>Choose</span> MT Safaris?</h2>
      <p class="section-subtitle" style="color:rgba(255,255,255,.75)">We combine local expertise with global standards to deliver travel experiences that exceed every expectation.</p>
    </div>

    <div class="grid-3">
      <?php
      $features = [
        ['fas fa-shield-alt',    'Trusted & Certified',      'KATO and ATTA certified with 18+ years of delivering safe, reliable, and exceptional travel experiences across Africa and beyond.'],
        ['fas fa-gem',           'Luxury at Every Level',    'From budget to ultra-luxury, we curate accommodations and experiences that deliver outstanding value without compromise.'],
        ['fas fa-headset',       '24/7 Concierge Support',   'Our dedicated travel consultants are available around the clock before, during, and after your trip for complete peace of mind.'],
        ['fas fa-map-marked-alt','Expert Local Knowledge',   'Our guides and consultants have first-hand knowledge of every destination — ensuring authentic and memorable experiences.'],
        ['fas fa-dollar-sign',   'Transparent Pricing',      'No hidden fees. Fully itemized quotes so you know exactly what you\'re getting — and what you\'re paying for.'],
        ['fas fa-star',          'Award-Winning Service',    'Consistently rated 5-stars by our clients and recognized by leading travel associations for service excellence.'],
      ];
      foreach ($features as $i => $f): ?>
      <div class="feature-card" data-animate data-delay="<?= $i * 80 ?>">
        <div class="feature-icon"><i class="<?= $f[0] ?>"></i></div>
        <h3 class="feature-title"><?= $f[1] ?></h3>
        <p class="feature-desc"><?= $f[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     STATS
     ============================================================ -->
<section class="stats-section section-sm">
  <div class="container">
    <div class="grid-4">
      <?php
      $stats = [['5,000+','Happy Travelers',5000],['150+','Destinations',150],['18','Years Experience',18],['98%','Satisfaction Rate',98]];
      foreach ($stats as $s): ?>
      <div class="stat-item" data-animate>
        <div class="stat-number"><span data-counter="<?= $s[2] ?>"><?= $s[2] ?></span><?= strpos($s[0],'+')!==false?'<span>+</span>':'' ?><?= strpos($s[0],'%')!==false?'<span>%</span>':'' ?></div>
        <div class="stat-label"><?= $s[1] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     TESTIMONIALS
     ============================================================ -->
<section class="section" style="background:var(--clr-light)">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><i class="fas fa-quote-left" style="margin-right:5px"></i> Testimonials</span>
      <h2 class="section-title">What Our <span>Travelers</span> Say</h2>
      <p class="section-subtitle">Thousands of happy travelers have trusted MT Safaris with their most memorable journeys.</p>
    </div>
    <div class="testimonial-carousel" id="testimonialCarousel">
      <div class="testimonial-track" id="testimonialTrack">
        <?php foreach ($testimonials as $t): ?>
        <div class="testimonial-card">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:14px">
            <div style="display:flex;gap:3px">
              <?php $stars = min(5, max(1, (int)($t['rating'] ?? 5))); for ($s=0;$s<5;$s++): ?>
              <i class="fas fa-star" style="color:<?= $s<$stars?'var(--clr-gold)':'#e2e8f0' ?>;font-size:.8rem"></i>
              <?php endfor; ?>
            </div>
            <i class="fas fa-quote-right" style="font-size:1.6rem;color:var(--clr-border)"></i>
          </div>
          <p class="testimonial-body">"<?= h($t['body']) ?>"</p>
          <div class="testimonial-author">
            <?php if ($t['avatar']): ?>
            <img src="<?= h($t['avatar']) ?>" alt="<?= h($t['name']) ?>" class="testimonial-avatar">
            <?php else: ?>
            <div class="testimonial-avatar-placeholder"><?= strtoupper(substr($t['name'],0,1)) ?></div>
            <?php endif; ?>
            <div>
              <div class="testimonial-name"><?= h($t['name']) ?></div>
              <div class="testimonial-role"><?= h($t['position'] . ($t['company']?', '.$t['company']:'')) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <!-- Carousel nav -->
    <div class="testimonial-nav">
      <button class="testimonial-btn" id="testimonialPrev" aria-label="Previous"><i class="fas fa-arrow-left"></i></button>
      <div class="testimonial-dots" id="testimonialDots">
        <?php for ($i=0;$i<ceil(count($testimonials)/3);$i++): ?>
        <button class="testimonial-dot<?= $i===0?' active':'' ?>" data-tpage="<?= $i ?>"></button>
        <?php endfor; ?>
      </div>
      <button class="testimonial-btn" id="testimonialNext" aria-label="Next"><i class="fas fa-arrow-right"></i></button>
    </div>
  </div>
</section>

<!-- ============================================================
     BLOG PREVIEW
     ============================================================ -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><i class="fas fa-pen-nib" style="margin-right:5px"></i> Travel Stories</span>
      <h2 class="section-title">Latest from Our <span>Blog</span></h2>
      <p class="section-subtitle">Expert travel guides, destination insights, and stories from the road.</p>
    </div>
    <div class="grid-3">
      <?php if ($recentPosts): foreach ($recentPosts as $i => $post): ?>
      <article class="blog-card" data-animate data-delay="<?= $i * 80 ?>">
        <div class="blog-card-img">
          <a href="<?= url('blog-detail.php?slug=' . h($post['slug'])) ?>">
            <img src="<?= h($post['featured_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>"
                 alt="<?= h($post['title']) ?>" loading="lazy">
          </a>
        </div>
        <div class="blog-card-body">
          <div class="blog-cat"><?= h($post['category_name'] ?? 'Travel') ?></div>
          <h3 class="blog-title"><a href="<?= url('blog-detail.php?slug=' . h($post['slug'])) ?>"><?= h($post['title']) ?></a></h3>
          <p class="blog-excerpt"><?= h(excerpt($post['excerpt'] ?: $post['body'], 120)) ?></p>
          <div class="blog-meta">
            <span><i class="far fa-calendar-alt"></i> <?= formatDate($post['published_at'] ?? $post['created_at']) ?></span>
            <span><i class="far fa-eye"></i> <?= number_format($post['view_count']) ?> views</span>
          </div>
        </div>
      </article>
      <?php endforeach; else: ?>
      <div class="col-span-3 text-center text-muted">Blog posts coming soon.</div>
      <?php endif; ?>
    </div>
    <div class="text-center" style="margin-top:40px" data-animate>
      <a href="<?= url('blog.php') ?>" class="btn btn-outline btn-lg"><i class="fas fa-rss"></i> Read All Articles</a>
    </div>
  </div>
</section>

<!-- ============================================================
     NEWSLETTER
     ============================================================ -->
<section class="newsletter-section section-sm">
  <div class="container" style="position:relative;z-index:1">
    <div style="max-width:660px;margin:0 auto;text-align:center">
      <span class="section-badge"><i class="fas fa-envelope" style="margin-right:5px"></i> Newsletter</span>
      <h2 style="color:#fff;font-size:2rem;margin:16px 0 12px">Stay Inspired on <span style="color:var(--clr-gold)">Every Journey</span></h2>
      <p style="color:rgba(255,255,255,.75);margin-bottom:32px">Get exclusive travel deals, destination guides, and travel tips delivered straight to your inbox. Join 10,000+ subscribers.</p>
      <form class="newsletter-form" style="display:flex;gap:12px;max-width:480px;margin:0 auto">
        <input type="email" placeholder="Enter your email address" required
               style="flex:1;padding:14px 20px;border-radius:999px;border:none;font-size:.9rem;outline:none">
        <button type="submit" class="btn btn-gold">Subscribe <i class="fas fa-arrow-right"></i></button>
      </form>
      <p style="font-size:.75rem;color:rgba(255,255,255,.5);margin-top:14px">No spam. Unsubscribe anytime. We respect your privacy.</p>
    </div>
  </div>
</section>

<!-- ============================================================
     PARTNERS / CERTIFICATIONS
     ============================================================ -->
<section class="section-sm" style="background:var(--clr-light);border-top:1px solid var(--clr-border)">
  <div class="container">
    <p class="text-center text-muted" style="font-size:.78rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;margin-bottom:32px">CERTIFIED & TRUSTED PARTNERS</p>
    <div style="display:flex;align-items:center;justify-content:center;gap:40px;flex-wrap:wrap;opacity:.6;filter:grayscale(1)">
      <?php foreach (['KATO','ATTA','IATA','Kenya Tourism Board','Tripadvisor'] as $partner): ?>
      <div style="font-size:.9rem;font-weight:800;color:var(--clr-primary);letter-spacing:.08em;padding:10px 20px;background:#fff;border-radius:8px;border:1px solid var(--clr-border)"><?= $partner ?></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============================================================
     CTA STRIP
     ============================================================ -->
<section style="background:var(--clr-gold);padding:64px 0">
  <div class="container text-center">
    <h2 style="color:#fff;font-size:2.2rem;margin-bottom:12px">Ready to Start Your <span style="color:var(--clr-primary)">Adventure?</span></h2>
    <p style="color:rgba(255,255,255,.85);font-size:1.05rem;margin-bottom:32px;max-width:560px;margin-left:auto;margin-right:auto">Let our expert consultants design your perfect trip. No obligation — just inspiration.</p>
    <div style="display:flex;gap:14px;justify-content:center;flex-wrap:wrap">
      <a href="<?= url('packages.php') ?>" class="btn btn-white btn-lg"><i class="fas fa-compass"></i> Browse Packages</a>
      <a href="<?= url('contact.php') ?>" class="btn btn-outline-white btn-lg" style="border-color:rgba(255,255,255,.8)"><i class="fas fa-phone-alt"></i> Speak to an Expert</a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
