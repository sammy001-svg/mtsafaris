<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$user = currentUser();

// Mark all as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    verifyCsrf();
    DB::query("UPDATE notifications SET is_read=1 WHERE user_id=?", [$user['id']]);
    redirect(url('portal/notifications.php'));
}
// Mark single
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    verifyCsrf();
    DB::update('notifications', ['is_read'=>1], ['id'=>(int)$_POST['notification_id'],'user_id'=>$user['id']]);
    redirect(url('portal/notifications.php'));
}

$page   = max(1,(int)($_GET['page']??1));
$perPage = 20;
$total  = (int)DB::value("SELECT COUNT(*) FROM notifications WHERE user_id=?", [$user['id']]);
$pages  = max(1, ceil($total/$perPage));
$offset = ($page-1)*$perPage;
$notifications = DB::rows("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", [$user['id']]);
$unreadCount = DB::value("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$user['id']]);

$pageTitle = 'Notifications | MT Safaris';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="portal-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="portal-main">
    <?php echo renderFlash(); ?>
    <div class="portal-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px">
      <div>
        <h1>Notifications</h1>
        <?php if ($unreadCount): ?><p><?= $unreadCount ?> unread notification<?= $unreadCount!=1?'s':'' ?></p><?php endif; ?>
      </div>
      <?php if ($unreadCount): ?>
      <form method="POST">
        <?= csrfField() ?>
        <button type="submit" name="mark_all_read" class="btn btn-outline btn-sm"><i class="fas fa-check-double"></i> Mark All Read</button>
      </form>
      <?php endif; ?>
    </div>

    <?php if ($notifications): ?>
    <div class="card">
      <div class="card-body" style="padding:0">
        <?php foreach ($notifications as $notif): ?>
        <div style="display:flex;gap:14px;padding:16px 20px;border-bottom:1px solid var(--clr-border);background:<?= !$notif['is_read']?'#fffbf0':'#fff' ?>;transition:background .2s">
          <div style="width:40px;height:40px;border-radius:50%;background:<?= !$notif['is_read']?'var(--clr-gold)':'#e5e7eb' ?>;display:grid;place-items:center;flex-shrink:0">
            <i class="fas <?= in_array($notif['type']??'',['booking_confirmed','booking_update'])?'fa-calendar-check':($notif['type']==='payment'?'fa-credit-card':'fa-bell') ?>" style="color:<?= !$notif['is_read']?'var(--clr-primary)':'#6b7280' ?>;font-size:.875rem"></i>
          </div>
          <div style="flex:1">
            <div style="font-weight:<?= !$notif['is_read']?'600':'400' ?>;color:var(--clr-primary);margin-bottom:4px"><?= h($notif['title']??'Notification') ?></div>
            <p style="font-size:.875rem;color:var(--clr-muted);line-height:1.6;margin:0 0 6px"><?= h($notif['message']??'') ?></p>
            <div style="font-size:.75rem;color:var(--clr-muted)"><?= timeAgo($notif['created_at']) ?></div>
          </div>
          <?php if (!$notif['is_read']): ?>
          <form method="POST" style="flex-shrink:0">
            <?= csrfField() ?>
            <input type="hidden" name="notification_id" value="<?= $notif['id'] ?>">
            <button type="submit" name="mark_read" class="btn btn-sm btn-outline" style="white-space:nowrap">Mark read</button>
          </form>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php if ($pages > 1): ?>
    <div style="margin-top:20px"><?= paginationHtml($total, $pages, $page, url('portal/notifications.php')) ?></div>
    <?php endif; ?>
    <?php else: ?>
    <div class="portal-empty">
      <i class="fas fa-bell" style="font-size:3rem;color:var(--clr-gold);margin-bottom:16px"></i>
      <h3>No notifications yet</h3>
      <p>You'll receive notifications about your bookings, special offers, and travel updates here.</p>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
