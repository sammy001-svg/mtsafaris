<?php
$pageTitle       = 'About MT Safaris — Our Story, Mission & Team';
$pageDescription = 'Learn about MT Safaris — East Africa\'s leading travel company. Our story, mission, vision, expert team, and 18+ years of crafting exceptional travel experiences.';
$headerClass     = 'solid';
require_once 'includes/header.php';

$team = [
  ['Michael Tanaka',   'Founder & CEO',               'Leading MT Safaris since 2005 with 25+ years in East African travel.', null],
  ['Sarah Achieng',    'Head of Operations',           'Ensuring every journey runs smoothly with meticulous attention to detail.', null],
  ['James Otieno',     'Chief Safari Guide',           'Expert wildlife guide with 15 years exploring East African wilderness.', null],
  ['Fatuma Hassan',    'Corporate Travel Director',    'Specialized in premium corporate travel solutions for Fortune 500 clients.', null],
  ['David Mwangi',     'Head of Customer Experience',  'Dedicated to making every traveler feel valued and inspired.', null],
  ['Amina Wangari',    'Finance & Compliance Manager', 'Ensuring transparent pricing and regulatory compliance across all operations.', null],
];

$milestones = [
  ['2005', 'MT Safaris Founded', 'Established in Nairobi with a vision to provide authentic, premium East African safari experiences.'],
  ['2009', 'First Corporate Clients', 'Expanded to corporate travel, partnering with leading multinationals for executive travel.'],
  ['2012', 'International Recognition', 'Awarded Best Safari Operator by East African Tourism Board.'],
  ['2015', 'Regional Expansion', 'Expanded operations to Tanzania, Uganda, Rwanda, and Indian Ocean destinations.'],
  ['2018', 'Digital Transformation', 'Launched online booking platform, reaching clients across 50+ countries.'],
  ['2024', 'Carbon Neutral Initiative', 'Committed to sustainable travel, partnering with conservation projects across East Africa.'],
];
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>About Us</span>
      </div>
      <h1>Our <span style="color:var(--clr-gold)">Story</span></h1>
      <p>18 years of crafting extraordinary travel experiences across Africa and the world.</p>
    </div>
  </div>
</section>

<!-- Mission, Vision, Values -->
<section class="section">
  <div class="container">
    <div class="grid-2" style="align-items:center;gap:64px">

      <!-- Image Grid -->
      <div style="position:relative" data-animate>
        <div class="about-img-grid">
          <div class="main-img">
            <img src="https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800&q=85" alt="Masai Mara Safari" loading="lazy" decoding="async">
          </div>
          <div class="sub-img">
            <img src="https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=400&q=80" alt="Safari Guide" loading="lazy" decoding="async">
          </div>
          <div class="sub-img">
            <img src="https://images.unsplash.com/photo-1531366936337-7c912a4589a7?w=400&q=80" alt="Mt Kilimanjaro" loading="lazy" decoding="async">
          </div>
        </div>
        <div class="about-badge" style="position:absolute;bottom:20px;right:-20px">
          <span class="about-badge-num">18+</span>
          <span class="about-badge-text">Years of Excellence</span>
        </div>
      </div>

      <!-- Content -->
      <div data-animate data-delay="150">
        <span class="section-badge"><i class="fas fa-leaf" style="margin-right:5px"></i>Our Story</span>
        <h2 class="section-title" style="margin-top:12px">Born from a <span>Passion</span> for Africa</h2>
        <p style="color:var(--clr-muted);margin-bottom:24px;line-height:1.8">
          MT Safaris was founded in 2005 with a single belief: that travel has the power to transform lives. What began as a small safari company in Nairobi has grown into East Africa's most trusted travel partner, serving thousands of clients from over 60 countries.
        </p>
        <p style="color:var(--clr-muted);margin-bottom:32px;line-height:1.8">
          We combine deep local expertise with world-class service standards to create journeys that are not just vacations, but life-changing experiences. Every itinerary we craft reflects our commitment to authenticity, sustainability, and excellence.
        </p>
        <div class="grid-2" style="gap:16px;margin-bottom:32px">
          <?php foreach (['Mission','Vision'] as $v): ?>
          <div style="background:var(--clr-light);border-radius:12px;padding:20px;border-left:4px solid var(--clr-gold)">
            <h4 style="color:var(--clr-primary);margin-bottom:8px;font-size:.9rem">Our <?= $v ?></h4>
            <p style="font-size:.82rem;color:var(--clr-muted)">
              <?= $v==='Mission'
                ? 'To craft transformative travel experiences that connect people with the natural beauty, wildlife, and cultures of Africa and beyond.'
                : 'To be Africa\'s most trusted travel partner — known for authenticity, sustainability, and world-class service.' ?>
            </p>
          </div>
          <?php endforeach; ?>
        </div>
        <a href="<?= url('contact.php') ?>" class="btn btn-primary btn-lg"><i class="fas fa-phone-alt"></i> Talk to Our Team</a>
      </div>

    </div>
  </div>
