<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['b.user_id = ?'];
$params = [$user['id']];
if ($status) { $where[] = 'b.status = ?'; $params[] = $status; }
if ($search) { $where[] = '(b.reference LIKE ? OR p.title LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql = "SELECT b.*, p.title AS package_title, p.hero_image, p.duration_days, d.name AS destination_name
        FROM bookings b
        JOIN packages p ON b.package_id = p.id
        LEFT JOIN destinations d ON p.destination_id = d.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC";

$result   = DB::paginate($sql, $params, $page, 10);
$bookings = $result['rows'];

// Status counts for this user
$rawCounts = DB::rows("SELECT status, COUNT(*) AS cnt FROM bookings WHERE user_id=? GROUP BY status", [$user['id']]);
$counts    = [];
foreach ($rawCounts as $r) $counts[$r['status']] = (int)$r['cnt'];
$totalAll  = array_sum($counts);

$statusConfig = [
    ''          => 'All',
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
    'paid'      => 'Paid',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

function portalBadge(string $s): string {
    $map = [
        'pending'   => ['#fef3c7','#92400e'],
        'confirmed' => ['#dbeafe','#1e40af'],
        'paid'      => ['#d1fae5','#065f46'],
        'completed' => ['#d1fae5','#065f46'],
        'cancelled' => ['#fee2e2','#991b1b'],
        'refunded'  => ['#f3f4f6','#374151'],
    ];
    [$bg, $clr] = $map[$s] ?? ['#f3f4f6','#374151'];
    return "<span style=\"display:inline-block;padding:3px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:{$bg};color:{$clr}\">".ucfirst($s).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style>
    .portal-tab-bar { display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px }
    .portal-tab { display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:20px;font-size:.78rem;font-weight:600;text-decoration:none;border:1.5px solid var(--clr-border);color:var(--clr-muted);background:#fff;transition:all .2s }
    .portal-tab.active,.portal-tab:hover { background:var(--clr-primary);border-color:var(--clr-primary);color:#fff }
    .portal-tab .cnt { font-size:.65rem;opacity:.8 }
    .bk-card { background:#fff;border:1px solid var(--clr-border);border-radius:14px;padding:20px;margin-bottom:16px;transition:box-shadow .2s }
    .bk-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.08) }
    .bk-card-grid { display:grid;grid-template-columns:76px 1fr auto;gap:16px;align-items:center }
    .meta-pill { font-size:.72rem;color:var(--clr-muted);display:inline-flex;align-items:center;gap:4px }
    .meta-pill i { color:var(--clr-gold);font-size:.68rem }
  </style>
</head>
<body>
<div class="portal-layout">
  <?php require_once 'includes/sidebar.php'; ?>

  <main class="portal-main">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.4rem;color:var(--clr-primary);margin-bottom:2px">My Bookings</h1>
        <p style="color:var(--clr-muted);font-size:.82rem"><?= $totalAll ?> booking<?= $totalAll!=1?'s':'' ?> total</p>
      </div>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> New Booking</a>
    </div>

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:20px">
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Status Tabs -->
    <div class="portal-tab-bar">
      <?php foreach ($statusConfig as $s => $label): ?>
      <a href="?status=<?= urlencode($s) ?><?= $search?"&search=".urlencode($search):'' ?>"
         class="portal-tab <?= $status===$s?'active':'' ?>">
        <?= $label ?>
        <span class="cnt">(<?= $s===''?$totalAll:($counts[$s]??0) ?>)</span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search Bar -->
    <form method="GET" style="display:flex;gap:8px;margin-bottom:20px">
      <input type="hidden" name="status" value="<?= h($status) ?>">
      <div style="flex:1;position:relative">
        <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--clr-muted);font-size:.82rem"></i>
        <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search by reference or package name…"
               class="form-control" style="padding-left:36px;font-size:.85rem">
      </div>
      <button type="submit" class="btn btn-primary btn-sm" style="white-space:nowrap"><i class="fas fa-search"></i> Search</button>
      <?php if ($search): ?><a href="?status=<?= h($status) ?>" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></a><?php endif; ?>
    </form>

    <!-- Booking Cards -->
    <?php if ($bookings): ?>
    <?php foreach ($bookings as $b):
      $lead = jd($b['lead_traveler'] ?? '{}', []);
    ?>
    <div class="bk-card">
      <div class="bk-card-grid">
        <img src="<?= h($b['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=200&q=70') ?>"
             style="width:76px;height:60px;object-fit:cover;border-radius:10px" alt="">
        <div>
          <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px">
            <h4 style="font-size:.9rem;font-weight:700;color:var(--clr-primary);margin:0"><?= h($b['package_title']) ?></h4>
            <?= portalBadge($b['status']) ?>
          </div>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <span class="meta-pill"><i class="fas fa-hashtag"></i><?= h($b['reference']) ?></span>
            <span class="meta-pill"><i class="fas fa-calendar-alt"></i><?= formatDate($b['travel_date'],'M j, Y') ?></span>
            <span class="meta-pill"><i class="fas fa-users"></i><?= (int)$b['adults'] ?> Adult<?= $b['adults']>1?'s':'' ?><?= $b['children']?' + '.(int)$b['children'].' Child':'' ?></span>
            <?php if ($b['destination_name']): ?><span class="meta-pill"><i class="fas fa-map-marker-alt"></i><?= h($b['destination_name']) ?></span><?php endif; ?>
            <?php if ($b['duration_days']): ?><span class="meta-pill"><i class="fas fa-clock"></i><?= (int)$b['duration_days'] ?> days</span><?php endif; ?>
          </div>
        </div>
        <div style="text-align:right;flex-shrink:0">
          <div style="font-size:1.15rem;font-weight:800;color:var(--clr-primary);margin-bottom:4px"><?= money($b['total_amount']) ?></div>
          <div style="font-size:.68rem;color:var(--clr-muted);margin-bottom:10px">Total Amount</div>
          <a href="<?= url('portal/booking-view.php?ref='.h($b['reference'])) ?>" class="btn btn-primary btn-sm">View Details</a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>

    <!-- Pagination -->
    <?php if ($result['pages'] > 1):
      $baseUrl = url('portal/bookings.php?' . http_build_query(array_filter(['status' => $status, 'search' => $search])));
    ?>
    <div style="display:flex;justify-content:center;gap:4px;margin-top:24px">
      <?php if ($page > 1): ?>
      <a href="<?= $baseUrl ?>&page=<?= $page-1 ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-left"></i></a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($result['pages'],$page+2); $i++): ?>
      <a href="<?= $baseUrl ?>&page=<?= $i ?>" class="btn btn-sm <?= $i===$page?'btn-primary':'btn-outline' ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($page < $result['pages']): ?>
      <a href="<?= $baseUrl ?>&page=<?= $page+1 ?>" class="btn btn-outline btn-sm"><i class="fas fa-chevron-right"></i></a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="card text-center" style="padding:60px 20px">
      <i class="fas fa-suitcase" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
      <h4 style="color:var(--clr-primary);margin-bottom:8px">
        <?= $search ? 'No bookings match your search' : ($status ? "No {$status} bookings" : 'No bookings yet') ?>
      </h4>
      <?php if ($search || $status): ?>
      <a href="<?= url('portal/bookings.php') ?>" class="btn btn-outline btn-sm" style="margin-top:12px">Clear filters</a>
      <?php else: ?>
      <p style="color:var(--clr-muted);font-size:.875rem;margin-bottom:20px">Start your journey today!</p>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold">Browse Packages</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
