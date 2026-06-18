<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$errors = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['save'])) {
        $data = [
            'name'        => trim($_POST['name'] ?? ''),
            'position'    => trim($_POST['position'] ?? ''),
            'company'     => trim($_POST['company'] ?? ''),
            'body'        => trim($_POST['body'] ?? ''),
            'rating'      => min(5, max(1, (int)($_POST['rating'] ?? 5))),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['name'] || !$data['body']) { $errors[] = 'Name and testimonial body are required.'; }

        if (!empty($_FILES['avatar']['name'])) {
            $url = uploadImage($_FILES['avatar'], 'testimonials');
            if ($url) $data['avatar'] = $url;
        }

        if (!$errors) {
            if ($id) {
                DB::update('testimonials', $data, ['id' => $id]);
                auditLog('update', 'testimonials', $id, [], $data);
                flash('success', 'Testimonial updated.');
            } else {
                $newId = DB::insert('testimonials', $data);
                auditLog('create', 'testimonials', $newId, [], $data);
                flash('success', 'Testimonial added.');
            }
            redirect(url('admin/testimonials.php'));
        }
    }

    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM testimonials WHERE id=?", [$id]);
        if ($row) DB::update('testimonials', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/testimonials.php'));
    }
    if (isset($_POST['toggle_featured'])) {
        $row = DB::row("SELECT is_featured FROM testimonials WHERE id=?", [$id]);
        if ($row) DB::update('testimonials', ['is_featured' => $row['is_featured'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/testimonials.php'));
    }
    if (isset($_POST['delete'])) {
        DB::delete('testimonials', ['id' => $id]);
        auditLog('delete', 'testimonials', $id, [], []);
        flash('success', 'Testimonial deleted.');
        redirect(url('admin/testimonials.php'));
    }
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = $editId ? DB::row("SELECT * FROM testimonials WHERE id=?", [$editId]) : null;

$items = DB::rows("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Testimonials — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Testimonials</span></div>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>
    <?php if ($errors): ?><div class="flash-msg flash-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i><span><?= implode(' ', array_map('h',$errors)) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Testimonials</div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">

      <!-- List -->
      <div class="admin-card">
        <div class="admin-card-body" style="padding:0">
          <table class="admin-table">
            <thead>
              <tr><th>Person</th><th>Testimonial</th><th>Rating</th><th>Featured</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <?php foreach ($items as $t): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <?php if ($t['avatar']): ?>
                    <img src="<?= h($t['avatar']) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover">
                    <?php else: ?>
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--clr-gold);display:grid;place-items:center;font-weight:700;color:#fff;font-size:.8rem"><?= strtoupper(substr($t['name'],0,1)) ?></div>
                    <?php endif; ?>
                    <div>
                      <div style="font-weight:600;font-size:.875rem"><?= h($t['name']) ?></div>
                      <div style="font-size:.72rem;color:var(--clr-muted)"><?= h($t['position']) ?><?= $t['company']?' · '.h($t['company']):'' ?></div>
                    </div>
                  </div>
                </td>
                <td><div style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.82rem;color:var(--clr-muted)"><?= h($t['body']) ?></div></td>
                <td>
                  <div style="display:flex;gap:2px">
                    <?php for ($s=1;$s<=5;$s++): ?>
                    <i class="fas fa-star" style="font-size:.7rem;color:<?= $s<=$t['rating']?'var(--clr-gold)':'#e2e8f0' ?>"></i>
                    <?php endfor; ?>
                  </div>
                </td>
                <td>
                  <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" name="toggle_featured" style="background:none;border:none;cursor:pointer;font-size:1rem;color:<?= $t['is_featured']?'var(--clr-gold)':'#cbd5e0' ?>"><i class="fas fa-star"></i></button>
                  </form>
                </td>
                <td>
                  <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" name="toggle" class="badge <?= $t['is_active']?'badge-success':'badge-danger' ?>" style="border:none;cursor:pointer;padding:4px 10px;border-radius:20px;font-size:.72rem"><?= $t['is_active']?'Active':'Hidden' ?></button>
                  </form>
                </td>
                <td>
                  <div style="display:flex;gap:6px">
                    <a href="<?= url('admin/testimonials.php?edit='.$t['id']) ?>" class="btn-icon-admin"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" name="delete" class="btn-icon-admin btn-icon-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-header"><i class="fas fa-<?= $editing?'edit':'plus' ?>" style="color:var(--clr-gold)"></i> <?= $editing?'Edit':'Add' ?> Testimonial</div>
        <div class="admin-card-body">
          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>

            <?php if (!empty($editing['avatar'])): ?>
            <div style="text-align:center;margin-bottom:14px">
              <img src="<?= h($editing['avatar']) ?>" style="width:64px;height:64px;border-radius:50%;object-fit:cover">
            </div>
            <?php endif; ?>
            <div class="form-group">
              <label class="form-label">Photo</label>
              <input type="file" name="avatar" accept="image/*" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Full Name <span class="text-danger">*</span></label>
              <input type="text" name="name" value="<?= h($editing['name']??'') ?>" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Position / Title</label>
              <input type="text" name="position" value="<?= h($editing['position']??'') ?>" class="form-control" placeholder="CEO, Software Engineer…">
            </div>
            <div class="form-group">
              <label class="form-label">Company</label>
              <input type="text" name="company" value="<?= h($editing['company']??'') ?>" class="form-control">
            </div>
            <div class="form-group">
              <label class="form-label">Testimonial <span class="text-danger">*</span></label>
              <textarea name="body" class="form-control" rows="4" required><?= h($editing['body']??'') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Rating</label>
              <select name="rating" class="form-control">
                <?php for ($s=5;$s>=1;$s--): ?>
                <option value="<?= $s ?>" <?= ($editing['rating']??5)==$s?'selected':'' ?>><?= str_repeat('★',$s).str_repeat('☆',5-$s) ?> (<?= $s ?> stars)</option>
                <?php endfor; ?>
              </select>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
              <label class="admin-toggle"><input type="checkbox" name="is_active" <?= ($editing['is_active']??1)?'checked':'' ?>><span class="admin-toggle-slider"></span><span>Active</span></label>
              <label class="admin-toggle"><input type="checkbox" name="is_featured" <?= ($editing['is_featured']??1)?'checked':'' ?>><span class="admin-toggle-slider"></span><span>Featured</span></label>
            </div>
            <div class="form-group">
              <label class="form-label">Sort Order</label>
              <input type="number" name="sort_order" value="<?= (int)($editing['sort_order']??0) ?>" class="form-control" min="0">
            </div>
            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> <?= $editing?'Update':'Add' ?> Testimonial
            </button>
            <?php if ($editing): ?>
            <a href="<?= url('admin/testimonials.php') ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center;margin-top:8px"><i class="fas fa-plus"></i> Add New</a>
            <?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
