<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$ref     = trim($_GET['ref'] ?? '');
$booking = $ref ? DB::row(
    "SELECT b.*, p.title AS package_title, p.hero_image, p.duration_days, p.slug AS package_slug,
            d.name AS destination_name, d.country,
            CONCAT(u.first_name,' ',u.last_name) AS user_name, u.email AS user_email
     FROM bookings b
     JOIN packages p ON b.package_id = p.id
     LEFT JOIN destinations d ON p.destination_id = d.id
     LEFT JOIN users u ON b.user_id = u.id
     WHERE b.reference = ?", [$ref]
) : null;

if (!$booking) { header('Location: '.url('packages.php')); exit; }

// Auth check: guests need the exact ref; logged-in users must own the booking
$currentUser = currentUser();
if ($currentUser && $booking['user_id'] && $booking['user_id'] != $currentUser['id']) {
    header('Location: '.url('portal/')); exit;
}

$lead    = jd($booking['lead_traveler'] ?? '{}', []);
$addons  = jd($booking['addons']        ?? '[]', []);
$payments = DB::rows("SELECT * FROM booking_payments WHERE booking_id=? ORDER BY paid_at DESC", [$booking['id']]);
$totalPaid = array_sum(array_column(array_filter($payments, fn($p) => $p['status']==='completed'), 'amount'));
$balanceDue = $booking['total_amount'] - $totalPaid;

$statusColor = [
    'pending'   => ['#d69e2e','#fff8e1'],
    'confirmed' => ['#3182ce','#ebf8ff'],
    'paid'      => ['#38a169','#f0fff4'],
    'completed' => ['#38a169','#f0fff4'],
    'cancelled' => ['#e53e3e','#fff5f5'],
    'refunded'  => ['#718096','#f7fafc'],
][$booking['status']] ?? ['#718096','#f7fafc'];

