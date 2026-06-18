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

        // Image upload
        if (!empty($_FILES['image']['name'])) {
            $url = uploadImage($_FILES['image'], 'banners');
            if ($url) $data['image'] = $url;
            elseif (!$id) $errors[] = 'Image is required.';
        } elseif (!$id) {
            $errors[] = 'Image is required.';
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
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
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Banners</span></div>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>
    <?php if ($errors): ?><div class="flash-msg flash-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i><span><?= implode(' | ', array_map('h',$errors)) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Banners & Sliders</div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">
      <!-- Banner grid -->
      <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px">
        <?php if ($banners): foreach ($banners as $b): ?>
        <div class="admin-card" style="border:2px solid <?= $b['is_active']?'var(--clr-border)':'#fed7d7' ?>">
          <div style="position:relative">
            <img src="<?= h($b['image']) ?>" alt="<?= h($b['title']) ?>" style="width:100%;height:140px;object-fit:cover;border-radius:var(--radius-md) var(--radius-md) 0 0">
            <div style="position:absolute;top:8px;right:8px;display:flex;gap:6px">
              <span style="background:rgba(0,0,0,.6);color:#fff;font-size:.65rem;padding:3px 8px;border-radius:20px"><?= h($positions[$b['position']] ?? $b['position']) ?></span>
              <span style="background:<?= $b['is_active']?'#38a169':'#e53e3e' ?>;color:#fff;font-size:.65rem;padding:3px 8px;border-radius:20px"><?= $b['is_active']?'Active':'Inactive' ?></span>
            </div>
          </div>
          <div class="admin-card-body" style="padding:14px">
            <div style="font-weight:700;color:var(--clr-primary);margin-bottom:4px"><?= h($b['title']) ?></div>
            <?php if ($b['subtitle']): ?><div style="font-size:.78rem;color:var(--clr-muted);margin-bottom:8px"><?= h(excerpt($b['subtitle'],80)) ?></div><?php endif; ?>
            <?php if ($b['cta_text']): ?><div style="font-size:.75rem;color:var(--clr-sky)"><i class="fas fa-link"></i> <?= h($b['cta_text']) ?></div><?php endif; ?>
            <div style="display:flex;gap:8px;margin-top:12px">
              <a href="<?= url('admin/banners.php?edit='.$b['id']) ?>" class="btn-admin btn-admin-secondary btn-sm" style="flex:1;text-align:center"><i class="fas fa-edit"></i> Edit</a>
              <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" name="toggle" class="btn-admin btn-sm" style="background:<?= $b['is_active']?'#fff7ed':'#f0fff4' ?>;color:<?= $b['is_active']?'#c05621':'#276749' ?>;border:none;cursor:pointer">
                  <i class="fas fa-<?= $b['is_active']?'eye-slash':'eye' ?>"></i>
                </button>
              </form>
              <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
                <button type="submit" name="delete" class="btn-admin btn-sm" style="background:#fff5f5;color:var(--clr-danger);border:none;cursor:pointer"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </div>
        </div>
        <?php endforeach; else: ?>
        <div class="admin-card"><div class="admin-card-body" style="text-align:center;padding:60px;color:var(--clr-muted)">No banners yet. Add one →</div></div>
        <?php endif; ?>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-header"><i class="fas fa-images" style="color:var(--clr-gold)"></i> <?= $editing?'Edit Banner':'Add Banner' ?></div>
        <div class="admin-card-body">
          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <div class="form-group">
              <label class="form-label">Position</label>
              <select name="position" class="form-control">
                <?php foreach ($positions as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($editing['position']??'home_hero')===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Title <span class="text-danger">*</span></label>
              <input type="text" name="title" value="<?= h($editing['title']??'') ?>" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Subtitle</label>
              <textarea name="subtitle" class="form-control" rows="2"><?= h($editing['subtitle']??'') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
              <div class="form-group">
                <label class="form-label">Button Text</label>
                <input type="text" name="cta_text" value="<?= h($editing['cta_text']??'') ?>" class="form-control" placeholder="Explore Now">
              </div>
              <div class="form-group">
                <label class="form-label">Button URL</label>
                <input type="text" name="cta_url" value="<?= h($editing['cta_url']??'') ?>" class="form-control" placeholder="/packages.php">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Video URL <small style="color:var(--clr-muted)">(optional, overrides image)</small></label>
              <input type="url" name="video_url" value="<?= h($editing['video_url']??'') ?>" class="form-control" placeholder="https://youtube.com/…">
            </div>
            <div class="form-group">
              <label class="form-label">Image <?= !$editing?'<span class="text-danger">*</span>':'' ?></label>
              <?php if (!empty($editing['image'])): ?>
              <img src="<?= h($editing['image']) ?>" style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:8px">
              <?php endif; ?>
              <input type="file" name="image" accept="image/*" class="form-control" <?= !$editing?'required':'' ?>>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)($editing['sort_order']??0) ?>" class="form-control" min="0">
              </div>
              <div class="form-group" style="padding-top:28px">
                <label class="admin-toggle"><input type="checkbox" name="is_active" <?= ($editing['is_active']??1)?'checked':'' ?>><span class="admin-toggle-slider"></span><span>Active</span></label>
              </div>
            </div>
            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> <?= $editing?'Update':'Create' ?> Banner
            </button>
            <?php if ($editing): ?><a href="<?= url('admin/banners.php') ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center;margin-top:8px"><i class="fas fa-plus"></i> Add New</a><?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
