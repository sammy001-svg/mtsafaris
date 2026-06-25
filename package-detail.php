<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) { header('Location: ' . url('packages.php')); exit; }

$pkg = getPackageBySlug($slug);
if (!$pkg) { http_response_code(404); header('Location: ' . url('packages.php')); exit; }

$itinerary = jd($pkg['itinerary']);
$included  = jd($pkg['included']);
$excluded  = jd($pkg['excluded']);
$gallery   = jd($pkg['gallery']);
$hotels    = jd($pkg['hotels']);
$faqs      = jd($pkg['faqs']);
// Addons: prefer JSON column (set in admin form), fall back to separate table
$addons = jd($pkg['addons'] ?? '[]', []);
if (empty($addons)) {
    $addons = DB::rows("SELECT name, price, '' AS description FROM package_addons WHERE package_id=? AND is_active=1", [$pkg['id']]);
}
$reviews   = getPackageReviews($pkg['id'], 8);

$related   = DB::rows("SELECT p.*, d.name AS destination_name, d.country
                        FROM packages p
                        LEFT JOIN destinations d ON p.destination_id = d.id
                        WHERE p.is_active = 1 AND p.id != ? AND (p.category_id = ? OR p.type = ?)
                        ORDER BY p.is_featured DESC LIMIT 3",
                       [$pkg['id'], $pkg['category_id'], $pkg['type']]);

$pageTitle       = h($pkg['meta_title'] ?: $pkg['title']) . ' — MT Safaris';
$pageDescription = $pkg['meta_description'] ?: excerpt($pkg['tagline'] ?: strip_tags($pkg['overview'] ?? ''), 160);
$pageImage       = $pkg['hero_image'];
$ogType          = 'product';
$headerClass     = 'solid';
// Enrich package array with destination name for schema
$pkg['dest_name'] = $pkg['destination_name'] ?? DB::value("SELECT name FROM destinations WHERE id=?", [$pkg['destination_id']??0]);
$jsonLd = schemaPackage($pkg, $reviews)
        . schemaBreadcrumb([
            ['name'=>'Home',     'url'=> url()],
            ['name'=>'Packages', 'url'=> url('packages.php')],
            ['name'=>$pkg['title'], 'url'=> url('package-detail.php?slug='.$pkg['slug'])],
          ]);
require_once 'includes/header.php';
?>

<!-- Package Hero -->
<div class="package-hero" id="pkgHero">
  <img src="<?= h($gallery[0] ?? $pkg['hero_image'] ?? 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1400&q=85') ?>"
       alt="<?= h($pkg['title']) ?>" id="galleryMainImg" class="gallery-main-img" fetchpriority="high" decoding="async">
  <div class="package-hero-overlay"></div>
  <div class="container">
    <div class="package-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i>
        <a href="<?= url('packages.php') ?>">Packages</a><i class="fas fa-chevron-right"></i>
        <span><?= h($pkg['category_name'] ?? $pkg['type']) ?></span>
      </div>
      <h1 style="color:#fff;font-size:clamp(1.8rem,4vw,3rem);margin-bottom:12px"><?= h($pkg['title']) ?></h1>
      <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center">
        <span style="color:rgba(255,255,255,.85);font-size:.9rem"><i class="fas fa-map-marker-alt" style="color:var(--clr-gold);margin-right:6px"></i><?= h($pkg['destination_name'] ?? '') ?>, <?= h($pkg['country'] ?? '') ?></span>
        <span style="color:rgba(255,255,255,.85);font-size:.9rem"><i class="fas fa-clock" style="color:var(--clr-gold);margin-right:6px"></i><?= $pkg['duration_days'] ?> Days / <?= $pkg['duration_nights'] ?> Nights</span>
        <?php if ($pkg['rating']): ?>
        <span style="color:rgba(255,255,255,.85);font-size:.9rem">
          <?= stars($pkg['rating']) ?> <?= number_format($pkg['rating'],1) ?> (<?= $pkg['review_count'] ?> reviews)
        </span>
        <?php endif; ?>
        <span class="badge badge-gold" style="font-size:.72rem"><?= ucfirst(h($pkg['type'])) ?></span>
      </div>
      <!-- Gallery Thumbs -->
      <?php if (count($gallery) > 1): ?>
      <div class="gallery-thumbs" style="margin-top:20px">
        <?php foreach (array_slice($gallery, 0, 5) as $i => $img): ?>
        <div class="gallery-thumb <?= $i===0?'active':'' ?>" onclick="document.getElementById('galleryMainImg').src='<?= h($img) ?>';this.parentElement.querySelectorAll('.gallery-thumb').forEach(t=>t.classList.remove('active'));this.classList.add('active')">
          <img src="<?= h($img) ?>" alt="Gallery <?= $i+1 ?>" loading="lazy" decoding="async">
        </div>
        <?php endforeach; ?>
        <?php if (count($gallery) > 5): ?>
        <div class="gallery-thumb" style="background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.8rem;font-weight:700">+<?= count($gallery)-5 ?></div>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Main Content -->
