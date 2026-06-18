<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) redirect(url('admin/bookings.php'));

$booking = DB::row(
    "SELECT b.*, p.title AS package_title, p.slug AS package_slug, p.hero_image, p.duration_days,
            u.first_name, u.last_name, u.email AS user_email, u.phone AS user_phone
     FROM bookings b
     LEFT JOIN packages p ON b.package_id = p.id
     LEFT JOIN users u ON b.user_id = u.id
     WHERE b.id = ?",
    [$id]
);
if (!$booking) redirect(url('admin/bookings.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    // Status + notes update
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'] ?? '';
        $allowed   = ['pending','confirmed','paid','cancelled','completed','refunded'];
        if (in_array($newStatus, $allowed)) {
            $upd = ['status' => $newStatus];
            if ($newStatus === 'confirmed') $upd['confirmed_at'] = date('Y-m-d H:i:s');
            if ($newStatus === 'completed') $upd['completed_at'] = date('Y-m-d H:i:s');
            if ($newStatus === 'cancelled') $upd['cancelled_at'] = date('Y-m-d H:i:s');
            if (isset($_POST['admin_notes'])) $upd['notes'] = trim($_POST['admin_notes']);
            DB::update('bookings', $upd, ['id' => $id]);
            auditLog('update_status', 'bookings', $id, ['status' => $booking['status']], $upd);
            flash('success', 'Status updated to ' . ucfirst($newStatus) . '.');
        }
        redirect(url('admin/booking-view.php?id=' . $id));
    }
    // Record payment
    if (isset($_POST['record_payment'])) {
        $amount = (float)($_POST['pay_amount'] ?? 0);
        if ($amount > 0) {
            $payRef = 'PAY-' . strtoupper(substr(uniqid(), -6));
            DB::insert('booking_payments', [
                'booking_id'  => $id,
                'reference'   => $payRef,
                'method'      => $_POST['pay_method'] ?? 'other',
                'type'        => $_POST['pay_type']   ?? 'deposit',
                'amount'      => $amount,
                'currency'    => $booking['currency'] ?? 'USD',
                'status'      => 'completed',
                'gateway_ref' => trim($_POST['gateway_ref'] ?? ''),
                'notes'       => trim($_POST['pay_notes'] ?? ''),
                'paid_at'     => date('Y-m-d H:i:s'),
            ]);
            // Auto-upgrade to paid when fully paid
            $totalPaidNow = (float)DB::value(
                "SELECT COALESCE(SUM(amount),0) FROM booking_payments WHERE booking_id=? AND status='completed'",
                [$id]
            );
            if ($totalPaidNow >= $booking['total_amount'] && $booking['status'] === 'confirmed') {
                DB::update('bookings', ['status' => 'paid'], ['id' => $id]);
            }
            auditLog('record_payment', 'bookings', $id, [], ['amount' => $amount, 'method' => $_POST['pay_method'] ?? '']);
            flash('success', 'Payment of ' . money($amount) . ' recorded.');
        }
        redirect(url('admin/booking-view.php?id=' . $id));
    }
    // Delete payment
    if (isset($_POST['delete_payment'])) {
        $pid = (int)($_POST['payment_id'] ?? 0);
        if ($pid) DB::delete('booking_payments', ['id' => $pid, 'booking_id' => $id]);
        flash('success', 'Payment record removed.');
        redirect(url('admin/booking-view.php?id=' . $id));
    }
}

$leadTraveler = jd($booking['lead_traveler'] ?? '{}', []);
$addons       = jd($booking['addons'] ?? '[]', []);
$payments     = DB::rows("SELECT * FROM booking_payments WHERE booking_id=? ORDER BY paid_at DESC", [$id]);
$totalPaid    = array_sum(array_column(array_filter($payments, fn($p) => $p['status'] === 'completed'), 'amount'));
$total        = (float)$booking['total_amount'];
$balance      = max(0, $total - $totalPaid);
$pctPaid      = $total > 0 ? min(100, round($totalPaid / $total * 100)) : 0;
$deposit      = defined('BOOKING_DEPOSIT') ? $total * BOOKING_DEPOSIT / 100 : 0;

