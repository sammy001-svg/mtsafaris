<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

// Quick status update from list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && isset($_POST['update_status'])) {
    $allowed   = ['pending','confirmed','paid','cancelled','completed'];
    $newStatus = in_array($_POST['status'], $allowed) ? $_POST['status'] : null;
    if ($newStatus) {
        $upd = ['status' => $newStatus];
        if ($newStatus === 'confirmed') $upd['confirmed_at'] = date('Y-m-d H:i:s');
        if ($newStatus === 'completed') $upd['completed_at'] = date('Y-m-d H:i:s');
        if ($newStatus === 'cancelled') $upd['cancelled_at'] = date('Y-m-d H:i:s');
        DB::update('bookings', $upd, ['id' => (int)$_POST['id']]);
        flash('success', 'Booking status updated.');
    }
    redirect(url('admin/bookings.php?' . http_build_query(array_filter([
        'status'    => $_GET['status']    ?? '',
        'search'    => $_GET['search']    ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
    ]))));
}

$status   = $_GET['status']    ?? '';
$search   = trim($_GET['search']    ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($status)   { $where[] = 'b.status = ?'; $params[] = $status; }
if ($search)   { $where[] = '(b.reference LIKE ? OR CONCAT(u.first_name," ",u.last_name) LIKE ? OR p.title LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($dateFrom) { $where[] = 'b.travel_date >= ?'; $params[] = $dateFrom; }
if ($dateTo)   { $where[] = 'b.travel_date <= ?'; $params[] = $dateTo; }

$sql = "SELECT b.*, p.title AS package_title, p.duration_days, d.name AS destination_name,
               CONCAT(u.first_name,' ',u.last_name) AS customer_name, u.email AS customer_email
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        LEFT JOIN destinations d ON p.destination_id = d.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC";

$result   = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$bookings = $result['rows'];

// Status counts for tabs
$rawCounts = DB::rows("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
$counts    = [];
foreach ($rawCounts as $r) $counts[$r['status']] = (int)$r['cnt'];
$totalAll  = array_sum($counts);

// Revenue summary stats
$revenue    = (float)(DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status NOT IN ('cancelled')") ?? 0);
$pendingAmt = (float)(DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status='pending'") ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Bookings — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <style>
    .stat-card { background:#fff;border:1px solid var(--clr-border);border-radius:var(--radius-md);padding:20px;display:flex;align-items:center;gap:16px }
    .stat-icon { width:44px;height:44px;border-radius:var(--radius-sm);display:grid;place-items:center;flex-shrink:0 }
    .stat-label { font-size:.7rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:2px }
    .stat-value { font-size:1.4rem;font-weight:800;color:var(--clr-primary);line-height:1 }
    .stat-sub { font-size:.7rem;color:var(--clr-muted);margin-top:4px }
    .bk-badge { display:inline-block;padding:3px 10px;border-radius:20px;font-size:.71rem;font-weight:700;letter-spacing:.02em;white-space:nowrap }
    .bk-pending   { background:#fef3c7;color:#92400e }
    .bk-confirmed { background:#dbeafe;color:#1e40af }
    .bk-paid      { background:#d1fae5;color:#065f46 }
    .bk-completed { background:#ede9fe;color:#5b21b6 }
    .bk-cancelled { background:#fee2e2;color:#991b1b }
    .bk-refunded  { background:#f3f4f6;color:#374151 }
    .quick-status { font-size:.73rem;padding:4px 8px;border:1px solid var(--clr-border);border-radius:var(--radius-sm);background:#fff;color:var(--clr-primary);font-weight:600;cursor:pointer;width:100%;min-width:96px }
    .quick-status:focus { outline:2px solid var(--clr-primary);outline-offset:1px }
    .ref-link { color:var(--clr-primary);font-weight:700;font-size:.8rem;text-decoration:none;font-family:monospace }
    .ref-link:hover { text-decoration:underline }
    .filter-bar { background:#fff;border:1px solid var(--clr-border);border-top:none;border-radius:0 0 var(--radius-md) var(--radius-md);padding:12px 16px;margin-bottom:16px }
  </style>
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Bookings</span></div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/reports.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px">
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <div class="page-header" style="margin-bottom:24px">
      <div>
        <h1 class="page-header-title">Booking Management</h1>
        <p class="page-header-sub"><?= number_format($totalAll) ?> total bookings across all statuses</p>
      </div>
    </div>

    <!-- Summary Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
      <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe"><i class="fas fa-calendar-check" style="color:#1e40af"></i></div>
        <div>
          <div class="stat-label">Total Bookings</div>
          <div class="stat-value"><?= number_format($totalAll) ?></div>
          <div class="stat-sub"><?= $result['total'] ?> matching filter</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#fef3c7"><i class="fas fa-clock" style="color:#d97706"></i></div>
        <div>
          <div class="stat-label">Pending</div>
          <div class="stat-value"><?= number_format($counts['pending'] ?? 0) ?></div>
          <div class="stat-sub"><?= money($pendingAmt) ?> outstanding</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#d1fae5"><i class="fas fa-check-circle" style="color:#059669"></i></div>
        <div>
          <div class="stat-label">Active (Conf. + Paid)</div>
          <div class="stat-value"><?= number_format(($counts['confirmed']??0)+($counts['paid']??0)) ?></div>
          <div class="stat-sub"><?= ($counts['completed']??0) ?> completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7"><i class="fas fa-dollar-sign" style="color:#16a34a"></i></div>
        <div>
          <div class="stat-label">Revenue</div>
          <div class="stat-value" style="font-size:1.1rem"><?= money($revenue) ?></div>
          <div class="stat-sub">excl. cancellations</div>
        </div>
      </div>
    </div>

    <!-- Status Tabs -->
    <div class="admin-tabs" style="margin-bottom:0;border-radius:var(--radius-md) var(--radius-md) 0 0">
      <?php
      $tabDefs = [
        ''          => 'All',
        'pending'   => 'Pending',
        'confirmed' => 'Confirmed',
        'paid'      => 'Paid',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
      ];
      foreach ($tabDefs as $s => $label):
        $cnt = $s === '' ? $totalAll : ($counts[$s] ?? 0);
      ?>
      <a href="?status=<?= urlencode($s) ?><?= $search?"&search=".urlencode($search):'' ?><?= $dateFrom?"&date_from=".urlencode($dateFrom):'' ?><?= $dateTo?"&date_to=".urlencode($dateTo):'' ?>"
         class="admin-tab <?= $status===$s?'active':'' ?>">
        <?= $label ?><span class="tab-count"><?= $cnt ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Filter Bar -->
    <div class="filter-bar" style="margin-bottom:16px">
      <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
        <input type="hidden" name="status" value="<?= h($status) ?>">
        <div class="input-group" style="flex:1;min-width:220px">
          <span class="ig-icon"><i class="fas fa-search"></i></span>
          <input type="text" name="search" value="<?= h($search) ?>" placeholder="Reference, customer or package…" class="form-control" style="font-size:.82rem">
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex-shrink:0">
          <input type="date" name="date_from" value="<?= h($dateFrom) ?>" class="form-control" style="font-size:.8rem;width:140px" title="Travel date from">
          <span style="color:var(--clr-muted);font-size:.8rem;white-space:nowrap">to</span>
          <input type="date" name="date_to" value="<?= h($dateTo) ?>" class="form-control" style="font-size:.8rem;width:140px" title="Travel date to">
        </div>
        <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($search || $dateFrom || $dateTo): ?>
        <a href="?status=<?= h($status) ?>" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- Bookings Table -->
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Customer</th>
              <th>Package / Destination</th>
              <th>Travel Date</th>
              <th>Travelers</th>
              <th>Total</th>
              <th>Status</th>
              <th>Booked On</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b):
              $lead      = jd($b['lead_traveler'] ?? '{}', []);
              $custName  = trim($b['customer_name'] ?: (($lead['first_name']??'').' '.($lead['last_name']??'')));
              $custEmail = $b['customer_email'] ?: ($lead['email'] ?? '');
            ?>
            <tr>
              <td>
                <a href="<?= url('admin/booking-view.php?id='.$b['id']) ?>" class="ref-link"><?= h($b['reference']) ?></a>
              </td>
              <td>
                <div class="td-primary"><?= h($custName ?: 'Guest') ?></div>
                <?php if ($custEmail): ?><div class="td-secondary"><?= h($custEmail) ?></div><?php endif; ?>
              </td>
              <td>
                <div class="td-primary" style="max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($b['package_title']) ?></div>
                <?php if ($b['destination_name']): ?><div class="td-secondary"><i class="fas fa-map-marker-alt" style="font-size:.6rem"></i> <?= h($b['destination_name']) ?></div><?php endif; ?>
              </td>
              <td class="td-mono" style="white-space:nowrap"><?= formatDate($b['travel_date'],'M j, Y') ?></td>
              <td>
                <div class="td-primary"><?= (int)$b['adults'] ?>A<?= $b['children']?' + '.(int)$b['children'].'C':'' ?></div>
                <div class="td-secondary"><?= (int)($b['duration_days']??0) ?>d</div>
              </td>
              <td style="font-weight:700;color:var(--clr-primary);white-space:nowrap"><?= money($b['total_amount']) ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <input type="hidden" name="update_status" value="1">
                  <select name="status" class="quick-status" onchange="this.form.submit()" title="Quick status update">
                    <?php foreach (['pending','confirmed','paid','completed','cancelled'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </td>
              <td class="td-secondary" style="white-space:nowrap"><?= formatDate($b['created_at'],'M j, Y') ?></td>
              <td>
                <div class="tbl-actions">
                  <a href="<?= url('admin/booking-view.php?id='.$b['id']) ?>" class="btn-tbl" title="View"><i class="fas fa-eye"></i></a>
                  <a href="<?= url('admin/booking-view.php?id='.$b['id'].'&print=1') ?>" class="btn-tbl" title="Print Invoice" target="_blank"><i class="fas fa-print"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?>
            <tr>
              <td colspan="9" style="text-align:center;padding:56px 20px">
                <i class="fas fa-calendar-times" style="font-size:2.5rem;color:var(--clr-border);display:block;margin-bottom:14px"></i>
                <div style="color:var(--clr-muted);font-size:.9rem">No bookings found<?= ($search||$dateFrom||$dateTo)?' matching your filters':'' ?>.</div>
                <?php if ($search||$dateFrom||$dateTo): ?>
                <a href="?status=<?= h($status) ?>" style="display:inline-block;margin-top:10px;font-size:.8rem;color:var(--clr-primary)">Clear filters</a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($result['pages'] > 1):
        $baseUrl = url('admin/bookings.php?' . http_build_query(array_filter([
            'status'    => $status,
            'search'    => $search,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ])));
      ?>
      <div style="padding:14px 20px;border-top:1px solid var(--clr-border);display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:.78rem;color:var(--clr-muted)">
          Page <?= $page ?> of <?= $result['pages'] ?> &mdash; <?= number_format($result['total']) ?> bookings
        </span>
        <div style="display:flex;gap:4px">
          <?php if ($page > 1): ?>
          <a href="<?= $baseUrl ?>&page=<?= $page-1 ?>" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-chevron-left"></i></a>
          <?php endif; ?>
          <?php for ($i = max(1,$page-2); $i <= min($result['pages'],$page+2); $i++): ?>
          <a href="<?= $baseUrl ?>&page=<?= $i ?>"
             class="btn-admin btn-admin-sm <?= $i===$page?'btn-admin-primary':'btn-admin-secondary' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <?php if ($page < $result['pages']): ?>
          <a href="<?= $baseUrl ?>&page=<?= $page+1 ?>" class="btn-admin btn-admin-secondary btn-admin-sm"><i class="fas fa-chevron-right"></i></a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
