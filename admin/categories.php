<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

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
            flash('danger', "Cannot delete — {$pkgCount} package(s) assigned to this category.");
        } else {
            DB::delete('categories', ['id' => $id]);
            auditLog('delete', 'categories', $id, [], []);
            flash('success', 'Category deleted.');
        }
        redirect(url('admin/categories.php'));
    }
}

$editId  = (int)($_GET['edit'] ?? 0);
$editCat = $editId ? DB::row("SELECT * FROM categories WHERE id=?", [$editId]) : null;
$cats    = DB::rows("SELECT c.*, (SELECT COUNT(*) FROM packages WHERE category_id=c.id AND is_active=1) AS pkg_count
                     FROM categories c ORDER BY c.sort_order ASC, c.name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
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
      <div class="breadcrumb-admin">
        <a href="<?= url('admin/') ?>">Admin</a>
        <i class="fas fa-chevron-right"></i>
        <span>Categories</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/categories.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New Category
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger"><i class="fas fa-exclamation-circle"></i><span><?= implode(' ', array_map('h', $errors)) ?></span></div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">Tour Categories</div>
        <div class="page-subtitle">Organise packages into browsable categories shown on the public site</div>
      </div>
      <div class="page-header-actions">
        <span style="font-size:.82rem;color:var(--clr-muted);background:var(--clr-light);border:1px solid var(--clr-border);padding:6px 14px;border-radius:var(--radius-full)"><?= count($cats) ?> total</span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

      <!-- Category list -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-tags" style="color:var(--clr-gold)"></i> All Categories
          </span>
        </div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th style="width:52px">Order</th>
                <th>Category</th>
                <th style="width:60px">Icon</th>
                <th style="width:90px">Packages</th>
                <th style="width:100px">Status</th>
                <th style="width:90px"></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($cats): foreach ($cats as $cat): ?>
              <tr>
                <td style="font-variant-numeric:tabular-nums">
                  <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--clr-light);border-radius:6px;font-size:.78rem;font-weight:700;color:var(--clr-muted)"><?= $cat['sort_order'] ?: '—' ?></span>
                </td>
                <td>
                  <div style="display:flex;align-items:center;gap:12px">
                    <?php if ($cat['color']): ?>
                    <span style="width:10px;height:10px;border-radius:50%;background:<?= h($cat['color']) ?>;flex-shrink:0;box-shadow:0 0 0 2px <?= h($cat['color']) ?>33"></span>
                    <?php endif; ?>
                    <div>
                      <div class="td-primary"><?= h($cat['name']) ?></div>
                      <div class="td-secondary">/<?= h($cat['slug']) ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <?php if ($cat['icon']): ?>
                  <i class="<?= h($cat['icon']) ?>" style="font-size:1.1rem;color:var(--clr-gold)"></i>
                  <?php else: ?>
                  <span style="color:var(--clr-border)">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge" style="background:#e0f2fe;color:#0369a1">
                    <?= $cat['pkg_count'] ?> pkg<?= $cat['pkg_count'] != 1 ? 's' : '' ?>
                  </span>
                </td>
                <td>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                    <button type="submit" name="toggle"
                            class="status-badge <?= $cat['is_active'] ? 'sb-active' : 'sb-inactive' ?>"
                            style="border:none;cursor:pointer;font-family:inherit">
                      <?= $cat['is_active'] ? 'Active' : 'Inactive' ?>
                    </button>
                  </form>
                </td>
                <td>
                  <div class="tbl-actions">
                    <a href="<?= url('admin/categories.php?edit=' . $cat['id']) ?>" class="btn-tbl" title="Edit category">
                      <i class="fas fa-pencil"></i>
                    </a>
                    <?php if ($cat['pkg_count'] == 0): ?>
                    <form method="POST" style="display:contents" onsubmit="return confirm('Delete «<?= h($cat['name']) ?>»? This cannot be undone.')">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                      <button type="submit" name="delete" class="btn-tbl btn-tbl-danger" title="Delete category">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                    <?php else: ?>
                    <span class="btn-tbl" title="Cannot delete — has packages" style="opacity:.35;cursor:not-allowed">
                      <i class="fas fa-trash"></i>
                    </span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:48px;color:var(--clr-muted)">
                  <i class="fas fa-tags" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.25"></i>
                  No categories yet. Create your first one →
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Create / Edit form -->
      <div class="admin-card" id="categoryForm">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-<?= $editCat ? 'pencil' : 'plus-circle' ?>" style="color:var(--clr-gold)"></i>
            <?= $editCat ? 'Edit Category' : 'New Category' ?>
          </span>
          <?php if ($editCat): ?>
          <a href="<?= url('admin/categories.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
            <i class="fas fa-plus"></i> New
          </a>
          <?php endif; ?>
        </div>
        <div class="admin-card-body">
          <form method="POST">
            <?= csrfField() ?>
            <?php if ($editCat): ?>
            <input type="hidden" name="id" value="<?= $editCat['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Name <span class="req">*</span></label>
              <input type="text" name="name" id="catName"
                     value="<?= h($editCat['name'] ?? '') ?>"
                     class="form-control" required
                     placeholder="e.g. Wildlife Safari">
            </div>

            <div class="form-group">
              <label class="form-label">Slug <span class="hint-text">auto-generated</span></label>
              <div class="input-group">
                <i class="ig-icon fas fa-link"></i>
                <input type="text" name="slug" id="catSlug"
                       value="<?= h($editCat['slug'] ?? '') ?>"
                       class="form-control" placeholder="wildlife-safari">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Icon <span class="hint-text">FA class</span></label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="text" name="icon" id="catIcon"
                         value="<?= h($editCat['icon'] ?? '') ?>"
                         class="form-control" placeholder="fas fa-paw"
                         style="flex:1">
                  <div style="width:38px;height:38px;border:1.5px solid var(--clr-border);border-radius:var(--radius-sm);display:grid;place-items:center;flex-shrink:0">
                    <i id="iconPreview" class="<?= h($editCat['icon'] ?? 'fas fa-tag') ?>" style="color:var(--clr-gold)"></i>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Colour</label>
                <div style="display:flex;gap:8px;align-items:center">
                  <input type="color" name="color" id="catColor"
                         value="<?= h($editCat['color'] ?? '#0D3B66') ?>"
                         style="height:40px;width:48px;padding:3px;border:1.5px solid var(--clr-border);border-radius:var(--radius-sm);cursor:pointer;background:#fff">
                  <input type="text" id="colorHex"
                         value="<?= h($editCat['color'] ?? '#0D3B66') ?>"
                         class="form-control" style="font-family:monospace;font-size:.82rem" maxlength="7" readonly>
                </div>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea name="description" class="form-control" rows="3"
                        placeholder="Brief description shown to site visitors"><?= h($editCat['description'] ?? '') ?></textarea>
            </div>

            <div class="form-row" style="align-items:end">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?= (int)($editCat['sort_order'] ?? 0) ?>"
                       class="form-control" min="0">
                <span class="form-hint">Lower numbers appear first</span>
              </div>
              <div class="form-group">
                <label class="admin-toggle">
                  <input type="checkbox" name="is_active" <?= ($editCat['is_active'] ?? 1) ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Active & visible</span>
                </label>
              </div>
            </div>

            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block" style="margin-top:8px">
              <i class="fas fa-save"></i> <?= $editCat ? 'Update Category' : 'Create Category' ?>
            </button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Slug from name
const nameEl = document.getElementById('catName');
const slugEl = document.getElementById('catSlug');
nameEl?.addEventListener('input', function() {
  if (!slugEl.dataset.manual)
    slugEl.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
});
slugEl?.addEventListener('input', function() { this.dataset.manual = '1'; });

// Icon preview
document.getElementById('catIcon')?.addEventListener('input', function() {
  document.getElementById('iconPreview').className = this.value || 'fas fa-tag';
});

// Colour sync
const colPicker = document.getElementById('catColor');
const colHex    = document.getElementById('colorHex');
colPicker?.addEventListener('input', function() { colHex.value = this.value; });
</script>
</body>
</html>
