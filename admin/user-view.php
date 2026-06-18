<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin(); requireRole('super_admin');

$id   = (int)($_GET['id'] ?? 0);
$user = $id ? DB::row("SELECT * FROM users WHERE id=?", [$id]) : null;
if (!$user) { flash('danger', 'User not found.'); redirect(url('admin/users.php')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (isset($_POST['change_status'])) {
        $newStatus = in_array($_POST['new_status'], ['active','inactive','suspended']) ? $_POST['new_status'] : 'active';
        DB::update('users', ['status' => $newStatus], ['id' => $id]);
        auditLog('update', 'users', $id, ['status' => $user['status']], ['status' => $newStatus]);
        flash('success', 'Status updated.');
        redirect(url('admin/user-view.php?id=' . $id));
    }
}

$bookings = DB::rows("SELECT b.*, p.title AS pkg_title, p.slug AS pkg_slug
                       FROM bookings b JOIN packages p ON b.package_id=p.id
                       WHERE b.user_id=? ORDER BY b.created_at DESC LIMIT 10", [$id]);
$wishlist = DB::rows("SELECT w.*, p.title, p.slug, p.base_price, p.hero_image
                       FROM wishlists w JOIN packages p ON w.package_id=p.id
                       WHERE w.user_id=?", [$id]);
$documents = DB::rows("SELECT * FROM user_documents WHERE user_id=? ORDER BY created_at DESC", [$id]);
$totalSpent = DB::value("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE user_id=? AND status IN ('paid','completed')", [$id]);
$loginSessions = DB::rows("SELECT * FROM user_sessions WHERE user_id=? ORDER BY created_at DESC LIMIT 5", [$id]);

$statusColors = ['active'=>'badge-success','inactive'=>'badge-warning','suspended'=>'badge-danger','pending'=>'badge-secondary'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($user['first_name'].' '.$user['last_name']) ?> — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">
        <a href="<?= url('admin/users.php') ?>">Users</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <span><?= h($user['first_name'].' '.$user['last_name']) ?></span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/user-edit.php?id='.$id) ?>" class="btn-admin btn-admin-primary">
        <i class="fas fa-edit"></i> Edit User
      </a>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>

    <div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

      <!-- Profile card -->
      <div style="display:flex;flex-direction:column;gap:16px">
        <div class="admin-card">
          <div class="admin-card-body" style="text-align:center;padding:28px">
            <?php if ($user['avatar']): ?>
            <img src="<?= h($user['avatar']) ?>" style="width:80px;height:80px;border-radius:50%;object-fit:cover;margin-bottom:14px">
            <?php else: ?>
            <div style="width:80px;height:80px;border-radius:50%;background:var(--clr-primary);display:grid;place-items:center;font-size:2rem;font-weight:700;color:#fff;margin:0 auto 14px">
              <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
            </div>
            <?php endif; ?>
            <h3 style="font-size:1.1rem;color:var(--clr-primary);margin-bottom:4px"><?= h($user['first_name'].' '.$user['last_name']) ?></h3>
            <div style="font-size:.8rem;color:var(--clr-muted);margin-bottom:12px"><?= h(ROLES[$user['role']] ?? $user['role']) ?></div>
            <span class="badge <?= $statusColors[$user['status']] ?? 'badge-secondary' ?>" style="padding:5px 14px;border-radius:20px;font-size:.8rem"><?= ucfirst($user['status']) ?></span>
          </div>
        </div>

        <!-- Contact -->
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-address-card" style="color:var(--clr-gold)"></i> Contact</div>
          <div class="admin-card-body" style="padding:12px 16px">
            <?php foreach ([
              ['fas fa-envelope','Email',$user['email'],"mailto:{$user['email']}"],
              ['fas fa-phone','Phone',$user['phone']??'—',$user['phone']?"tel:{$user['phone']}":null],
              ['fas fa-calendar','Joined',formatDate($user['created_at'],'M j, Y'),null],
              ['fas fa-clock','Last login',$user['last_login_at']?timeAgo($user['last_login_at']):'Never',null],
              ['fas fa-shield-alt','Email verified',$user['email_verified']?'Yes':'No',null],
            ] as [$icon,$label,$value,$href]): ?>
            <div style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid var(--clr-border)">
              <i class="<?= $icon ?>" style="width:20px;color:var(--clr-gold);font-size:.85rem"></i>
              <div style="flex:1">
                <div style="font-size:.68rem;color:var(--clr-muted);text-transform:uppercase;font-weight:600"><?= $label ?></div>
                <?php if ($href): ?><a href="<?= h($href) ?>" style="font-size:.82rem;color:var(--clr-primary)"><?= h($value) ?></a>
                <?php else: ?><div style="font-size:.82rem"><?= h($value) ?></div><?php endif; ?>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Quick stats -->
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-chart-bar" style="color:var(--clr-gold)"></i> Activity</div>
          <div class="admin-card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;padding:16px">
            <?php foreach ([
              [count($bookings),'Bookings','fas fa-ticket-alt','#e0f2fe','#0369a1'],
              [money($totalSpent),'Total Spent','fas fa-dollar-sign','#f0fff4','#276749'],
              [count($wishlist),'Wishlist','fas fa-heart','#fff1f2','#be185d'],
              [count($documents),'Documents','fas fa-file','#fff8e1','#92400e'],
            ] as [$val,$label,$icon,$bg,$color]): ?>
            <div style="background:<?= $bg ?>;border-radius:10px;padding:14px;text-align:center">
              <i class="<?= $icon ?>" style="color:<?= $color ?>;font-size:1.1rem;margin-bottom:6px;display:block"></i>
              <div style="font-size:1.1rem;font-weight:800;color:<?= $color ?>"><?= $val ?></div>
              <div style="font-size:.7rem;color:<?= $color ?>;opacity:.8"><?= $label ?></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Change Status -->
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-toggle-on" style="color:var(--clr-gold)"></i> Change Status</div>
          <div class="admin-card-body">
            <form method="POST">
              <?= csrfField() ?>
              <div class="form-group">
                <select name="new_status" class="form-control">
                  <?php foreach (['active','inactive','suspended'] as $s): ?>
                  <option value="<?= $s ?>" <?= $user['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <button type="submit" name="change_status" class="btn-admin btn-admin-primary btn-block">Update Status</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Right: Bookings, sessions -->
      <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Recent Bookings -->
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-ticket-alt" style="color:var(--clr-gold)"></i> Bookings</div>
          <div class="admin-card-body" style="padding:0">
            <table class="admin-table">
              <thead><tr><th>Reference</th><th>Package</th><th>Travel Date</th><th>Amount</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php if ($bookings): foreach ($bookings as $b): ?>
                <tr>
                  <td><span style="font-family:monospace;font-size:.8rem;color:var(--clr-primary)"><?= h($b['reference']) ?></span></td>
                  <td style="font-size:.82rem"><a href="<?= url('package-detail.php?slug='.h($b['pkg_slug'])) ?>" target="_blank"><?= h(excerpt($b['pkg_title'],40)) ?></a></td>
                  <td style="font-size:.78rem;color:var(--clr-muted)"><?= formatDate($b['travel_date'],'M j, Y') ?></td>
                  <td style="font-weight:700;color:var(--clr-gold)"><?= money($b['total_amount']) ?></td>
                  <td>
                    <?php $bc=['pending'=>'badge-warning','confirmed'=>'badge-success','paid'=>'badge-success','completed'=>'badge-primary','cancelled'=>'badge-danger']; ?>
                    <span class="badge <?= $bc[$b['status']]??'badge-secondary' ?>" style="padding:3px 8px;border-radius:20px;font-size:.7rem"><?= ucfirst($b['status']) ?></span>
                  </td>
                  <td><a href="<?= url('admin/booking-view.php?ref='.$b['reference']) ?>" class="btn-icon-admin"><i class="fas fa-eye"></i></a></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--clr-muted)">No bookings yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Wishlist -->
        <?php if ($wishlist): ?>
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-heart" style="color:var(--clr-gold)"></i> Wishlist (<?= count($wishlist) ?>)</div>
          <div class="admin-card-body" style="display:flex;flex-wrap:wrap;gap:10px">
            <?php foreach ($wishlist as $w): ?>
            <a href="<?= url('package-detail.php?slug='.h($w['slug'])) ?>" target="_blank" style="display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid var(--clr-border);border-radius:8px;padding:8px 12px;text-decoration:none">
              <?php if ($w['hero_image']): ?><img src="<?= h($w['hero_image']) ?>" style="width:36px;height:28px;object-fit:cover;border-radius:4px"><?php endif; ?>
              <div>
                <div style="font-size:.8rem;font-weight:600;color:var(--clr-primary)"><?= h(excerpt($w['title'],30)) ?></div>
                <div style="font-size:.72rem;color:var(--clr-muted)"><?= money($w['base_price']) ?></div>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Login Sessions -->
        <div class="admin-card">
          <div class="admin-card-header"><i class="fas fa-history" style="color:var(--clr-gold)"></i> Recent Login Sessions</div>
          <div class="admin-card-body" style="padding:0">
            <table class="admin-table">
              <thead><tr><th>IP Address</th><th>Device / User Agent</th><th>Date</th></tr></thead>
              <tbody>
                <?php if ($loginSessions): foreach ($loginSessions as $sess): ?>
                <tr>
                  <td><span style="font-family:monospace;font-size:.8rem"><?= h($sess['ip_address'] ?? '—') ?></span></td>
                  <td style="font-size:.75rem;color:var(--clr-muted)"><div style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= h($sess['user_agent'] ?? '—') ?></div></td>
                  <td style="font-size:.75rem;color:var(--clr-muted)"><?= timeAgo($sess['created_at'] ?? $sess['last_used_at'] ?? '') ?></td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3" style="text-align:center;padding:24px;color:var(--clr-muted)">No sessions recorded.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
