<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$errors  = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['save'])) {
        $data = [
            'title'      => trim($_POST['title'] ?? ''),
            'subtitle'   => trim($_POST['subtitle'] ?? ''),
            'cta_text'   => trim($_POST['cta_text'] ?? ''),
            'cta_url'    => trim($_POST['cta_url'] ?? ''),
            'video_url'  => trim($_POST['video_url'] ?? ''),
            'position'   => trim($_POST['position'] ?? 'home_hero'),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['title']) $errors[] = 'Title is required.';

        if (!empty($_FILES['image']['name'])) {
            $imgUrl = uploadImage($_FILES['image'], 'banners');
            if ($imgUrl) $data['image'] = $imgUrl;
            elseif (!$id) $errors[] = 'Image upload failed.';
        } elseif (!$id) {
            $errors[] = 'Banner image is required.';
        }

        if (!$errors) {
            if ($id) {
                DB::update('banners', $data, ['id' => $id]);
                auditLog('update', 'banners', $id, [], $data);
                flash('success', 'Banner updated.');
            } else {
                $newId = DB::insert('banners', $data);
                auditLog('create', 'banners', $newId, [], $data);
                flash('success', 'Banner created.');
            }
            redirect(url('admin/banners.php'));
        }
    }

    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM banners WHERE id=?", [$id]);
        if ($row) DB::update('banners', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/banners.php'));
    }
    if (isset($_POST['delete'])) {
        DB::delete('banners', ['id' => $id]);
        auditLog('delete', 'banners', $id, [], []);
        flash('success', 'Banner deleted.');
        redirect(url('admin/banners.php'));
    }
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = $editId ? DB::row("SELECT * FROM banners WHERE id=?", [$editId]) : null;
$banners = DB::rows("SELECT * FROM banners ORDER BY position, sort_order ASC");

$positions = [
    'home_hero'    => 'Homepage Hero Slideshow',
    'home_promo'   => 'Homepage Promo Banner',
    'packages_top' => 'Packages Page Top',
    'blog_sidebar' => 'Blog Sidebar',
];

