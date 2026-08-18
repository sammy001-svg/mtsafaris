<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// -------------------------------------------------------------------------
// Every section below is editable from admin > About Page. The arrays and
// strings here are the fallbacks: they are used when a field has never been
// saved, so the page looks exactly as it always has on a fresh install and
// never renders an empty section.
// -------------------------------------------------------------------------

$defaultTeam = [
  ['name' => 'Michael Tanaka', 'role' => 'Founder & CEO',                'bio' => 'Leading MT Safaris since 2005 with 25+ years in East African travel.'],
  ['name' => 'Sarah Achieng',  'role' => 'Head of Operations',           'bio' => 'Ensuring every journey runs smoothly with meticulous attention to detail.'],
  ['name' => 'James Otieno',   'role' => 'Chief Safari Guide',           'bio' => 'Expert wildlife guide with 15 years exploring East African wilderness.'],
  ['name' => 'Fatuma Hassan',  'role' => 'Corporate Travel Director',    'bio' => 'Specialized in premium corporate travel solutions for Fortune 500 clients.'],
  ['name' => 'David Mwangi',   'role' => 'Head of Customer Experience',  'bio' => 'Dedicated to making every traveler feel valued and inspired.'],
  ['name' => 'Amina Wangari',  'role' => 'Finance & Compliance Manager', 'bio' => 'Ensuring transparent pricing and regulatory compliance across all operations.'],
];

$defaultMilestones = [
  ['year' => '2005', 'title' => 'MT Safaris Founded',        'text' => 'Established in Nairobi with a vision to provide authentic, premium East African safari experiences.'],
  ['year' => '2009', 'title' => 'First Corporate Clients',   'text' => 'Expanded to corporate travel, partnering with leading multinationals for executive travel.'],
  ['year' => '2012', 'title' => 'International Recognition', 'text' => 'Awarded Best Safari Operator by East African Tourism Board.'],
  ['year' => '2015', 'title' => 'Regional Expansion',        'text' => 'Expanded operations to Tanzania, Uganda, Rwanda, and Indian Ocean destinations.'],
  ['year' => '2018', 'title' => 'Digital Transformation',    'text' => 'Launched online booking platform, reaching clients across 50+ countries.'],
  ['year' => '2024', 'title' => 'Carbon Neutral Initiative', 'text' => 'Committed to sustainable travel, partnering with conservation projects across East Africa.'],
];

$defaultValues = [
  ['icon' => 'fas fa-leaf',        'title' => 'Sustainability', 'text' => 'We are committed to responsible tourism that protects wildlife, preserves cultures, and benefits local communities.'],
  ['icon' => 'fas fa-heart',       'title' => 'Authenticity',   'text' => 'Every experience we create is genuine, locally-rooted, and thoughtfully designed to tell the true story of our destinations.'],
  ['icon' => 'fas fa-star',        'title' => 'Excellence',     'text' => 'We hold ourselves to the highest standards of service, safety, and professionalism in everything we do.'],
  ['icon' => 'fas fa-users',       'title' => 'Community',      'text' => 'We invest in local communities, employ local guides, and support conservation initiatives across East Africa.'],
  ['icon' => 'fas fa-shield-alt',  'title' => 'Trust',          'text' => 'Transparency, integrity, and reliability are the foundations of every client relationship we build.'],
  ['icon' => 'fas fa-magic',       'title' => 'Innovation',     'text' => 'We continuously evolve our offerings to deliver fresh, memorable experiences that surprise and delight our clients.'],
];

$defaultStats = [
  ['value' => '5,000+', 'label' => 'Happy Travelers'],
  ['value' => '150+',   'label' => 'Destinations'],
  ['value' => '18',     'label' => 'Years Experience'],
  ['value' => '200+',   'label' => 'Corporate Clients'],
];

$defaultAwards = [
  ['name' => 'KATO Certified'], ['name' => 'ATTA Member'], ['name' => 'Kenya Tourism Board'],
  ['name' => 'TripAdvisor Certificate of Excellence'], ['name' => 'Eco-Tourism Kenya'],
];