// Status badge helper
function bkBadge(string $s): string {
    $map = [
        'pending'   => ['Pending',   '#fef3c7','#92400e'],
        'confirmed' => ['Confirmed', '#dbeafe','#1e40af'],
        'paid'      => ['Paid',      '#d1fae5','#065f46'],
        'completed' => ['Completed', '#ede9fe','#5b21b6'],
        'cancelled' => ['Cancelled', '#fee2e2','#991b1b'],
        'refunded'  => ['Refunded',  '#f3f4f6','#374151'],
    ];
    [$label, $bg, $clr] = $map[$s] ?? [ucfirst($s), '#f3f4f6', '#374151'];
    return "<span class=\"bk-status-badge\" style=\"background:{$bg};color:{$clr}\">{$label}</span>";
}

// Print invoice mode — minimal layout for print
if (!empty($_GET['print'])) {
    $subtotal = (float)($booking['subtotal'] ?? $total);
    $discount = (float)($booking['discount'] ?? 0);
    $tax      = (float)($booking['tax_amount'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice <?= h($booking['reference']) ?> — MT Safaris</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;color:#1a1a2e;font-size:13px;padding:40px}
    .inv-header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:3px solid #1a1a2e}
    .company-name{font-size:22px;font-weight:800;color:#1a1a2e}
    .company-sub{font-size:11px;color:#6b7280;margin-top:2px}
    .inv-label{font-size:11px;color:#6b7280;text-transform:uppercase;letter-spacing:.07em}
    .inv-ref{font-size:20px;font-weight:800;color:#1a1a2e}
    .inv-date{font-size:11px;color:#6b7280;margin-top:4px}
    .section{margin-bottom:24px}
    .section-title{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#6b7280;margin-bottom:10px;padding-bottom:6px;border-bottom:1px solid #e5e7eb}
    .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .field-label{font-size:10px;color:#9ca3af;text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px}
    .field-val{font-size:13px;font-weight:600;color:#1a1a2e}
    table{width:100%;border-collapse:collapse}
    th{text-align:left;padding:8px 12px;background:#f9fafb;font-size:10px;text-transform:uppercase;letter-spacing:.07em;color:#6b7280;border:1px solid #e5e7eb}
    td{padding:9px 12px;border:1px solid #e5e7eb;font-size:12px}
    .total-row td{font-weight:700;background:#f9fafb;font-size:14px}
    .payments-table td{font-size:11px}
    .badge{display:inline-block;padding:2px 10px;border-radius:20px;font-size:10px;font-weight:700}
    .footer{margin-top:40px;padding-top:16px;border-top:1px solid #e5e7eb;text-align:center;color:#9ca3af;font-size:10px}
    @media print{body{padding:24px}button{display:none}}
  </style>
</head>
<body>
  <div class="inv-header">
    <div>
      <div class="company-name">MT Safaris</div>
      <div class="company-sub">Premium African Safari Experiences</div>
    </div>
    <div style="text-align:right">
      <div class="inv-label">Booking Invoice</div>
      <div class="inv-ref"><?= h($booking['reference']) ?></div>
      <div class="inv-date">Issued: <?= formatDate($booking['created_at'], 'M j, Y') ?></div>
    </div>
  </div>

  <div class="info-grid" style="margin-bottom:24px">
    <div class="section">
      <div class="section-title">Customer</div>
      <div class="field-label">Name</div>
      <div class="field-val"><?= h(($booking['first_name']??$leadTraveler['first_name']??'').' '.($booking['last_name']??$leadTraveler['last_name']??'')) ?></div>
      <div class="field-label" style="margin-top:8px">Email</div>
      <div class="field-val"><?= h($booking['user_email'] ?? $leadTraveler['email'] ?? '—') ?></div>
      <div class="field-label" style="margin-top:8px">Phone</div>
      <div class="field-val"><?= h($booking['user_phone'] ?? $leadTraveler['phone'] ?? '—') ?></div>
    </div>
    <div class="section">
      <div class="section-title">Trip Details</div>
      <div class="field-label">Package</div>
      <div class="field-val"><?= h($booking['package_title'] ?? 'N/A') ?></div>
      <div class="field-label" style="margin-top:8px">Travel Date</div>
      <div class="field-val"><?= formatDate($booking['travel_date'], 'M j, Y') ?></div>
      <div class="field-label" style="margin-top:8px">Travelers</div>
      <div class="field-val"><?= (int)$booking['adults'] ?> Adult<?= $booking['children']?' + '.(int)$booking['children'].' Child':'' ?></div>
    </div>
  </div>

  <div class="section">
    <div class="section-title">Price Breakdown</div>
    <table>
      <thead><tr><th>Item</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <tr><td>Package Subtotal</td><td style="text-align:right"><?= money($subtotal) ?></td></tr>
        <?php if ($discount > 0): ?><tr><td style="color:#059669">Discount<?= $booking['coupon_code']?' ('.h($booking['coupon_code']).')':'' ?></td><td style="text-align:right;color:#059669">−<?= money($discount) ?></td></tr><?php endif; ?>
        <?php if ($tax > 0): ?><tr><td>Tax (<?= defined('TAX_RATE')?TAX_RATE:0 ?>%)</td><td style="text-align:right"><?= money($tax) ?></td></tr><?php endif; ?>
        <?php foreach ($addons as $ao): ?><tr><td>↳ Add-on: <?= h($ao['name']??'') ?></td><td style="text-align:right"><?= money($ao['price']??0) ?></td></tr><?php endforeach; ?>
        <tr class="total-row"><td>Total</td><td style="text-align:right"><?= money($total) ?></td></tr>
        <?php if ($deposit > 0): ?>
        <tr><td style="color:#6b7280;font-size:11px">Deposit Required (<?= defined('BOOKING_DEPOSIT')?BOOKING_DEPOSIT:0 ?>%)</td><td style="text-align:right;font-size:11px;color:#6b7280"><?= money($deposit) ?></td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($payments): ?>
  <div class="section">
    <div class="section-title">Payment History</div>
    <table class="payments-table">
      <thead><tr><th>Date</th><th>Type</th><th>Method</th><th>Reference</th><th style="text-align:right">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= formatDate($p['paid_at']??$p['created_at'],'M j, Y') ?></td>
          <td style="text-transform:capitalize"><?= h($p['type']) ?></td>
          <td><?= h(str_replace('_',' ',$p['method'])) ?></td>
          <td style="font-family:monospace"><?= h($p['gateway_ref']??$p['reference']) ?></td>
          <td style="text-align:right;font-weight:700;color:#059669"><?= money($p['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight:700;background:#f9fafb"><td colspan="4">Total Paid</td><td style="text-align:right;color:#059669"><?= money($totalPaid) ?></td></tr>
        <?php if ($balance > 0): ?><tr style="font-weight:700"><td colspan="4">Balance Due</td><td style="text-align:right;color:#991b1b"><?= money($balance) ?></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>

  <div class="footer">
    Thank you for choosing MT Safaris &bull; <?= defined('CONTACT_EMAIL')?CONTACT_EMAIL:'' ?> &bull; <?= defined('APP_URL')?APP_URL:'' ?>
  </div>

  <div style="text-align:center;margin-top:24px">
    <button onclick="window.print()" style="padding:8px 20px;background:#1a1a2e;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:13px">Print Invoice</button>
    <button onclick="window.close()" style="padding:8px 20px;background:#f3f4f6;color:#374151;border:none;border-radius:6px;cursor:pointer;font-size:13px;margin-left:8px">Close</button>
  </div>
</body>
</html>
<?php
    exit;
}
// End print mode
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Booking <?= h($booking['reference']) ?> — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <style>
    .bk-status-badge { display:inline-flex;align-items:center;padding:5px 14px;border-radius:20px;font-size:.78rem;font-weight:700;letter-spacing:.03em }
    .detail-row { display:grid;grid-template-columns:140px 1fr;gap:6px;padding:10px 0;border-bottom:1px solid var(--clr-border);align-items:start }
    .detail-row:last-child { border-bottom:none }
    .detail-label { font-size:.72rem;font-weight:600;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.05em;padding-top:2px }
    .detail-value { font-size:.875rem;font-weight:500;color:var(--clr-text) }
    .stat-mini { text-align:center;padding:14px;background:var(--clr-light);border-radius:var(--radius-sm) }
    .stat-mini-icon { font-size:1rem;margin-bottom:4px;display:block }
    .stat-mini-label { font-size:.65rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.05em }
    .stat-mini-val { font-size:.9rem;font-weight:700;color:var(--clr-primary);margin-top:2px }
    .timeline-item { display:flex;gap:12px;padding:10px 0;border-bottom:1px solid var(--clr-border) }
    .timeline-item:last-child { border-bottom:none }
    .timeline-dot { width:28px;height:28px;border-radius:50%;display:grid;place-items:center;flex-shrink:0;margin-top:1px }
    .pay-progress { height:6px;background:var(--clr-border);border-radius:3px;overflow:hidden;margin:8px 0 }
    .pay-progress-fill { height:100%;border-radius:3px;transition:width .4s }
    .section-card { background:#fff;border:1px solid var(--clr-border);border-radius:var(--radius-md);margin-bottom:20px;overflow:hidden }
    .section-card-header { display:flex;justify-content:space-between;align-items:center;padding:14px 20px;border-bottom:1px solid var(--clr-border);background:var(--clr-light) }
    .section-card-title { font-size:.85rem;font-weight:700;color:var(--clr-primary) }
    .section-card-body { padding:20px }
  </style>
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">
        Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <a href="<?= url('admin/bookings.php') ?>" style="color:var(--clr-muted);text-decoration:none">Bookings</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <span><?= h($booking['reference']) ?></span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/booking-view.php?id='.$id.'&print=1') ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-print"></i> Print Invoice</a>
      <a href="<?= url('admin/bookings.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:20px">
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Save Bar -->
    <div class="save-bar">
      <div style="display:flex;align-items:center;gap:14px">
        <?= bkBadge($booking['status']) ?>
        <span style="font-size:.78rem;color:var(--clr-muted)">Booked <?= formatDate($booking['created_at'], 'M j, Y \a\t g:ia') ?></span>
      </div>
      <div style="display:flex;gap:8px">
        <a href="<?= url('admin/booking-view.php?id='.$id.'&print=1') ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-file-invoice"></i> Invoice</a>
        <?php if ($booking['package_slug']): ?>
        <a href="<?= url('package-detail.php?slug='.h($booking['package_slug'])) ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-external-link-alt"></i> View Package</a>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

      <!-- ── LEFT COLUMN ── -->
      <div>

        <!-- Package Info -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title"><i class="fas fa-suitcase" style="color:var(--clr-gold);margin-right:6px"></i>Package</span>
          </div>
          <div class="section-card-body">
            <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:20px">
              <?php if ($booking['hero_image']): ?>
              <img src="<?= h($booking['hero_image']) ?>" style="width:110px;height:72px;object-fit:cover;border-radius:var(--radius-sm);flex-shrink:0" alt="">
              <?php endif; ?>
              <div>
                <div style="font-size:1.05rem;font-weight:700;color:var(--clr-primary);margin-bottom:4px"><?= h($booking['package_title'] ?? 'Package Deleted') ?></div>
                <div style="font-size:.8rem;color:var(--clr-muted)"><?= (int)($booking['duration_days']??0) ?> day<?= ($booking['duration_days']??0)!=1?'s':'' ?> trip</div>
              </div>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
              <?php foreach ([
                ['fas fa-calendar',     'Travel Date',  formatDate($booking['travel_date'],'M j, Y')],
                ['fas fa-users',        'Travelers',    (int)$booking['adults'].'A'.($booking['children']?' + '.(int)$booking['children'].'C':'')],
                ['fas fa-clock',        'Duration',     (int)($booking['duration_days']??0).' Days'],
                ['fas fa-credit-card',  'Payment',      ucfirst(str_replace('_',' ',$booking['payment_method']??'—'))],
              ] as [$icon,$label,$value]): ?>
              <div class="stat-mini">
                <i class="<?= $icon ?> stat-mini-icon" style="color:var(--clr-gold)"></i>
                <div class="stat-mini-label"><?= $label ?></div>
                <div class="stat-mini-val"><?= h($value) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Lead Traveler -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title"><i class="fas fa-user" style="color:var(--clr-gold);margin-right:6px"></i>Lead Traveler</span>
          </div>
          <div class="section-card-body" style="padding:12px 20px">
            <?php
            $travelerFields = [
              ['Name',        trim(($leadTraveler['first_name']??'').' '.($leadTraveler['last_name']??'')) ?: ($booking['first_name']??'').' '.($booking['last_name']??'')],
              ['Email',       $leadTraveler['email']  ?? $booking['user_email']  ?? '—'],
              ['Phone',       $leadTraveler['phone']  ?? $booking['user_phone']  ?? '—'],
              ['Nationality', $leadTraveler['nationality'] ?? '—'],
              ['Passport #',  $leadTraveler['passport'] ?? '—'],
            ];
            foreach ($travelerFields as [$label, $value]):
            ?>
            <div class="detail-row">
              <div class="detail-label"><?= $label ?></div>
              <div class="detail-value"><?= h($value) ?></div>
            </div>
            <?php endforeach; ?>
            <?php if (!empty($booking['special_requests'])): ?>
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--clr-border)">
              <div class="detail-label" style="margin-bottom:6px">Special Requests</div>
              <p style="color:var(--clr-text);font-size:.875rem;line-height:1.7;margin:0"><?= nl2br(h($booking['special_requests'])) ?></p>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Price Breakdown -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title"><i class="fas fa-receipt" style="color:var(--clr-gold);margin-right:6px"></i>Price Breakdown</span>
            <span style="font-size:.8rem;font-weight:700;color:<?= $totalPaid>=$total?'var(--clr-success)':'var(--clr-muted)' ?>"><?= $pctPaid ?>% paid</span>
          </div>
          <div class="section-card-body" style="padding:8px 20px">
            <?php
            $subtotal = (float)($booking['subtotal'] ?? $total);
            $discount = (float)($booking['discount'] ?? 0);
            $tax      = (float)($booking['tax_amount'] ?? 0);
            ?>
            <div class="detail-row">
              <div class="detail-label">Subtotal</div>
              <div class="detail-value"><?= money($subtotal) ?></div>
            </div>
            <?php if ($discount > 0): ?>
            <div class="detail-row">
              <div class="detail-label" style="color:var(--clr-success)">Discount<?= $booking['coupon_code']?' ('.h($booking['coupon_code']).')':'' ?></div>
              <div class="detail-value" style="color:var(--clr-success)">−<?= money($discount) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($tax > 0): ?>
            <div class="detail-row">
              <div class="detail-label">Tax (<?= defined('TAX_RATE')?TAX_RATE:0 ?>%)</div>
              <div class="detail-value"><?= money($tax) ?></div>
            </div>
            <?php endif; ?>
            <?php foreach ($addons as $ao): ?>
            <div class="detail-row">
              <div class="detail-label" style="color:var(--clr-muted)">↳ <?= h($ao['name']??'Add-on') ?></div>
              <div class="detail-value"><?= money($ao['price']??0) ?></div>
            </div>
            <?php endforeach; ?>
            <div class="detail-row" style="border-top:2px solid var(--clr-primary);padding-top:12px">
              <div class="detail-label" style="font-size:.85rem;font-weight:800;color:var(--clr-primary)">Total</div>
              <div class="detail-value" style="font-size:1.15rem;font-weight:800;color:var(--clr-primary)"><?= money($total) ?></div>
            </div>
            <?php if ($deposit > 0): ?>
            <div class="detail-row">
              <div class="detail-label">Deposit (<?= defined('BOOKING_DEPOSIT')?BOOKING_DEPOSIT:0 ?>%)</div>
              <div class="detail-value"><?= money($deposit) ?></div>
            </div>
            <div class="detail-row">
              <div class="detail-label">Balance Due</div>
              <div class="detail-value" style="color:<?= $balance>0?'var(--clr-danger)':'var(--clr-success)' ?>;font-weight:700"><?= money($balance) ?></div>
            </div>
            <?php endif; ?>
            <!-- Payment progress -->
            <div style="margin-top:12px">
              <div style="display:flex;justify-content:space-between;font-size:.72rem;color:var(--clr-muted);margin-bottom:4px">
                <span>Paid: <?= money($totalPaid) ?></span>
                <span><?= $pctPaid ?>%</span>
              </div>
              <div class="pay-progress">
                <div class="pay-progress-fill" style="width:<?= $pctPaid ?>%;background:<?= $pctPaid>=100?'var(--clr-success)':'var(--clr-gold)' ?>"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Admin Notes -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title"><i class="fas fa-sticky-note" style="color:var(--clr-gold);margin-right:6px"></i>Admin Notes</span>
          </div>
          <div class="section-card-body">
            <form method="POST">
              <?= csrfField() ?>
              <input type="hidden" name="status" value="<?= h($booking['status']) ?>">
              <textarea name="admin_notes" class="form-control" rows="4" placeholder="Internal notes visible only to admins…"><?= h($booking['notes'] ?? '') ?></textarea>
              <button type="submit" name="update_status" class="btn-admin btn-admin-primary btn-admin-sm" style="margin-top:10px">
                <i class="fas fa-save"></i> Save Notes
              </button>
            </form>
          </div>
        </div>

      </div><!-- /left -->

      <!-- ── RIGHT SIDEBAR ── -->
      <div>

        <!-- Status Update -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title">Booking Status</span>
            <?= bkBadge($booking['status']) ?>
          </div>
          <div class="section-card-body">
            <form method="POST">
              <?= csrfField() ?>
              <div class="form-group">
                <label class="form-label">Update Status</label>
                <select name="status" class="form-control">
                  <?php foreach (['pending','confirmed','paid','cancelled','completed','refunded'] as $s): ?>
                  <option value="<?= $s ?>" <?= $booking['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" name="update_status" class="btn-admin btn-admin-primary btn-block">
                <i class="fas fa-check"></i> Update Status
              </button>
            </form>
          </div>
        </div>

        <!-- Timeline -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title">Timeline</span>
          </div>
          <div class="section-card-body" style="padding:12px 20px">
            <?php
            $events = [
              ['Booking Created',  $booking['created_at'],   'fas fa-plus-circle', '#1e40af', '#dbeafe'],
              ['Confirmed',        $booking['confirmed_at'], 'fas fa-check-circle','#065f46', '#d1fae5'],
              ['Completed',        $booking['completed_at'], 'fas fa-flag',        '#5b21b6', '#ede9fe'],
              ['Cancelled',        $booking['cancelled_at'], 'fas fa-times-circle','#991b1b', '#fee2e2'],
            ];
            $hasEvent = false;
            foreach ($events as $ev): if (!$ev[1]) continue; $hasEvent = true; ?>
            <div class="timeline-item">
              <div class="timeline-dot" style="background:<?= $ev[4] ?>"><i class="<?= $ev[2] ?>" style="color:<?= $ev[3] ?>;font-size:.8rem"></i></div>
              <div>
                <div style="font-size:.8rem;font-weight:600;color:var(--clr-primary)"><?= $ev[0] ?></div>
                <div style="font-size:.72rem;color:var(--clr-muted)"><?= formatDate($ev[1],'M j, Y \a\t g:ia') ?></div>
              </div>
            </div>
            <?php endforeach;
            if (!$hasEvent): ?>
            <p style="font-size:.8rem;color:var(--clr-muted);text-align:center;padding:8px 0">No events yet.</p>
            <?php endif; ?>
          </div>
        </div>

        <!-- Customer Card -->
        <?php if ($booking['user_email'] || $booking['user_phone']): ?>
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title">Customer</span>
          </div>
          <div class="section-card-body">
            <div style="text-align:center;margin-bottom:14px">
              <div style="width:52px;height:52px;border-radius:50%;background:var(--clr-primary);color:#fff;display:grid;place-items:center;font-size:1.3rem;font-weight:700;margin:0 auto 10px">
                <?= strtoupper(substr($booking['first_name'] ?? ($leadTraveler['first_name'] ?? 'G'), 0, 1)) ?>
              </div>
              <div style="font-weight:700;font-size:.9rem;color:var(--clr-primary)"><?= h(trim(($booking['first_name']??'').' '.($booking['last_name']??'')) ?: 'Guest') ?></div>
              <?php if ($booking['user_email']): ?><div style="font-size:.78rem;color:var(--clr-muted)"><?= h($booking['user_email']) ?></div><?php endif; ?>
            </div>
            <?php if ($booking['user_email']): ?>
            <a href="mailto:<?= h($booking['user_email']) ?>" class="btn-admin btn-admin-secondary btn-block" style="margin-bottom:6px">
              <i class="fas fa-envelope"></i> Send Email
            </a>
            <?php endif; ?>
            <?php if ($booking['user_phone']): ?>
            <a href="tel:<?= h($booking['user_phone']) ?>" class="btn-admin btn-admin-secondary btn-block">
              <i class="fas fa-phone"></i> <?= h($booking['user_phone']) ?>
            </a>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Payments -->
        <div class="section-card">
          <div class="section-card-header">
            <span class="section-card-title"><i class="fas fa-money-bill-wave" style="color:var(--clr-gold);margin-right:6px"></i>Payments</span>
            <span style="font-size:.78rem;font-weight:700;color:<?= $totalPaid>=$total?'var(--clr-success)':'var(--clr-muted)' ?>">
              <?= money($totalPaid) ?> / <?= money($total) ?>
            </span>
          </div>

          <!-- Payment list -->
          <?php if ($payments): ?>
          <div style="padding:0">
            <table class="admin-table" style="font-size:.78rem">
              <thead>
                <tr><th>Type</th><th>Method</th><th>Amt</th><th></th></tr>
              </thead>
              <tbody>
                <?php foreach ($payments as $p): ?>
                <tr>
                  <td>
                    <div style="font-weight:600;text-transform:capitalize"><?= h($p['type']) ?></div>
                    <div style="color:var(--clr-muted);font-size:.68rem"><?= formatDate($p['paid_at']??$p['created_at'],'M j') ?></div>
                  </td>
                  <td style="text-transform:capitalize"><?= h(str_replace('_',' ',$p['method'])) ?></td>
                  <td style="font-weight:700;color:<?= $p['status']==='completed'?'var(--clr-success)':'var(--clr-muted)' ?>">
                    <?= money($p['amount']) ?>
                  </td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Remove this payment record?')">
                      <?= csrfField() ?>
                      <input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                      <button type="submit" name="delete_payment" class="btn-tbl btn-tbl-danger" title="Remove"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php else: ?>
          <div style="padding:16px 20px;text-align:center;font-size:.8rem;color:var(--clr-muted)">No payments recorded yet.</div>
          <?php endif; ?>

          <!-- Record new payment -->
          <div style="padding:16px;border-top:1px solid var(--clr-border)">
            <details <?= !$payments?'open':'' ?>>
              <summary style="cursor:pointer;font-size:.82rem;font-weight:700;color:var(--clr-primary);list-style:none;display:flex;align-items:center;gap:6px;margin-bottom:0;user-select:none">
                <i class="fas fa-plus-circle" style="color:var(--clr-gold)"></i> Record Payment
              </summary>
              <form method="POST" style="display:flex;flex-direction:column;gap:10px;margin-top:14px">
                <?= csrfField() ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:.7rem">Amount (<?= $booking['currency']??'USD' ?>)</label>
                    <input type="number" name="pay_amount" class="form-control" min="0.01" step="0.01" placeholder="0.00" style="font-size:.82rem" required>
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label" style="font-size:.7rem">Type</label>
                    <select name="pay_type" class="form-control" style="font-size:.82rem">
                      <option value="deposit">Deposit</option>
                      <option value="balance">Balance</option>
                      <option value="full">Full Payment</option>
                      <option value="refund">Refund</option>
                    </select>
                  </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label" style="font-size:.7rem">Payment Method</label>
                  <select name="pay_method" class="form-control" style="font-size:.82rem">
                    <option value="mobile_money">M-Pesa / Mobile Money</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="stripe">Stripe / Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="cash">Cash</option>
                    <option value="other">Other</option>
                  </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label" style="font-size:.7rem">Transaction / Reference ID</label>
                  <input type="text" name="gateway_ref" class="form-control" placeholder="e.g. QKA8XYZBP2" style="font-size:.82rem">
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label" style="font-size:.7rem">Notes (optional)</label>
                  <input type="text" name="pay_notes" class="form-control" placeholder="Any notes…" style="font-size:.82rem">
                </div>
                <button type="submit" name="record_payment" class="btn-admin btn-admin-primary btn-block">
                  <i class="fas fa-check"></i> Record Payment
                </button>
              </form>
            </details>
          </div>
        </div>

      </div><!-- /sidebar -->
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