$positionIcons = [
    'home_hero'    => 'fa-home',
    'home_promo'   => 'fa-bullhorn',
    'packages_top' => 'fa-box-open',
    'blog_sidebar' => 'fa-newspaper',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Banners — Admin | MT Safaris</title>
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
        <span>Banners</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/banners.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New Banner
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

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">Banners &amp; Sliders</div>
        <div class="page-subtitle">Manage hero images and promotional banners across the site</div>
      </div>
      <div class="page-header-actions">
        <span style="font-size:.82rem;color:var(--clr-muted);background:var(--clr-light);border:1px solid var(--clr-border);padding:6px 14px;border-radius:var(--radius-full)"><?= count($banners) ?> total</span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start">

      <!-- Banner grid -->
      <div>
        <?php if ($banners):
          $grouped = [];
          foreach ($banners as $b) $grouped[$b['position']][] = $b;
          foreach ($grouped as $pos => $group):
        ?>
        <div style="margin-bottom:24px">
          <div style="display:flex;align-items:center;gap:8px;margin-bottom:12px">
            <i class="fas <?= $positionIcons[$pos] ?? 'fa-image' ?>"
               style="color:var(--clr-gold);font-size:.9rem"></i>
            <span style="font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--clr-primary)">
              <?= h($positions[$pos] ?? ucfirst($pos)) ?>
            </span>
            <span style="font-size:.75rem;color:var(--clr-muted)">(<?= count($group) ?>)</span>
          </div>
          <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
            <?php foreach ($group as $b): ?>
            <div class="admin-card" style="border-top:3px solid <?= $b['is_active'] ? 'var(--clr-primary)' : 'var(--clr-danger)' ?>;overflow:hidden">
              <!-- Thumbnail -->
              <div style="position:relative;height:130px;overflow:hidden">
                <img src="<?= h($b['image']) ?>" alt="<?= h($b['title']) ?>"
                     style="width:100%;height:100%;object-fit:cover">
                <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.6) 0%,transparent 60%);display:flex;align-items:flex-end;padding:10px 12px">
                  <span style="color:#fff;font-weight:700;font-size:.85rem;text-shadow:0 1px 2px rgba(0,0,0,.4)"><?= h($b['title']) ?></span>
                </div>
                <span class="status-badge <?= $b['is_active'] ? 'sb-active' : 'sb-inactive' ?>"
                      style="position:absolute;top:8px;right:8px">
                  <?= $b['is_active'] ? 'Active' : 'Inactive' ?>
                </span>
              </div>
              <!-- Meta -->
              <div style="padding:10px 14px 14px">
                <?php if ($b['subtitle']): ?>
                <div class="td-secondary" style="margin-bottom:6px;white-space:normal"><?= h(excerpt($b['subtitle'], 70)) ?></div>
                <?php endif; ?>
                <?php if ($b['cta_text']): ?>
                <div style="font-size:.73rem;color:var(--clr-primary);display:flex;align-items:center;gap:4px;margin-bottom:8px">
                  <i class="fas fa-arrow-right"></i> <?= h($b['cta_text']) ?>
                </div>
                <?php endif; ?>
                <div class="tbl-actions" style="justify-content:flex-start;margin-top:8px">
                  <a href="<?= url('admin/banners.php?edit=' . $b['id']) ?>" class="btn-tbl" title="Edit">
                    <i class="fas fa-pencil"></i>
                  </a>
                  <form method="POST" style="display:contents">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button type="submit" name="toggle" class="btn-tbl" title="<?= $b['is_active'] ? 'Deactivate' : 'Activate' ?>">
                      <i class="fas fa-<?= $b['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                    </button>
                  </form>
                  <form method="POST" style="display:contents" onsubmit="return confirm('Delete «<?= h($b['title']) ?>»?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button type="submit" name="delete" class="btn-tbl btn-tbl-danger" title="Delete">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="admin-card">
          <div class="admin-card-body" style="text-align:center;padding:60px;color:var(--clr-muted)">
            <i class="fas fa-images" style="font-size:2.5rem;display:block;margin-bottom:12px;opacity:.2"></i>
            No banners yet. Create your first one →
          </div>
        </div>
        <?php endif; ?>
      </div>

      <!-- Form -->
      <div class="admin-card" style="position:sticky;top:80px">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-<?= $editing ? 'pencil' : 'plus-circle' ?>" style="color:var(--clr-gold)"></i>
            <?= $editing ? 'Edit Banner' : 'New Banner' ?>
          </span>
          <?php if ($editing): ?>
          <a href="<?= url('admin/banners.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
            <i class="fas fa-plus"></i> New
          </a>
          <?php endif; ?>
        </div>
        <div class="admin-card-body">
          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Position</label>
              <select name="position" class="form-control">
                <?php foreach ($positions as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($editing['position'] ?? 'home_hero') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Title <span style="color:var(--clr-danger)">*</span></label>
              <input type="text" name="title" value="<?= h($editing['title'] ?? '') ?>"
                     class="form-control" required placeholder="e.g. Discover Africa's Wild Heart">
            </div>

            <div class="form-group">
              <label class="form-label">Subtitle</label>
              <textarea name="subtitle" class="form-control" rows="2"
                        placeholder="Supporting tagline or description"><?= h($editing['subtitle'] ?? '') ?></textarea>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Button Text</label>
                <input type="text" name="cta_text" value="<?= h($editing['cta_text'] ?? '') ?>"
                       class="form-control" placeholder="Explore Now">
              </div>
              <div class="form-group">
                <label class="form-label">Button URL</label>
                <input type="text" name="cta_url" value="<?= h($editing['cta_url'] ?? '') ?>"
                       class="form-control" placeholder="/packages.php">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Video URL <span class="form-hint" style="display:inline;font-size:.7rem">(optional — overrides image)</span></label>
              <input type="url" name="video_url" value="<?= h($editing['video_url'] ?? '') ?>"
                     class="form-control" placeholder="https://youtube.com/…">
            </div>

            <div class="form-group">
              <label class="form-label">Image <?= !$editing ? '<span style="color:var(--clr-danger)">*</span>' : '' ?></label>
              <?php if (!empty($editing['image'])): ?>
              <img src="<?= h($editing['image']) ?>" alt=""
                   style="width:100%;height:100px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:8px;border:1.5px solid var(--clr-border)">
              <?php endif; ?>
              <input type="file" name="image" accept="image/*" class="form-control"
                     <?= !$editing ? 'required' : '' ?>>
              <span class="form-hint">Recommended: 1920×900px JPG. Max 5MB.</span>
            </div>

            <div class="form-row" style="align-items:end">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?= (int)($editing['sort_order'] ?? 0) ?>"
                       class="form-control" min="0">
                <span class="form-hint">Lower = shows first</span>
              </div>
              <div class="form-group">
                <label class="admin-toggle">
                  <input type="checkbox" name="is_active" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Active</span>
                </label>
              </div>
            </div>

            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block" style="margin-top:8px">
              <i class="fas fa-save"></i> <?= $editing ? 'Update Banner' : 'Create Banner' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