<section class="section-sm">
  <div class="container">
    <div class="pkg-detail-layout">

      <!-- LEFT: Package Info -->
      <div>
        <!-- Quick Stats -->
        <div class="pkg-quick-stats">
          <?php
          $qs = [
            ['fas fa-clock','Duration',$pkg['duration_days'].' Days / '.$pkg['duration_nights'].' Nights'],
            ['fas fa-users','Group Size',$pkg['min_pax'].($pkg['max_pax']?'–'.$pkg['max_pax']:'').' People'],
            ['fas fa-hotel','Accommodation',ucfirst(str_replace('_',' ',$pkg['accommodation_level']??'Standard'))],
            ['fas fa-plane','Departure',$pkg['departure_city']??'Nairobi'],
          ];
          foreach ($qs as $q): ?>
          <div style="text-align:center;padding:16px 12px;background:var(--clr-light);border-radius:12px;border:1px solid var(--clr-border)">
            <i class="<?= $q[0] ?>" style="color:var(--clr-gold);font-size:1.3rem;margin-bottom:8px;display:block"></i>
            <div style="font-size:.68rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px"><?= $q[1] ?></div>
            <div style="font-size:.82rem;font-weight:700;color:var(--clr-primary)"><?= h($q[2]) ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Tabs -->
        <div class="tab-nav" id="pkgTabs">
          <?php $tabs = [['overview','Overview'],['itinerary','Itinerary'],['included','Included/Excluded'],['hotels','Hotels'],['reviews','Reviews'],['faq','FAQ']]; ?>
          <?php foreach ($tabs as $i => $tab): ?>
          <div class="tab-nav-item <?= $i===0?'active':'' ?>" data-tab-id="<?= $tab[0] ?>"><?= $tab[1] ?></div>
          <?php endforeach; ?>
        </div>

        <div class="tab-content">

          <!-- Overview -->
          <div data-panel="overview" class="active">
            <h3 style="color:var(--clr-primary);margin-bottom:16px">Package Overview</h3>
            <div style="color:var(--clr-muted);line-height:1.8;font-size:.9rem">
              <?= nl2br(h($pkg['overview'] ?: $pkg['description'])) ?>
            </div>
            <?php if ($pkg['video_url']): ?>
            <div style="margin-top:28px">
              <h4 style="margin-bottom:12px">Package Video</h4>
              <div style="aspect-ratio:16/9;border-radius:12px;overflow:hidden;background:#000">
                <iframe src="<?= h($pkg['video_url']) ?>" width="100%" height="100%" frameborder="0" allowfullscreen loading="lazy"></iframe>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Itinerary -->
          <div data-panel="itinerary">
            <h3 style="color:var(--clr-primary);margin-bottom:24px">Day-by-Day Itinerary</h3>
            <?php if ($itinerary): foreach ($itinerary as $day): ?>
            <div class="itinerary-day">
              <div class="day-number">
                <span class="num"><?= $day['day'] ?? '–' ?></span>
                <span class="day">Day</span>
              </div>
              <div class="day-info">
                <h4><?= h($day['title'] ?? 'Day ' . ($day['day'] ?? '')) ?></h4>
                <p><?= h($day['description'] ?? '') ?></p>
                <?php if (!empty($day['meals'])): ?>
                <div style="margin-top:8px;font-size:.78rem;color:var(--clr-muted)">
                  <i class="fas fa-utensils" style="color:var(--clr-gold);margin-right:5px"></i>
                  Meals: <?= h(implode(', ', (array)$day['meals'])) ?>
                </div>
                <?php endif; ?>
              </div>
            </div>
            <?php endforeach; else: ?>
            <p class="text-muted">Detailed itinerary available upon booking confirmation.</p>
            <?php endif; ?>
          </div>

          <!-- Included / Excluded -->
          <div data-panel="included">
            <div class="grid-2" style="gap:32px">
              <div>
                <h3 style="color:var(--clr-success);margin-bottom:16px"><i class="fas fa-check-circle"></i> What's Included</h3>
                <?php if ($included): ?>
                <ul class="included-list">
                  <?php foreach ($included as $item): ?>
                  <li><i class="fas fa-check-circle"></i> <?= h($item) ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">Contact us for full inclusion details.</p>
                <?php endif; ?>
              </div>
              <div>
                <h3 style="color:var(--clr-danger);margin-bottom:16px"><i class="fas fa-times-circle"></i> What's Excluded</h3>
                <?php if ($excluded): ?>
                <ul class="excluded-list">
                  <?php foreach ($excluded as $item): ?>
                  <li><i class="fas fa-times-circle"></i> <?= h($item) ?></li>
                  <?php endforeach; ?>
                </ul>
                <?php else: ?>
                <p class="text-muted">Ask us about any specific exclusions.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Hotels -->
          <div data-panel="hotels">
            <h3 style="color:var(--clr-primary);margin-bottom:20px">Accommodation</h3>
            <?php if ($hotels): foreach ($hotels as $hotel): ?>
            <div style="display:flex;gap:16px;padding:20px;background:var(--clr-light);border-radius:12px;border:1px solid var(--clr-border);margin-bottom:14px">
              <div style="flex:1">
                <h4 style="margin-bottom:4px"><?= h($hotel['name'] ?? '') ?></h4>
                <p style="font-size:.82rem;color:var(--clr-muted);margin-bottom:8px"><?= h($hotel['location'] ?? '') ?></p>
                <div style="font-size:.78rem;color:var(--clr-gold)">
                  <?php for ($s=0;$s<($hotel['stars']??3);$s++): ?><i class="fas fa-star"></i><?php endfor; ?>
                </div>
              </div>
            </div>
            <?php endforeach; else: ?>
            <p class="text-muted">Accommodation details will be provided upon booking. We partner with top-rated lodges and hotels.</p>
            <?php endif; ?>
          </div>

          <!-- Reviews -->
          <div data-panel="reviews">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;flex-wrap:wrap;gap:12px">
              <h3 style="color:var(--clr-primary)">Traveler Reviews</h3>
              <?php if ($pkg['rating']): ?>
              <div style="text-align:center;background:var(--clr-primary);color:#fff;padding:16px 24px;border-radius:12px">
                <div style="font-size:2.5rem;font-weight:800;font-family:var(--ff-head)"><?= number_format($pkg['rating'],1) ?></div>
                <div style="color:var(--clr-gold);margin:4px 0"><?= stars($pkg['rating']) ?></div>
                <div style="font-size:.75rem;opacity:.8"><?= $pkg['review_count'] ?> Reviews</div>
              </div>
              <?php endif; ?>
            </div>
            <?php foreach ($reviews as $rev): ?>
            <div style="padding:20px 0;border-bottom:1px solid var(--clr-border)">
              <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:10px;flex-wrap:wrap;gap:8px">
                <div>
                  <div style="font-weight:700;color:var(--clr-primary)"><?= h($rev['name']) ?></div>
                  <div style="font-size:.75rem;color:var(--clr-muted)"><?= formatDate($rev['created_at']) ?></div>
                </div>
                <?= stars($rev['rating']) ?>
              </div>
              <?php if ($rev['title']): ?>
              <h5 style="margin-bottom:8px;font-size:.9rem"><?= h($rev['title']) ?></h5>
              <?php endif; ?>
              <p style="font-size:.875rem;color:var(--clr-muted)"><?= nl2br(h($rev['body'])) ?></p>
            </div>
            <?php endforeach; ?>
            <?php if (!$reviews): ?>
            <p class="text-muted">No reviews yet. Be the first to travel and share your experience!</p>
            <?php endif; ?>
          </div>

          <!-- FAQ -->
          <div data-panel="faq">
            <h3 style="color:var(--clr-primary);margin-bottom:24px">Frequently Asked Questions</h3>
            <?php if ($faqs): foreach ($faqs as $faq): ?>
            <div class="accordion-item">
              <div class="accordion-header"><?= h($faq['question'] ?? '') ?> <i class="fas fa-chevron-down"></i></div>
              <div class="accordion-body"><p><?= h($faq['answer'] ?? '') ?></p></div>
            </div>
            <?php endforeach; else: ?>
            <?php
            $genericFaqs = DB::rows("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
            foreach ($genericFaqs as $faq): ?>
            <div class="accordion-item">
              <div class="accordion-header"><?= h($faq['question']) ?> <i class="fas fa-chevron-down"></i></div>
              <div class="accordion-body"><p><?= h($faq['answer']) ?></p></div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div><!-- /.tab-content -->
      </div>

      <!-- RIGHT: Booking Widget -->
      <div>
        <div class="booking-widget">
          <div class="booking-widget-header">
            <div class="booking-widget-price">
              <?php if ($pkg['sale_price'] && $pkg['sale_price'] < $pkg['base_price']): ?>
              <div style="font-size:.8rem;text-decoration:line-through;color:rgba(255,255,255,.55)"><?= money($pkg['base_price']) ?></div>
              <span class="amount" style="color:var(--clr-gold)"><?= money($pkg['sale_price']) ?></span>
              <?php else: ?>
              <div style="font-size:.75rem;color:rgba(255,255,255,.65)">From</div>
              <span class="amount"><?= money($pkg['base_price']) ?></span>
              <?php endif; ?>
              <span class="per">/ person</span>
            </div>
            <?php if ($pkg['seats_left'] !== null && $pkg['seats_left'] <= 5): ?>
            <div style="background:var(--clr-danger);color:#fff;font-size:.7rem;font-weight:700;padding:4px 10px;border-radius:20px;margin-top:8px;display:inline-block">
              Only <?= $pkg['seats_left'] ?> spots left!
            </div>
            <?php endif; ?>
          </div>

          <div class="booking-widget-body">
            <div style="display:flex;flex-direction:column;gap:12px;margin-bottom:20px">
              <div class="form-group" style="margin-bottom:0">
                <label>Travel Date</label>
                <input type="date" class="form-control" id="travelDate" min="<?= date('Y-m-d', strtotime('+7 days')) ?>">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                <div class="form-group" style="margin-bottom:0">
                  <label>Adults</label>
                  <div class="traveler-counter" style="display:flex;align-items:center;border:2px solid var(--clr-border);border-radius:6px">
                    <button type="button" class="tc-minus" style="padding:8px 12px;background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--clr-muted)">−</button>
                    <input type="number" id="adultsCount" value="2" min="1" max="<?= $pkg['max_pax'] ?>" style="text-align:center;border:none;outline:none;width:100%;font-size:.9rem" readonly>
                    <button type="button" class="tc-plus" style="padding:8px 12px;background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--clr-muted)">+</button>
                  </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label>Children</label>
                  <div class="traveler-counter" style="display:flex;align-items:center;border:2px solid var(--clr-border);border-radius:6px">
                    <button type="button" class="tc-minus" style="padding:8px 12px;background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--clr-muted)">−</button>
                    <input type="number" id="childrenCount" value="0" min="0" max="10" style="text-align:center;border:none;outline:none;width:100%;font-size:.9rem" readonly>
                    <button type="button" class="tc-plus" style="padding:8px 12px;background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--clr-muted)">+</button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Add-ons -->
            <?php if ($addons): ?>
            <div style="margin-bottom:16px">
              <label style="font-size:.82rem;font-weight:600;margin-bottom:10px;display:block">Optional Add-ons</label>
              <?php foreach ($addons as $addon): ?>
              <label style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--clr-border);font-size:.82rem;cursor:pointer">
                <span><input type="checkbox" style="margin-right:8px;accent-color:var(--clr-primary)"> <?= h($addon['name']) ?></span>
                <span style="color:var(--clr-gold);font-weight:700">+<?= money($addon['price']) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Price Summary -->
            <div class="price-summary" style="margin-bottom:16px">
              <div class="price-row">
                <span>Base Price</span>
                <span><?= money($pkg['sale_price'] ?: $pkg['base_price']) ?> × <span id="totalPax">2</span></span>
              </div>
              <div class="price-row"><span>Subtotal</span><span id="summarySubtotal"><?= money(($pkg['sale_price']??$pkg['base_price'])*2) ?></span></div>
              <div class="price-row"><span>Tax (<?= TAX_RATE ?>%)</span><span id="summaryTax">—</span></div>
              <div class="price-row total"><span>Total</span><span id="summaryTotal">—</span></div>
            </div>

            <input type="hidden" id="basePrice" value="<?= $pkg['sale_price'] ?: $pkg['base_price'] ?>">
            <input type="hidden" id="discountAmount" value="0">
            <input type="hidden" id="taxRate" value="<?= TAX_RATE ?>">
            <input type="hidden" id="hiddenTotal" value="">

            <a href="<?= url('booking.php?package=' . $pkg['id']) ?>" class="btn btn-gold btn-block btn-lg" style="margin-bottom:10px">
              <i class="fas fa-calendar-check"></i> Book Now
            </a>
            <a href="<?= url('contact.php#quote') ?>" class="btn btn-outline btn-block" style="font-size:.85rem">
              <i class="fas fa-envelope"></i> Enquire / Get Quote
            </a>

            <div style="margin-top:16px;text-align:center;font-size:.75rem;color:var(--clr-muted)">
              <i class="fas fa-lock" style="color:var(--clr-success);margin-right:4px"></i> Secure booking · Free cancellation available
            </div>
          </div>
        </div>

        <!-- Share -->
        <div style="background:#fff;border-radius:12px;border:1px solid var(--clr-border);padding:16px;margin-top:16px;text-align:center">
          <p style="font-size:.82rem;font-weight:600;color:var(--clr-primary);margin-bottom:12px">Share This Package</p>
          <div style="display:flex;justify-content:center;gap:10px">
            <a href="https://wa.me/?text=<?= urlencode('Check out this amazing tour: ' . url('package-detail.php?slug=' . $slug)) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:#fff">
              <i class="fab fa-whatsapp"></i>
            </a>
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(currentUrl()) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:#fff">
              <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode(currentUrl()) ?>&text=<?= urlencode($pkg['title']) ?>" target="_blank" class="btn btn-sm" style="background:#000;color:#fff">
              <i class="fab fa-x-twitter"></i>
            </a>
          </div>
        </div>

        <!-- Need Help -->
        <div style="background:linear-gradient(135deg,var(--clr-primary),var(--clr-primary-l));border-radius:12px;padding:20px;margin-top:16px;text-align:center">
          <i class="fas fa-headset" style="font-size:1.8rem;color:var(--clr-gold);margin-bottom:10px;display:block"></i>
          <p style="color:#fff;font-weight:600;margin-bottom:4px">Need Help Planning?</p>
          <p style="color:rgba(255,255,255,.7);font-size:.8rem;margin-bottom:14px">Our consultants are available 24/7</p>
          <a href="tel:<?= CONTACT_PHONE ?>" class="btn btn-gold btn-sm btn-block">
            <i class="fas fa-phone-alt"></i> Call Us Now
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Related Packages -->
<?php if ($related): ?>
<section class="section" style="background:var(--clr-light)">
  <div class="container">
    <h2 style="color:var(--clr-primary);margin-bottom:32px">You Might Also Like</h2>
    <div class="grid-3">
      <?php foreach ($related as $r): ?>
      <article class="package-card" data-animate>
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug=' . h($r['slug'])) ?>">
            <img src="<?= h($r['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>" alt="<?= h($r['title']) ?>" loading="lazy" decoding="async">
          </a>
          <span class="package-badge"><?= ucfirst(h($r['type'])) ?></span>
        </div>
        <div class="package-card-body">
          <div class="package-meta">
            <span><i class="fas fa-map-marker-alt"></i> <?= h($r['destination_name'] ?? '') ?></span>
            <span><i class="fas fa-clock"></i> <?= $r['duration_days'] ?> Days</span>
          </div>
          <h3 class="package-title"><a href="<?= url('package-detail.php?slug=' . h($r['slug'])) ?>"><?= h($r['title']) ?></a></h3>
          <div class="package-footer">
            <div class="package-price"><span class="from">From</span><span class="amount"><?= money($r['base_price']) ?></span></div>
            <a href="<?= url('package-detail.php?slug=' . h($r['slug'])) ?>" class="btn btn-primary btn-sm">View</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