// Editable content
$heroTitle   = getSetting('about_hero_title',    'Our Story');
$heroSub     = getSetting('about_hero_subtitle', '18 years of crafting extraordinary travel experiences across Africa and the world.');
$storyBadge  = getSetting('about_story_badge',   'Our Story');
$storyTitle  = getSetting('about_story_title',   'Born from a Passion for Africa');
$storyP1     = getSetting('about_story_p1',      'MT Safaris was founded in 2005 with a single belief: that travel has the power to transform lives. What began as a small safari company in Nairobi has grown into East Africa\'s most trusted travel partner, serving thousands of clients from over 60 countries.');
$storyP2     = getSetting('about_story_p2',      'We combine deep local expertise with world-class service standards to create journeys that are not just vacations, but life-changing experiences. Every itinerary we craft reflects our commitment to authenticity, sustainability, and excellence.');
$missionText = getSetting('about_mission',       'To craft transformative travel experiences that connect people with the natural beauty, wildlife, and cultures of Africa and beyond.');
$visionText  = getSetting('about_vision',        'To be Africa\'s most trusted travel partner — known for authenticity, sustainability, and world-class service.');
$imgMain     = getSetting('about_img_main',      'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=800&q=85');
$imgSub1     = getSetting('about_img_sub1',      'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=400&q=80');
$imgSub2     = getSetting('about_img_sub2',      'https://images.unsplash.com/photo-1531366936337-7c912a4589a7?w=400&q=80');
$badgeNum    = getSetting('about_badge_num',     '18+');
$badgeText   = getSetting('about_badge_text',    'Years of Excellence');
$ctaText     = getSetting('about_cta_text',      'Talk to Our Team');
$ctaUrl      = getSetting('about_cta_url',       url('contact.php'));
$valuesBadge = getSetting('about_values_badge',  'Our Values');
$valuesTitle = getSetting('about_values_title',  'What Drives Us');
$teamBadge   = getSetting('about_team_badge',    'Our People');
$teamTitle   = getSetting('about_team_title',    'Meet the Team');
$teamSub     = getSetting('about_team_subtitle', 'Passionate travel professionals dedicated to creating your perfect journey.');
$mileBadge   = getSetting('about_milestones_badge', 'Our Journey');
$mileTitle   = getSetting('about_milestones_title', 'Our Milestones');
$awardsTitle = getSetting('about_awards_title',  'Awards & Certifications');

$team       = getSettingList('about_team',       $defaultTeam);
$milestones = getSettingList('about_milestones', $defaultMilestones);
$values     = getSettingList('about_values',     $defaultValues);
$stats      = getSettingList('about_stats',      $defaultStats);
$awards     = getSettingList('about_awards',     $defaultAwards);

$pageTitle       = getSetting('about_meta_title', 'About MT Safaris — Our Story, Mission & Team');
$pageDescription = getSetting('about_meta_description', 'Learn about MT Safaris — East Africa\'s leading travel company. Our story, mission, vision, expert team, and 18+ years of crafting exceptional travel experiences.');
$headerClass     = 'solid';
$jsonLd = schemaAboutPage()
        . schemaBreadcrumb([
            ['name' => 'Home',     'url' => url()],
            ['name' => 'About Us', 'url' => url('about.php')],
          ]);
require_once 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>About Us</span>
      </div>
      <h1><?= h($heroTitle) ?></h1>
      <p><?= h($heroSub) ?></p>
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
            <img src="<?= h($imgMain) ?>" alt="<?= h($storyTitle) ?>" loading="lazy" decoding="async">
          </div>
          <div class="sub-img">
            <img src="<?= h($imgSub1) ?>" alt="<?= h($storyTitle) ?>" loading="lazy" decoding="async">
          </div>
          <div class="sub-img">
            <img src="<?= h($imgSub2) ?>" alt="<?= h($storyTitle) ?>" loading="lazy" decoding="async">
          </div>
        </div>
        <?php if ($badgeNum || $badgeText): ?>
        <div class="about-badge" style="position:absolute;bottom:20px;right:-20px">
          <span class="about-badge-num"><?= h($badgeNum) ?></span>
          <span class="about-badge-text"><?= h($badgeText) ?></span>
        </div>
        <?php endif; ?>
      </div>

      <!-- Content -->
      <div data-animate data-delay="150">
        <span class="section-badge"><i class="fas fa-leaf" style="margin-right:5px"></i><?= h($storyBadge) ?></span>
        <h2 class="section-title" style="margin-top:12px"><?= h($storyTitle) ?></h2>
        <?php if ($storyP1): ?>
        <p style="color:var(--clr-muted);margin-bottom:24px;line-height:1.8"><?= nl2br(h($storyP1)) ?></p>
        <?php endif; ?>
        <?php if ($storyP2): ?>
        <p style="color:var(--clr-muted);margin-bottom:32px;line-height:1.8"><?= nl2br(h($storyP2)) ?></p>
        <?php endif; ?>
        <div class="grid-2" style="gap:16px;margin-bottom:32px">
          <?php foreach ([['Our Mission', $missionText], ['Our Vision', $visionText]] as $mv): if (!$mv[1]) continue; ?>
          <div style="background:var(--clr-light);border-radius:12px;padding:20px;border-left:4px solid var(--clr-gold)">
            <h4 style="color:var(--clr-primary);margin-bottom:8px;font-size:.9rem"><?= h($mv[0]) ?></h4>
            <p style="font-size:.82rem;color:var(--clr-muted)"><?= h($mv[1]) ?></p>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($ctaText): ?>
        <a href="<?= safeUrl($ctaUrl, url('contact.php')) ?>" class="btn btn-primary btn-lg"><i class="fas fa-phone-alt"></i> <?= h($ctaText) ?></a>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<!-- Stats -->
