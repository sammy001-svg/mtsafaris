<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

// Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf() && isset($_POST['update_status'])) {
    $allowedStatus = ['pending','confirmed','paid','cancelled','completed'];
    $newStatus = in_array($_POST['status'], $allowedStatus) ? $_POST['status'] : null;
    if ($newStatus) {
        $extra = [];
        if ($newStatus === 'confirmed') $extra['confirmed_at'] = date('Y-m-d H:i:s');
        if ($newStatus === 'completed') $extra['completed_at'] = date('Y-m-d H:i:s');
        if ($newStatus === 'cancelled') $extra['cancelled_at'] = date('Y-m-d H:i:s');
        DB::update('bookings', array_merge(['status' => $newStatus], $extra), ['id' => (int)$_POST['id']]);
        flash('success', 'Booking status updated.');
    }
    redirect(url('admin/bookings.php'));
}

$status  = $_GET['status'] ?? '';
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($status) { $where[] = 'b.status = ?'; $params[] = $status; }
if ($search) { $where[] = '(b.reference LIKE ? OR CONCAT(u.first_name," ",u.last_name) LIKE ? OR p.title LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

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

// Status counts
$counts = [];
foreach (DB::rows("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status") as $r) {
    $counts[$r['status']] = $r['cnt'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bookings — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
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
      <a href="<?= url('admin/reports.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm"><i class="fas fa-download"></i> Export</a>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Booking Management</div>
    <div class="admin-page-sub"><?= $result['total'] ?> total bookings</div>

    <!-- Status Tabs -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
      <?php foreach ([''=> 'All', 'pending'=>'Pending', 'confirmed'=>'Confirmed', 'paid'=>'Paid', 'completed'=>'Completed', 'cancelled'=>'Cancelled'] as $s=>$label): ?>
      <a href="?status=<?= $s ?><?= $search?"&search=".urlencode($search):'' ?>"
         style="padding:7px 14px;border-radius:20px;font-size:.78rem;font-weight:600;border:1.5px solid <?= $status===$s?'var(--clr-primary)':'var(--clr-border)' ?>;background:<?= $status===$s?'var(--clr-primary)':'#fff' ?>;color:<?= $status===$s?'#fff':'var(--clr-muted)' ?>;text-decoration:none">
        <?= $label ?> <?php if ($s && isset($counts[$s])): ?><span style="opacity:.8">(<?= $counts[$s] ?>)</span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-body" style="padding:14px 16px">
        <form method="GET" style="display:flex;gap:10px">
          <input type="hidden" name="status" value="<?= h($status) ?>">
          <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search reference, customer or package…" class="form-control" style="flex:1;font-size:.82rem">
          <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">Search</button>
          <?php if ($search): ?><a href="?status=<?= h($status) ?>" class="btn-admin btn-admin-outline btn-admin-sm">Clear</a><?php endif; ?>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Customer</th>
              <th>Package</th>
              <th>Travel Date</th>
              <th>Travelers</th>
              <th>Total</th>
              <th>Status</th>
              <th>Booked</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($bookings as $b):
              $lead = jd($b['lead_traveler']);
              $cls  = ['pending'=>'sb-pending','confirmed'=>'sb-confirmed','paid'=>'sb-paid','cancelled'=>'sb-cancelled','completed'=>'sb-completed'];
            ?>
            <tr>
              <td><a href="<?= url('admin/booking-view.php?id='.$b['id']) ?>" style="color:var(--clr-primary);font-weight:700;font-size:.8rem"><?= h($b['reference']) ?></a></td>
              <td>
                <div style="font-size:.8rem;font-weight:600"><?= h($b['customer_name'] ?? ($lead['first_name'].' '.$lead['last_name'])) ?></div>
                <div style="font-size:.68rem;color:var(--clr-muted)"><?= h($b['customer_email'] ?? $lead['email'] ?? '') ?></div>
              </td>
              <td style="font-size:.78rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($b['package_title']) ?></td>
              <td style="font-size:.78rem;white-space:nowrap"><?= formatDate($b['travel_date']) ?></td>
              <td style="font-size:.78rem"><?= $b['adults'] ?>A<?= $b['children']?' + '.$b['children'].'C':'' ?></td>
              <td style="font-size:.82rem;font-weight:700;color:var(--clr-primary)">$<?= number_format($b['total_amount'],0) ?></td>
              <td>
                <form method="POST" style="display:inline" id="statusForm_<?= $b['id'] ?>">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $b['id'] ?>">
                  <select name="status" class="form-control" style="font-size:.72rem;padding:4px 8px;width:auto" onchange="this.form.submit()">
                    <?php foreach (['pending','confirmed','paid','cancelled','completed'] as $s): ?>
                    <option value="<?= $s ?>" <?= $b['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input type="hidden" name="update_status" value="1">
                </form>
              </td>
              <td style="font-size:.72rem;color:var(--clr-muted);white-space:nowrap"><?= formatDate($b['created_at'],'M j, Y') ?></td>
              <td>
                <div class="table-actions">
                  <a href="<?= url('admin/booking-view.php?id='.$b['id']) ?>" class="action-btn view" title="View"><i class="fas fa-eye"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$bookings): ?><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--clr-muted)">No bookings found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($result['pages'] > 1): ?>
      <div style="padding:14px 20px;border-top:1px solid var(--clr-border);display:flex;justify-content:center;gap:6px">
        <?php for ($i=1;$i<=$result['pages'];$i++): ?>
        <a href="?page=<?= $i ?>&status=<?= h($status) ?>&search=<?= urlencode($search) ?>"
           style="padding:5px 10px;border:1px solid var(--clr-border);border-radius:4px;font-size:.75rem;color:<?= $i===$page?'#fff':'var(--clr-text)' ?>;background:<?= $i===$page?'var(--clr-primary)':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
