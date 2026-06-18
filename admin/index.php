<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

// Dashboard analytics
$stats = [
    'revenue'       => DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('paid','completed') AND MONTH(created_at)=MONTH(NOW())"),
    'bookings'      => DB::value("SELECT COUNT(*) FROM bookings WHERE MONTH(created_at)=MONTH(NOW())"),
    'packages'      => DB::value("SELECT COUNT(*) FROM packages WHERE is_active=1"),
    'customers'     => DB::value("SELECT COUNT(*) FROM users WHERE role='customer'"),
    'pending'       => DB::value("SELECT COUNT(*) FROM bookings WHERE status='pending'"),
    'new_inquiries' => DB::value("SELECT COUNT(*) FROM inquiries WHERE status='new'"),
    'total_revenue' => DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('paid','completed')"),
    'subscribers'   => DB::value("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active=1"),
];

$recentBookings = DB::rows("SELECT b.*, p.title AS package_title, CONCAT(u.first_name,' ',u.last_name) AS customer_name
                            FROM bookings b
                            JOIN packages p ON b.package_id=p.id
                            LEFT JOIN users u ON b.user_id=u.id
                            ORDER BY b.created_at DESC LIMIT 8");

$recentInquiries = DB::rows("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 5");

// Revenue last 12 months
$months = []; $revenueData = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-$i months"));
    $months[]     = date('M Y', strtotime("-$i months"));
    $revenueData[] = (float) DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE status IN ('paid','completed') AND DATE_FORMAT(created_at,'%Y-%m')=?", [$m]);
}

