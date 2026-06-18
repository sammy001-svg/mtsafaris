<?php
$pageTitle       = 'Frequently Asked Questions — MT Safaris';
$pageDescription = 'Find answers to common questions about safari bookings, payments, travel documents, visa requirements, and what to expect on your MT Safaris adventure.';
$headerClass     = 'solid';

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$faqs = DB::rows("SELECT * FROM faqs WHERE is_active=1 ORDER BY category, sort_order, id");

$categoryLabels = [
    'general'   => ['General',    'fas fa-info-circle',   '#3182ce'],
    'booking'   => ['Booking',    'fas fa-calendar-check','#38a169'],
    'payment'   => ['Payment',    'fas fa-credit-card',   '#d69e2e'],
    'safety'    => ['Safety',     'fas fa-shield-alt',    '#e53e3e'],
    'visa'      => ['Visa & Docs','fas fa-passport',      '#805ad5'],
    'corporate' => ['Corporate',  'fas fa-briefcase',     '#2b6cb0'],
    'travel'    => ['Travel Tips','fas fa-map-marked-alt','#dd6b20'],
    'safari'    => ['Safari',     'fas fa-binoculars',    '#276749'],
];

// Group FAQs by category
$grouped = [];
foreach ($faqs as $faq) {
    $grouped[$faq['category']][] = $faq;
}

require_once 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>FAQs</span>
      </div>
      <h1>Frequently Asked <span style="color:var(--clr-gold)">Questions</span></h1>
      <p>Everything you need to know before booking your safari adventure</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:48px;align-items:start">

      <!-- FAQ Accordion -->
      <div>
        <!-- Search bar -->
        <div style="background:#fff;border:2px solid var(--clr-border);border-radius:50px;display:flex;align-items:center;padding:6px 16px;gap:10px;margin-bottom:36px;box-shadow:var(--shadow-sm)">
          <i class="fas fa-search" style="color:var(--clr-muted)"></i>
          <input type="text" id="faqSearch" placeholder="Search questions…" style="border:none;outline:none;flex:1;font-size:.95rem;background:transparent;color:var(--clr-text)">
          <button onclick="document.getElementById('faqSearch').value='';filterFaqs()" style="border:none;background:none;color:var(--clr-muted);cursor:pointer"><i class="fas fa-times"></i></button>
        </div>

        <!-- Category filter pills -->
        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:32px" id="catFilters">
          <button class="faq-cat-btn active" data-cat="all" onclick="filterCat(this,'all')">
            <i class="fas fa-th"></i> All Questions
          </button>
          <?php foreach ($categoryLabels as $key => [$label, $icon, $color]): if (empty($grouped[$key])) continue; ?>
          <button class="faq-cat-btn" data-cat="<?= $key ?>" onclick="filterCat(this,'<?= $key ?>')" style="--cat-color:<?= $color ?>">
            <i class="<?= $icon ?>"></i> <?= $label ?>
          </button>
          <?php endforeach; ?>
        </div>

        <!-- FAQs by category -->
        <?php foreach ($grouped as $cat => $items):
          [$label, $icon, $color] = $categoryLabels[$cat] ?? [$cat, 'fas fa-question-circle', '#718096'];
        ?>
        <div class="faq-category" data-cat="<?= h($cat) ?>" style="margin-bottom:36px">
          <h3 style="display:flex;align-items:center;gap:10px;color:var(--clr-primary);font-size:1.1rem;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid var(--clr-border)">
            <span style="width:34px;height:34px;background:<?= $color ?>18;border-radius:8px;display:grid;place-items:center;flex-shrink:0">
              <i class="<?= $icon ?>" style="color:<?= $color ?>;font-size:.9rem"></i>
            </span>
            <?= $label ?>
            <span style="font-size:.75rem;font-weight:500;color:var(--clr-muted);background:var(--clr-light);padding:2px 10px;border-radius:20px;margin-left:4px"><?= count($items) ?></span>
          </h3>

          <?php foreach ($items as $i => $faq): ?>
          <div class="faq-item" data-cat="<?= h($cat) ?>" style="border:1px solid var(--clr-border);border-radius:12px;margin-bottom:10px;overflow:hidden;transition:box-shadow .2s">
            <button class="faq-toggle" onclick="toggleFaq(this)" style="width:100%;text-align:left;background:none;border:none;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;gap:16px;cursor:pointer;font-family:inherit">
              <span style="font-weight:600;color:var(--clr-primary);font-size:.95rem;line-height:1.4"><?= h($faq['question']) ?></span>
              <span class="faq-arrow" style="width:28px;height:28px;background:var(--clr-light);border-radius:50%;display:grid;place-items:center;flex-shrink:0;transition:transform .3s">
                <i class="fas fa-chevron-down" style="font-size:.7rem;color:var(--clr-muted)"></i>
              </span>
            </button>
            <div class="faq-body" style="display:none;padding:0 20px 18px">
              <div style="font-size:.9rem;color:var(--clr-text);line-height:1.8;border-top:1px solid var(--clr-border);padding-top:14px">
                <?= nl2br(h($faq['answer'])) ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <!-- No results message -->
        <div id="faqNoResults" style="display:none;text-align:center;padding:60px 20px;color:var(--clr-muted)">
          <i class="fas fa-search" style="font-size:3rem;margin-bottom:16px;display:block;opacity:.3"></i>
          <p style="font-size:1.05rem">No questions match your search.</p>
          <button onclick="document.getElementById('faqSearch').value='';filterFaqs()" class="btn btn-outline" style="margin-top:16px">Clear Search</button>
        </div>
      </div>

      <!-- Sidebar -->
      <div style="position:sticky;top:calc(var(--header-h)+24px)">

        <!-- Still have questions? -->
        <div style="background:var(--clr-primary);border-radius:16px;padding:28px;margin-bottom:20px;text-align:center">
          <div style="width:56px;height:56px;background:rgba(255,255,255,.15);border-radius:50%;display:grid;place-items:center;margin:0 auto 16px">
            <i class="fas fa-headset" style="font-size:1.5rem;color:var(--clr-gold)"></i>
          </div>
          <h3 style="color:#fff;font-size:1.1rem;margin-bottom:8px">Still have questions?</h3>
          <p style="color:rgba(255,255,255,.75);font-size:.85rem;margin-bottom:20px">Our travel experts are available 24/7 to help you plan your perfect safari.</p>
          <a href="<?= url('contact.php') ?>" class="btn btn-gold btn-block" style="margin-bottom:10px"><i class="fas fa-envelope"></i> Send a Message</a>
          <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>" target="_blank" class="btn btn-block" style="background:#25D366;color:#fff"><i class="fab fa-whatsapp"></i> WhatsApp Us</a>
        </div>

        <!-- Quick facts -->
        <div class="card">
          <div class="card-header"><h3 style="font-size:1rem"><i class="fas fa-star" style="color:var(--clr-gold)"></i> Quick Facts</h3></div>
          <div class="card-body">
            <?php foreach ([
              ['fas fa-clock','Response Time','Within 2 hours'],
              ['fas fa-shield-alt','Booking Security','100% Secure & Protected'],
              ['fas fa-undo','Cancellation','Free within 48 hours'],
              ['fas fa-users','Group Sizes','2 to 50+ travellers'],
              ['fas fa-globe-africa','Destinations','150+ worldwide'],
            ] as [$icon, $label, $value]): ?>
            <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--clr-border)">
              <div style="width:32px;height:32px;background:var(--clr-light);border-radius:8px;display:grid;place-items:center;flex-shrink:0">
                <i class="<?= $icon ?>" style="color:var(--clr-gold);font-size:.8rem"></i>
              </div>
              <div>
                <div style="font-size:.72rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.05em"><?= $label ?></div>
                <div style="font-size:.85rem;font-weight:600;color:var(--clr-primary)"><?= $value ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Leave a review CTA -->
        <div style="background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:12px;padding:20px;margin-top:20px;text-align:center">
          <i class="fas fa-star" style="font-size:2rem;color:#d69e2e;display:block;margin-bottom:10px"></i>
          <h4 style="color:var(--clr-primary);margin-bottom:8px">Travelled with us?</h4>
          <p style="font-size:.82rem;color:var(--clr-muted);margin-bottom:14px">Share your experience and help future travellers.</p>
          <a href="<?= url('review-submit.php') ?>" class="btn btn-gold btn-sm">Leave a Review</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<style>
