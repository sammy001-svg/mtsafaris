<?php
$pageTitle   = 'Book Your Trip — MT Safaris';
$headerClass = 'solid';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$pkgId = (int)($_GET['package'] ?? 0);
if (!$pkgId) redirect(url('packages.php'));

$pkg = DB::row("SELECT p.*, d.name AS destination_name, d.country FROM packages p LEFT JOIN destinations d ON p.destination_id=d.id WHERE p.id=? AND p.is_active=1", [$pkgId]);
if (!$pkg) redirect(url('packages.php'));

$addons = DB::rows("SELECT * FROM package_addons WHERE package_id=? AND is_active=1", [$pkgId]);
$user   = currentUser();

// Handle form submission
$errors = [];
$step   = (int)($_POST['step'] ?? 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $step === 3) {
    if (!verifyCsrf()) { $errors[] = 'Security check failed.'; }
    else {
        $lead = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name'  => trim($_POST['last_name'] ?? ''),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'nationality'=> trim($_POST['nationality'] ?? ''),
            'passport_no'=> trim($_POST['passport_no'] ?? ''),
        ];
        $adults       = max(1, (int)($_POST['adults'] ?? 1));
        $children     = max(0, (int)($_POST['children'] ?? 0));
        $travel_date  = $_POST['travel_date'] ?? '';
        $coupon_code  = trim($_POST['coupon_code'] ?? '');
        $special_req  = trim($_POST['special_requests'] ?? '');

        if (!$lead['first_name'] || !$lead['last_name'] || !$lead['email']) $errors[] = 'Lead traveler details are required.';
        if (!filter_var($lead['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
        if (!$travel_date) $errors[] = 'Please select a travel date.';

        if (!$errors) {
            $basePrice = (float)($pkg['sale_price'] ?: $pkg['base_price']);
            $subtotal  = $basePrice * ($adults + $children * 0.5);

            // Addons
            $selectedAddons = [];
            foreach ($addons as $addon) {
                if (!empty($_POST['addon_' . $addon['id']])) {
                    $selectedAddons[] = ['id'=>$addon['id'],'name'=>$addon['name'],'price'=>$addon['price']];
                    $subtotal += $addon['price'];
                }
            }

            // Coupon
            $discount = 0;
            if ($coupon_code) {
                $couponResult = validateCoupon($coupon_code, $subtotal);
                if ($couponResult['valid']) {
                    $discount = $couponResult['discount'];
                    DB::update('coupons', ['used_count' => DB::value("SELECT used_count FROM coupons WHERE code=?", [$coupon_code]) + 1], ['code' => $coupon_code]);
                }
            }

            $tax   = ($subtotal - $discount) * TAX_RATE / 100;
            $total = $subtotal - $discount + $tax;

            DB::beginTransaction();
            try {
                $ref = generateReference();
                $bookingId = DB::insert('bookings', [
                    'reference'       => $ref,
                    'user_id'         => $user ? $user['id'] : null,
                    'package_id'      => $pkg['id'],
                    'travel_date'     => $travel_date,
                    'adults'          => $adults,
                    'children'        => $children,
                    'lead_traveler'   => json_encode($lead),
                    'addons'          => json_encode($selectedAddons),
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discount,
                    'tax_amount'      => $tax,
                    'total_amount'    => $total,
                    'coupon_code'     => $coupon_code ?: null,
                    'special_requests'=> $special_req,
                    'status'          => 'pending',
                ]);
                DB::commit();
                redirect(url('booking-confirmation.php?ref=' . $ref));
            } catch (Exception $e) {
                DB::rollback();
                $errors[] = 'Booking could not be saved. Please try again.';
            }
        }
    }
}

require_once 'includes/header.php';
$basePrice = (float)($pkg['sale_price'] ?: $pkg['base_price']);
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i>
        <a href="<?= url('packages.php') ?>">Packages</a><i class="fas fa-chevron-right"></i>
        <span>Book Now</span>
      </div>
      <h1>Book Your <span style="color:var(--clr-gold)">Adventure</span></h1>
      <p><?= h($pkg['title']) ?></p>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <!-- Step Indicators -->
    <div class="booking-steps" style="max-width:600px;margin:0 auto 48px">
      <?php foreach (['Trip Details','Traveler Info','Confirm & Pay'] as $i => $label): ?>
      <div class="booking-step <?= $i===0?'active':'' ?>">
        <div class="step-circle"><?= $i+1 ?></div>
        <div class="step-label"><?= $label ?></div>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($errors): ?>
    <div class="flash-msg flash-error" style="max-width:700px;margin:0 auto 24px">
      <i class="fas fa-exclamation-circle"></i>
      <div><ul style="padding-left:16px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div>
    </div>
    <?php endif; ?>

    <div class="pkg-detail-layout" style="gap:36px">

      <!-- BOOKING FORM -->
      <form method="POST" action="booking.php?package=<?= $pkgId ?>" id="bookingForm">
        <?= csrfField() ?>
        <input type="hidden" name="step" id="stepInput" value="3">

        <!-- STEP 1: Trip Details -->
        <div data-step="1" class="card" style="margin-bottom:20px">
          <div class="card-header"><h3><i class="fas fa-calendar-alt" style="color:var(--clr-gold);margin-right:8px"></i>Trip Details</h3></div>
          <div class="card-body">
            <div class="form-row">
              <div class="form-group">
                <label>Travel Date <span class="required">*</span></label>
                <input type="date" name="travel_date" class="form-control" required min="<?= date('Y-m-d',strtotime('+7 days')) ?>" value="<?= h($_POST['travel_date']??'') ?>">
              </div>
              <div class="form-group">
                <label>Return Date (estimated)</label>
                <input type="date" name="return_date" class="form-control" value="<?= h($_POST['return_date']??'') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Adults <span class="required">*</span></label>
                <div class="traveler-counter" style="display:flex;align-items:center;border:2px solid var(--clr-border);border-radius:8px;overflow:hidden">
                  <button type="button" class="tc-minus" style="padding:10px 16px;background:var(--clr-light);border:none;cursor:pointer;font-size:1.2rem;color:var(--clr-muted)">−</button>
                  <input type="number" name="adults" id="adultsCount" value="<?= (int)($_POST['adults']??2) ?>" min="<?= $pkg['min_pax'] ?>" max="<?= $pkg['max_pax'] ?>" class="form-control" style="text-align:center;border:none;border-radius:0;font-weight:600">
                  <button type="button" class="tc-plus" style="padding:10px 16px;background:var(--clr-light);border:none;cursor:pointer;font-size:1.2rem;color:var(--clr-muted)">+</button>
                </div>
                <p class="form-hint">Min <?= $pkg['min_pax'] ?> — Max <?= $pkg['max_pax'] ?> people</p>
              </div>
              <div class="form-group">
                <label>Children (under 12)</label>
                <div class="traveler-counter" style="display:flex;align-items:center;border:2px solid var(--clr-border);border-radius:8px;overflow:hidden">
                  <button type="button" class="tc-minus" style="padding:10px 16px;background:var(--clr-light);border:none;cursor:pointer;font-size:1.2rem;color:var(--clr-muted)">−</button>
                  <input type="number" name="children" id="childrenCount" value="<?= (int)($_POST['children']??0) ?>" min="0" max="10" class="form-control" style="text-align:center;border:none;border-radius:0;font-weight:600">
                  <button type="button" class="tc-plus" style="padding:10px 16px;background:var(--clr-light);border:none;cursor:pointer;font-size:1.2rem;color:var(--clr-muted)">+</button>
                </div>
                <p class="form-hint">Charged at 50% of adult rate</p>
              </div>
            </div>

            <!-- Add-ons -->
            <?php if ($addons): ?>
            <div class="form-group">
              <label>Optional Add-ons</label>
              <?php foreach ($addons as $addon): ?>
              <label style="display:flex;align-items:center;justify-content:space-between;padding:12px 14px;border:1px solid var(--clr-border);border-radius:8px;margin-bottom:8px;cursor:pointer;transition:all .2s" onmouseover="this.style.borderColor='var(--clr-primary)'" onmouseout="this.style.borderColor='var(--clr-border)'">
                <span style="display:flex;align-items:center;gap:10px">
                  <input type="checkbox" name="addon_<?= $addon['id'] ?>" value="1" style="accent-color:var(--clr-primary)">
                  <span><?= h($addon['name']) ?></span>
                </span>
                <span style="font-weight:700;color:var(--clr-gold)">+<?= money($addon['price']) ?></span>
              </label>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="form-group">
              <label>Special Requests</label>
              <textarea name="special_requests" class="form-control" rows="3" placeholder="Dietary requirements, room preferences, accessibility needs…"><?= h($_POST['special_requests']??'') ?></textarea>
            </div>
          </div>
        </div>

        <!-- STEP 2: Lead Traveler -->
        <div data-step="2" class="card" style="margin-bottom:20px">
          <div class="card-header"><h3><i class="fas fa-user" style="color:var(--clr-gold);margin-right:8px"></i>Lead Traveler Details</h3></div>
          <div class="card-body">
            <?php if ($user): ?>
            <div class="flash-msg flash-info" style="margin-bottom:16px"><i class="fas fa-info-circle"></i><span>Logged in as <?= h($user['first_name'] . ' ' . $user['last_name']) ?> — your details are pre-filled.</span></div>
            <?php endif; ?>
            <div class="form-row">
              <div class="form-group">
                <label>First Name <span class="required">*</span></label>
                <input type="text" name="first_name" class="form-control" required value="<?= h($_POST['first_name'] ?? $user['first_name'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Last Name <span class="required">*</span></label>
                <input type="text" name="last_name" class="form-control" required value="<?= h($_POST['last_name'] ?? $user['last_name'] ?? '') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Email Address <span class="required">*</span></label>
                <input type="email" name="email" class="form-control" required value="<?= h($_POST['email'] ?? $user['email'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Phone Number <span class="required">*</span></label>
                <input type="tel" name="phone" class="form-control" required value="<?= h($_POST['phone'] ?? $user['phone'] ?? '') ?>">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label>Nationality</label>
                <input type="text" name="nationality" class="form-control" placeholder="e.g., Kenyan" value="<?= h($_POST['nationality']??'') ?>">
              </div>
              <div class="form-group">
                <label>Passport Number</label>
                <input type="text" name="passport_no" class="form-control" placeholder="For visa processing" value="<?= h($_POST['passport_no']??'') ?>">
              </div>
            </div>
          </div>
        </div>

        <!-- STEP 3: Payment -->
        <div data-step="3" class="card" style="margin-bottom:20px">
          <div class="card-header"><h3><i class="fas fa-credit-card" style="color:var(--clr-gold);margin-right:8px"></i>Payment</h3></div>
          <div class="card-body">
            <div class="form-group">
              <label>Payment Method</label>
              <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px">
                <?php foreach (['stripe'=>['fab fa-stripe','Pay with Stripe','Credit/Debit Card'],'paypal'=>['fab fa-paypal','PayPal','PayPal Account'],'bank_transfer'=>['fas fa-university','Bank Transfer','EFT / Wire'],'mobile_money'=>['fas fa-mobile-alt','Mobile Money','M-PESA / Airtel']] as $method=>$info): ?>
                <label style="border:2px solid var(--clr-border);border-radius:10px;padding:14px;cursor:pointer;transition:all .2s" onclick="this.parentElement.querySelectorAll('label').forEach(l=>l.style.borderColor='var(--clr-border)');this.style.borderColor='var(--clr-primary)'">
                  <input type="radio" name="payment_method" value="<?= $method ?>" style="display:none">
                  <i class="<?= $info[0] ?>" style="font-size:1.5rem;color:var(--clr-primary);display:block;margin-bottom:6px"></i>
                  <div style="font-size:.82rem;font-weight:700;color:var(--clr-primary)"><?= $info[1] ?></div>
                  <div style="font-size:.72rem;color:var(--clr-muted)"><?= $info[2] ?></div>
                </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Coupon -->
            <div class="form-group">
              <label>Coupon Code</label>
              <div style="display:flex;gap:8px">
                <input type="text" name="coupon_code" id="couponCode" class="form-control" placeholder="Enter promo code" value="<?= h($_POST['coupon_code']??'') ?>">
                <button type="button" id="applyCoupon" class="btn btn-outline" style="white-space:nowrap">Apply</button>
              </div>
              <div id="couponResult" class="form-hint"></div>
              <input type="hidden" id="discountAmount" value="0">
            </div>

            <div class="flash-msg flash-info">
              <i class="fas fa-info-circle"></i>
              <span>A <?= BOOKING_DEPOSIT ?>% deposit (<?= money($basePrice*2*BOOKING_DEPOSIT/100) ?> approx.) is required to confirm your booking. The balance is due 30 days before departure.</span>
            </div>
          </div>
        </div>

        <!-- Terms & Submit -->
        <div class="card">
          <div class="card-body">
            <label class="filter-option" style="margin-bottom:16px;font-size:.875rem">
              <input type="checkbox" required style="accent-color:var(--clr-primary)">
              I agree to MT Safaris' <a href="<?= url('terms.php') ?>" target="_blank" style="color:var(--clr-gold)">Terms & Conditions</a> and <a href="<?= url('privacy.php') ?>" target="_blank" style="color:var(--clr-gold)">Privacy Policy</a>.
            </label>
            <button type="submit" class="btn btn-gold btn-lg btn-block">
              <i class="fas fa-lock"></i> Complete Booking
            </button>
            <p style="text-align:center;font-size:.75rem;color:var(--clr-muted);margin-top:12px">
              <i class="fas fa-shield-alt" style="color:var(--clr-success)"></i> 256-bit SSL encrypted · Free cancellation within 48 hours
            </p>
          </div>
        </div>
      </form>

      <!-- BOOKING SUMMARY -->
      <aside>
        <div style="background:#fff;border:2px solid var(--clr-border);border-radius:16px;overflow:hidden;position:sticky;top:calc(var(--header-h)+20px)">
          <div style="background:var(--clr-primary);padding:20px 24px">
            <h3 style="color:#fff;font-size:.95rem;margin-bottom:12px">Booking Summary</h3>
            <div style="display:flex;gap:12px;align-items:start">
              <img src="<?= h($pkg['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=200&q=70') ?>"
                   style="width:64px;height:52px;object-fit:cover;border-radius:8px;flex-shrink:0" alt="" loading="lazy" decoding="async">
              <div>
                <p style="color:#fff;font-size:.85rem;font-weight:700;line-height:1.3"><?= h($pkg['title']) ?></p>
                <p style="color:rgba(255,255,255,.7);font-size:.75rem;margin-top:4px">
                  <i class="fas fa-map-marker-alt" style="color:var(--clr-gold)"></i> <?= h($pkg['destination_name']??'') ?>
                  · <?= $pkg['duration_days'] ?> Days
                </p>
              </div>
            </div>
          </div>
          <div style="padding:20px 24px">
            <div class="price-summary">
              <div class="price-row">
                <span>Base Price</span>
                <span><?= money($basePrice) ?> / person</span>
              </div>
              <div class="price-row">
                <span>Adults (<span id="adultsDisplay">2</span>)</span>
                <span id="summarySubtotal"><?= money($basePrice*2) ?></span>
              </div>
              <div class="price-row">
                <span>Children (<span id="childrenDisplay">0</span>)</span>
                <span id="summaryChildren">$0.00</span>
              </div>
              <div class="price-row" id="discountRow" style="color:var(--clr-success);display:none">
                <span>Coupon Discount</span>
                <span id="summaryDiscount">-$0.00</span>
              </div>
              <div class="price-row">
                <span>Tax (<?= TAX_RATE ?>%)</span>
                <span id="summaryTax">—</span>
              </div>
              <div class="price-row total">
                <span>Total</span>
                <span id="summaryTotal">—</span>
              </div>
              <div class="price-row" style="color:var(--clr-gold);font-weight:600;font-size:.8rem">
                <span>Deposit Required (<?= BOOKING_DEPOSIT ?>%)</span>
                <span id="summaryDeposit">—</span>
              </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;margin-top:16px;font-size:.78rem;color:var(--clr-muted)">
              <div style="display:flex;gap:8px">
                <i class="fas fa-check-circle" style="color:var(--clr-success);margin-top:2px"></i>
                Free cancellation within 48 hours
              </div>
              <div style="display:flex;gap:8px">
                <i class="fas fa-check-circle" style="color:var(--clr-success);margin-top:2px"></i>
                Instant booking confirmation
              </div>
              <div style="display:flex;gap:8px">
                <i class="fas fa-check-circle" style="color:var(--clr-success);margin-top:2px"></i>
                24/7 customer support
              </div>
            </div>
          </div>
        </div>
      </aside>

    </div>
  </div>
</section>

<script>
const BASE = <?= $basePrice ?>;
const TAX  = <?= TAX_RATE ?> / 100;
const DEP  = <?= BOOKING_DEPOSIT ?> / 100;

function calcPrice() {
  const adults   = +document.getElementById('adultsCount')?.value || 0;
  const children = +document.getElementById('childrenCount')?.value || 0;
  const discount = +document.getElementById('discountAmount')?.value || 0;

  const adultTotal    = BASE * adults;
  const childrenTotal = BASE * children * 0.5;
  const subtotal      = adultTotal + childrenTotal - discount;
  const tax           = subtotal * TAX;
  const total         = subtotal + tax;
  const deposit       = total * DEP;

  const set = (id, val) => { const e = document.getElementById(id); if (e) e.textContent = val; };
  set('adultsDisplay',    adults);
  set('childrenDisplay',  children);
  set('summarySubtotal',  '$'+adultTotal.toFixed(2));
  set('summaryChildren',  '$'+childrenTotal.toFixed(2));
  set('summaryTax',       '$'+tax.toFixed(2));
  set('summaryTotal',     '$'+total.toFixed(2));
  set('summaryDeposit',   '$'+deposit.toFixed(2));
  if (discount > 0) {
    set('summaryDiscount', '-$'+discount.toFixed(2));
    document.getElementById('discountRow').style.display = '';
  }
}
['adultsCount','childrenCount'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', calcPrice);
  document.getElementById(id)?.addEventListener('change', calcPrice);
});
calcPrice();
</script>

<?php require_once 'includes/footer.php'; ?>
