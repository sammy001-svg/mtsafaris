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
            'name'        => trim($_POST['name'] ?? ''),
            'position'    => trim($_POST['position'] ?? ''),
            'company'     => trim($_POST['company'] ?? ''),
            'body'        => trim($_POST['body'] ?? ''),
            'rating'      => min(5, max(1, (int)($_POST['rating'] ?? 5))),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            'is_active'   => isset($_POST['is_active']) ? 1 : 0,
            'sort_order'  => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['name'] || !$data['body']) $errors[] = 'Name and testimonial body are required.';

        if (!empty($_FILES['avatar']['name'])) {
            $imgUrl = uploadImage($_FILES['avatar'], 'testimonials');
            if ($imgUrl) $data['avatar'] = $imgUrl;
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
$items   = DB::rows("SELECT * FROM testimonials ORDER BY sort_order ASC, created_at DESC");
$total   = count($items);
$featured = array_sum(array_column($items, 'is_featured'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
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
      <div class="breadcrumb-admin">
        <a href="<?= url('admin/') ?>">Admin</a>
        <i class="fas fa-chevron-right"></i>
        <span>Testimonials</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/testimonials.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New Testimonial
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
        <div class="page-title">Testimonials</div>
        <div class="page-subtitle">Customer reviews and quotes displayed on the public site</div>
      </div>
      <div class="page-header-actions">
        <span style="font-size:.82rem;color:var(--clr-muted);background:var(--clr-light);border:1px solid var(--clr-border);padding:6px 14px;border-radius:var(--radius-full)">
          <?= $total ?> total &nbsp;·&nbsp; <i class="fas fa-star" style="color:var(--clr-gold)"></i> <?= $featured ?> featured
        </span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

      <!-- List -->
      <div class="admin-card">
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Person</th>
                <th>Testimonial</th>
                <th style="width:100px">Rating</th>
                <th style="width:80px">Featured</th>
                <th style="width:100px">Status</th>
                <th style="width:80px"></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($items): foreach ($items as $t): ?>
              <tr>
                <td>
                  <div style="display:flex;align-items:center;gap:10px">
                    <?php if (!empty($t['avatar'])): ?>
                    <img src="<?= h($t['avatar']) ?>" alt=""
                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid var(--clr-border)">
                    <?php else: ?>
                    <div style="width:36px;height:36px;border-radius:50%;background:var(--clr-primary);display:grid;place-items:center;font-weight:700;color:#fff;font-size:.8rem;flex-shrink:0">
                      <?= strtoupper(substr($t['name'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div>
                      <div class="td-primary"><?= h($t['name']) ?></div>
                      <div class="td-secondary"><?= h($t['position']) ?><?= $t['company'] ? ' · ' . h($t['company']) : '' ?></div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="td-secondary" style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                    <?= h($t['body']) ?>
                  </div>
                </td>
                <td>
                  <div style="display:flex;gap:2px;align-items:center">
                    <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="fas fa-star" style="font-size:.7rem;color:<?= $s <= $t['rating'] ? 'var(--clr-gold)' : '#e2e8f0' ?>"></i>
                    <?php endfor; ?>
                    <span class="td-secondary" style="margin-left:4px"><?= $t['rating'] ?></span>
                  </div>
                </td>
                <td>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" name="toggle_featured" title="Toggle featured"
                            style="background:none;border:none;cursor:pointer;padding:4px;font-size:1.1rem;color:<?= $t['is_featured'] ? 'var(--clr-gold)' : '#cbd5e0' ?>;transition:color .15s">
                      <i class="fas fa-star"></i>
                    </button>
                  </form>
                </td>
                <td>
                  <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" name="toggle"
                            class="status-badge <?= $t['is_active'] ? 'sb-active' : 'sb-inactive' ?>"
                            style="border:none;cursor:pointer;font-family:inherit">
                      <?= $t['is_active'] ? 'Active' : 'Hidden' ?>
                    </button>
                  </form>
                </td>
                <td>
                  <div class="tbl-actions">
                    <a href="<?= url('admin/testimonials.php?edit=' . $t['id']) ?>" class="btn-tbl" title="Edit">
                      <i class="fas fa-pencil"></i>
                    </a>
                    <form method="POST" style="display:contents" onsubmit="return confirm('Delete this testimonial?')">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $t['id'] ?>">
                      <button type="submit" name="delete" class="btn-tbl btn-tbl-danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr>
                <td colspan="6" style="text-align:center;padding:48px;color:var(--clr-muted)">
                  <i class="fas fa-comments" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.25"></i>
                  No testimonials yet. Add your first one →
                </td>
              </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-<?= $editing ? 'pencil' : 'plus-circle' ?>" style="color:var(--clr-gold)"></i>
            <?= $editing ? 'Edit Testimonial' : 'Add Testimonial' ?>
          </span>
          <?php if ($editing): ?>
          <a href="<?= url('admin/testimonials.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
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

            <?php if (!empty($editing['avatar'])): ?>
            <div style="text-align:center;margin-bottom:16px">
              <img src="<?= h($editing['avatar']) ?>" alt=""
                   style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:3px solid var(--clr-gold)">
            </div>
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Photo</label>
              <input type="file" name="avatar" accept="image/*" class="form-control">
              <span class="form-hint">JPG/PNG, max 2MB. Square images work best.</span>
            </div>

            <div class="form-group">
              <label class="form-label">Full Name <span style="color:var(--clr-danger)">*</span></label>
              <input type="text" name="name" value="<?= h($editing['name'] ?? '') ?>"
                     class="form-control" required placeholder="Jane Smith">
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Job Title</label>
                <input type="text" name="position" value="<?= h($editing['position'] ?? '') ?>"
                       class="form-control" placeholder="CEO, Director…">
              </div>
              <div class="form-group">
                <label class="form-label">Company</label>
                <input type="text" name="company" value="<?= h($editing['company'] ?? '') ?>"
                       class="form-control" placeholder="Acme Ltd">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Testimonial <span style="color:var(--clr-danger)">*</span></label>
              <textarea name="body" class="form-control" rows="4" required
                        placeholder="Write the customer's testimonial…"><?= h($editing['body'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Star Rating</label>
              <div style="display:flex;gap:6px;align-items:center" id="starPicker">
                <?php for ($s = 5; $s >= 1; $s--): ?>
                <label style="cursor:pointer;order:<?= 6 - $s ?>">
                  <input type="radio" name="rating" value="<?= $s ?>"
                         <?= ($editing['rating'] ?? 5) == $s ? 'checked' : '' ?>
                         style="display:none">
                  <i class="fas fa-star" data-val="<?= $s ?>"
                     style="font-size:1.3rem;color:<?= ($editing['rating'] ?? 5) >= $s ? 'var(--clr-gold)' : '#e2e8f0' ?>;transition:color .1s"></i>
                </label>
                <?php endfor; ?>
                <span id="ratingLabel" style="font-size:.8rem;color:var(--clr-muted);margin-left:6px"><?= ($editing['rating'] ?? 5) ?> stars</span>
              </div>
            </div>

            <div class="form-row" style="align-items:end">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?= (int)($editing['sort_order'] ?? 0) ?>"
                       class="form-control" min="0">
              </div>
              <div class="form-group">
                <div style="display:flex;flex-direction:column;gap:10px">
                  <label class="admin-toggle">
                    <input type="checkbox" name="is_active" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
                    <span class="admin-toggle-slider"></span>
                    <span class="admin-toggle-label">Active</span>
                  </label>
                  <label class="admin-toggle">
                    <input type="checkbox" name="is_featured" <?= ($editing['is_featured'] ?? 0) ? 'checked' : '' ?>>
                    <span class="admin-toggle-slider"></span>
                    <span class="admin-toggle-label">Featured</span>
                  </label>
                </div>
              </div>
            </div>

            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block" style="margin-top:8px">
              <i class="fas fa-save"></i> <?= $editing ? 'Update Testimonial' : 'Add Testimonial' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Star rating picker
const stars = document.querySelectorAll('#starPicker i[data-val]');
const label = document.getElementById('ratingLabel');
stars.forEach(star => {
  star.parentElement.addEventListener('change', function() {
    const val = +this.querySelector('input').value;
    stars.forEach(s => s.style.color = +s.dataset.val <= val ? 'var(--clr-gold)' : '#e2e8f0');
    label.textContent = val + ' star' + (val !== 1 ? 's' : '');
  });
});
</script>
</body>
</html>