.faq-cat-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 7px 16px;
  border-radius: 50px;
  border: 1.5px solid var(--clr-border);
  background: #fff;
  color: var(--clr-muted);
  font-size: .8rem;
  font-weight: 600;
  cursor: pointer;
  transition: all .2s;
  font-family: inherit;
  -webkit-user-select: none;
  user-select: none;
}
.faq-cat-btn.active,
.faq-cat-btn:hover {
  background: var(--cat-color, var(--clr-primary));
  border-color: var(--cat-color, var(--clr-primary));
  color: #fff;
}
.faq-cat-btn.active { background: var(--clr-primary); border-color: var(--clr-primary); }
.faq-item:hover { box-shadow: var(--shadow-sm); }
.faq-item.open { box-shadow: var(--shadow-md); border-color: var(--clr-primary); }
.faq-item.open .faq-arrow { background: var(--clr-primary); transform: rotate(180deg); }
.faq-item.open .faq-arrow i { color: #fff; }
</style>

<script>
function toggleFaq(btn) {
  const item   = btn.closest('.faq-item');
  const body   = item.querySelector('.faq-body');
  const isOpen = item.classList.contains('open');
  // Close all
  document.querySelectorAll('.faq-item.open').forEach(el => {
    el.classList.remove('open');
    el.querySelector('.faq-body').style.display = 'none';
  });
  if (!isOpen) {
    item.classList.add('open');
    body.style.display = 'block';
  }
}

function filterCat(btn, cat) {
  document.querySelectorAll('.faq-cat-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('faqSearch').value = '';
  document.querySelectorAll('.faq-category').forEach(el => {
    el.style.display = (cat === 'all' || el.dataset.cat === cat) ? '' : 'none';
  });
  document.getElementById('faqNoResults').style.display = 'none';
}

function filterFaqs() {
  const q = document.getElementById('faqSearch').value.toLowerCase().trim();
  // Reset category filter
  document.querySelectorAll('.faq-cat-btn').forEach(b => b.classList.remove('active'));
  document.querySelector('[data-cat="all"]').classList.add('active');

  let visible = 0;
  document.querySelectorAll('.faq-category').forEach(cat => {
    let catVisible = 0;
    cat.querySelectorAll('.faq-item').forEach(item => {
      const text = item.textContent.toLowerCase();
      const show = !q || text.includes(q);
      item.style.display = show ? '' : 'none';
      if (show) catVisible++;
    });
    cat.style.display = catVisible ? '' : 'none';
    visible += catVisible;
  });
  document.getElementById('faqNoResults').style.display = visible ? 'none' : 'block';
}

document.getElementById('faqSearch').addEventListener('input', filterFaqs);
</script>
