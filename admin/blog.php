<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verifyCsrf();
    $bid = (int)$_POST['post_id'];
    $current = DB::value("SELECT status FROM blog_posts WHERE id=?", [$bid]);
    $new = $current === 'published' ? 'draft' : 'published';
    $update = ['status' => $new];
    if ($new === 'published') $update['published_at'] = date('Y-m-d H:i:s');
    DB::update('blog_posts', $update, ['id' => $bid]);
    auditLog('toggle_status', 'blog_posts', $bid, ['status' => $current], $update);
    flash('success', 'Post status updated.');
    redirect(url('admin/blog.php'));
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    verifyCsrf();
    $bid = (int)$_POST['post_id'];
    DB::delete('blog_posts', ['id' => $bid]);
    auditLog('delete', 'blog_posts', $bid, [], []);
    flash('success', 'Blog post deleted.');
    redirect(url('admin/blog.php'));
}

$search   = trim($_GET['s'] ?? '');
$status   = trim($_GET['status'] ?? '');
$catId    = (int)($_GET['cat'] ?? 0);
$page     = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];
if ($search)  { $where[] = '(bp.title LIKE ? OR bp.excerpt LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status)  { $where[] = 'bp.status=?'; $params[] = $status; }
if ($catId)   { $where[] = 'bp.category_id=?'; $params[] = $catId; }

$baseQuery = "FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id=bc.id LEFT JOIN users u ON bp.author_id=u.id WHERE " . implode(' AND ', $where);
$total = (int)DB::value("SELECT COUNT(*) $baseQuery", $params);
$perPage = 15;
$pages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$posts = DB::rows("SELECT bp.id, bp.title, bp.slug, bp.status, bp.is_featured, bp.views, bp.created_at, bp.published_at, bc.name AS category_name, CONCAT(u.first_name,' ',u.last_name) AS author_name $baseQuery ORDER BY bp.created_at DESC LIMIT $perPage OFFSET $offset", $params);

$categories = DB::rows("SELECT * FROM blog_categories ORDER BY sort_order");
$pageTitle  = 'Blog Posts | MT Safaris Admin';
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
  <div class="admin-header-title">Blog Posts</div>
  <div class="admin-header-actions">
    <a href="<?= url('admin/blog-edit.php') ?>" class="btn btn-admin-primary btn-sm"><i class="fas fa-plus"></i> New Post</a>
  </div>
</header>
<main class="admin-main">
<?php echo renderFlash(); ?>

<!-- Filters -->
<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:200px">
        <label class="admin-label">Search</label>
        <input type="text" name="s" class="admin-input" value="<?= h($search) ?>" placeholder="Search by title...">
      </div>
      <div>
        <label class="admin-label">Status</label>
        <select name="status" class="admin-select">
          <option value="">All Status</option>
          <option value="published" <?= $status==='published'?'selected':'' ?>>Published</option>
          <option value="draft" <?= $status==='draft'?'selected':'' ?>>Draft</option>
          <option value="archived" <?= $status==='archived'?'selected':'' ?>>Archived</option>
        </select>
      </div>
      <div>
        <label class="admin-label">Category</label>
        <select name="cat" class="admin-select">
          <option value="">All Categories</option>
          <?php foreach ($categories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= $catId==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-admin-primary btn-sm">Filter</button>
      <a href="<?= url('admin/blog.php') ?>" class="btn btn-admin-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
    <h3>Posts <span class="badge"><?= $total ?></span></h3>
  </div>
  <div class="admin-card-body" style="padding:0">
    <div style="overflow-x:auto">
      <table class="admin-table">
        <thead>
          <tr>
            <th width="50">#</th>
            <th>Title</th>
            <th>Category</th>
            <th>Author</th>
            <th>Status</th>
            <th>Views</th>
            <th>Published</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($posts): ?>
          <?php foreach ($posts as $i => $post): ?>
          <tr>
            <td><?= ($page-1)*$perPage + $i + 1 ?></td>
            <td>
              <div style="font-weight:600;color:var(--admin-text)"><?= h(excerpt($post['title'],60)) ?></div>
              <div style="font-size:.75rem;color:var(--admin-muted)">/<?= h($post['slug']) ?></div>
              <?php if ($post['is_featured']): ?><span class="admin-badge admin-badge-gold">Featured</span><?php endif; ?>
            </td>
            <td><?= h($post['category_name']??'—') ?></td>
            <td><?= h($post['author_name']??'System') ?></td>
            <td>
              <form method="POST" style="display:inline">
                <?= csrfField() ?>
                <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                <button type="submit" name="toggle_status" class="sb-<?= $post['status']==='published'?'published':'draft' ?>" style="background:none;border:none;cursor:pointer">
                  <?= ucfirst($post['status']) ?>
                </button>
              </form>
            </td>
            <td><?= number_format($post['views']) ?></td>
            <td><?= $post['published_at'] ? formatDate($post['published_at'],'M j, Y') : '—' ?></td>
            <td>
              <div style="display:flex;gap:6px">
                <?php if ($post['status']==='published'): ?>
                <a href="<?= url('blog-detail.php?slug='.h($post['slug'])) ?>" target="_blank" class="btn btn-admin-outline btn-xs" title="View"><i class="fas fa-eye"></i></a>
                <?php endif; ?>
                <a href="<?= url('admin/blog-edit.php?id='.$post['id']) ?>" class="btn btn-admin-primary btn-xs" title="Edit"><i class="fas fa-edit"></i></a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this post?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                  <button type="submit" name="delete_post" class="btn btn-danger btn-xs" title="Delete"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php else: ?>
          <tr><td colspan="8" style="text-align:center;padding:40px;color:var(--admin-muted)"><i class="fas fa-blog" style="font-size:2rem;margin-bottom:8px;display:block"></i>No blog posts found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($pages > 1): ?>
    <div style="padding:16px 20px"><?= paginationHtml($total, $pages, $page, url('admin/blog.php?s='.urlencode($search).'&status='.$status.'&cat='.$catId)) ?></div>
    <?php endif; ?>
  </div>
</div>
</main>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
