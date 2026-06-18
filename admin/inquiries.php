<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    verifyCsrf();
    DB::update('inquiries', ['status' => 'read'], ['id' => (int)$_POST['inquiry_id']]);
    redirect(url('admin/inquiries.php'));
}

$status = trim($_GET['status'] ?? '');
$search = trim($_GET['s'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($status) { $where[] = 'status=?'; $params[] = $status; }
if ($search) { $where[] = '(name LIKE ? OR email LIKE ? OR message LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$baseQuery = "FROM inquiries WHERE " . implode(' AND ', $where);
$total  = (int)DB::value("SELECT COUNT(*) $baseQuery", $params);
$perPage = 15;
$pages  = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$inquiries = DB::rows("SELECT * $baseQuery ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);
$newCount  = DB::value("SELECT COUNT(*) FROM inquiries WHERE status='new'");

$pageTitle = 'Inquiries | MT Safaris Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-wrapper">
<header class="admin-header">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <div class="admin-header-title">Inquiries <?php if ($newCount): ?><span class="badge" style="background:#ef4444;color:#fff;margin-left:8px"><?= $newCount ?> new</span><?php endif; ?></div>
</header>
<main class="admin-main">
<?php echo renderFlash(); ?>

<!-- Status Tabs -->
<div style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
  <?php foreach ([''=> 'All', 'new'=>'New', 'read'=>'Read', 'replied'=>'Replied', 'closed'=>'Closed'] as $v=>$l): ?>
  <a href="?status=<?= $v ?>&s=<?= urlencode($search) ?>" class="btn btn-sm <?= $status===$v ? 'btn-admin-primary' : 'btn-admin-outline' ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<!-- Search -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="GET" style="display:flex;gap:12px">
      <input type="hidden" name="status" value="<?= h($status) ?>">
      <input type="text" name="s" class="admin-input" value="<?= h($search) ?>" placeholder="Search by name, email, message..." style="flex:1">
      <button type="submit" class="btn btn-admin-primary btn-sm">Search</button>
      <?php if ($search): ?><a href="?status=<?= h($status) ?>" class="btn btn-admin-outline btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><h3>Inquiries <span class="badge"><?= $total ?></span></h3></div>
  <div class="admin-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead><tr><th>Name</th><th>Email</th><th>Type</th><th>Subject / Message</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($inquiries): ?>
          <?php foreach ($inquiries as $inq): ?>
          <tr style="<?= $inq['status']==='new' ? 'background:#fffbf0' : '' ?>">
            <td>
              <?php if ($inq['status']==='new'): ?><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#ef4444;margin-right:6px"></span><?php endif; ?>
              <strong><?= h($inq['name']) ?></strong>
              <?php if ($inq['phone']): ?><div style="font-size:.75rem;color:var(--admin-muted)"><?= h($inq['phone']) ?></div><?php endif; ?>
            </td>
            <td><a href="mailto:<?= h($inq['email']) ?>" style="color:var(--admin-primary)"><?= h($inq['email']) ?></a></td>
            <td><span class="sb-<?= $inq['type']==='corporate'?'confirmed':'draft' ?>"><?= ucfirst($inq['type']??'general') ?></span></td>
            <td>
              <?php if ($inq['subject']): ?><div style="font-weight:500;font-size:.85rem;color:var(--admin-text);margin-bottom:3px"><?= h(excerpt($inq['subject'],50)) ?></div><?php endif; ?>
              <div style="font-size:.8rem;color:var(--admin-muted)"><?= h(excerpt(strip_tags($inq['message']??''),80)) ?></div>
            </td>
            <td><span class="sb-<?= $inq['status']==='new'?'pending':($inq['status']==='replied'?'confirmed':'draft') ?>"><?= ucfirst($inq['status']??'new') ?></span></td>
            <td><div style="font-size:.8rem"><?= formatDate($inq['created_at'],'M j, Y') ?></div><div style="font-size:.7rem;color:var(--admin-muted)"><?= timeAgo($inq['created_at']) ?></div></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="<?= url('admin/inquiry-view.php?id='.$inq['id']) ?>" class="btn btn-admin-primary btn-xs"><i class="fas fa-eye"></i> View</a>
                <?php if ($inq['status']==='new'): ?>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?><input type="hidden" name="inquiry_id" value="<?= $inq['id'] ?>">
                  <button type="submit" name="mark_read" class="btn btn-admin-outline btn-xs"><i class="fas fa-check"></i></button>
                </form>
                <?php endif; ?>
                <a href="mailto:<?= h($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject']??'Your Inquiry') ?>" class="btn btn-admin-outline btn-xs" title="Reply"><i class="fas fa-reply"></i></a>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--admin-muted)"><i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:8px"></i>No inquiries found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
    <div style="padding:16px 20px"><?= paginationHtml($total, $pages, $page, url('admin/inquiries.php?status='.$status.'&s='.urlencode($search))) ?></div>
    <?php endif; ?>
  </div>
</div>
</main>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