<?php if ($stats): ?>
<section class="section-sm" style="background:var(--clr-primary)">
  <div class="container">
    <div class="grid-4" style="gap:0">
      <?php foreach ($stats as $s): ?>
      <div style="text-align:center;padding:36px 20px;border-right:1px solid rgba(255,255,255,.1)">
        <div style="font-size:2.5rem;font-weight:800;color:var(--clr-gold);font-family:var(--ff-head)"><?= h($s['value'] ?? '') ?></div>
        <div style="font-size:.82rem;color:rgba(255,255,255,.7);margin-top:8px;text-transform:uppercase;letter-spacing:.06em"><?= h($s['label'] ?? '') ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Values -->
<?php if ($values): ?>
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><?= h($valuesBadge) ?></span>
      <h2 class="section-title"><?= h($valuesTitle) ?></h2>
    </div>
    <div class="grid-3">
      <?php foreach ($values as $i => $v): ?>
      <div style="text-align:center;padding:36px 24px;border-radius:16px;border:1px solid var(--clr-border);background:#fff;transition:all .25s" data-animate data-delay="<?= $i*80 ?>">
        <div style="width:68px;height:68px;background:linear-gradient(135deg,rgba(12,38,20,.1),rgba(12,38,20,.05));border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:1.6rem;color:var(--clr-primary)">
          <i class="<?= h($v['icon'] ?? 'fas fa-star') ?>"></i>
        </div>
        <h4 style="color:var(--clr-primary);margin-bottom:10px"><?= h($v['title'] ?? '') ?></h4>
        <p style="color:var(--clr-muted);font-size:.875rem"><?= h($v['text'] ?? '') ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Team -->
<?php if ($team): ?>
<section class="section" style="background:var(--clr-light)">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><?= h($teamBadge) ?></span>
      <h2 class="section-title"><?= h($teamTitle) ?></h2>
      <?php if ($teamSub): ?><p class="section-subtitle"><?= h($teamSub) ?></p><?php endif; ?>
    </div>
    <div class="grid-3">
      <?php foreach ($team as $i => $member): ?>
      <div class="team-card" data-animate data-delay="<?= $i*80 ?>">
        <?php if (!empty($member['photo'])): ?>
        <img src="<?= h($member['photo']) ?>" alt="<?= h($member['name'] ?? '') ?>"
             style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin:0 auto 16px;display:block"
             loading="lazy" decoding="async">
        <?php else: ?>
        <div class="testimonial-avatar-placeholder" style="width:80px;height:80px;font-size:1.5rem;margin:0 auto 16px">
          <?= h(strtoupper(mb_substr($member['name'] ?? 'M', 0, 1))) ?>
        </div>
        <?php endif; ?>
        <div class="team-name"><?= h($member['name'] ?? '') ?></div>
        <div class="team-role"><?= h($member['role'] ?? '') ?></div>
        <p class="team-bio"><?= h($member['bio'] ?? '') ?></p>
        <?php if (!empty($member['linkedin']) || !empty($member['twitter'])): ?>
        <div class="team-socials">
          <?php if (!empty($member['linkedin'])): ?>
          <a href="<?= safeUrl($member['linkedin']) ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <?php endif; ?>
          <?php if (!empty($member['twitter'])): ?>
          <a href="<?= safeUrl($member['twitter']) ?>" target="_blank" rel="noopener noreferrer" aria-label="X"><i class="fab fa-x-twitter"></i></a>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Milestones -->
<?php if ($milestones): ?>
<section class="section">
  <div class="container">
    <div class="section-header" data-animate>
      <span class="section-badge"><?= h($mileBadge) ?></span>
      <h2 class="section-title"><?= h($mileTitle) ?></h2>
    </div>
    <div style="max-width:760px;margin:0 auto">
      <?php foreach ($milestones as $i => $m): ?>
      <div class="milestone-item" data-animate data-delay="<?= $i*80 ?>">
        <div class="milestone-year"><?= h($m['year'] ?? '') ?></div>
        <div class="milestone-info">
          <h4><?= h($m['title'] ?? '') ?></h4>
          <p><?= h($m['text'] ?? '') ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Awards -->
<?php if ($awards): ?>
<section class="section-sm" style="background:var(--clr-primary)">
  <div class="container text-center">
    <h3 style="color:#fff;margin-bottom:32px"><?= h($awardsTitle) ?></h3>
    <div style="display:flex;justify-content:center;gap:24px;flex-wrap:wrap">
      <?php foreach ($awards as $award): $an = is_array($award) ? ($award['name'] ?? '') : (string)$award; if (!$an) continue; ?>
      <div style="background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:10px;padding:16px 24px;color:#fff;font-weight:600;font-size:.875rem">
        <i class="fas fa-award" style="color:var(--clr-gold);margin-right:8px"></i><?= h($an) ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
