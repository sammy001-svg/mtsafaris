<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(url('admin/bookings.php'));

$booking = DB::row("SELECT b.*, p.title AS package_title, p.slug AS package_slug, p.hero_image, p.duration_days,
                           u.first_name, u.last_name, u.email AS user_email, u.phone AS user_phone
                    FROM bookings b
                    LEFT JOIN packages p ON b.package_id=p.id
                    LEFT JOIN users u ON b.user_id=u.id
                    WHERE b.id=?", [$id]);
if (!$booking) redirect(url('admin/bookings.php'));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    // Status update
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['status'] ?? '';
        $allowed   = ['pending','confirmed','paid','cancelled','completed','refunded'];
        if (in_array($newStatus, $allowed)) {
            $update = ['status' => $newStatus];
            if ($newStatus === 'confirmed')  $update['confirmed_at']  = date('Y-m-d H:i:s');
            if ($newStatus === 'completed')  $update['completed_at']  = date('Y-m-d H:i:s');
            if ($newStatus === 'cancelled')  $update['cancelled_at']  = date('Y-m-d H:i:s');
            if (!empty($_POST['admin_notes'])) $update['notes'] = $_POST['admin_notes'];
            DB::update('bookings', $update, ['id' => $id]);
            auditLog('update_status', 'bookings', $id, ['status'=>$booking['status']], $update);
            flash('success', 'Status updated to ' . ucfirst($newStatus));
            redirect(url('admin/booking-view.php?id='.$id));
        }
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
            // Auto-upgrade status if fully paid
            $paid = DB::value("SELECT COALESCE(SUM(amount),0) FROM booking_payments WHERE booking_id=? AND status='completed'", [$id]);
            if ($paid >= $booking['total_amount'] && $booking['status'] === 'confirmed') {
                DB::update('bookings', ['status' => 'paid'], ['id' => $id]);
            }
            auditLog('record_payment', 'bookings', $id, [], ['amount'=>$amount,'method'=>$_POST['pay_method']??'']);
            flash('success', 'Payment of ' . money($amount) . ' recorded.');
            redirect(url('admin/booking-view.php?id='.$id));
        }
    }
    // Delete payment
    if (isset($_POST['delete_payment'])) {
        $pid = (int)($_POST['payment_id'] ?? 0);
        if ($pid) { DB::delete('booking_payments', ['id'=>$pid, 'booking_id'=>$id]); }
        flash('success', 'Payment record removed.');
        redirect(url('admin/booking-view.php?id='.$id));
    }
}

$leadTraveler = jd($booking['lead_traveler'] ?? '{}', []);
$addons       = jd($booking['addons'] ?? '[]', []);
$payments     = DB::rows("SELECT * FROM booking_payments WHERE booking_id=? ORDER BY paid_at DESC", [$id]);
$totalPaid    = array_sum(array_column(array_filter($payments, fn($p)=>$p['status']==='completed'), 'amount'));
$pageTitle    = 'Booking #' . h($booking['reference']) . ' | MT Safaris Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-wrapper">
<header class="admin-header">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <div class="admin-header-title">Booking: <?= h($booking['reference']) ?></div>
  <div class="admin-header-actions">
    <a href="<?= url('admin/bookings.php') ?>" class="btn btn-admin-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</header>
