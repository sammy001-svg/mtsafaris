<?php
$pageTitle   = 'Booking Confirmed — MT Safaris';
$headerClass = 'solid';
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$ref     = trim($_GET['ref'] ?? '');
$booking = $ref ? DB::row("SELECT b.*, p.title AS package_title, p.hero_image, d.name AS destination_name, p.duration_days
                           FROM bookings b
                           JOIN packages p ON b.package_id=p.id
                           LEFT JOIN destinations d ON p.destination_id=d.id
                           WHERE b.reference=?", [$ref]) : null;

if (!$booking) redirect(url('packages.php'));

$lead = jd($booking['lead_traveler']);
require_once 'includes/header.php';
?>

<section class="section">
  <div class="container-sm">
    <div style="text-align:center;padding:48px 0 40px">
      <div style="width:90px;height:90px;background:linear-gradient(135deg,var(--clr-success),#48bb78);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;box-shadow:0 12px 32px rgba(56,161,105,.3)">
        <i class="fas fa-check" style="font-size:2.5rem;color:#fff"></i>
      </div>
      <h1 style="font-size:2.2rem;color:var(--clr-primary);margin-bottom:12px">Booking Received!</h1>
      <p style="font-size:1.05rem;color:var(--clr-muted);max-width:520px;margin:0 auto 24px">
        Thank you, <strong><?= h($lead['first_name'] ?? 'Traveler') ?></strong>! Your booking has been received and we'll confirm it within 24 hours.
      </p>
      <div style="display:inline-flex;align-items:center;gap:10px;background:var(--clr-light);border:2px solid var(--clr-border);border-radius:10px;padding:14px 24px">
        <i class="fas fa-ticket-alt" style="color:var(--clr-gold);font-size:1.2rem"></i>
        <span style="font-size:.85rem;color:var(--clr-muted)">Booking Reference:</span>
        <span style="font-size:1.1rem;font-weight:800;color:var(--clr-primary);font-family:var(--ff-head)"><?= h($booking['reference']) ?></span>
      </div>
    </div>

    <div style="background:#fff;border-radius:20px;border:1px solid var(--clr-border);overflow:hidden;margin-bottom:32px">
      <!-- Package Banner -->
      <div style="position:relative;height:200px;overflow:hidden">
        <img src="<?= h($booking['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=900&q=80') ?>"
             style="width:100%;height:100%;object-fit:cover" alt="">
        <div style="position:absolute;inset:0;background:rgba(12,38,20,.6)"></div>
        <div style="position:absolute;bottom:20px;left:24px">
          <h3 style="color:#fff;font-size:1.3rem"><?= h($booking['package_title']) ?></h3>
          <p style="color:rgba(255,255,255,.8);font-size:.875rem"><?= h($booking['destination_name']) ?> · <?= $booking['duration_days'] ?> Days</p>
        </div>
      </div>

      <!-- Booking Details -->
      <div style="padding:28px 28px">
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:24px">
          <?php
          $details = [
            ['fas fa-calendar-alt','Travel Date',formatDate($booking['travel_date'])],
            ['fas fa-users','Travelers',$booking['adults'].' Adult'.($booking['adults']>1?'s':'').($booking['children']?' · '.$booking['children'].' Child'.(($booking['children']>1)?'ren':''):'')],
            ['fas fa-info-circle','Status',ucfirst($booking['status'])],
          ];
          foreach ($details as $d): ?>
          <div style="text-align:center;padding:16px;background:var(--clr-light);border-radius:10px">
            <i class="<?= $d[0] ?>" style="color:var(--clr-gold);font-size:1.2rem;display:block;margin-bottom:8px"></i>
            <div style="font-size:.72rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:4px"><?= $d[1] ?></div>
            <div style="font-size:.9rem;font-weight:700;color:var(--clr-primary)"><?= $d[2] ?></div>
          </div>
          <?php endforeach; ?>
        </div>

        <div class="price-summary">
          <div class="price-row"><span>Subtotal</span><span><?= money($booking['subtotal']) ?></span></div>
          <?php if ($booking['discount_amount']>0): ?>
          <div class="price-row" style="color:var(--clr-success)"><span>Discount</span><span>-<?= money($booking['discount_amount']) ?></span></div>
          <?php endif; ?>
          <div class="price-row"><span>Tax (<?= TAX_RATE ?>%)</span><span><?= money($booking['tax_amount']) ?></span></div>
          <div class="price-row total"><span>Total</span><span><?= money($booking['total_amount']) ?></span></div>
          <div class="price-row" style="color:var(--clr-gold);font-weight:600;font-size:.85rem">
            <span>Deposit Due Now (<?= BOOKING_DEPOSIT ?>%)</span>
            <span><?= money($booking['total_amount'] * BOOKING_DEPOSIT / 100) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Next Steps -->
    <div style="background:var(--clr-light);border-radius:16px;padding:28px;margin-bottom:32px">
      <h3 style="color:var(--clr-primary);margin-bottom:20px"><i class="fas fa-list-ol" style="color:var(--clr-gold);margin-right:8px"></i>What Happens Next?</h3>
      <div style="display:flex;flex-direction:column;gap:14px">
        <?php foreach ([
          ['Confirmation Email','You\'ll receive a detailed booking confirmation email at '.h($lead['email']).' within 15 minutes.'],
          ['Our Team Reviews','Our team will review your booking and contact you within 24 hours to confirm all details.'],
          ['Deposit Payment','Pay your '.BOOKING_DEPOSIT.'% deposit ('.money($booking['total_amount']*BOOKING_DEPOSIT/100).') to fully secure your booking.'],
          ['Countdown to Adventure','30 days before departure, we\'ll send your final itinerary, packing guide, and travel documents.'],
        ] as $i => $step): ?>
        <div style="display:flex;gap:14px;align-items:start">
          <div style="width:32px;height:32px;background:var(--clr-primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0"><?= $i+1 ?></div>
          <div>
            <h5 style="color:var(--clr-primary);margin-bottom:4px"><?= $step[0] ?></h5>
            <p style="color:var(--clr-muted);font-size:.85rem"><?= $step[1] ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div style="display:flex;gap:12px;flex-wrap:wrap;justify-content:center">
      <a href="<?= url('invoice.php?ref='.urlencode($booking['reference'])) ?>" target="_blank" class="btn btn-gold btn-lg"><i class="fas fa-file-invoice"></i> Download Invoice</a>
      <?php if (isLoggedIn()): ?>
      <a href="<?= url('portal/bookings.php') ?>" class="btn btn-primary btn-lg"><i class="fas fa-ticket-alt"></i> View My Bookings</a>
      <?php endif; ?>
      <a href="<?= url('packages.php') ?>" class="btn btn-outline btn-lg"><i class="fas fa-compass"></i> Explore More</a>
      <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>?text=Hello!+I+just+booked+reference+<?= h($booking['reference']) ?>+and+have+a+question." class="btn btn-sky btn-lg" target="_blank">
        <i class="fab fa-whatsapp"></i> WhatsApp Us
      </a>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
