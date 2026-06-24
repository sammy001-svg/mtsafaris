<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$user = currentUser();

$ref = trim($_GET['ref'] ?? '');
if (!$ref) redirect(url('portal/bookings.php'));

$booking = DB::row("SELECT b.*, p.title AS package_title, p.slug AS package_slug, p.hero_image, p.duration_days, p.accommodation, p.transport, p.meals
                    FROM bookings b LEFT JOIN packages p ON b.package_id=p.id
                    WHERE b.reference=? AND b.user_id=?", [$ref, $user['id']]);
if (!$booking) redirect(url('portal/bookings.php'));

// Handle cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (isset($_POST['cancel']) && in_array($booking['status'], ['pending','confirmed'])) {
        $reason = trim($_POST['cancel_reason'] ?? '');
        DB::update('bookings', [
            'status'        => 'cancelled',
            'cancel_reason' => $reason,
            'cancelled_at'  => date('Y-m-d H:i:s'),
        ], ['id' => $booking['id']]);
        flash('success', 'Your cancellation request has been submitted. Our team will be in touch within 24 hours.');
        redirect(url('portal/booking-view.php?ref=' . $ref));
    }
}

$leadTraveler = jd($booking['lead_traveler']??'{}', []);
$addons       = jd($booking['addons']??'[]', []);
$payments     = DB::rows("SELECT * FROM booking_payments WHERE booking_id=? AND status='completed' ORDER BY paid_at DESC", [$booking['id']]);
$totalPaid    = array_sum(array_column($payments, 'amount'));
$pageTitle    = 'Booking '.$booking['reference'].' | MT Safaris';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="portal-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="portal-main">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:20px">
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>
    <div class="portal-header" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px">
      <div>
        <a href="<?= url('portal/bookings.php') ?>" style="color:var(--clr-muted);font-size:.875rem;text-decoration:none;display:block;margin-bottom:6px"><i class="fas fa-arrow-left"></i> Back to Bookings</a>
        <h1>Booking <?= h($booking['reference']) ?></h1>
      </div>
      <span class="status-badge status-<?= h($booking['status']) ?>" style="font-size:.875rem;padding:6px 16px"><?= ucfirst($booking['status']) ?></span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:28px;align-items:start">
      <div>
        <!-- Package Card -->
        <div class="card" style="margin-bottom:24px">
          <div class="card-body" style="display:flex;gap:16px">
            <?php if ($booking['hero_image']): ?><img src="<?= h($booking['hero_image']) ?>" style="width:120px;height:88px;object-fit:cover;border-radius:var(--radius);flex-shrink:0" alt="" loading="lazy" decoding="async"><?php endif; ?>
            <div>
              <h3 style="color:var(--clr-primary);margin-bottom:8px"><?= h($booking['package_title']??'Package') ?></h3>
              <div style="display:flex;gap:16px;flex-wrap:wrap">
                <span style="font-size:.875rem;color:var(--clr-muted)"><i class="fas fa-calendar" style="color:var(--clr-gold)"></i> <?= formatDate($booking['travel_date'],'F j, Y') ?></span>
                <span style="font-size:.875rem;color:var(--clr-muted)"><i class="fas fa-users" style="color:var(--clr-gold)"></i> <?= $booking['adults'] ?> Adults<?= $booking['children']?' + '.$booking['children'].' Child':'' ?></span>
                <span style="font-size:.875rem;color:var(--clr-muted)"><i class="fas fa-clock" style="color:var(--clr-gold)"></i> <?= $booking['duration_days'] ?> Days</span>
              </div>
              <?php if ($booking['accommodation']): ?><p style="font-size:.8rem;color:var(--clr-muted);margin-top:8px"><i class="fas fa-hotel"></i> <?= h($booking['accommodation']) ?> &nbsp;<i class="fas fa-car"></i> <?= h($booking['transport']??'') ?></p><?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Lead Traveler -->
        <div class="card" style="margin-bottom:24px">
          <div class="card-header"><h3>Lead Traveler</h3></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px">
              <?php foreach ([
                ['Full Name', ($leadTraveler['first_name']??'').' '.($leadTraveler['last_name']??'')],
                ['Email', $leadTraveler['email']??$user['email']],
                ['Phone', $leadTraveler['phone']??$user['phone']??'—'],
                ['Nationality', $leadTraveler['nationality']??'—'],
                ['Passport #', $leadTraveler['passport']??'—'],
              ] as [$label,$value]): ?>
              <div>
                <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--clr-muted);margin-bottom:3px"><?= $label ?></div>
                <div style="font-size:.875rem;font-weight:500;color:var(--clr-primary)"><?= h($value) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <?php if ($booking['special_requests']): ?>
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--clr-border)">
              <div style="font-size:.7rem;text-transform:uppercase;letter-spacing:.05em;color:var(--clr-muted);margin-bottom:6px">Special Requests</div>
              <p style="font-size:.875rem;color:var(--clr-text);line-height:1.6;margin:0"><?= nl2br(h($booking['special_requests'])) ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- What's Next -->
        <?php if (in_array($booking['status'],['pending','confirmed'])): ?>
        <div class="card">
          <div class="card-header"><h3>What Happens Next</h3></div>
          <div class="card-body">
            <?php $steps = [
              ['fas fa-check-circle','Confirmation Email','You\'ll receive a confirmation email with all booking details shortly.', $booking['status']!=='pending'],
              ['fas fa-file-alt','Documents','Our team will send your travel documents and itinerary 2 weeks before departure.', false],
              ['fas fa-plane','Pre-Departure Briefing','A detailed briefing call with your dedicated travel consultant will be arranged.', false],
              ['fas fa-map-marker-alt','Enjoy Your Trip!','Time to pack your bags and create memories that last a lifetime.', false],
            ]; ?>
            <?php foreach ($steps as $i => [$icon, $title, $desc, $done]): ?>
            <div style="display:flex;gap:14px;margin-bottom:20px;<?= $i<3?'':'margin-bottom:0' ?>">
              <div style="width:36px;height:36px;border-radius:50%;background:<?= $done?'var(--clr-primary)':'#e5e7eb' ?>;display:grid;place-items:center;flex-shrink:0">
                <i class="<?= $icon ?>" style="color:<?= $done?'#fff':'#9ca3af' ?>;font-size:.875rem"></i>
              </div>
              <div>
                <div style="font-weight:600;color:var(--clr-primary);margin-bottom:4px"><?= $title ?></div>
                <p style="font-size:.875rem;color:var(--clr-muted);line-height:1.6;margin:0"><?= $desc ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div>
        <!-- Price Summary -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><h3>Price Summary</h3></div>
          <div class="card-body">
            <?php
            $subtotal = (float)($booking['subtotal'] ?? $booking['total_amount']);
            $discount = (float)($booking['discount'] ?? 0);
            $tax      = (float)($booking['tax_amount'] ?? 0);
            $total    = (float)$booking['total_amount'];
            ?>
            <div style="display:flex;flex-direction:column;gap:8px;font-size:.875rem">
              <div style="display:flex;justify-content:space-between"><span style="color:var(--clr-muted)">Subtotal</span><span><?= money($subtotal) ?></span></div>
              <?php if ($discount): ?><div style="display:flex;justify-content:space-between;color:#059669"><span>Discount</span><span>-<?= money($discount) ?></span></div><?php endif; ?>
              <?php if ($tax): ?><div style="display:flex;justify-content:space-between"><span style="color:var(--clr-muted)">Tax</span><span><?= money($tax) ?></span></div><?php endif; ?>
              <?php foreach ($addons as $ao): ?><div style="display:flex;justify-content:space-between;font-size:.8rem"><span style="color:var(--clr-muted)">+ <?= h($ao['name']??'') ?></span><span><?= money($ao['price']??0) ?></span></div><?php endforeach; ?>
              <div style="display:flex;justify-content:space-between;font-weight:700;font-size:1rem;padding-top:8px;margin-top:8px;border-top:2px solid var(--clr-border)"><span style="color:var(--clr-primary)">Total</span><span style="color:var(--clr-primary)"><?= money($total) ?></span></div>
              <div style="display:flex;justify-content:space-between;font-size:.8rem"><span style="color:var(--clr-muted)">Deposit (<?= BOOKING_DEPOSIT ?>%)</span><span><?= money($total*BOOKING_DEPOSIT/100) ?></span></div>
              <?php if ($totalPaid > 0): ?>
              <div style="display:flex;justify-content:space-between;font-size:.8rem;color:#059669"><span><i class="fas fa-check-circle"></i> Paid</span><span><?= money($totalPaid) ?></span></div>
              <?php endif; ?>
              <div style="display:flex;justify-content:space-between;font-size:.8rem;font-weight:600"><span style="color:var(--clr-muted)">Balance Due</span><span style="color:<?= ($total-$totalPaid)>0?'var(--clr-danger)':'#059669' ?>"><?= money(max(0,$total-$totalPaid)) ?></span></div>
            </div>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--clr-border);font-size:.8rem;color:var(--clr-muted)">
              <i class="fas fa-credit-card"></i> Payment: <?= ucfirst(str_replace('_',' ',$booking['payment_method']??'—')) ?>
            </div>
          </div>
        </div>

        <!-- Timeline -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><h3>Status Timeline</h3></div>
          <div class="card-body">
            <?php foreach ([
              ['Booked',$booking['created_at'],'fas fa-plus-circle'],
              ['Confirmed',$booking['confirmed_at']??null,'fas fa-check-circle'],
              ['Completed',$booking['completed_at']??null,'fas fa-flag'],
            ] as [$label,$ts,$icon]): if (!$ts) continue; ?>
            <div style="display:flex;gap:10px;margin-bottom:12px">
              <i class="<?= $icon ?>" style="color:var(--clr-gold);flex-shrink:0;margin-top:2px"></i>
              <div>
                <div style="font-size:.85rem;font-weight:600;color:var(--clr-primary)"><?= $label ?></div>
                <div style="font-size:.75rem;color:var(--clr-muted)"><?= formatDate($ts,'M j, Y g:ia') ?></div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <div class="card-body">
            <a href="<?= url('invoice.php?ref='.urlencode($booking['reference'])) ?>" target="_blank" class="btn btn-block" style="background:var(--clr-primary);color:#fff;margin-bottom:8px"><i class="fas fa-file-invoice"></i> View Invoice</a>
            <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>?text=<?= urlencode('Hi, I have a question about booking '.$booking['reference']) ?>" target="_blank" class="btn btn-block" style="background:#25D366;color:#fff;margin-bottom:8px"><i class="fab fa-whatsapp"></i> WhatsApp Support</a>
            <a href="tel:<?= CONTACT_PHONE ?>" class="btn btn-outline btn-block" style="margin-bottom:8px"><i class="fas fa-phone"></i> Call Us</a>
            <?php if (in_array($booking['status'], ['pending','confirmed'])): ?>
            <button onclick="document.getElementById('cancelModal').style.display='flex'" class="btn btn-block" style="background:#fff5f5;color:var(--clr-danger);border:1px solid #fed7d7"><i class="fas fa-times-circle"></i> Request Cancellation</button>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;padding:20px">
  <div style="background:#fff;border-radius:16px;padding:32px;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div style="text-align:center;margin-bottom:20px">
      <div style="width:56px;height:56px;background:#fff5f5;border-radius:50%;display:grid;place-items:center;margin:0 auto 12px">
        <i class="fas fa-exclamation-triangle" style="font-size:1.5rem;color:var(--clr-danger)"></i>
      </div>
      <h3 style="color:var(--clr-primary);margin-bottom:6px">Request Cancellation</h3>
      <p style="font-size:.875rem;color:var(--clr-muted)">This will request cancellation for booking <strong><?= h($booking['reference']) ?></strong>. Our team will review and respond within 24 hours.</p>
    </div>
    <form method="POST">
      <?= csrfField() ?>
      <div class="form-group" style="margin-bottom:16px">
        <label style="display:block;font-size:.82rem;font-weight:600;color:var(--clr-muted);margin-bottom:6px">Reason for Cancellation</label>
        <textarea name="cancel_reason" class="form-control" rows="3" placeholder="Please share why you're cancelling so we can improve…"></textarea>
      </div>
      <div style="background:#fff8e1;border-radius:8px;padding:10px 14px;font-size:.78rem;color:#92400e;margin-bottom:16px">
        <i class="fas fa-info-circle"></i> Free cancellation is available within 48 hours of booking. Refund eligibility depends on the cancellation policy.
      </div>
      <div style="display:flex;gap:10px">
        <button type="button" onclick="document.getElementById('cancelModal').style.display='none'" class="btn btn-outline" style="flex:1">Keep Booking</button>
        <button type="submit" name="cancel" class="btn" style="flex:1;background:var(--clr-danger);color:#fff">Confirm Cancel</button>
      </div>
    </form>
  </div>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