$invoiceNo = 'INV-' . $booking['reference'];
$issuedDate = formatDate($booking['created_at'], 'M j, Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice <?= h($invoiceNo) ?> — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <style>
    :root {
      --blue: #0C2614;
      --gold: #F6A229;
      --text: #1a202c;
      --muted: #718096;
      --border: #e2e8f0;
      --light: #f8fafc;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', system-ui, sans-serif; background: #f0f4f8; color: var(--text); font-size: 14px; line-height: 1.6; }

    /* Screen toolbar */
    .invoice-toolbar {
      background: var(--blue);
      padding: 14px 32px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }
    .invoice-toolbar a { color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; }
    .invoice-toolbar a:hover { color: #fff; }
    .toolbar-actions { display: flex; gap: 10px; }
    .toolbar-btn {
      display: flex; align-items: center; gap: 7px;
      padding: 9px 20px; border-radius: 8px; font-size: .82rem; font-weight: 600; cursor: pointer; border: none; transition: all .2s;
    }
    .btn-print { background: var(--gold); color: #fff; }
    .btn-print:hover { background: #d48920; }
    .btn-back { background: rgba(255,255,255,.12); color: #fff; text-decoration: none; }
    .btn-back:hover { background: rgba(255,255,255,.2); }

    /* Invoice wrapper */
    .invoice-wrap { max-width: 800px; margin: 32px auto; padding: 0 20px 60px; }
    .invoice-doc { background: #fff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,.1); overflow: hidden; }

    /* Header band */
    .inv-header { background: var(--blue); padding: 36px 40px; display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }
    .inv-logo { display: flex; align-items: center; gap: 12px; }
    .inv-logo-icon { width: 46px; height: 46px; background: var(--gold); border-radius: 10px; display: grid; place-items: center; }
    .inv-logo-icon i { font-size: 1.4rem; color: #fff; }
    .inv-logo-text { color: #fff; }
    .inv-logo-text h1 { font-size: 1.2rem; font-weight: 800; letter-spacing: .02em; }
    .inv-logo-text p { font-size: .72rem; color: rgba(255,255,255,.6); }
    .inv-meta { text-align: right; }
    .inv-meta h2 { font-size: 1.5rem; color: var(--gold); font-weight: 800; letter-spacing: .05em; }
    .inv-meta p { font-size: .78rem; color: rgba(255,255,255,.65); margin-top: 4px; }
    .inv-meta .inv-status { display: inline-block; padding: 4px 14px; border-radius: 20px; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; background: <?= $statusColor[1] ?>; color: <?= $statusColor[0] ?>; margin-top: 8px; }

    /* Body sections */
    .inv-body { padding: 36px 40px; }
    .inv-parties { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 32px; }
    .inv-party h4 { font-size: .68rem; color: var(--muted); text-transform: uppercase; letter-spacing: .1em; font-weight: 700; margin-bottom: 8px; }
    .inv-party p { font-size: .88rem; color: var(--text); line-height: 1.7; }
    .inv-party strong { color: var(--blue); font-weight: 700; }

    /* Package info */
    .inv-package { background: var(--light); border-radius: 10px; padding: 18px 20px; margin-bottom: 28px; display: flex; gap: 16px; align-items: center; }
    .inv-package img { width: 80px; height: 60px; object-fit: cover; border-radius: 8px; flex-shrink: 0; }
    .inv-package h3 { font-size: 1rem; color: var(--blue); font-weight: 700; }
    .inv-package p { font-size: .8rem; color: var(--muted); margin-top: 3px; }
    .inv-package .inv-travel-dates { display: flex; gap: 20px; margin-top: 8px; font-size: .78rem; font-weight: 600; }
    .inv-package .inv-travel-dates span { background: #fff; border: 1px solid var(--border); border-radius: 6px; padding: 4px 10px; color: var(--blue); }

    /* Line items */
    .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .inv-table th { font-size: .68rem; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); font-weight: 700; padding: 8px 0; border-bottom: 2px solid var(--border); text-align: left; }
    .inv-table th:last-child, .inv-table td:last-child { text-align: right; }
    .inv-table td { padding: 12px 0; border-bottom: 1px solid var(--border); font-size: .88rem; vertical-align: top; }
    .inv-table td.desc { color: var(--muted); font-size: .78rem; display: block; }
    .inv-table tr.subtotal td { padding-top: 16px; font-weight: 600; border-bottom: none; }
    .inv-table tr.discount td { color: #38a169; border-bottom: none; }
    .inv-table tr.tax-row td { color: var(--muted); border-bottom: none; }
    .inv-table tr.total-row td { font-size: 1rem; font-weight: 800; color: var(--blue); padding-top: 12px; border-top: 2px solid var(--blue); border-bottom: none; }
    .inv-table tr.deposit-row td { font-size: .85rem; color: var(--gold); font-weight: 700; border-bottom: none; }
    .inv-table tr.paid-row td { color: #38a169; font-weight: 700; border-bottom: none; }
    .inv-table tr.balance-row td { color: <?= $balanceDue > 0 ? '#e53e3e' : '#38a169' ?>; font-weight: 800; border-top: 1px dashed var(--border); padding-top: 8px; border-bottom: none; }

    /* Payments section */
    .inv-payments { background: #f0fff4; border: 1px solid #c6f6d5; border-radius: 10px; padding: 16px 20px; margin-bottom: 28px; }
    .inv-payments h4 { font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: #276749; margin-bottom: 10px; }
    .inv-pay-row { display: flex; justify-content: space-between; font-size: .82rem; padding: 4px 0; }

    /* Footer */
    .inv-footer { border-top: 2px solid var(--border); padding: 20px 40px; display: flex; justify-content: space-between; align-items: center; gap: 16px; background: var(--light); font-size: .75rem; color: var(--muted); }
    .inv-footer strong { color: var(--blue); }

    .inv-note { border: 1px dashed var(--border); border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: .82rem; color: var(--muted); line-height: 1.7; }

    /* Print styles */
    @media print {
      body { background: #fff; }
      .invoice-toolbar { display: none; }
      .invoice-wrap { margin: 0; padding: 0; max-width: 100%; }
      .invoice-doc { box-shadow: none; border-radius: 0; }
      .inv-header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
      .inv-package { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
  </style>
</head>
<body>

<!-- Screen toolbar (hidden on print) -->
<div class="invoice-toolbar">
  <a href="javascript:history.back()" class="btn-back toolbar-btn"><i class="fas fa-arrow-left"></i> Back</a>
  <div class="toolbar-actions">
    <button onclick="window.print()" class="toolbar-btn btn-print"><i class="fas fa-print"></i> Print / Save PDF</button>
  </div>
</div>

<div class="invoice-wrap">
  <div class="invoice-doc">

    <!-- Invoice header -->
    <div class="inv-header">
      <div class="inv-logo">
        <img src="<?= url('assets/images/logo.png') ?>" alt="Mountain Top Safaris Adventures" style="height:64px;width:auto">
        <div class="inv-logo-text">
          <h1>Mountain Top Safaris</h1>
          <p>Adventures &amp; Travel Experiences</p>
        </div>
      </div>
      <div class="inv-meta">
        <h2>INVOICE</h2>
        <p><?= h($invoiceNo) ?></p>
        <p>Issued: <?= $issuedDate ?></p>
        <div class="inv-status"><?= ucfirst($booking['status']) ?></div>
      </div>
    </div>

    <!-- Body -->
    <div class="inv-body">

      <!-- Parties -->
      <div class="inv-parties">
        <div class="inv-party">
          <h4>From</h4>
          <p>
            <strong>MT Safaris Ltd</strong><br>
            Nairobi, Kenya<br>
            info@mtsafaris.com<br>
            +254 700 000 000
          </p>
        </div>
        <div class="inv-party">
          <h4>Bill To</h4>
          <p>
            <strong><?= h(($lead['first_name'] ?? '') . ' ' . ($lead['last_name'] ?? '')) ?></strong><br>
            <?= h($lead['email'] ?? $booking['user_email'] ?? '—') ?><br>
            <?= h($lead['phone'] ?? '—') ?><br>
            <?= h($lead['nationality'] ?? '') ?>
            <?php if (!empty($lead['passport_no'])): ?><br>Passport: <?= h($lead['passport_no']) ?><?php endif; ?>
          </p>
        </div>
      </div>

      <!-- Package strip -->
      <div class="inv-package">
        <?php if ($booking['hero_image']): ?>
        <img src="<?= h($booking['hero_image']) ?>" alt="<?= h($booking['package_title']) ?>">
        <?php endif; ?>
        <div style="flex:1">
          <h3><?= h($booking['package_title']) ?></h3>
          <p><i class="fas fa-map-marker-alt" style="color:var(--gold)"></i> <?= h($booking['destination_name'] ?? '') ?><?= $booking['country'] ? ', '.h($booking['country']) : '' ?> · <?= $booking['duration_days'] ?> Days</p>
          <div class="inv-travel-dates">
            <span><i class="fas fa-plane-departure"></i> <?= formatDate($booking['travel_date'], 'D, M j Y') ?></span>
            <?php if ($booking['return_date']): ?>
            <span><i class="fas fa-plane-arrival"></i> <?= formatDate($booking['return_date'], 'D, M j Y') ?></span>
            <?php endif; ?>
            <span><i class="fas fa-users"></i> <?= $booking['adults'] ?> Adult<?= $booking['adults']!=1?'s':'' ?><?= $booking['children'] ? ' · '.$booking['children'].' Child'.($booking['children']!=1?'ren':'') : '' ?></span>
          </div>
        </div>
      </div>

      <!-- Line items -->
      <table class="inv-table">
        <thead>
          <tr><th style="width:50%">Description</th><th>Qty</th><th>Unit Price</th><th>Amount</th></tr>
        </thead>
        <tbody>
          <?php
          $basePrice  = (float)($booking['subtotal'] / ($booking['adults'] + $booking['children'] * 0.5));
          ?>
          <tr>
            <td><?= h($booking['package_title']) ?><span class="desc">Adult rate</span></td>
            <td><?= $booking['adults'] ?></td>
            <td><?= money($basePrice) ?></td>
            <td><?= money($basePrice * $booking['adults']) ?></td>
          </tr>
          <?php if ($booking['children']): ?>
          <tr>
            <td>Child (under 12)<span class="desc">50% of adult rate</span></td>
            <td><?= $booking['children'] ?></td>
            <td><?= money($basePrice * 0.5) ?></td>
            <td><?= money($basePrice * 0.5 * $booking['children']) ?></td>
          </tr>
          <?php endif; ?>
          <?php foreach ($addons as $addon): ?>
          <tr>
            <td><?= h($addon['name'] ?? 'Add-on') ?><span class="desc">Optional add-on</span></td>
            <td>1</td>
            <td><?= money($addon['price'] ?? 0) ?></td>
            <td><?= money($addon['price'] ?? 0) ?></td>
          </tr>
          <?php endforeach; ?>

          <tr class="subtotal">
            <td colspan="3">Subtotal</td>
            <td><?= money($booking['subtotal']) ?></td>
          </tr>
          <?php if ($booking['discount_amount'] > 0): ?>
          <tr class="discount">
            <td colspan="3">Discount<?= $booking['coupon_code'] ? ' (Code: '.h($booking['coupon_code']).')' : '' ?></td>
            <td>−<?= money($booking['discount_amount']) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($booking['tax_amount'] > 0): ?>
          <tr class="tax-row">
            <td colspan="3">Tax (<?= TAX_RATE ?>%)</td>
            <td><?= money($booking['tax_amount']) ?></td>
          </tr>
          <?php endif; ?>
          <tr class="total-row">
            <td colspan="3">TOTAL</td>
            <td><?= money($booking['total_amount']) ?></td>
          </tr>
          <tr class="deposit-row">
            <td colspan="3">Deposit Required (<?= BOOKING_DEPOSIT ?>%)</td>
            <td><?= money($booking['total_amount'] * BOOKING_DEPOSIT / 100) ?></td>
          </tr>
          <?php if ($totalPaid > 0): ?>
          <tr class="paid-row">
            <td colspan="3"><i class="fas fa-check-circle"></i> Amount Paid</td>
            <td><?= money($totalPaid) ?></td>
          </tr>
          <tr class="balance-row">
            <td colspan="3"><?= $balanceDue > 0 ? 'Balance Due' : 'Fully Paid' ?></td>
            <td><?= $balanceDue > 0 ? money($balanceDue) : '<i class="fas fa-check"></i> '.money(0) ?></td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Payment history -->
      <?php if ($payments): ?>
      <div class="inv-payments">
        <h4><i class="fas fa-receipt"></i> Payment History</h4>
        <?php foreach ($payments as $p): ?>
        <div class="inv-pay-row">
          <span><?= formatDate($p['paid_at'] ?? $p['created_at'], 'M j, Y') ?> · <?= ucfirst(str_replace('_',' ',$p['method'])) ?> · <?= ucfirst($p['type']) ?></span>
          <span style="font-weight:700;color:<?= $p['status']==='completed'?'#276749':'#718096' ?>"><?= money($p['amount']) ?> <?= ucfirst($p['status']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Notes -->
      <?php if ($booking['special_requests']): ?>
      <div class="inv-note">
        <strong><i class="fas fa-sticky-note" style="color:var(--gold)"></i> Special Requests:</strong><br>
        <?= h($booking['special_requests']) ?>
      </div>
      <?php endif; ?>

      <!-- Terms note -->
      <div class="inv-note" style="font-size:.78rem">
        <strong>Payment Terms:</strong> A <?= BOOKING_DEPOSIT ?>% deposit is required within 48 hours of booking to secure your reservation. The remaining balance is due 30 days before departure. Free cancellation within 48 hours of booking. For full terms, visit our website.
      </div>
    </div>

    <!-- Footer -->
    <div class="inv-footer">
      <div>
        <strong>MT Safaris Ltd</strong> · Nairobi, Kenya · info@mtsafaris.com
      </div>
      <div>
        Booking Ref: <strong><?= h($booking['reference']) ?></strong>
        &nbsp;|&nbsp; Generated <?= date('M j, Y H:i') ?>
      </div>
    </div>

  </div>
</div>
</body>
</html>