// Booking status breakdown
$statusBreakdown = DB::rows("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
$statusLabels = $statusData = [];
foreach ($statusBreakdown as $row) { $statusLabels[] = ucfirst($row['status']); $statusData[] = $row['cnt']; }

// Top packages
$topPackages = DB::rows("SELECT p.title, COUNT(b.id) AS bookings, COALESCE(SUM(b.total_amount),0) AS revenue
                         FROM packages p LEFT JOIN bookings b ON p.id=b.package_id AND b.status IN ('paid','completed')
                         GROUP BY p.id ORDER BY bookings DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>

<div class="admin-main">
  <!-- Header -->
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Dashboard</span></div>
    </div>
    <div class="admin-header-right">
      <div class="search-bar-admin">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search…">
      </div>
      <div style="position:relative">
        <button class="header-icon-btn" id="notifBtn">
          <i class="fas fa-bell"></i>
          <?php if ($stats['pending'] + $stats['new_inquiries'] > 0): ?>
          <span class="header-notif-count"><?= $stats['pending'] + $stats['new_inquiries'] ?></span>
          <?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
          <div class="notif-header">Notifications <a href="#" style="color:var(--clr-gold);font-size:.72rem">Mark all read</a></div>
          <?php if ($stats['pending']): ?>
          <a href="<?= url('admin/bookings.php?status=pending') ?>" class="notif-item unread" style="text-decoration:none;display:flex">
            <div class="notif-icon" style="background:rgba(214,158,46,.15)"><i class="fas fa-ticket-alt" style="color:var(--clr-warning)"></i></div>
            <div><p class="notif-text"><?= $stats['pending'] ?> pending booking<?= $stats['pending']>1?'s':'' ?> need review</p><p class="notif-time">Just now</p></div>
          </a>
          <?php endif; ?>
          <?php if ($stats['new_inquiries']): ?>
          <a href="<?= url('admin/inquiries.php') ?>" class="notif-item unread" style="text-decoration:none;display:flex">
            <div class="notif-icon" style="background:rgba(13,59,102,.12)"><i class="fas fa-envelope" style="color:var(--clr-primary)"></i></div>
            <div><p class="notif-text"><?= $stats['new_inquiries'] ?> new inquiry<?= $stats['new_inquiries']>1?'ies':'' ?></p><p class="notif-time">Just now</p></div>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <a href="<?= url() ?>" target="_blank" class="header-icon-btn" title="View Site"><i class="fas fa-external-link-alt"></i></a>
    </div>
  </header>

  <div class="admin-content">
    <div class="admin-page-title">Dashboard Overview</div>
    <div class="admin-page-sub"><?= date('l, F j, Y') ?> — Welcome back, <?= h(currentUser()['first_name']) ?>!</div>

    <!-- Stats Grid -->
    <div class="admin-stats">
      <div class="admin-stat">
        <div class="admin-stat-icon blue"><i class="fas fa-dollar-sign"></i></div>
        <div>
          <div class="admin-stat-value">$<?= number_format($stats['revenue'], 0) ?></div>
          <div class="admin-stat-label">Revenue This Month</div>
          <div class="admin-stat-change up"><i class="fas fa-arrow-up"></i> 12% vs last month</div>
        </div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon gold"><i class="fas fa-ticket-alt"></i></div>
        <div>
          <div class="admin-stat-value"><?= $stats['bookings'] ?></div>
          <div class="admin-stat-label">Bookings This Month</div>
          <div class="admin-stat-change up"><i class="fas fa-arrow-up"></i> 8% vs last month</div>
        </div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon sky"><i class="fas fa-users"></i></div>
        <div>
          <div class="admin-stat-value"><?= number_format($stats['customers']) ?></div>
          <div class="admin-stat-label">Registered Customers</div>
        </div>
      </div>
      <div class="admin-stat">
        <div class="admin-stat-icon green"><i class="fas fa-box-open"></i></div>
        <div>
          <div class="admin-stat-value"><?= $stats['packages'] ?></div>
          <div class="admin-stat-label">Active Packages</div>
        </div>
      </div>
    </div>

    <!-- Alert Row -->
    <?php if ($stats['pending'] || $stats['new_inquiries']): ?>
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <?php if ($stats['pending']): ?>
      <a href="<?= url('admin/bookings.php?status=pending') ?>" style="display:flex;align-items:center;gap:10px;background:#FFFBEB;border:1px solid #FAF089;border-radius:8px;padding:12px 18px;flex:1;min-width:200px;text-decoration:none">
        <i class="fas fa-clock" style="color:var(--clr-warning);font-size:1.1rem"></i>
        <div><div style="font-weight:600;font-size:.85rem;color:#975A16"><?= $stats['pending'] ?> Pending Booking<?= $stats['pending']>1?'s':'' ?></div><div style="font-size:.72rem;color:#b7791f">Action required</div></div>
        <i class="fas fa-chevron-right" style="color:#975A16;margin-left:auto"></i>
      </a>
      <?php endif; ?>
      <?php if ($stats['new_inquiries']): ?>
      <a href="<?= url('admin/inquiries.php') ?>" style="display:flex;align-items:center;gap:10px;background:#EBF8FF;border:1px solid #90CDF4;border-radius:8px;padding:12px 18px;flex:1;min-width:200px;text-decoration:none">
        <i class="fas fa-envelope" style="color:var(--clr-info);font-size:1.1rem"></i>
        <div><div style="font-weight:600;font-size:.85rem;color:#2C5282"><?= $stats['new_inquiries'] ?> New Inquir<?= $stats['new_inquiries']>1?'ies':'y' ?></div><div style="font-size:.72rem;color:#2b6cb0">Awaiting response</div></div>
        <i class="fas fa-chevron-right" style="color:#2C5282;margin-left:auto"></i>
      </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Charts Row -->
    <div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:20px">
      <div class="admin-card">
        <div class="admin-card-header"><h3>Revenue Overview (12 Months)</h3></div>
        <div class="admin-card-body">
          <div class="chart-container">
            <canvas id="revenueChart"
                    data-labels='<?= json_encode($months) ?>'
                    data-values='<?= json_encode($revenueData) ?>'></canvas>
          </div>
        </div>
      </div>
      <div class="admin-card">
        <div class="admin-card-header"><h3>Booking Status</h3></div>
        <div class="admin-card-body">
          <div class="chart-container" style="height:220px">
            <canvas id="bookingsChart"
                    data-labels='<?= json_encode($statusLabels) ?>'
                    data-values='<?= json_encode($statusData) ?>'></canvas>
          </div>
          <div class="quick-stats" style="margin-top:12px">
            <div class="qs-item"><div class="qs-val"><?= $stats['pending'] ?></div><div class="qs-lab">Pending</div></div>
            <div class="qs-item"><div class="qs-val">$<?= number_format($stats['total_revenue'],0) ?></div><div class="qs-lab">Total Rev.</div></div>
            <div class="qs-item"><div class="qs-val"><?= $stats['subscribers'] ?></div><div class="qs-lab">Subscribers</div></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Bookings & Top Packages -->
    <div style="display:grid;grid-template-columns:3fr 2fr;gap:20px;margin-bottom:20px">
      <div class="admin-card">
        <div class="admin-card-header">
          <h3>Recent Bookings</h3>
          <a href="<?= url('admin/bookings.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm">View All</a>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Reference</th><th>Customer</th><th>Package</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentBookings as $b): ?>
              <tr>
                <td><a href="<?= url('admin/booking-view.php?id='.$b['id']) ?>" style="color:var(--clr-primary);font-weight:600;font-size:.78rem"><?= h($b['reference']) ?></a></td>
                <td style="font-size:.78rem"><?= h($b['customer_name'] ?? 'Guest') ?></td>
                <td style="font-size:.78rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($b['package_title']) ?></td>
                <td style="font-size:.75rem;white-space:nowrap"><?= formatDate($b['travel_date']) ?></td>
                <td style="font-size:.78rem;font-weight:700">$<?= number_format($b['total_amount'],0) ?></td>
                <td><span class="status-badge sb-<?= h($b['status']) ?>"><?= ucfirst(h($b['status'])) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header"><h3>Top Packages</h3></div>
        <div class="admin-card-body" style="padding:0">
          <?php foreach ($topPackages as $i => $pkg): ?>
          <div style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-bottom:1px solid var(--clr-border)">
            <div style="width:24px;height:24px;background:<?= ['var(--clr-gold)','var(--clr-primary)','var(--clr-sky)','var(--clr-success)','var(--clr-warning)'][$i] ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.72rem;font-weight:700;flex-shrink:0"><?= $i+1 ?></div>
            <div style="flex:1;min-width:0">
              <p style="font-size:.8rem;font-weight:600;color:var(--clr-primary);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($pkg['title']) ?></p>
              <p style="font-size:.68rem;color:var(--clr-muted)"><?= $pkg['bookings'] ?> bookings · $<?= number_format($pkg['revenue'],0) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Recent Inquiries & Activity -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div class="admin-card">
        <div class="admin-card-header">
          <h3>New Inquiries</h3>
          <a href="<?= url('admin/inquiries.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm">View All</a>
        </div>
        <div class="admin-card-body" style="padding:0">
          <?php foreach ($recentInquiries as $inq): ?>
          <a href="<?= url('admin/inquiry-view.php?id='.$inq['id']) ?>" style="display:flex;gap:12px;padding:12px 16px;border-bottom:1px solid var(--clr-border);text-decoration:none" onmouseover="this.style.background='var(--clr-light)'" onmouseout="this.style.background=''">
            <div style="width:36px;height:36px;border-radius:50%;background:rgba(13,59,102,.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:.8rem;color:var(--clr-primary)"><?= strtoupper(substr($inq['name'],0,1)) ?></div>
            <div style="flex:1;min-width:0">
              <p style="font-size:.8rem;font-weight:600;color:var(--clr-primary)"><?= h($inq['name']) ?></p>
              <p style="font-size:.72rem;color:var(--clr-muted);overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h(excerpt($inq['message'],60)) ?></p>
            </div>
            <span class="status-badge sb-<?= $inq['status'] === 'new' ? 'pending' : $inq['status'] ?>" style="align-self:center;white-space:nowrap"><?= ucfirst($inq['status']) ?></span>
          </a>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="admin-card">
        <div class="admin-card-header"><h3>Quick Actions</h3></div>
        <div class="admin-card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <?php foreach ([
              [url('admin/package-edit.php'),'fas fa-plus','Add Package','blue'],
              [url('admin/blog-edit.php'),'fas fa-pen','New Post','gold'],
              [url('admin/bookings.php'),'fas fa-ticket-alt','View Bookings','sky'],
              [url('admin/users.php'),'fas fa-users','Manage Users','green'],
              [url('admin/reports.php'),'fas fa-chart-bar','Reports','blue'],
              [url('admin/settings.php'),'fas fa-cog','Settings','gold'],
            ] as $action): ?>
            <a href="<?= $action[0] ?>" style="display:flex;align-items:center;gap:8px;padding:12px;background:var(--clr-light);border-radius:8px;border:1px solid var(--clr-border);text-decoration:none;transition:all .2s" onmouseover="this.style.borderColor='var(--clr-primary)'" onmouseout="this.style.borderColor='var(--clr-border)'">
              <div style="width:32px;height:32px;border-radius:6px;background:rgba(13,59,102,.1);display:flex;align-items:center;justify-content:center;color:var(--clr-primary);font-size:.85rem;flex-shrink:0"><i class="<?= $action[1] ?>"></i></div>
              <span style="font-size:.8rem;font-weight:600;color:var(--clr-primary)"><?= $action[2] ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
