<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$id   = (int)($_GET['id'] ?? 0);
$dest = $id ? DB::row("SELECT * FROM destinations WHERE id=?", [$id]) : null;
if ($id && !$dest) redirect(url('admin/destinations.php'));

$regions = DB::rows("SELECT * FROM regions ORDER BY name");
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'region_id'        => (int)($_POST['region_id'] ?? 0) ?: null,
        'name'             => trim($_POST['name'] ?? ''),
        'slug'             => trim($_POST['slug'] ?? ''),
        'country'          => trim($_POST['country'] ?? ''),
        'continent'        => trim($_POST['continent'] ?? ''),
        'description'      => trim($_POST['description'] ?? ''),
        'highlights'       => json_encode(array_filter(array_map('trim', explode("\n", $_POST['highlights'] ?? '')))),
        'climate_info'     => trim($_POST['climate_info'] ?? ''),
        'best_time'        => trim($_POST['best_time'] ?? ''),
        'latitude'         => trim($_POST['latitude'] ?? '') ?: null,
        'longitude'        => trim($_POST['longitude'] ?? '') ?: null,
        'is_featured'      => isset($_POST['is_featured']) ? 1 : 0,
        'is_active'        => isset($_POST['is_active'])   ? 1 : 0,
        'meta_title'       => trim($_POST['meta_title'] ?? ''),
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'sort_order'       => (int)($_POST['sort_order'] ?? 0),
    ];

    if (!$data['name'])    $errors[] = 'Name is required.';
    if (!$data['country']) $errors[] = 'Country is required.';
    if (!$data['slug'])    $data['slug'] = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $data['name']));

    // Hero image upload
    if (!empty($_FILES['hero_image']['name'])) {
        $imgUrl = uploadImage($_FILES['hero_image'], 'destinations');
        if ($imgUrl) $data['hero_image'] = $imgUrl;
        else $errors[] = 'Image upload failed.';
    }

    if (!$errors) {
        if ($id) {
            $old = $dest;
            DB::update('destinations', $data, ['id' => $id]);
            auditLog('update', 'destinations', $id, $old, $data);
            flash('success', 'Destination updated.');
        } else {
            $newId = DB::insert('destinations', $data);
            auditLog('create', 'destinations', $newId, [], $data);
            flash('success', 'Destination created.');
            redirect(url('admin/destination-edit.php?id=' . $newId));
        }
        redirect(url('admin/destinations.php'));
    }
}
$dest = $dest ?? [];
$highlights = implode("\n", jd($dest['highlights'] ?? null, []));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $id ? 'Edit' : 'Add' ?> Destination — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/destinations.php') ?>">Destinations</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <span><?= $id ? 'Edit' : 'New' ?></span>
      </div>
    </div>
  </header>

  <div class="admin-content">
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger" style="margin-bottom:16px">
      <i class="fas fa-exclamation-circle"></i>
      <span><?= implode(' ', array_map('h', $errors)) ?></span>
    </div>
    <?php endif; ?>

    <div class="admin-page-title"><?= $id ? 'Edit Destination' : 'Add New Destination' ?></div>

    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

        <!-- Left -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Basic Info -->
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-map-marker-alt" style="color:var(--clr-gold)"></i> Basic Information</div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Destination Name <span class="text-danger">*</span></label>
                <input type="text" name="name" id="destName" value="<?= h($dest['name'] ?? '') ?>"
                       class="form-control" required placeholder="e.g. Masai Mara">
              </div>
              <div class="form-group">
                <label class="form-label">Slug <span class="text-danger">*</span></label>
                <input type="text" name="slug" id="destSlug" value="<?= h($dest['slug'] ?? '') ?>"
                       class="form-control" placeholder="masai-mara">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                  <label class="form-label">Country <span class="text-danger">*</span></label>
                  <input type="text" name="country" value="<?= h($dest['country'] ?? '') ?>" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Continent</label>
                  <select name="continent" class="form-control">
                    <option value="">Select…</option>
                    <?php foreach (['Africa','Asia','Europe','Americas','Oceania','Middle East'] as $c): ?>
                    <option value="<?= $c ?>" <?= ($dest['continent'] ?? '') === $c ? 'selected' : '' ?>><?= $c ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Region</label>
                <select name="region_id" class="form-control">
                  <option value="">No region</option>
                  <?php foreach ($regions as $r): ?>
                  <option value="<?= $r['id'] ?>" <?= ($dest['region_id'] ?? 0) == $r['id'] ? 'selected' : '' ?>><?= h($r['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="5"><?= h($dest['description'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Highlights <small style="color:var(--clr-muted)">(one per line)</small></label>
                <textarea name="highlights" class="form-control" rows="5" placeholder="Great Migration&#10;Big Five&#10;Endless plains"><?= h($highlights) ?></textarea>
              </div>
            </div>
          </div>

          <!-- Climate & Travel Info -->
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-cloud-sun" style="color:var(--clr-sky)"></i> Climate & Travel</div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Climate Info</label>
                <textarea name="climate_info" class="form-control" rows="3"><?= h($dest['climate_info'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Best Time to Visit</label>
                <input type="text" name="best_time" value="<?= h($dest['best_time'] ?? '') ?>"
                       class="form-control" placeholder="e.g. July – October (Great Migration)">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                  <label class="form-label">Latitude</label>
                  <input type="number" name="latitude" value="<?= h($dest['latitude'] ?? '') ?>" step="0.0000001" class="form-control" placeholder="-1.4061">
                </div>
                <div class="form-group">
                  <label class="form-label">Longitude</label>
                  <input type="number" name="longitude" value="<?= h($dest['longitude'] ?? '') ?>" step="0.0000001" class="form-control" placeholder="35.0027">
                </div>
              </div>
            </div>
          </div>

          <!-- SEO -->
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-search" style="color:var(--clr-sky)"></i> SEO</div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Meta Title <small id="metaTitleCount" style="color:var(--clr-muted)">0/60</small></label>
                <input type="text" name="meta_title" id="metaTitle" value="<?= h($dest['meta_title'] ?? '') ?>" class="form-control" maxlength="70">
              </div>
              <div class="form-group">
                <label class="form-label">Meta Description <small id="metaDescCount" style="color:var(--clr-muted)">0/160</small></label>
                <textarea name="meta_description" id="metaDesc" class="form-control" rows="3" maxlength="200"><?= h($dest['meta_description'] ?? '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Right sidebar -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Publish -->
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-cog" style="color:var(--clr-gold)"></i> Publish</div>
            <div class="admin-card-body">
              <label class="admin-toggle" style="margin-bottom:12px">
                <input type="checkbox" name="is_active" <?= ($dest['is_active'] ?? 1) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span>Active / Visible on site</span>
              </label>
              <label class="admin-toggle">
                <input type="checkbox" name="is_featured" <?= ($dest['is_featured'] ?? 0) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span>Featured on homepage</span>
              </label>
              <div class="form-group" style="margin-top:14px">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)($dest['sort_order'] ?? 0) ?>" class="form-control" min="0">
              </div>
            </div>
          </div>

          <!-- Hero Image -->
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-image" style="color:var(--clr-gold)"></i> Hero Image</div>
            <div class="admin-card-body">
              <?php if (!empty($dest['hero_image'])): ?>
              <img src="<?= h($dest['hero_image']) ?>" alt="Hero" style="width:100%;height:140px;object-fit:cover;border-radius:8px;margin-bottom:12px">
              <?php endif; ?>
              <input type="file" name="hero_image" accept="image/*" class="form-control" onchange="previewImg(this,'heroPreview')">
              <img id="heroPreview" src="" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-top:10px;display:none">
            </div>
          </div>

          <!-- Actions -->
          <div class="admin-card">
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:10px">
              <button type="submit" class="btn-admin btn-admin-primary btn-block">
                <i class="fas fa-save"></i> <?= $id ? 'Update Destination' : 'Create Destination' ?>
              </button>
              <a href="<?= url('admin/destinations.php') ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center">
                <i class="fas fa-arrow-left"></i> Back to List
              </a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function previewImg(input, previewId) {
  const preview = document.getElementById(previewId);
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}
// Slug generator
document.getElementById('destName')?.addEventListener('input', function() {
  const slugEl = document.getElementById('destSlug');
  if (!slugEl.dataset.manual) {
    slugEl.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
  }
});
document.getElementById('destSlug')?.addEventListener('input', function() { this.dataset.manual = '1'; });

// Meta counters
['metaTitle:metaTitleCount:60','metaDesc:metaDescCount:160'].forEach(s => {
  const [fid, cid, max] = s.split(':');
  const field = document.getElementById(fid), cnt = document.getElementById(cid);
  if (!field || !cnt) return;
  const update = () => { cnt.textContent = field.value.length + '/' + max; cnt.style.color = field.value.length > max ? 'var(--clr-danger)' : 'var(--clr-muted)'; };
  field.addEventListener('input', update); update();
});
</script>
</body>
</html>
