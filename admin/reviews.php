<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);
    if (isset($_POST['approve'])) {
        DB::update('reviews', ['is_approved' => 1], ['id' => $id]);
        flash('success', 'Review approved.');
        redirect(url('admin/reviews.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')));
    }
    if (isset($_POST['unapprove'])) {
        DB::update('reviews', ['is_approved' => 0], ['id' => $id]);
        flash('success', 'Review unapproved.');
        redirect(url('admin/reviews.php' . ($_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '')));
    }
    if (isset($_POST['toggle_featured'])) {
        $row = DB::row("SELECT is_featured FROM reviews WHERE id=?", [$id]);
        if ($row) DB::update('reviews', ['is_featured' => $row['is_featured'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/reviews.php'));
    }
    if (isset($_POST['reply'])) {
        $reply = trim($_POST['reply_text'] ?? '');
        DB::update('reviews', ['reply' => $reply, 'replied_at' => date('Y-m-d H:i:s')], ['id' => $id]);
        flash('success', 'Reply saved.');
        redirect(url('admin/reviews.php'));
    }
    if (isset($_POST['delete'])) {
        DB::delete('reviews', ['id' => $id]);
        auditLog('delete', 'reviews', $id, [], []);
        flash('success', 'Review deleted.');
        redirect(url('admin/reviews.php'));
    }
}

$status  = $_GET['status'] ?? '';
$pkgId   = (int)($_GET['pkg'] ?? 0);
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($status === 'pending')  { $where[] = 'r.is_approved=0'; }
if ($status === 'approved') { $where[] = 'r.is_approved=1'; }
if ($pkgId)   { $where[] = 'r.package_id=?'; $params[] = $pkgId; }
if ($search)  { $where[] = '(r.name LIKE ? OR r.body LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }

$sql    = "SELECT r.*, p.title AS pkg_title, p.slug AS pkg_slug
           FROM reviews r LEFT JOIN packages p ON r.package_id=p.id
           WHERE " . implode(' AND ', $where) . " ORDER BY r.created_at DESC";
$result = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$reviews = $result['rows'];

$pending  = DB::value("SELECT COUNT(*) FROM reviews WHERE is_approved=0");
$approved = DB::value("SELECT COUNT(*) FROM reviews WHERE is_approved=1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reviews — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Reviews</span></div>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Customer Reviews</div>

    <!-- Stat tabs -->
    <div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
      <?php foreach ([''=>['All', $result['total']+0,'#f8f9fc','#0D3B66'],'pending'=>['Pending', $pending,'#fff7ed','#c05621'],'approved'=>['Approved', $approved,'#f0fff4','#276749']] as $k=>$v): ?>
      <a href="?status=<?= $k ?><?= $search?"&search=$search":'' ?>" style="padding:14px 20px;border-radius:12px;background:<?= $status===$k?$v[2]:'#fff' ?>;color:<?= $status===$k?$v[3]:'var(--clr-muted)' ?>;border:2px solid <?= $status===$k?$v[2]:'var(--clr-border)' ?>;text-decoration:none;font-weight:600;display:flex;flex-direction:column;align-items:center;gap:4px;min-width:100px">
        <span style="font-size:1.4rem;font-weight:800"><?= $v[1] ?></span>
        <span style="font-size:.75rem"><?= $v[0] ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Search -->
    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-body" style="padding:12px">
        <form method="GET" style="display:flex;gap:10px">
          <?php if ($status): ?><input type="hidden" name="status" value="<?= h($status) ?>"><?php endif; ?>
          <div style="position:relative;flex:1">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search by name or content…" class="form-control" style="padding-left:36px">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          </div>
          <button type="submit" class="btn-admin btn-admin-secondary"><i class="fas fa-filter"></i> Filter</button>
        </form>
      </div>
    </div>

    <!-- Reviews -->
    <div style="display:flex;flex-direction:column;gap:16px">
      <?php if ($reviews): foreach ($reviews as $rev): ?>
      <div class="admin-card" style="border-left:4px solid <?= $rev['is_approved']?'#38a169':'#e53e3e' ?>">
        <div class="admin-card-body">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap">
            <div style="flex:1">
              <!-- Header -->
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;flex-wrap:wrap">
                <div style="width:36px;height:36px;border-radius:50%;background:var(--clr-primary);display:grid;place-items:center;font-weight:700;color:#fff;font-size:.8rem;flex-shrink:0"><?= strtoupper(substr($rev['name'],0,1)) ?></div>
                <div>
                  <div style="font-weight:700;color:var(--clr-primary)"><?= h($rev['name']) ?></div>
                  <div style="font-size:.72rem;color:var(--clr-muted)"><?= h($rev['email'] ?? '') ?> · <?= timeAgo($rev['created_at']) ?></div>
                </div>
                <div style="display:flex;gap:2px">
                  <?php for ($s=1;$s<=5;$s++): ?>
                  <i class="fas fa-star" style="color:<?= $s<=$rev['rating']?'var(--clr-gold)':'#e2e8f0' ?>;font-size:.75rem"></i>
                  <?php endfor; ?>
                </div>
                <?php if ($rev['pkg_title']): ?>
                <a href="<?= url('package-detail.php?slug='.h($rev['pkg_slug'])) ?>" target="_blank" style="font-size:.72rem;color:var(--clr-sky);background:#e0f2fe;padding:3px 8px;border-radius:20px"><?= h($rev['pkg_title']) ?></a>
                <?php endif; ?>
                <span class="badge <?= $rev['is_approved']?'badge-success':'badge-warning' ?>" style="font-size:.72rem;padding:3px 8px;border-radius:20px"><?= $rev['is_approved']?'Approved':'Pending' ?></span>
              </div>
              <!-- Title + Body -->
              <?php if ($rev['title']): ?><div style="font-weight:600;margin-bottom:6px"><?= h($rev['title']) ?></div><?php endif; ?>
              <p style="color:var(--clr-text);line-height:1.6;margin-bottom:12px"><?= h($rev['body']) ?></p>
              <!-- Reply -->
              <?php if ($rev['reply']): ?>
              <div style="background:#f8fafc;border-left:3px solid var(--clr-gold);padding:10px 14px;border-radius:0 8px 8px 0;margin-bottom:12px">
                <div style="font-size:.72rem;font-weight:700;color:var(--clr-gold);margin-bottom:4px"><i class="fas fa-reply"></i> MT SAFARIS REPLIED · <?= formatDate($rev['replied_at'],'M j, Y') ?></div>
                <div style="font-size:.85rem;color:var(--clr-text)"><?= h($rev['reply']) ?></div>
              </div>
              <?php endif; ?>
              <!-- Reply form (hidden toggle) -->
              <details style="margin-top:8px">
                <summary style="cursor:pointer;font-size:.8rem;color:var(--clr-primary);font-weight:600"><i class="fas fa-reply"></i> <?= $rev['reply']?'Edit Reply':'Add Reply' ?></summary>
                <form method="POST" style="margin-top:10px">
                  <?= csrfField() ?><input type="hidden" name="id" value="<?= $rev['id'] ?>">
                  <textarea name="reply_text" class="form-control" rows="3" placeholder="Write your reply…" style="margin-bottom:8px"><?= h($rev['reply'] ?? '') ?></textarea>
                  <button type="submit" name="reply" class="btn-admin btn-admin-primary btn-sm"><i class="fas fa-paper-plane"></i> Save Reply</button>
                </form>
              </details>
            </div>
            <!-- Actions -->
            <div style="display:flex;flex-direction:column;gap:8px;align-items:flex-end">
              <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $rev['id'] ?>">
                <?php if (!$rev['is_approved']): ?>
                <button type="submit" name="approve" class="btn-admin btn-admin-success btn-sm"><i class="fas fa-check"></i> Approve</button>
                <?php else: ?>
                <button type="submit" name="unapprove" class="btn-admin btn-sm" style="background:#fff7ed;color:#c05621;border:1px solid #fed7aa"><i class="fas fa-times"></i> Unapprove</button>
                <?php endif; ?>
              </form>
              <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $rev['id'] ?>">
                <button type="submit" name="toggle_featured" class="btn-admin btn-sm" style="background:<?= $rev['is_featured']?'#fff8e1':'#f8f9fc' ?>;color:<?= $rev['is_featured']?'var(--clr-gold)':'var(--clr-muted)' ?>;border:1px solid <?= $rev['is_featured']?'#fcd34d':'#e2e8f0' ?>">
                  <i class="fas fa-star"></i> <?= $rev['is_featured']?'Featured':'Feature' ?>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete this review?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= $rev['id'] ?>">
                <button type="submit" name="delete" class="btn-admin btn-sm" style="background:#fff5f5;color:var(--clr-danger);border:1px solid #feb2b2"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; else: ?>
      <div class="admin-card"><div class="admin-card-body" style="text-align:center;padding:60px;color:var(--clr-muted)">No reviews found.</div></div>
      <?php endif; ?>
    </div>

    <?php if ($result['pages'] > 1): ?>
    <div style="margin-top:20px">
      <?= paginationHtml($result['total'], $result['pages'], $result['page'], url('admin/reviews.php?' . http_build_query(array_filter(compact('status','search'))))) ?>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
