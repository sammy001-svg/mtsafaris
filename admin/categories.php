<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

    // Inline create / update
    if (isset($_POST['save'])) {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'slug'        => trim($_POST['slug'] ?? ''),
            'icon'        => trim($_POST['icon'] ?? ''),
            'color'       => trim($_POST['color'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['name']) $errors[] = 'Name is required.';
        if (!$data['slug']) $data['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name']));

        if (!$errors) {
            if ($id) {
                DB::update('categories', $data, ['id' => $id]);
                auditLog('update', 'categories', $id, [], $data);
                flash('success', 'Category updated.');
            } else {
                $newId = DB::insert('categories', $data);
                auditLog('create', 'categories', $newId, [], $data);
                flash('success', 'Category created.');
            }
            redirect(url('admin/categories.php'));
        }
    }

    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM categories WHERE id=?", [$id]);
        if ($row) DB::update('categories', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/categories.php'));
    }

    if (isset($_POST['delete'])) {
        $pkgCount = DB::value("SELECT COUNT(*) FROM packages WHERE category_id=?", [$id]);
        if ($pkgCount > 0) {
            flash('danger', "Cannot delete — $pkgCount packages are assigned to this category.");
        } else {
            DB::delete('categories', ['id' => $id]);
            auditLog('delete', 'categories', $id, [], []);
            flash('success', 'Category deleted.');
        }
        redirect(url('admin/categories.php'));
    }

    if (isset($_POST['reorder'])) {
        foreach ($_POST['order'] ?? [] as $catId => $sortOrder) {
            DB::update('categories', ['sort_order' => (int)$sortOrder], ['id' => (int)$catId]);
        }
        flash('success', 'Order saved.');
        redirect(url('admin/categories.php'));
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editCat = $editId ? DB::row("SELECT * FROM categories WHERE id=?", [$editId]) : null;

$cats = DB::rows("SELECT c.*, (SELECT COUNT(*) FROM packages WHERE category_id=c.id AND is_active=1) AS pkg_count
                  FROM categories c ORDER BY c.sort_order ASC, c.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categories — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Categories</span></div>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i><span><?= implode(' ', array_map('h',$errors)) ?></span></div>
    <?php endif; ?>

    <div class="admin-page-title">Tour Categories</div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

      <!-- Category List -->
      <div class="admin-card">
        <div class="admin-card-header" style="justify-content:space-between">
          <span><i class="fas fa-tags" style="color:var(--clr-gold)"></i> All Categories</span>
          <span style="font-size:.8rem;color:var(--clr-muted)"><?= count($cats) ?> total</span>
        </div>
        <div class="admin-card-body" style="padding:0">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:40px">#</th>
                <th>Category</th>
                <th>Icon</th>
                <th>Packages</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($cats): foreach ($cats as $cat): ?>
              <tr>
                <td style="color:var(--clr-muted);font-size:.8rem"><?= $cat['sort_order'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <?php if ($cat['color']): ?>
                    <div style="width:10px;height:10px;border-radius:50%;background:<?= h($cat['color']) ?>;flex-shrink:0"></div>
                    <?php endif; ?>
                    <div>
                      <div style="font-weight:600;color:var(--clr-primary)"><?= h($cat['name']) ?></div>
                      <div style="font-size:.72rem;color:var(--clr-muted)"><?= h($cat['slug']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($cat['icon']): ?>
                  <i class="<?= h($cat['icon']) ?>" style="font-size:1.2rem;color:var(--clr-gold)"></i>
                  <?php else: ?>—<?php endif; ?>
                </td>
                <td>
                  <span style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:20px;font-size:.75rem">
                    <?= $cat['pkg_count'] ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" name="toggle" class="badge <?= $cat['is_active'] ? 'badge-success' : 'badge-danger' ?>"
                            style="border:none;cursor:pointer;padding:4px 10px;border-radius:20px;font-size:.72rem">
                      <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                    </button>
                  </form>
                </td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="<?= url('admin/categories.php?edit=' . $cat['id']) ?>" class="btn-icon-admin" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>
                    <?php if ($cat['pkg_count'] == 0): ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete this category?')">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                      <button type="submit" name="delete" class="btn-icon-admin btn-icon-danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="6" style="text-align:center;padding:32px;color:var(--clr-muted)">No categories yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create / Edit Form -->
      <div class="admin-card">
        <div class="admin-card-header">
          <i class="fas fa-<?= $editCat ? 'edit' : 'plus' ?>" style="color:var(--clr-gold)"></i>
          <?= $editCat ? 'Edit Category' : 'Add New Category' ?>
        </div>
        <div class="admin-card-body">
          <form method="POST">
            <?= csrfField() ?>
            <?php if ($editCat): ?>
            <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>
            <div class="form-group">
              <label class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="catName" value="<?= h($editCat['name'] ?? '') ?>" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Slug</label>
              <input type="text" name="slug" id="catSlug" value="<?= h($editCat['slug'] ?? '') ?>" class="form-control">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="form-group">
                <label class="form-label">Icon <small style="color:var(--clr-muted)">Font Awesome class</small></label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="text" name="icon" id="catIcon" value="<?= h($editCat['icon'] ?? '') ?>" class="form-control" placeholder="fas fa-paw">
                  <i id="iconPreview" class="<?= h($editCat['icon'] ?? 'fas fa-tag') ?>" style="font-size:1.3rem;color:var(--clr-gold)"></i>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Color</label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="color" name="color" id="catColor" value="<?= h($editCat['color'] ?? '#0D3B66') ?>" style="height:38px;width:60px;padding:3px;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer">
                  <span id="colorHex" style="font-size:.8rem;color:var(--clr-muted)"><?= h($editCat['color'] ?? '#0D3B66') ?></span>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"><?= h($editCat['description'] ?? '') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)($editCat['sort_order'] ?? 0) ?>" class="form-control" min="0">
              </div>
              <div class="form-group" style="padding-top:28px">
                <label class="admin-toggle">
                  <input type="checkbox" name="is_active" <?= ($editCat['is_active'] ?? 1) ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span>Active</span>
                </label>
              </div>
            </div>
            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> <?= $editCat ? 'Update Category' : 'Create Category' ?>
            </button>
            <?php if ($editCat): ?>
            <a href="<?= url('admin/categories.php') ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center;margin-top:8px">
              <i class="fas fa-plus"></i> Add New Instead
            </a>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
document.getElementById('catName')?.addEventListener('input', function() {
  const sl = document.getElementById('catSlug');
  if (!sl.dataset.m) sl.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
});
document.getElementById('catSlug')?.addEventListener('input', function() { this.dataset.m = 1; });
document.getElementById('catIcon')?.addEventListener('input', function() {
  document.getElementById('iconPreview').className = this.value || 'fas fa-tag';
});
document.getElementById('catColor')?.addEventListener('input', function() {
  document.getElementById('colorHex').textContent = this.value;
});
</script>
</body>
</html>