<main class="admin-main">
<?php echo renderFlash(); ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
  <!-- Main -->
  <div>
    <!-- Package Info -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Package</h3></div>
      <div class="admin-card-body">
        <div style="display:flex;gap:16px;align-items:flex-start">
          <?php if ($booking['hero_image']): ?><img src="<?= h($booking['hero_image']) ?>" style="width:120px;height:80px;object-fit:cover;border-radius:8px;flex-shrink:0" alt=""><?php endif; ?>
          <div>
            <h4 style="color:var(--admin-text);margin-bottom:6px"><?= h($booking['package_title']??'Package Deleted') ?></h4>
            <?php if ($booking['package_slug']): ?><a href="<?= url('package-detail.php?slug='.h($booking['package_slug'])) ?>" target="_blank" class="btn btn-admin-outline btn-xs"><i class="fas fa-external-link-alt"></i> View Package</a><?php endif; ?>
          </div>
        </div>
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-top:20px">
          <?php foreach ([
            ['Travel Date', formatDate($booking['travel_date'],'M j, Y'), 'fas fa-calendar'],
            ['Travelers', $booking['adults'].' Adults'.($booking['children']?' + '.$booking['children'].' Child':''), 'fas fa-users'],
            ['Duration', ($booking['duration_days']??'?').' Days', 'fas fa-clock'],
            ['Payment', ucfirst($booking['payment_method']??'—'), 'fas fa-credit-card'],
          ] as $stat): ?>
          <div style="background:#f8fafc;border-radius:8px;padding:14px;text-align:center">
            <i class="<?= $stat[2] ?>" style="color:var(--admin-primary);margin-bottom:6px"></i>
            <div style="font-size:.7rem;color:var(--admin-muted);margin-bottom:2px"><?= $stat[0] ?></div>
            <div style="font-size:.875rem;font-weight:600;color:var(--admin-text)"><?= h($stat[1]) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Lead Traveler -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Lead Traveler</h3></div>
      <div class="admin-card-body">
        <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:16px">
          <?php foreach ([
            ['Name', ($leadTraveler['first_name']??'').' '.($leadTraveler['last_name']??'')],
            ['Email', $leadTraveler['email']??$booking['user_email']??'—'],
            ['Phone', $leadTraveler['phone']??$booking['user_phone']??'—'],
            ['Nationality', $leadTraveler['nationality']??'—'],
            ['Passport', $leadTraveler['passport']??'—'],
          ] as $field): ?>
          <div>
            <div style="font-size:.75rem;color:var(--admin-muted);margin-bottom:3px"><?= $field[0] ?></div>
            <div style="font-size:.875rem;font-weight:500;color:var(--admin-text)"><?= h($field[1]) ?></div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php if ($booking['special_requests']): ?>
        <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--admin-border)">
          <div style="font-size:.75rem;color:var(--admin-muted);margin-bottom:6px">Special Requests</div>
          <p style="color:var(--admin-text);font-size:.875rem;line-height:1.6;margin:0"><?= nl2br(h($booking['special_requests'])) ?></p>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Price Breakdown -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Price Breakdown</h3></div>
      <div class="admin-card-body">
        <table style="width:100%;border-collapse:collapse">
          <?php
          $subtotal = (float)($booking['subtotal'] ?? $booking['total_amount']);
          $discount = (float)($booking['discount'] ?? 0);
          $tax      = (float)($booking['tax_amount'] ?? 0);
          $total    = (float)$booking['total_amount'];
          ?>
          <tr><td style="padding:8px 0;color:var(--admin-muted)">Subtotal</td><td style="text-align:right;font-weight:500"><?= money($subtotal) ?></td></tr>
          <?php if ($discount > 0): ?><tr><td style="padding:8px 0;color:#059669">Discount (<?= h($booking['coupon_code']??'') ?>)</td><td style="text-align:right;color:#059669">-<?= money($discount) ?></td></tr><?php endif; ?>
          <?php if ($tax > 0): ?><tr><td style="padding:8px 0;color:var(--admin-muted)">Tax (<?= TAX_RATE ?>%)</td><td style="text-align:right"><?= money($tax) ?></td></tr><?php endif; ?>
          <?php if ($addons): ?>
          <?php foreach ($addons as $ao): ?><tr><td style="padding:6px 0;color:var(--admin-muted);font-size:.85rem">↳ Add-on: <?= h($ao['name']??'') ?></td><td style="text-align:right;font-size:.85rem"><?= money($ao['price']??0) ?></td></tr><?php endforeach; ?>
          <?php endif; ?>
          <tr style="border-top:2px solid var(--admin-border)"><td style="padding:12px 0;font-weight:700;color:var(--admin-text)">Total</td><td style="text-align:right;font-weight:700;font-size:1.1rem;color:var(--admin-primary)"><?= money($total) ?></td></tr>
          <tr><td style="padding:6px 0;color:var(--admin-muted);font-size:.85rem">Deposit Paid (<?= BOOKING_DEPOSIT ?>%)</td><td style="text-align:right;font-size:.85rem"><?= money($total * BOOKING_DEPOSIT / 100) ?></td></tr>
          <tr><td style="padding:6px 0;color:var(--admin-muted);font-size:.85rem">Balance Due</td><td style="text-align:right;font-size:.85rem"><?= money($total - $total * BOOKING_DEPOSIT / 100) ?></td></tr>
        </table>
      </div>
    </div>

    <!-- Admin Notes -->
    <div class="admin-card">
      <div class="admin-card-header"><h3>Admin Notes</h3></div>
      <div class="admin-card-body">
        <form method="POST">
          <?= csrfField() ?>
          <input type="hidden" name="status" value="<?= h($booking['status']) ?>">
          <textarea name="admin_notes" class="admin-input" rows="4" placeholder="Internal notes about this booking..."><?= h($booking['admin_notes']??'') ?></textarea>
          <button type="submit" name="update_status" class="btn btn-admin-primary btn-sm" style="margin-top:10px"><i class="fas fa-save"></i> Save Notes</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div>
    <!-- Status -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Booking Status</h3></div>
      <div class="admin-card-body">
        <div style="text-align:center;margin-bottom:16px">
          <span class="sb-<?= h($booking['status']) ?>" style="font-size:1rem;padding:8px 20px"><?= ucfirst($booking['status']) ?></span>
        </div>
        <form method="POST">
          <?= csrfField() ?>
          <div class="admin-form-group">
            <label class="admin-label">Update Status</label>
            <select name="status" class="admin-select">
              <?php foreach (['pending','confirmed','paid','cancelled','completed','refunded'] as $s): ?>
              <option value="<?= $s ?>" <?= $booking['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="update_status" class="btn btn-admin-primary btn-block"><i class="fas fa-check"></i> Update Status</button>
        </form>
      </div>
    </div>

    <!-- Timeline -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Timeline</h3></div>
      <div class="admin-card-body">
        <?php $events = [
          ['Booking Created',  $booking['created_at'],   'fas fa-plus-circle', 'var(--admin-primary)'],
          ['Confirmed',        $booking['confirmed_at'], 'fas fa-check-circle','#059669'],
          ['Completed',        $booking['completed_at'], 'fas fa-flag',        '#7c3aed'],
          ['Cancelled',        $booking['cancelled_at'], 'fas fa-times-circle','#ef4444'],
        ]; ?>
        <?php foreach ($events as $ev): if (!$ev[1]) continue; ?>
        <div style="display:flex;gap:10px;margin-bottom:14px">
          <i class="<?= $ev[2] ?>" style="color:<?= $ev[3] ?>;width:18px;margin-top:2px;flex-shrink:0"></i>
          <div>
            <div style="font-size:.8rem;font-weight:600;color:var(--admin-text)"><?= $ev[0] ?></div>
            <div style="font-size:.75rem;color:var(--admin-muted)"><?= formatDate($ev[1],'M j, Y g:ia') ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Customer -->
    <?php if ($booking['user_email']): ?>
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Customer</h3></div>
      <div class="admin-card-body">
        <div style="text-align:center;margin-bottom:12px">
          <div style="width:52px;height:52px;border-radius:50%;background:var(--admin-primary);color:#fff;display:grid;place-items:center;font-size:1.25rem;font-weight:700;margin:0 auto 8px"><?= strtoupper(substr($booking['first_name']??'G',0,1)) ?></div>
          <div style="font-weight:600;color:var(--admin-text)"><?= h(($booking['first_name']??'Guest').' '.($booking['last_name']??'')) ?></div>
          <div style="font-size:.8rem;color:var(--admin-muted)"><?= h($booking['user_email']) ?></div>
        </div>
        <a href="mailto:<?= h($booking['user_email']) ?>" class="btn btn-admin-outline btn-block btn-sm"><i class="fas fa-envelope"></i> Send Email</a>
        <?php if ($booking['user_phone']): ?><a href="tel:<?= h($booking['user_phone']) ?>" class="btn btn-admin-outline btn-block btn-sm" style="margin-top:6px"><i class="fas fa-phone"></i> <?= h($booking['user_phone']) ?></a><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Payments -->
    <div class="admin-card">
      <div class="admin-card-header">
        <h3>Payments</h3>
        <span style="font-size:.78rem;color:<?= $totalPaid>=$booking['total_amount']?'var(--clr-success)':'var(--clr-warning)' ?>;font-weight:700"><?= money($totalPaid) ?> / <?= money($booking['total_amount']) ?></span>
      </div>
      <!-- Payment list -->
      <div class="admin-card-body" style="padding:0">
        <?php if ($payments): ?>
        <table class="admin-table" style="font-size:.78rem">
          <thead><tr><th>Type</th><th>Method</th><th>Amount</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <div style="font-weight:600;text-transform:capitalize"><?= $p['type'] ?></div>
                <div style="color:var(--admin-muted);font-size:.7rem"><?= formatDate($p['paid_at']??$p['created_at'],'M j') ?></div>
              </td>
              <td style="text-transform:capitalize"><?= str_replace('_',' ',$p['method']) ?></td>
              <td style="font-weight:700;color:<?= $p['status']==='completed'?'var(--clr-success)':'var(--admin-muted)' ?>"><?= money($p['amount']) ?></td>
              <td>
                <form method="POST" onsubmit="return confirm('Remove this payment?')"><?= csrfField() ?><input type="hidden" name="payment_id" value="<?= $p['id'] ?>">
                  <button type="submit" name="delete_payment" class="btn-icon-admin btn-icon-danger"><i class="fas fa-trash"></i></button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
        <p style="text-align:center;padding:16px 20px;font-size:.8rem;color:var(--admin-muted)">No payments recorded yet.</p>
        <?php endif; ?>
      </div>
      <!-- Record payment form -->
      <div class="admin-card-body" style="border-top:1px solid var(--clr-border)">
        <details <?= !$payments ? 'open' : '' ?>>
          <summary style="cursor:pointer;font-size:.82rem;font-weight:600;color:var(--admin-primary);margin-bottom:12px"><i class="fas fa-plus-circle" style="color:var(--clr-gold)"></i> Record Payment</summary>
          <form method="POST" style="display:flex;flex-direction:column;gap:10px;margin-top:10px">
            <?= csrfField() ?>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
              <div>
                <label style="font-size:.72rem;font-weight:600;color:var(--admin-muted);display:block;margin-bottom:4px">Amount</label>
                <input type="number" name="pay_amount" class="form-control" min="0.01" step="0.01" placeholder="0.00" style="font-size:.82rem" required>
              </div>
              <div>
                <label style="font-size:.72rem;font-weight:600;color:var(--admin-muted);display:block;margin-bottom:4px">Type</label>
                <select name="pay_type" class="form-control" style="font-size:.82rem">
                  <option value="deposit">Deposit</option>
                  <option value="balance">Balance</option>
                  <option value="full">Full Payment</option>
                  <option value="refund">Refund</option>
                </select>
              </div>
            </div>
            <div>
              <label style="font-size:.72rem;font-weight:600;color:var(--admin-muted);display:block;margin-bottom:4px">Method</label>
              <select name="pay_method" class="form-control" style="font-size:.82rem">
                <option value="mobile_money">M-Pesa / Mobile Money</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="stripe">Stripe / Card</option>
                <option value="paypal">PayPal</option>
                <option value="cash">Cash</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div>
              <label style="font-size:.72rem;font-weight:600;color:var(--admin-muted);display:block;margin-bottom:4px">Reference / Transaction ID</label>
              <input type="text" name="gateway_ref" class="form-control" placeholder="e.g. QKA8XYZBP2" style="font-size:.82rem">
            </div>
            <button type="submit" name="record_payment" class="btn-admin btn-admin-primary btn-block" style="margin-top:4px">
              <i class="fas fa-check"></i> Record Payment
            </button>
          </form>
        </details>
      </div>
    </div>
  </div>
</div>
</main>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
