<?php
$pageTitle       = 'Share Your Experience — MT Safaris';
$pageDescription = 'Travelled with MT Safaris? Share your safari experience and help future adventurers discover the magic of Africa.';
$headerClass     = 'solid';

require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user     = currentUser();
$packages = DB::rows("SELECT id, title, slug, hero_image FROM packages WHERE is_active=1 ORDER BY is_featured DESC, title ASC");

// Pre-select package if ?package=slug passed (e.g. from package detail page)
$preSlug  = trim($_GET['package'] ?? '');
$prePkg   = $preSlug ? DB::row("SELECT id, title FROM packages WHERE slug=? AND is_active=1", [$preSlug]) : null;

// If logged in, check which packages they've completed bookings on
$completedPkgIds = [];
if ($user) {
    $rows = DB::rows("SELECT DISTINCT package_id FROM bookings WHERE user_id=? AND status IN ('completed','paid')", [$user['id']]);
    $completedPkgIds = array_column($rows, 'package_id');
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $pkgId   = (int)($_POST['package_id'] ?? 0);
    $name    = trim($_POST['name']   ?? '');
    $email   = trim($_POST['email']  ?? '');
    $rating  = (int)($_POST['rating'] ?? 5);
    $title   = trim($_POST['title']  ?? '');
    $body    = trim($_POST['body']   ?? '');

    if (!$pkgId)  $errors[] = 'Please select a package.';
    if (!$name)   $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Please select a star rating.';
    if (strlen($body) < 20)  $errors[] = 'Review must be at least 20 characters.';
    if (strlen($body) > 2000) $errors[] = 'Review must be under 2,000 characters.';

    // Spam check: prevent double-submission same email + package
    if (!$errors) {
        $dup = DB::value("SELECT COUNT(*) FROM reviews WHERE package_id=? AND email=?", [$pkgId, $email]);
        if ($dup) $errors[] = 'You have already submitted a review for this package.';
    }

    if (!$errors) {
        // Find matching booking for this user
        $bookingId = null;
        if ($user) {
            $brow = DB::row("SELECT id FROM bookings WHERE user_id=? AND package_id=? AND status IN ('completed','paid') LIMIT 1", [$user['id'], $pkgId]);
            $bookingId = $brow['id'] ?? null;
        }

        DB::insert('reviews', [
            'package_id' => $pkgId,
            'booking_id' => $bookingId,
            'user_id'    => $user['id'] ?? null,
            'name'       => $name,
            'email'      => $email,
            'rating'     => $rating,
            'title'      => $title,
            'body'       => $body,
            'is_approved'=> 0,
        ]);
        $success = true;
    }
}

