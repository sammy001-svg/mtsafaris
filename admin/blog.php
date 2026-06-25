<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    verifyCsrf();
    $bid     = (int)$_POST['post_id'];
    $current = DB::value("SELECT status FROM blog_posts WHERE id=?", [$bid]);
    $new     = $current === 'published' ? 'draft' : 'published';
    $upd     = ['status' => $new];
    if ($new === 'published') $upd['published_at'] = date('Y-m-d H:i:s');
    DB::update('blog_posts', $upd, ['id' => $bid]);
    auditLog('toggle_status', 'blog_posts', $bid, ['status' => $current], $upd);
    flash('success', 'Post status updated.');
    redirect(url('admin/blog.php'));
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    verifyCsrf();
    $bid = (int)$_POST['post_id'];
    DB::delete('blog_posts', ['id' => $bid]);
    auditLog('delete', 'blog_posts', $bid, [], []);
    flash('success', 'Post deleted.');
    redirect(url('admin/blog.php'));
}

$search = trim($_GET['s']      ?? '');
$status = trim($_GET['status'] ?? '');
$catId  = (int)($_GET['cat']   ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(bp.title LIKE ? OR bp.excerpt LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status) { $where[] = 'bp.status=?'; $params[] = $status; }
if ($catId)  { $where[] = 'bp.category_id=?'; $params[] = $catId; }

$sql = "SELECT bp.id, bp.title, bp.slug, bp.status, bp.is_featured, bp.views,
               bp.created_at, bp.published_at,
               bc.name AS category_name,
               CONCAT(u.first_name,' ',u.last_name) AS author_name
        FROM blog_posts bp
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id
        LEFT JOIN users u ON bp.author_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY bp.created_at DESC";

$result     = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$posts      = $result['rows'];
$categories = DB::rows("SELECT * FROM blog_categories ORDER BY sort_order");
$qs         = http_build_query(array_filter(['s' => $search, 'status' => $status, 'cat' => $catId]));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Blog Posts — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body class="admin-body">
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">
        <a href="<?= url('admin/') ?>">Admin</a>
        <i class="fas fa-chevron-right"></i>
        <span>Blog Posts</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/blog-edit.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New Post
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>">
      <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">Blog Posts</div>
        <div class="page-subtitle">
          Manage articles, travel guides, and news —
          <?= $result['total'] ?> post<?= $result['total'] !== 1 ? 's' : '' ?> total
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-body">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <div style="flex:1;min-width:200px">
            <label class="form-label">Search</label>
            <div class="input-group">
              <i class="ig-icon fas fa-search"></i>
              <input type="text" name="s" class="form-control"
                     value="<?= h($search) ?>" placeholder="Search titles or excerpts…">
            </div>
          </div>
          <div>
            <label class="form-label">Status</label>
            <select name="status" class="form-control">
              <option value="">All Status</option>
              <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
              <option value="draft"     <?= $status === 'draft'     ? 'selected' : '' ?>>Draft</option>
              <option value="archived"  <?= $status === 'archived'  ? 'selected' : '' ?>>Archived</option>
            </select>
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="cat" class="form-control">
              <option value="">All Categories</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $catId == $cat['id'] ? 'selected' : '' ?>>
                <?= h($cat['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="display:flex;gap:8px">
            <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">
              <i class="fas fa-filter"></i> Filter
            </button>
            <a href="<?= url('admin/blog.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
              <i class="fas fa-times"></i> Reset
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-card">
      <div class="admin-card-body" style="padding:0">
        <div style="overflow-x:auto">
          <table class="admin-table">
            <thead>
              <tr>
                <th width="46">#</th>
                <th>Post</th>
                <th>Category</th>
                <th>Author</th>
                <th>Status</th>
                <th>Views</th>
                <th>Published</th>
                <th width="110">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($posts): foreach ($posts as $i => $post): ?>
              <tr>
                <td class="td-secondary"><?= ($result['page'] - 1) * ADMIN_PER_PAGE + $i + 1 ?></td>
                <td>
                  <div class="td-primary"><?= h(excerpt($post['title'], 65)) ?></div>
                  <div class="td-secondary">/<?= h($post['slug']) ?></div>
                  <?php if ($post['is_featured']): ?>
                  <span style="font-size:.65rem;font-weight:700;color:var(--clr-gold);text-transform:uppercase;letter-spacing:.05em">
                    <i class="fas fa-star"></i> Featured
                  </span>
                  <?php endif; ?>
                </td>
                <td class="td-secondary"><?= h($post['category_name'] ?? '—') ?></td>
                <td class="td-secondary"><?= h($post['author_name']   ?? 'System') ?></td>
                <td>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                    <button type="submit" name="toggle_status"
                            class="status-badge <?= $post['status'] === 'published' ? 'sb-active' : 'sb-inactive' ?>"
                            style="background:none;border:none;cursor:pointer">
                      <?= $post['status'] === 'published' ? 'Published' : ucfirst($post['status']) ?>
                    </button>
                  </form>
                </td>
                <td class="td-secondary"><?= number_format($post['views']) ?></td>
                <td class="td-secondary">
                  <?= $post['published_at'] ? formatDate($post['published_at'], 'M j, Y') : '—' ?>
                </td>
                <td>
                  <div class="tbl-actions">
                    <?php if ($post['status'] === 'published'): ?>
                    <a href="<?= url('blog-detail.php?slug=' . h($post['slug'])) ?>" target="_blank"
                       class="btn-tbl" title="View live"><i class="fas fa-eye"></i></a>
                    <?php endif; ?>
                    <a href="<?= url('admin/blog-edit.php?id=' . $post['id']) ?>"
                       class="btn-tbl" title="Edit"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('Delete this post permanently?')">
                      <?= csrfField() ?>
                      <input type="hidden" name="post_id" value="<?= $post['id'] ?>">
                      <button type="submit" name="delete_post" class="btn-tbl btn-tbl-danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr>
                <td colspan="8" style="text-align:center;padding:52px 20px;color:var(--clr-muted)">
                  <i class="fas fa-blog" style="font-size:2.2rem;display:block;margin-bottom:12px;opacity:.3"></i>
                  No blog posts found.
                  <br>
                  <a href="<?= url('admin/blog-edit.php') ?>"
                     class="btn-admin btn-admin-primary btn-admin-sm" style="margin-top:14px">
                    Write your first post
                  </a>
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($result['pages'] > 1): ?>
        <div style="padding:16px 20px;border-top:1px solid var(--clr-border)">
          <?= paginationHtml($result['total'], $result['pages'], $result['page'],
              url('admin/blog.php?' . $qs)) ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
