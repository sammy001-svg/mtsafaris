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

$dest       = $dest ?? [];
$highlights = implode("\n", jd($dest['highlights'] ?? null, []));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $id ? 'Edit' : 'New' ?> Destination — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/destinations.php') ?>">Destinations</a>
        <i class="fas fa-chevron-right"></i>
        <span><?= $id ? h(excerpt($dest['name'] ?? 'Edit', 30)) : 'New Destination' ?></span>
      </div>
    </div>
    <div class="admin-header-right">
      <?php if ($id && !empty($dest['slug'])): ?>
      <a href="<?= url('destinations.php?slug=' . h($dest['slug'])) ?>" target="_blank"
         class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-eye"></i> View Live
      </a>
      <?php endif; ?>
      <a href="<?= url('admin/destinations.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-arrow-left"></i> Back
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php if ($errors): ?>
    <div class="flash-msg flash-danger">
      <i class="fas fa-exclamation-circle"></i>
      <span><?= implode(' &nbsp;·&nbsp; ', array_map('h', $errors)) ?></span>
    </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title"><?= $id ? 'Edit Destination' : 'New Destination' ?></div>
        <div class="page-subtitle"><?= $id ? 'Update destination details, climate info, and SEO settings' : 'Add a new destination to your catalogue' ?></div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="destForm">
      <?= csrfField() ?>

      <!-- Sticky save bar -->
      <div class="save-bar">
        <div style="display:flex;align-items:center;gap:10px">
          <label class="admin-toggle" style="margin:0">
            <input type="checkbox" name="is_active" <?= ($dest['is_active'] ?? 1) ? 'checked' : '' ?>>
            <span class="admin-toggle-slider"></span>
            <span class="admin-toggle-label">Active</span>
          </label>
          <label class="admin-toggle" style="margin:0">
            <input type="checkbox" name="is_featured" <?= ($dest['is_featured'] ?? 0) ? 'checked' : '' ?>>
            <span class="admin-toggle-slider"></span>
            <span class="admin-toggle-label">Featured</span>
          </label>
        </div>
        <div style="display:flex;gap:10px">
          <a href="<?= url('admin/destinations.php') ?>" class="btn-admin btn-admin-secondary">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn-admin btn-admin-primary">
            <i class="fas fa-save"></i> <?= $id ? 'Update Destination' : 'Create Destination' ?>
          </button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

        <!-- Left column -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Basic Info -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-map-marker-alt" style="color:var(--clr-gold)"></i> Basic Information
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Destination Name <span style="color:var(--clr-danger)">*</span></label>
                <input type="text" name="name" id="destName"
                       value="<?= h($dest['name'] ?? '') ?>"
                       class="form-control" required placeholder="e.g. Masai Mara">
              </div>

              <div class="form-group">
                <label class="form-label">URL Slug</label>
                <div class="input-group">
                  <i class="ig-icon fas fa-link"></i>
                  <input type="text" name="slug" id="destSlug"
                         value="<?= h($dest['slug'] ?? '') ?>"
                         class="form-control" placeholder="masai-mara">
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Country <span style="color:var(--clr-danger)">*</span></label>
                  <input type="text" name="country" value="<?= h($dest['country'] ?? '') ?>"
                         class="form-control" required placeholder="Kenya">
                </div>
                <div class="form-group">
                  <label class="form-label">Continent</label>
                  <select name="continent" class="form-control">
                    <option value="">Select…</option>
                    <?php foreach (['Africa','Asia','Europe','Americas','Oceania','Middle East'] as $cont): ?>
                    <option value="<?= $cont ?>" <?= ($dest['continent'] ?? '') === $cont ? 'selected' : '' ?>><?= $cont ?></option>
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
                <textarea name="description" class="form-control" rows="5"
                          placeholder="Rich description of the destination for visitors…"><?= h($dest['description'] ?? '') ?></textarea>
              </div>

              <div class="form-group">
                <label class="form-label">Highlights <span class="form-hint" style="display:inline">(one per line)</span></label>
                <textarea name="highlights" class="form-control" rows="6"
                          placeholder="Great Migration&#10;Big Five&#10;Maasai Culture&#10;Endless plains"><?= h($highlights) ?></textarea>
              </div>
            </div>
          </div>

          <!-- Climate -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-cloud-sun" style="color:#0ea5e9"></i> Climate &amp; Travel
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Climate Info</label>
                <textarea name="climate_info" class="form-control" rows="3"
                          placeholder="Describe the climate, seasons, and weather patterns…"><?= h($dest['climate_info'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Best Time to Visit</label>
                <input type="text" name="best_time" value="<?= h($dest['best_time'] ?? '') ?>"
                       class="form-control" placeholder="e.g. July – October (Great Migration)">
              </div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Latitude</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-location-dot"></i>
                    <input type="number" name="latitude" value="<?= h($dest['latitude'] ?? '') ?>"
                           step="0.0000001" class="form-control" placeholder="-1.4061">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Longitude</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-location-dot"></i>
                    <input type="number" name="longitude" value="<?= h($dest['longitude'] ?? '') ?>"
                           step="0.0000001" class="form-control" placeholder="35.0027">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- SEO -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-search" style="color:#0ea5e9"></i> SEO Settings
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">
                  Meta Title
                  <span id="metaTitleCount" class="form-hint" style="display:inline;float:right">0/60</span>
                </label>
                <input type="text" name="meta_title" id="metaTitle"
                       value="<?= h($dest['meta_title'] ?? '') ?>"
                       class="form-control" maxlength="70"
                       placeholder="Leave blank to use destination name">
              </div>
              <div class="form-group">
                <label class="form-label">
                  Meta Description
                  <span id="metaDescCount" class="form-hint" style="display:inline;float:right">0/160</span>
                </label>
                <textarea name="meta_description" id="metaDesc"
                          class="form-control" rows="3" maxlength="200"
                          placeholder="150-160 character SEO description…"><?= h($dest['meta_description'] ?? '') ?></textarea>
              </div>

              <!-- Google preview -->
              <div class="seo-preview" id="seoPreview">
                <div class="seo-preview-url">mtsafaris.com/destinations.php?slug=<?= h($dest['slug'] ?? 'slug') ?></div>
                <div class="seo-preview-title" id="seoTitle"><?= h($dest['meta_title'] ?: ($dest['name'] ?? 'Destination Name')) ?></div>
                <div class="seo-preview-desc" id="seoDesc"><?= h($dest['meta_description'] ?? 'Meta description will appear here…') ?></div>
              </div>
            </div>
          </div>

        </div><!-- /left -->

        <!-- Right sidebar -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Publish settings -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-cog" style="color:var(--clr-gold)"></i> Settings
              </span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:14px">
              <label class="admin-toggle">
                <input type="checkbox" name="is_active" <?= ($dest['is_active'] ?? 1) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Active &amp; visible on site</span>
              </label>
              <label class="admin-toggle">
                <input type="checkbox" name="is_featured" <?= ($dest['is_featured'] ?? 0) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Featured on homepage</span>
              </label>
              <div class="form-group" style="margin:0">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?= (int)($dest['sort_order'] ?? 0) ?>"
                       class="form-control" min="0">
                <span class="form-hint">Lower = appears first</span>
              </div>
            </div>
          </div>

          <!-- Hero Image -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-image" style="color:var(--clr-gold)"></i> Hero Image
              </span>
            </div>
            <div class="admin-card-body">
              <?php if (!empty($dest['hero_image'])): ?>
              <img src="<?= h($dest['hero_image']) ?>" alt="Hero"
                   style="width:100%;height:150px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;border:1.5px solid var(--clr-border)">
              <?php endif; ?>
              <img id="heroPreview" src="" alt=""
                   style="width:100%;height:130px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:10px;display:none;border:1.5px solid var(--clr-border)">
              <input type="file" name="hero_image" accept="image/*"
                     class="form-control" onchange="previewImg(this,'heroPreview')">
              <span class="form-hint">Recommended: 1920×1080px JPG. Max 5MB.</span>
            </div>
          </div>

          <!-- Save actions -->
          <div class="admin-card">
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:10px">
              <button type="submit" class="btn-admin btn-admin-primary btn-block">
                <i class="fas fa-save"></i> <?= $id ? 'Update Destination' : 'Create Destination' ?>
              </button>
              <a href="<?= url('admin/destinations.php') ?>"
                 class="btn-admin btn-admin-secondary btn-block" style="text-align:center">
                <i class="fas fa-arrow-left"></i> Back to List
              </a>
            </div>
          </div>

        </div><!-- /right -->
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
const nameEl = document.getElementById('destName');
const slugEl = document.getElementById('destSlug');
nameEl?.addEventListener('input', function() {
  if (!slugEl.dataset.manual)
    slugEl.value = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
});
slugEl?.addEventListener('input', function() { this.dataset.manual = '1'; });

// Meta counters + SEO preview
function updateSeo() {
  const title = document.getElementById('metaTitle')?.value;
  const desc  = document.getElementById('metaDesc')?.value;
  const len1  = title?.length || 0;
  const len2  = desc?.length  || 0;

  const titleCnt = document.getElementById('metaTitleCount');
  const descCnt  = document.getElementById('metaDescCount');
  if (titleCnt) { titleCnt.textContent = len1+'/60'; titleCnt.style.color = len1 > 60 ? 'var(--clr-danger)' : 'var(--clr-muted)'; }
  if (descCnt)  { descCnt.textContent  = len2+'/160'; descCnt.style.color  = len2 > 160 ? 'var(--clr-danger)' : 'var(--clr-muted)'; }

  const seoTitle = document.getElementById('seoTitle');
  const seoDesc  = document.getElementById('seoDesc');
  if (seoTitle) seoTitle.textContent = title || nameEl?.value || 'Destination Name';
  if (seoDesc)  seoDesc.textContent  = desc  || 'Meta description will appear here…';
}

document.getElementById('metaTitle')?.addEventListener('input', updateSeo);
document.getElementById('metaDesc')?.addEventListener('input', updateSeo);
updateSeo();
</script>
</body>
</html>