require_once 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Leave a Review</span>
      </div>
      <h1>Share Your <span style="color:var(--clr-gold)">Experience</span></h1>
      <p>Your story inspires other travellers to take the leap</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container-sm" style="max-width:860px">

    <?php if ($success): ?>
    <!-- Success state -->
    <div style="text-align:center;padding:60px 20px">
      <div style="width:90px;height:90px;background:linear-gradient(135deg,var(--clr-gold),#f6d365);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 12px 32px rgba(201,168,76,.35)">
        <i class="fas fa-star" style="font-size:2.5rem;color:#fff"></i>
      </div>
      <h2 style="color:var(--clr-primary);margin-bottom:12px">Thank You for Your Review!</h2>
      <p style="font-size:1.05rem;color:var(--clr-muted);max-width:480px;margin:0 auto 32px">Your review has been submitted and is awaiting approval by our team. We really appreciate you taking the time to share your experience.</p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="<?= url('packages.php') ?>" class="btn btn-primary btn-lg"><i class="fas fa-compass"></i> Explore More Packages</a>
        <a href="<?= url() ?>" class="btn btn-outline btn-lg"><i class="fas fa-home"></i> Back to Home</a>
      </div>
    </div>

    <?php else: ?>
    <div style="display:grid;grid-template-columns:1fr 280px;gap:36px;align-items:start">

      <!-- Form -->
      <div>
        <?php if ($errors): ?>
        <div class="flash-msg flash-error" style="margin-bottom:20px">
          <i class="fas fa-exclamation-circle"></i>
          <ul style="padding-left:16px;margin:0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>

        <form method="POST" id="reviewForm">
          <?= csrfField() ?>

          <!-- Package selection -->
          <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3><i class="fas fa-suitcase-rolling" style="color:var(--clr-gold);margin-right:8px"></i>Which trip are you reviewing?</h3></div>
            <div class="card-body">
              <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px">
                <?php foreach ($packages as $pkg):
                  $selected = ($prePkg && $prePkg['id']==$pkg['id']) || (!$prePkg && !empty($_POST['package_id']) && $_POST['package_id']==$pkg['id']);
                  $completed = in_array($pkg['id'], $completedPkgIds);
                ?>
                <label class="pkg-review-option <?= $selected ? 'selected' : '' ?>" style="position:relative;cursor:pointer;display:block">
                  <input type="radio" name="package_id" value="<?= $pkg['id'] ?>" <?= $selected ? 'checked' : '' ?> style="position:absolute;opacity:0" required>
                  <?php if ($pkg['hero_image']): ?>
                  <img src="<?= h($pkg['hero_image']) ?>" alt="<?= h($pkg['title']) ?>" style="width:100%;height:90px;object-fit:cover;border-radius:8px;display:block">
                  <?php else: ?>
                  <div style="width:100%;height:90px;background:var(--clr-light);border-radius:8px;display:grid;place-items:center"><i class="fas fa-mountain" style="font-size:2rem;color:var(--clr-border)"></i></div>
                  <?php endif; ?>
                  <?php if ($completed): ?>
                  <span style="position:absolute;top:6px;right:6px;background:#38a169;color:#fff;font-size:.6rem;font-weight:700;padding:2px 6px;border-radius:20px"><i class="fas fa-check"></i> Travelled</span>
                  <?php endif; ?>
                  <div style="padding:8px 6px 4px;font-size:.78rem;font-weight:600;color:var(--clr-primary);line-height:1.3"><?= h($pkg['title']) ?></div>
                  <div class="pkg-review-check" style="display:none;position:absolute;inset:0;border-radius:8px;border:2.5px solid var(--clr-gold);pointer-events:none">
                    <div style="position:absolute;top:-8px;right:-8px;width:22px;height:22px;background:var(--clr-gold);border-radius:50%;display:grid;place-items:center">
                      <i class="fas fa-check" style="font-size:.6rem;color:#fff"></i>
                    </div>
                  </div>
                </label>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- Star rating -->
          <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3><i class="fas fa-star" style="color:var(--clr-gold);margin-right:8px"></i>How would you rate your experience?</h3></div>
            <div class="card-body">
              <div id="starRating" style="display:flex;gap:8px;margin-bottom:12px">
                <?php for ($i=5; $i>=1; $i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= (($_POST['rating']??5)==$i)?'checked':'' ?> style="display:none">
                <label for="star<?= $i ?>" class="star-label" style="font-size:2.5rem;cursor:pointer;color:#cbd5e0;transition:color .15s;-webkit-user-select:none;user-select:none">★</label>
                <?php endfor; ?>
              </div>
              <div id="ratingText" style="font-size:.9rem;font-weight:600;color:var(--clr-primary)">Select your rating</div>
            </div>
          </div>

          <!-- Your review -->
          <div class="card" style="margin-bottom:20px">
            <div class="card-header"><h3><i class="fas fa-pencil-alt" style="color:var(--clr-gold);margin-right:8px"></i>Write your review</h3></div>
            <div class="card-body">
              <div class="form-group">
                <label>Review Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="Summarise your experience in one line" maxlength="250" value="<?= h($_POST['title']??'') ?>">
              </div>
              <div class="form-group">
                <label>Your Review <span class="required">*</span></label>
                <textarea name="body" id="reviewBody" class="form-control" rows="6" placeholder="Tell us about the highlights, guides, accommodation, food, wildlife… What made it special?" maxlength="2000" required><?= h($_POST['body']??'') ?></textarea>
                <div style="display:flex;justify-content:space-between;margin-top:4px">
                  <span class="form-hint">Minimum 20 characters</span>
                  <span class="form-hint" id="charCount">0 / 2000</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Personal details -->
          <div class="card" style="margin-bottom:24px">
            <div class="card-header"><h3><i class="fas fa-user" style="color:var(--clr-gold);margin-right:8px"></i>About you</h3></div>
            <div class="card-body">
              <?php if ($user): ?>
              <div class="flash-msg flash-info" style="margin-bottom:16px"><i class="fas fa-info-circle"></i><span>Submitting as <strong><?= h($user['first_name'].' '.$user['last_name']) ?></strong></span></div>
              <?php endif; ?>
              <div class="form-row">
                <div class="form-group">
                  <label>Full Name <span class="required">*</span></label>
                  <input type="text" name="name" class="form-control" required value="<?= h($_POST['name'] ?? ($user ? $user['first_name'].' '.$user['last_name'] : '')) ?>">
                </div>
                <div class="form-group">
                  <label>Email <span class="required">*</span></label>
                  <input type="email" name="email" class="form-control" required value="<?= h($_POST['email'] ?? ($user['email'] ?? '')) ?>">
                  <span class="form-hint">Not shown publicly</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Terms + submit -->
          <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;font-size:.85rem;color:var(--clr-muted)">
            <input type="checkbox" required id="agreeTerms" style="accent-color:var(--clr-gold);margin-top:2px">
            <label for="agreeTerms">I confirm this is a genuine review of a trip I took with MT Safaris and agree to the <a href="<?= url('terms.php') ?>" style="color:var(--clr-gold)" target="_blank">Terms & Conditions</a>.</label>
          </div>

          <button type="submit" class="btn btn-gold btn-lg btn-block">
            <i class="fas fa-paper-plane"></i> Submit Review
          </button>
          <p style="text-align:center;font-size:.78rem;color:var(--clr-muted);margin-top:12px">
            <i class="fas fa-lock" style="color:var(--clr-success)"></i> Reviews are moderated and published within 24–48 hours.
          </p>
        </form>
      </div>

      <!-- Sidebar -->
      <div style="position:sticky;top:calc(var(--header-h)+24px)">
        <div class="card" style="margin-bottom:20px">
          <div class="card-body" style="text-align:center;padding:28px">
            <div style="font-size:3.5rem;line-height:1;margin-bottom:8px;color:var(--clr-gold)">★</div>
            <h4 style="color:var(--clr-primary);margin-bottom:4px">Your voice matters</h4>
            <p style="font-size:.82rem;color:var(--clr-muted);line-height:1.7">Reviews help future travellers choose the right trip and help us keep improving. We read every single one.</p>
          </div>
        </div>

        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><h3 style="font-size:.95rem">Review Guidelines</h3></div>
          <div class="card-body">
            <?php foreach ([
              ['fas fa-check-circle','#38a169','Be specific about your experience'],
              ['fas fa-check-circle','#38a169','Mention your guide, wildlife, or accommodation'],
              ['fas fa-check-circle','#38a169','Include what you loved and any suggestions'],
              ['fas fa-times-circle','#e53e3e','No personal information of third parties'],
              ['fas fa-times-circle','#e53e3e','No offensive or discriminatory language'],
            ] as [$icon, $color, $text]): ?>
            <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:10px;font-size:.82rem;color:var(--clr-muted)">
              <i class="<?= $icon ?>" style="color:<?= $color ?>;margin-top:2px;flex-shrink:0"></i><?= $text ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="background:var(--clr-light);border-radius:12px;padding:16px;text-align:center">
          <p style="font-size:.82rem;color:var(--clr-muted);margin-bottom:10px">Have a question instead?</p>
          <a href="<?= url('contact.php') ?>" class="btn btn-outline btn-sm btn-block"><i class="fas fa-envelope"></i> Contact Us</a>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>

<style>
.pkg-review-option {
  border-radius: 10px;
  border: 2px solid var(--clr-border);
  overflow: hidden;
  transition: border-color .2s, transform .15s;
}
.pkg-review-option:hover { border-color: var(--clr-primary); }
.pkg-review-option.selected,
.pkg-review-option:has(input:checked) { border-color: var(--clr-gold); }
.pkg-review-option:has(input:checked) .pkg-review-check { display: block !important; }

/* Star rating — CSS-only RTL trick */
#starRating { flex-direction: row-reverse; justify-content: flex-end; }
#starRating input:checked ~ label,
#starRating label:hover,
#starRating label:hover ~ label { color: var(--clr-gold); }
</style>

<script>
// Package selection visual feedback
document.querySelectorAll('.pkg-review-option input').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.querySelectorAll('.pkg-review-option').forEach(function(el) { el.classList.remove('selected'); });
    if (this.checked) this.closest('.pkg-review-option').classList.add('selected');
  });
});

// Star rating label
const ratingLabels = {1:'Poor',2:'Fair',3:'Good',4:'Great',5:'Excellent!'};
document.querySelectorAll('#starRating input').forEach(function(radio) {
  radio.addEventListener('change', function() {
    document.getElementById('ratingText').textContent = ratingLabels[this.value] || '';
  });
});
// Set initial label
const checkedStar = document.querySelector('#starRating input:checked');
if (checkedStar) document.getElementById('ratingText').textContent = ratingLabels[checkedStar.value] || '';

// Character counter
const bodyEl = document.getElementById('reviewBody');
const countEl = document.getElementById('charCount');
function updateCount() { countEl.textContent = bodyEl.value.length + ' / 2000'; }
bodyEl.addEventListener('input', updateCount);
updateCount();
</script>