</section>

<!-- Stats -->
<section class="section-sm" style="background:var(--clr-primary)">
  <div class="container">
    <div class="grid-4" style="gap:0">
      <?php foreach ([['5,000+','Happy Travelers'],['150+','Destinations'],['18','Years Experience'],['200+','Corporate Clients']] as $s): ?>
      <div style="text-align:center;padding:36px 20px;border-right:1px solid rgba(255,255,255,.1)">
        <div style="font-size:2.5rem;font-weight:800;color:var(--clr-gold);font-family:var(--ff-head)"><?= $s[0] ?></div>
        <div style="font-size:.82rem;color:rgba(255,255,255,.7);margin-top:8px;text-transform:uppercase;letter-spacing:.06em"><?= $s[1] ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Values -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge">Our Values</span>
      <h2 class="section-title">What <span>Drives</span> Us</h2>
    </div>
    <div class="grid-3">
      <?php
      $values = [
        ['fas fa-leaf','Sustainability','We are committed to responsible tourism that protects wildlife, preserves cultures, and benefits local communities.'],
        ['fas fa-heart','Authenticity','Every experience we create is genuine, locally-rooted, and thoughtfully designed to tell the true story of our destinations.'],
        ['fas fa-star','Excellence','We hold ourselves to the highest standards of service, safety, and professionalism in everything we do.'],
        ['fas fa-users','Community','We invest in local communities, employ local guides, and support conservation initiatives across East Africa.'],
        ['fas fa-shield-alt','Trust','Transparency, integrity, and reliability are the foundations of every client relationship we build.'],
        ['fas fa-magic','Innovation','We continuously evolve our offerings to deliver fresh, memorable experiences that surprise and delight our clients.'],
      ];
      foreach ($values as $i => $v): ?>
      <div style="text-align:center;padding:36px 24px;border-radius:16px;border:1px solid var(--clr-border);background:#fff;transition:all .25s" data-animate data-delay="<?= $i*80 ?>">
        <div style="width:68px;height:68px;background:linear-gradient(135deg,rgba(13,59,102,.1),rgba(59,175,218,.1));border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.6rem;color:var(--clr-primary)">
          <i class="<?= $v[0] ?>"></i>
        </div>
        <h4 style="color:var(--clr-primary);margin-bottom:10px"><?= $v[1] ?></h4>
        <p style="color:var(--clr-muted);font-size:.875rem"><?= $v[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Team -->
<section class="section" style="background:var(--clr-light)">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge">Our People</span>
      <h2 class="section-title">Meet the <span>Team</span></h2>
      <p class="section-subtitle">Passionate travel professionals dedicated to creating your perfect journey.</p>
    </div>
    <div class="grid-3">
      <?php foreach ($team as $i => $member): ?>
      <div class="team-card" data-animate data-delay="<?= $i*80 ?>">
        <div class="testimonial-avatar-placeholder" style="width:80px;height:80px;font-size:1.5rem;margin:0 auto 16px">
          <?= strtoupper(substr($member[0],0,1)) ?>
        </div>
        <div class="team-name"><?= h($member[0]) ?></div>
        <div class="team-role"><?= h($member[1]) ?></div>
        <p class="team-bio"><?= h($member[2]) ?></p>
        <div class="team-socials">
          <a href="#"><i class="fab fa-linkedin-in"></i></a>
          <a href="#"><i class="fab fa-x-twitter"></i></a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Milestones -->
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge">Our Journey</span>
      <h2 class="section-title">Our <span>Milestones</span></h2>
    </div>
    <div style="max-width:760px;margin:0 auto">
      <?php foreach ($milestones as $i => $m): ?>
      <div class="milestone-item" data-animate data-delay="<?= $i*80 ?>">
        <div class="milestone-year"><?= $m[0] ?></div>
        <div class="milestone-info">
          <h4><?= h($m[1]) ?></h4>
          <p><?= h($m[2]) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Awards -->
<section class="section-sm" style="background:var(--clr-primary)">
  <div class="container text-center">
    <h3 style="color:#fff;margin-bottom:32px">Awards & Certifications</h3>
    <div style="display:flex;justify-content:center;gap:24px;flex-wrap:wrap">
      <?php foreach (['KATO Certified','ATTA Member','Kenya Tourism Board','TripAdvisor Certificate of Excellence','Eco-Tourism Kenya'] as $award): ?>
      <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:16px 24px;color:#fff;font-weight:600;font-size:.875rem">
        <i class="fas fa-award" style="color:var(--clr-gold);margin-right:8px"></i><?= $award ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
