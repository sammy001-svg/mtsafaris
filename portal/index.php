<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$bookings     = DB::rows(
    "SELECT b.*, p.title AS package_title, p.hero_image, d.name AS destination_name
     FROM bookings b JOIN packages p ON b.package_id=p.id
     LEFT JOIN destinations d ON p.destination_id=d.id
     WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 5",
    [$user['id']]
);
$upcoming     = (int)DB::value("SELECT COUNT(*) FROM bookings WHERE user_id=? AND travel_date>=CURDATE() AND status IN ('confirmed','paid')", [$user['id']]);
$totalBookings= (int)DB::value("SELECT COUNT(*) FROM bookings WHERE user_id=?", [$user['id']]);
$totalSpent   = (float)(DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE user_id=? AND status IN ('paid','completed')", [$user['id']]) ?? 0);
$wishlistCount= (int)DB::value("SELECT COUNT(*) FROM wishlists WHERE user_id=?", [$user['id']]);
$notifications= DB::rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 5", [$user['id']]);
$unread       = (int)DB::value("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$user['id']]);

function bkStatusBadge(string $s): string {
    $map = [
        'pending'   => ['warning', '#92400e','#fef3c7'],
        'confirmed' => ['info',    '#1e40af','#dbeafe'],
        'paid'      => ['success', '#065f46','#d1fae5'],
        'completed' => ['success', '#065f46','#d1fae5'],
        'cancelled' => ['danger',  '#991b1b','#fee2e2'],
    ];
    [$type, $clr, $bg] = $map[$s] ?? ['muted','#374151','#f3f4f6'];
    return "<span style=\"display:inline-block;padding:2px 10px;border-radius:20px;font-size:.7rem;font-weight:700;background:{$bg};color:{$clr}\">".ucfirst($s).'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Dashboard — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style>
    .dash-stat { background:#fff;border:1px solid var(--clr-border);border-radius:14px;padding:20px;display:flex;align-items:center;gap:16px;box-shadow:0 1px 4px rgba(0,0,0,.04) }
    .dash-stat-icon { width:48px;height:48px;border-radius:12px;display:grid;place-items:center;font-size:1.2rem;flex-shrink:0 }
    .dash-stat-value { font-size:1.5rem;font-weight:800;color:var(--clr-primary);line-height:1 }
    .dash-stat-label { font-size:.72rem;color:var(--clr-muted);text-transform:uppercase;letter-spacing:.05em;margin-top:4px }
    .booking-row { display:grid;grid-template-columns:56px 1fr auto;gap:14px;align-items:center;padding:14px 20px;border-bottom:1px solid var(--clr-border) }
    .booking-row:last-child { border-bottom:none }
    .notif-row { display:flex;gap:12px;padding:13px 20px;border-bottom:1px solid var(--clr-border);align-items:flex-start }
    .notif-row:last-child { border-bottom:none }
  </style>
</head>
<body>
<div class="portal-layout">
  <?php require_once 'includes/sidebar.php'; ?>

  <main class="portal-main">
    <!-- Topbar -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px">
      <div>
        <h1 style="font-size:1.4rem;color:var(--clr-primary);margin-bottom:2px">Welcome back, <?= h($user['first_name']) ?>!</h1>
        <p style="color:var(--clr-muted);font-size:.82rem"><?= date('l, F j, Y') ?> — Your next adventure awaits.</p>
      </div>
      <a href="<?= url('packages.php') ?>" class="btn btn-gold"><i class="fas fa-plus"></i> Book a Trip</a>
    </div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px">
      <div class="dash-stat">
        <div class="dash-stat-icon" style="background:#dbeafe"><i class="fas fa-suitcase" style="color:#1e40af"></i></div>
        <div>
          <div class="dash-stat-value"><?= $totalBookings ?></div>
          <div class="dash-stat-label">Total Bookings</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon" style="background:#dcfce7"><i class="fas fa-plane" style="color:#16a34a"></i></div>
        <div>
          <div class="dash-stat-value"><?= $upcoming ?></div>
          <div class="dash-stat-label">Upcoming Trips</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon" style="background:#fef3c7"><i class="fas fa-dollar-sign" style="color:#d97706"></i></div>
        <div>
          <div class="dash-stat-value" style="font-size:1.2rem"><?= money($totalSpent) ?></div>
          <div class="dash-stat-label">Total Spent</div>
        </div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-icon" style="background:#fce7f3"><i class="fas fa-heart" style="color:#be185d"></i></div>
        <div>
          <div class="dash-stat-value"><?= $wishlistCount ?></div>
          <div class="dash-stat-label">Saved Packages</div>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">

      <!-- Recent Bookings -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-ticket-alt" style="color:var(--clr-gold);margin-right:6px"></i>Recent Bookings</h3>
          <a href="<?= url('portal/bookings.php') ?>" style="font-size:.78rem;color:var(--clr-gold);font-weight:600">View All</a>
        </div>
        <?php if ($bookings): ?>
        <div>
          <?php foreach ($bookings as $b):
            $lead = jd($b['lead_traveler'] ?? '{}', []);
          ?>
          <div class="booking-row">
            <img src="<?= h($b['hero_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=100&q=70') ?>"
                 style="width:56px;height:44px;object-fit:cover;border-radius:8px" alt="" loading="lazy" decoding="async">
            <div>
              <div style="font-weight:600;font-size:.85rem;color:var(--clr-primary);margin-bottom:3px"><?= h($b['package_title']) ?></div>
              <div style="display:flex;gap:10px;flex-wrap:wrap">
                <span style="font-size:.72rem;color:var(--clr-muted);font-family:monospace"><?= h($b['reference']) ?></span>
                <span style="font-size:.72rem;color:var(--clr-muted)"><i class="fas fa-calendar-alt" style="color:var(--clr-gold)"></i> <?= formatDate($b['travel_date'],'M j, Y') ?></span>
                <?php if ($b['destination_name']): ?><span style="font-size:.72rem;color:var(--clr-muted)"><i class="fas fa-map-marker-alt" style="color:var(--clr-gold)"></i> <?= h($b['destination_name']) ?></span><?php endif; ?>
              </div>
            </div>
            <div style="text-align:right">
              <?= bkStatusBadge($b['status']) ?>
              <div style="font-size:.82rem;font-weight:700;color:var(--clr-primary);margin-top:4px"><?= money($b['total_amount']) ?></div>
              <a href="<?= url('portal/booking-view.php?ref='.h($b['reference'])) ?>" style="font-size:.72rem;color:var(--clr-gold);text-decoration:none">Details →</a>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card-body text-center" style="padding:60px 20px">
          <i class="fas fa-suitcase" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
          <h4 style="color:var(--clr-primary);margin-bottom:8px">No bookings yet</h4>
          <p style="color:var(--clr-muted);font-size:.875rem;margin-bottom:20px">Start your journey today!</p>
          <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-sm">Explore Packages</a>
        </div>
        <?php endif; ?>
      </div>

      <!-- Right column -->
      <div>

        <!-- Upcoming Trip Highlight -->
        <?php
        $next = DB::row("SELECT b.*, p.title AS package_title, p.hero_image FROM bookings b JOIN packages p ON b.package_id=p.id WHERE b.user_id=? AND b.travel_date>=CURDATE() AND b.status IN ('confirmed','paid') ORDER BY b.travel_date ASC LIMIT 1", [$user['id']]);
        if ($next):
          $daysUntil = (int)ceil((strtotime($next['travel_date']) - time()) / 86400);
        ?>
        <div class="card" style="margin-bottom:20px;overflow:hidden">
          <div style="position:relative;height:100px;overflow:hidden">
            <img src="<?= h($next['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=400&q=70') ?>"
                 style="width:100%;height:100%;object-fit:cover" alt="" loading="lazy" decoding="async">
            <div style="position:absolute;inset:0;background:linear-gradient(to bottom,transparent,rgba(15,23,42,.7))"></div>
            <div style="position:absolute;bottom:12px;left:14px;right:14px">
              <div style="color:#fff;font-weight:700;font-size:.85rem"><?= h($next['package_title']) ?></div>
            </div>
          </div>
          <div class="card-body" style="padding:14px 16px">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
              <span style="font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--clr-muted)">Next Trip</span>
              <span style="background:var(--clr-gold);color:#fff;font-size:.72rem;font-weight:700;padding:2px 10px;border-radius:20px"><?= $daysUntil ?> days away</span>
            </div>
            <div style="font-size:.8rem;color:var(--clr-muted)"><i class="fas fa-calendar" style="color:var(--clr-gold)"></i> <?= formatDate($next['travel_date'],'M j, Y') ?></div>
            <a href="<?= url('portal/booking-view.php?ref='.h($next['reference'])) ?>" class="btn btn-primary btn-block btn-sm" style="margin-top:12px">View Booking</a>
          </div>
        </div>
        <?php endif; ?>

        <!-- Recent Notifications -->
        <?php if ($notifications): ?>
        <div class="card">
          <div class="card-header">
            <h3 style="font-size:.9rem"><i class="fas fa-bell" style="color:var(--clr-gold)"></i> Notifications</h3>
            <?php if ($unread): ?><span style="background:var(--clr-danger);color:#fff;font-size:.65rem;font-weight:700;padding:2px 7px;border-radius:20px"><?= $unread ?> new</span><?php endif; ?>
          </div>
          <?php foreach ($notifications as $n): ?>
          <div class="notif-row" style="<?= !$n['is_read']?'background:rgba(59,130,246,.03)':'' ?>">
            <div style="width:32px;height:32px;border-radius:50%;background:<?= !$n['is_read']?'#dbeafe':'var(--clr-light)' ?>;display:grid;place-items:center;flex-shrink:0;color:<?= !$n['is_read']?'#1e40af':'var(--clr-muted)' ?>;font-size:.8rem">
              <i class="fas fa-bell"></i>
            </div>
            <div>
              <p style="font-size:.8rem;font-weight:<?= !$n['is_read']?'600':'400' ?>;color:var(--clr-text);margin:0 0 2px"><?= h($n['title']) ?></p>
              <p style="font-size:.7rem;color:var(--clr-muted);margin:0"><?= timeAgo($n['created_at']) ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Links -->
        <div class="card" style="margin-top:20px">
          <div class="card-header"><h3 style="font-size:.9rem">Quick Actions</h3></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:8px;padding:16px">
            <a href="<?= url('packages.php') ?>" class="btn btn-primary btn-sm"><i class="fas fa-compass"></i> Browse Packages</a>
            <a href="<?= url('portal/profile.php') ?>" class="btn btn-outline btn-sm"><i class="fas fa-user-cog"></i> Edit Profile</a>
            <a href="<?= url('contact.php') ?>" class="btn btn-outline btn-sm"><i class="fas fa-headset"></i> Contact Support</a>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
