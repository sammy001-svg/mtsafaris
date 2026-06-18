<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin(); requireRole(['super_admin','travel_manager','finance']);

$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to']   ?? date('Y-m-d');

$summary = [
    'gross_revenue' => DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
    'total_bookings'=> DB::value("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
    'confirmed'     => DB::value("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed','paid','completed') AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
    'cancelled'     => DB::value("SELECT COUNT(*) FROM bookings WHERE status='cancelled' AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
    'avg_booking'   => DB::value("SELECT COALESCE(AVG(total_amount),0) FROM bookings WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
    'new_customers' => DB::value("SELECT COUNT(*) FROM users WHERE role='customer' AND DATE(created_at) BETWEEN ? AND ?", [$from,$to]),
];

$bookingsByStatus = DB::rows("SELECT status, COUNT(*) AS cnt, SUM(total_amount) AS revenue FROM bookings WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY status", [$from,$to]);

$topPackages = DB::rows("SELECT p.title, COUNT(b.id) AS cnt, SUM(b.total_amount) AS revenue
                         FROM bookings b JOIN packages p ON b.package_id=p.id
                         WHERE b.status IN ('paid','completed') AND DATE(b.created_at) BETWEEN ? AND ?
                         GROUP BY p.id ORDER BY revenue DESC LIMIT 10", [$from,$to]);

$monthlyRev = DB::rows("SELECT DATE_FORMAT(created_at,'%Y-%m') AS month, SUM(total_amount) AS revenue, COUNT(*) AS cnt
                        FROM bookings WHERE status IN ('paid','completed') AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY month ORDER BY month ASC", [$from,$to]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Reports</span></div>
    </div>
    <div class="admin-header-right">
      <a href="?from=<?= h($from) ?>&to=<?= h($to) ?>&export=csv" class="btn-admin btn-admin-outline btn-admin-sm"><i class="fas fa-download"></i> CSV</a>
      <button onclick="window.print()" class="btn-admin btn-admin-outline btn-admin-sm"><i class="fas fa-print"></i> Print</button>
    </div>
  </header>
  <div class="admin-content">
    <div class="admin-page-title">Business Reports</div>

    <!-- Date Range Filter -->
    <form method="GET" style="display:flex;gap:12px;align-items:center;margin-bottom:24px;flex-wrap:wrap">
      <label style="font-size:.82rem;font-weight:600;color:var(--clr-muted)">From:</label>
      <input type="date" name="from" value="<?= h($from) ?>" class="form-control" style="width:auto;font-size:.82rem">
      <label style="font-size:.82rem;font-weight:600;color:var(--clr-muted)">To:</label>
      <input type="date" name="to" value="<?= h($to) ?>" class="form-control" style="width:auto;font-size:.82rem">
      <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">Generate Report</button>
      <span style="font-size:.78rem;color:var(--clr-muted)">Period: <?= formatDate($from) ?> — <?= formatDate($to) ?></span>
    </form>

    <!-- Summary Stats -->
    <div class="admin-stats" style="margin-bottom:20px">
      <div class="admin-stat">
        <div class="admin-stat-icon green"><i class="fas fa-dollar-sign"></i></div>
        <div><div class="admin-stat-value">$<?= number_format($summary['gross_revenue'],0) ?></div><div class="admin-stat-label">Gross Revenue</div></div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon blue"><i class="fas fa-ticket-alt"></i></div>
        <div><div class="admin-stat-value"><?= $summary['total_bookings'] ?></div><div class="admin-stat-label">Total Bookings</div></div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon sky"><i class="fas fa-chart-line"></i></div>
        <div><div class="admin-stat-value">$<?= number_format($summary['avg_booking'],0) ?></div><div class="admin-stat-label">Avg. Booking Value</div></div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon gold"><i class="fas fa-user-plus"></i></div>
        <div><div class="admin-stat-value"><?= $summary['new_customers'] ?></div><div class="admin-stat-label">New Customers</div></div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">

      <!-- Booking by Status -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Bookings by Status</h3></div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Status</th><th>Count</th><th>Revenue</th><th>% of Total</th></tr></thead>
            <tbody>
              <?php $total = array_sum(array_column($bookingsByStatus, 'cnt'));
              foreach ($bookingsByStatus as $r): ?>
              <tr>
                <td><span class="status-badge sb-<?= h($r['status']) ?>"><?= ucfirst(h($r['status'])) ?></span></td>
                <td style="font-size:.82rem"><?= $r['cnt'] ?></td>
                <td style="font-size:.82rem;font-weight:700">$<?= number_format($r['revenue']??0,0) ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:8px">
                    <div style="background:var(--clr-light);border-radius:4px;height:6px;flex:1;overflow:hidden">
                      <div style="background:var(--clr-primary);height:100%;width:<?= $total?round($r['cnt']/$total*100):0 ?>%"></div>
                    </div>
                    <span style="font-size:.72rem;color:var(--clr-muted);white-space:nowrap"><?= $total?round($r['cnt']/$total*100):0 ?>%</span>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Monthly Revenue -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Monthly Revenue</h3></div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Month</th><th>Bookings</th><th>Revenue</th></tr></thead>
            <tbody>
              <?php foreach ($monthlyRev as $mr): ?>
              <tr>
                <td style="font-size:.8rem"><?= date('M Y', strtotime($mr['month'].'-01')) ?></td>
                <td style="font-size:.8rem"><?= $mr['cnt'] ?></td>
                <td style="font-size:.8rem;font-weight:700;color:var(--clr-primary)">$<?= number_format($mr['revenue'],0) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if (!$monthlyRev): ?><tr><td colspan="3" style="text-align:center;padding:20px;color:var(--clr-muted)">No data for period.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Top Packages -->
    <div class="admin-card">
      <div class="admin-card-header"><h3>Top Performing Packages</h3></div>
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead><tr><th>#</th><th>Package</th><th>Bookings</th><th>Revenue</th><th>Avg. Value</th></tr></thead>
          <tbody>
            <?php foreach ($topPackages as $i => $pkg): ?>
            <tr>
              <td style="font-weight:700;color:var(--clr-gold)"><?= $i+1 ?></td>
              <td style="font-size:.82rem;font-weight:600"><?= h($pkg['title']) ?></td>
              <td style="font-size:.82rem"><?= $pkg['cnt'] ?></td>
              <td style="font-size:.82rem;font-weight:700;color:var(--clr-primary)">$<?= number_format($pkg['revenue'],0) ?></td>
              <td style="font-size:.78rem;color:var(--clr-muted)">$<?= $pkg['cnt']?number_format($pkg['revenue']/$pkg['cnt'],0):'0' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$topPackages): ?><tr><td colspan="5" style="text-align:center;padding:20px;color:var(--clr-muted)">No paid bookings in period.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
