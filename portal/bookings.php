<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['b.user_id = ?'];
$params = [$user['id']];
if ($status) { $where[] = 'b.status = ?'; $params[] = $status; }

$sql = "SELECT b.*, p.title AS package_title, p.hero_image, p.duration_days, d.name AS destination_name
        FROM bookings b
        JOIN packages p ON b.package_id=p.id
        LEFT JOIN destinations d ON p.destination_id=d.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC";

$result  = DB::paginate($sql, $params, $page, 10);
$bookings = $result['rows'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Bookings — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="portal-layout">
  <aside class="portal-sidebar">
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:10px;margin-bottom:28px;text-decoration:none">
      <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
      <div class="logo-text">MT Safaris</div>
    </a>
    <nav class="portal-nav">
      <a href="<?= url('portal/') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= url('portal/bookings.php') ?>" class="active"><i class="fas fa-ticket-alt"></i> My Bookings</a>
      <a href="<?= url('portal/wishlist.php') ?>"><i class="fas fa-heart"></i> Wishlist</a>
      <a href="<?= url('portal/profile.php') ?>"><i class="fas fa-user"></i> My Profile</a>
      <a href="<?= url('portal/logout.php') ?>" style="color:rgba(255,80,80,.8)"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </nav>
  </aside>
  <main class="portal-main">
    <div class="flex-between" style="margin-bottom:24px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.4rem;color:var(--clr-primary)">My Bookings</h1>
        <p style="color:var(--clr-muted);font-size:.82rem"><?= $result['total'] ?> total bookings</p>
      </div>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-sm"><i class="fas fa-plus"></i> New Booking</a>
    </div>

    <!-- Status Filter -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px">
      <?php foreach ([''=> 'All', 'pending'=>'Pending','confirmed'=>'Confirmed','paid'=>'Paid','completed'=>'Completed','cancelled'=>'Cancelled'] as $s=>$label): ?>
      <a href="?status=<?= $s ?>" class="cat-tab <?= $status===$s?'active':'' ?>" style="padding:8px 16px;font-size:.8rem"><?= $label ?></a>
      <?php endforeach; ?>
    </div>

    <?php if ($bookings): ?>
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php foreach ($bookings as $b):
        $lead = jd($b['lead_traveler']);
        $cls  = ['pending'=>'warning','confirmed'=>'info','paid'=>'success','cancelled'=>'danger','completed'=>'success'];
      ?>
      <div class="card">
        <div class="card-body" style="padding:20px">
          <div style="display:grid;grid-template-columns:80px 1fr auto;gap:16px;align-items:center;flex-wrap:wrap">
            <img src="<?= h($b['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=200&q=70') ?>"
                 style="width:80px;height:64px;object-fit:cover;border-radius:10px" alt="">
            <div>
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
                <h4 style="font-size:.95rem;color:var(--clr-primary)"><?= h($b['package_title']) ?></h4>
                <span class="badge badge-<?= $cls[$b['status']]??'muted' ?>"><?= ucfirst($b['status']) ?></span>
              </div>
              <div style="display:flex;gap:16px;flex-wrap:wrap">
                <span style="font-size:.78rem;color:var(--clr-muted)"><i class="fas fa-hashtag" style="color:var(--clr-gold)"></i> <?= h($b['reference']) ?></span>
                <span style="font-size:.78rem;color:var(--clr-muted)"><i class="fas fa-calendar-alt" style="color:var(--clr-sky)"></i> <?= formatDate($b['travel_date']) ?></span>
                <span style="font-size:.78rem;color:var(--clr-muted)"><i class="fas fa-users" style="color:var(--clr-sky)"></i> <?= $b['adults'] ?> Adult<?= $b['adults']>1?'s':'' ?></span>
                <span style="font-size:.78rem;color:var(--clr-muted)"><i class="fas fa-map-marker-alt" style="color:var(--clr-sky)"></i> <?= h($b['destination_name']??'') ?></span>
              </div>
            </div>
            <div style="text-align:right">
              <div style="font-size:1.1rem;font-weight:800;color:var(--clr-primary);font-family:var(--ff-head)"><?= money($b['total_amount']) ?></div>
              <div style="font-size:.72rem;color:var(--clr-muted);margin-bottom:10px">Total Amount</div>
              <a href="<?= url('portal/booking-view.php?ref=' . h($b['reference'])) ?>" class="btn btn-primary btn-sm">View Details</a>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php echo paginationHtml($result['total'], $result['pages'], $result['page'], url('portal/bookings.php?status='.$status)); ?>
    <?php else: ?>
    <div class="card text-center" style="padding:60px 20px">
      <i class="fas fa-ticket-alt" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
      <h4 style="color:var(--clr-primary);margin-bottom:8px">No bookings <?= $status?'with this status':'' ?></h4>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold" style="margin-top:16px">Browse Packages</a>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
