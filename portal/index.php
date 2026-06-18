<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$bookings     = DB::rows("SELECT b.*, p.title AS package_title, p.hero_image, d.name AS destination_name
                          FROM bookings b JOIN packages p ON b.package_id=p.id
                          LEFT JOIN destinations d ON p.destination_id=d.id
                          WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 5", [$user['id']]);

$upcoming     = DB::value("SELECT COUNT(*) FROM bookings WHERE user_id=? AND travel_date>=CURDATE() AND status IN ('confirmed','paid')", [$user['id']]);
$totalBookings= DB::value("SELECT COUNT(*) FROM bookings WHERE user_id=?", [$user['id']]);
$totalSpent   = DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE user_id=? AND status IN ('paid','completed')", [$user['id']]);
$wishlistCount= DB::value("SELECT COUNT(*) FROM wishlists WHERE user_id=?", [$user['id']]);
$notifications= DB::rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5", [$user['id']]);
$unread       = DB::value("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$user['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>

<!-- Portal Layout -->
<div class="portal-layout">

  <!-- Sidebar -->
  <aside class="portal-sidebar" id="portalSidebar">
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:10px;margin-bottom:28px;text-decoration:none">
      <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
      <div class="logo-text">MT Safaris</div>
    </a>
    <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:14px 16px;margin-bottom:20px;display:flex;align-items:center;gap:12px">
      <div style="width:42px;height:42px;border-radius:50%;background:var(--clr-gold);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:1rem;flex-shrink:0">
        <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
      </div>
      <div>
        <div style="color:#fff;font-weight:600;font-size:.9rem"><?= h($user['first_name'].' '.$user['last_name']) ?></div>
        <div style="color:rgba(255,255,255,.55);font-size:.72rem"><?= h($user['email']) ?></div>
      </div>
    </div>

    <nav class="portal-nav">
      <div class="nav-section">Main</div>
      <a href="<?= url('portal/') ?>" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= url('portal/bookings.php') ?>"><i class="fas fa-ticket-alt"></i> My Bookings</a>
      <a href="<?= url('portal/wishlist.php') ?>"><i class="fas fa-heart"></i> Wishlist <?php if ($wishlistCount): ?><span style="background:var(--clr-gold);color:#fff;font-size:.6rem;padding:2px 6px;border-radius:10px;margin-left:auto"><?= $wishlistCount ?></span><?php endif; ?></a>

      <div class="nav-section" style="margin-top:8px">Account</div>
      <a href="<?= url('portal/profile.php') ?>"><i class="fas fa-user"></i> My Profile</a>
      <a href="<?= url('portal/documents.php') ?>"><i class="fas fa-file-alt"></i> Documents</a>
      <a href="<?= url('portal/notifications.php') ?>">
        <i class="fas fa-bell"></i> Notifications
        <?php if ($unread): ?><span style="background:var(--clr-danger);color:#fff;font-size:.6rem;padding:2px 6px;border-radius:10px;margin-left:auto"><?= $unread ?></span><?php endif; ?>
      </a>

      <div style="margin-top:auto;padding-top:24px">
        <a href="<?= url('packages.php') ?>"><i class="fas fa-compass"></i> Explore Packages</a>
        <a href="<?= url('contact.php') ?>"><i class="fas fa-headset"></i> Support</a>
        <a href="<?= url('portal/logout.php') ?>" style="color:rgba(255,80,80,.8)"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
      </div>
    </nav>
  </aside>

  <!-- Main Content -->
  <main class="portal-main">
    <!-- Topbar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.4rem;color:var(--clr-primary)">Welcome back, <?= h($user['first_name']) ?>! 👋</h1>
        <p style="color:var(--clr-muted);font-size:.875rem"><?= date('l, F j, Y') ?> — Your next adventure awaits.</p>
      </div>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold"><i class="fas fa-plus"></i> Book a Trip</a>
    </div>

    <!-- Stats Row -->
    <div class="grid-4" style="margin-bottom:24px">
      <?php
      $stats = [
        ['fas fa-ticket-alt','Total Bookings',$totalBookings,'blue'],
        ['fas fa-plane','Upcoming Trips',$upcoming,'sky'],
        ['fas fa-dollar-sign','Total Spent','$'.number_format($totalSpent,2),'gold'],
        ['fas fa-heart','Saved Packages',$wishlistCount,'green'],
      ];
      foreach ($stats as $s): ?>
      <div class="stat-box">
        <div class="stat-box-icon <?= $s[3] ?>"><i class="<?= $s[0] ?>"></i></div>
        <div>
          <div class="stat-box-value"><?= $s[2] ?></div>
          <div class="stat-box-label"><?= $s[1] ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Recent Bookings -->
    <div class="card" style="margin-bottom:24px">
      <div class="card-header">
        <h3>Recent Bookings</h3>
        <a href="<?= url('portal/bookings.php') ?>" style="font-size:.82rem;color:var(--clr-gold)">View All</a>
      </div>
      <?php if ($bookings): ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Package</th>
              <th>Travel Date</th>
              <th>Travelers</th>
              <th>Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <img src="<?= h($b['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=80&q=70') ?>" style="width:44px;height:36px;object-fit:cover;border-radius:6px" alt="">
                <div>
                  <div style="font-weight:600;font-size:.82rem;color:var(--clr-primary)"><?= h($b['package_title']) ?></div>
                  <div style="font-size:.72rem;color:var(--clr-muted)"><?= h($b['reference']) ?></div>
                </div>
              </div>
            </td>
            <td style="font-size:.82rem"><?= formatDate($b['travel_date']) ?></td>
            <td style="font-size:.82rem"><?= $b['adults'] ?> Adult<?= $b['adults']>1?'s':'' ?><?= $b['children']?' + '.$b['children'].' Child':'' ?></td>
            <td style="font-size:.82rem;font-weight:700"><?= money($b['total_amount']) ?></td>
            <td>
              <?php
              $cls = ['pending'=>'warning','confirmed'=>'info','paid'=>'success','cancelled'=>'danger','completed'=>'success'];
              ?>
              <span class="badge badge-<?= $cls[$b['status']]??'muted' ?>"><?= ucfirst($b['status']) ?></span>
            </td>
            <td>
              <a href="<?= url('portal/booking-view.php?ref=' . h($b['reference'])) ?>" class="action-btn view" title="View"><i class="fas fa-eye"></i></a>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div class="card-body text-center" style="padding:60px 20px">
        <i class="fas fa-ticket-alt" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
        <h4 style="color:var(--clr-primary);margin-bottom:8px">No bookings yet</h4>
        <p class="text-muted" style="margin-bottom:24px">Start your journey today!</p>
        <a href="<?= url('packages.php') ?>" class="btn btn-gold">Explore Packages</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- Notifications -->
    <?php if ($notifications): ?>
    <div class="card">
      <div class="card-header">
        <h3>Notifications</h3>
        <?php if ($unread): ?><span class="badge badge-danger"><?= $unread ?> new</span><?php endif; ?>
      </div>
      <div class="card-body" style="padding:0">
        <?php foreach ($notifications as $n): ?>
        <div style="display:flex;gap:12px;padding:14px 20px;border-bottom:1px solid var(--clr-border);<?= !$n['is_read']?'background:rgba(59,175,218,.04)':'' ?>">
          <div style="width:36px;height:36px;border-radius:50%;background:<?= !$n['is_read']?'rgba(59,175,218,.15)':'var(--clr-light)' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--clr-sky);font-size:.9rem">
            <i class="fas fa-bell"></i>
          </div>
          <div>
            <p style="font-size:.82rem;font-weight:<?= !$n['is_read']?'600':'400' ?>;color:var(--clr-text)"><?= h($n['title']) ?></p>
            <p style="font-size:.75rem;color:var(--clr-muted);margin-top:2px"><?= timeAgo($n['created_at']) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </main>
</div>

<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
