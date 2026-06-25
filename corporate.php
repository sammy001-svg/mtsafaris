<?php
$pageTitle       = 'Corporate Travel Solutions — MT Safaris';
$pageDescription = 'Premium corporate travel management, conference planning, team retreats, executive transfers and business travel solutions by MT Safaris.';
$headerClass     = 'solid';
require_once 'includes/header.php';
$pkgs = DB::rows("SELECT p.*, d.name AS destination_name FROM packages p LEFT JOIN destinations d ON p.destination_id=d.id WHERE p.type='corporate' AND p.is_active=1 ORDER BY p.is_featured DESC LIMIT 3");
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Corporate Travel</span>
      </div>
      <h1>Premium <span style="color:var(--clr-gold)">Corporate</span> Travel Solutions</h1>
      <p>End-to-end corporate travel management designed to elevate your business travel experience.</p>
    </div>
  </div>
</section>

<!-- Services Grid -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><i class="fas fa-briefcase" style="margin-right:5px"></i>Our Corporate Services</span>
      <h2 class="section-title">Complete Business <span>Travel Management</span></h2>
      <p class="section-subtitle">We handle every aspect of your corporate travel — so your team can focus on business.</p>
    </div>
    <div class="grid-3">
      <?php
      $services = [
        ['fas fa-plane-departure','Business Travel','End-to-end flight bookings, airport transfers, visa assistance, and travel insurance for executives and teams.'],
        ['fas fa-chalkboard-teacher','Conference Management','Full event management for corporate conferences, product launches, and business summits — locally and internationally.'],
        ['fas fa-users-cog','Team Retreats','Bespoke team-building retreats that energize, inspire, and strengthen your team in stunning settings.'],
        ['fas fa-car-side','Airport Transfers','Professional, punctual executive transfers in luxury vehicles — 24/7 availability across East Africa.'],
        ['fas fa-passport','Visa Support','Expert visa application assistance and documentation support for business travel to any destination.'],
        ['fas fa-shield-alt','Travel Insurance','Comprehensive corporate travel insurance covering medical, evacuation, trip cancellation, and liability.'],
        ['fas fa-hotel','Hotel Bookings','Corporate hotel agreements with preferred rates at top properties worldwide.'],
        ['fas fa-file-invoice-dollar','Expense Management','Consolidated billing, expense reports, and travel policy compliance tools for finance teams.'],
        ['fas fa-headset','24/7 Support','Dedicated corporate account managers available around the clock for all travel needs.'],
      ];
      foreach ($services as $i => $s): ?>
      <div style="padding:28px;border-radius:16px;background:#fff;border:1px solid var(--clr-border);transition:all .25s" data-animate data-delay="<?= $i*60 ?>"
           onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='var(--shadow-lg)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="width:56px;height:56px;background:rgba(12,38,20,.08);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:var(--clr-primary);margin-bottom:16px">
          <i class="<?= $s[0] ?>"></i>
        </div>
        <h4 style="color:var(--clr-primary);margin-bottom:10px"><?= $s[1] ?></h4>
        <p style="color:var(--clr-muted);font-size:.875rem"><?= $s[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Why Corporate Clients Choose Us -->
<section class="section" style="background:var(--clr-light)">
  <div class="container">
    <div class="grid-2" style="align-items:center;gap:64px">
      <div data-animate>
        <span class="section-badge">Why Us</span>
        <h2 class="section-title" style="margin-top:12px">The <span>Corporate</span> Advantage</h2>
        <div style="display:flex;flex-direction:column;gap:16px;margin-top:28px">
          <?php foreach ([
            ['fas fa-handshake','Dedicated Account Manager','A single point of contact for all your corporate travel needs — always available, always responsive.'],
            ['fas fa-chart-line','Cost Optimization','Negotiated corporate rates, policy enforcement, and detailed reporting to keep your travel budget on track.'],
            ['fas fa-clock','Fast Turnaround','Quote and booking confirmations within 4 hours, and 24/7 emergency support for urgent changes.'],
            ['fas fa-file-alt','Custom Travel Policy','We implement your corporate travel policy across all bookings for compliance and cost control.'],
          ] as $benefit): ?>
          <div style="display:flex;gap:16px;align-items:start;padding:20px;background:#fff;border-radius:12px;border:1px solid var(--clr-border)">
            <div style="width:44px;height:44px;background:rgba(12,38,20,.08);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--clr-primary);font-size:1rem">
              <i class="<?= $benefit[0] ?>"></i>
            </div>
            <div>
              <h5 style="color:var(--clr-primary);margin-bottom:6px"><?= $benefit[1] ?></h5>
              <p style="font-size:.85rem;color:var(--clr-muted)"><?= $benefit[2] ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div data-animate data-delay="150">
        <div style="background:linear-gradient(135deg,var(--clr-primary),var(--clr-primary-l));border-radius:20px;padding:40px;color:#fff">
          <h3 style="color:#fff;margin-bottom:24px">Request a Corporate Proposal</h3>
          <form method="POST" action="contact.php">
            <?= csrfField() ?>
            <input type="hidden" name="type" value="corporate">
            <div style="display:flex;flex-direction:column;gap:12px">
              <input type="text" name="name" placeholder="Contact Person Name *" required
                     class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff">
              <input type="email" name="email" placeholder="Work Email Address *" required
                     class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff">
              <input type="text" name="company" placeholder="Company Name" required
                     class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff">
              <input type="tel" name="phone" placeholder="Phone Number"
                     class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff">
              <select name="travelers" class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff">
                <option value="">Number of Travelers</option>
                <option>1–5 Executives</option><option>6–15 Team Members</option>
                <option>16–50 Group</option><option>50+ Large Group</option>
              </select>
              <textarea name="message" rows="3" placeholder="Tell us about your corporate travel needs…"
                        class="form-control" style="background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.25);color:#fff"></textarea>
              <button type="submit" class="btn btn-gold btn-lg"><i class="fas fa-paper-plane"></i> Request Proposal</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Corporate Packages -->
<?php if ($pkgs): ?>
<section class="section">
  <div class="container">
    <h2 style="color:var(--clr-primary);margin-bottom:32px">Corporate Travel Packages</h2>
    <div class="grid-3">
      <?php foreach ($pkgs as $pkg): ?>
      <article class="package-card">
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>">
            <img src="<?= h($pkg['hero_image'] ?: 'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=600&q=80') ?>" alt="<?= h($pkg['title']) ?>" loading="lazy">
          </a>
          <span class="package-badge">Corporate</span>
        </div>
        <div class="package-card-body">
          <div class="package-meta"><span><i class="fas fa-users"></i> <?= $pkg['min_pax'] ?>+ People</span><span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span></div>
          <h3 class="package-title"><a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a></h3>
          <div class="package-footer">
            <div class="package-price"><span class="from">From</span><span class="amount"><?= money($pkg['base_price']) ?></span></div>
            <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>" class="btn btn-primary btn-sm">Learn More</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center" style="margin-top:32px">
      <a href="<?= url('packages.php?type=corporate') ?>" class="btn btn-outline btn-lg">View All Corporate Packages</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Clients -->
<section class="section-sm" style="background:var(--clr-primary);text-align:center">
  <div class="container">
    <h3 style="color:#fff;margin-bottom:10px">Trusted by Leading Organizations</h3>
    <p style="color:rgba(255,255,255,.7);margin-bottom:32px;font-size:.875rem">200+ corporate clients trust MT Safaris for all their business travel needs</p>
    <a href="<?= url('contact.php') ?>" class="btn btn-gold btn-lg"><i class="fas fa-phone-alt"></i> Speak to Our Corporate Team</a>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
