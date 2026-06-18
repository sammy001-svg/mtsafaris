<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id      = (int)($_GET['id'] ?? 0);
$pkg     = $id ? DB::row("SELECT * FROM packages WHERE id=?", [$id]) : null;
$isEdit  = (bool)$pkg;

$categories   = DB::rows("SELECT * FROM categories ORDER BY sort_order");
$destinations = DB::rows("SELECT * FROM destinations WHERE is_active=1 ORDER BY name");
$regions      = DB::rows("SELECT * FROM regions ORDER BY sort_order");

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title         = trim($_POST['title'] ?? '');
    $slug_in       = trim($_POST['slug'] ?? '');
    $slug_val      = $slug_in ?: slug($title);
    $type          = $_POST['type'] ?? 'group';
    $category_id   = (int)($_POST['category_id'] ?? 0);
    $destination_id= (int)($_POST['destination_id'] ?? 0);
    $tagline       = trim($_POST['tagline'] ?? '');
    $overview      = trim($_POST['overview'] ?? '');
    $duration_days = (int)($_POST['duration_days'] ?? 1);
    $duration_nights=(int)($_POST['duration_nights'] ?? 0);
    $min_pax       = (int)($_POST['min_pax'] ?? 1);
    $max_pax       = (int)($_POST['max_pax'] ?? 0) ?: null;
    $base_price    = (float)($_POST['base_price'] ?? 0);
    $sale_price    = strlen(trim($_POST['sale_price']??'')) ? (float)$_POST['sale_price'] : null;
    $child_price   = strlen(trim($_POST['child_price']??'')) ? (float)$_POST['child_price'] : null;
    $accommodation = trim($_POST['accommodation'] ?? '');
    $transport     = trim($_POST['transport'] ?? '');
    $meals         = trim($_POST['meals'] ?? '');
    $difficulty    = trim($_POST['difficulty'] ?? 'easy');
    $physical_req  = trim($_POST['physical_req'] ?? '');
    $is_featured   = isset($_POST['is_featured']) ? 1 : 0;
    $is_active     = isset($_POST['is_active']) ? 1 : 0;

    // JSON fields
    $included      = array_values(array_filter(array_map('trim', explode("\n", $_POST['included'] ?? ''))));
    $excluded      = array_values(array_filter(array_map('trim', explode("\n", $_POST['excluded'] ?? ''))));
    $departure_dates = array_values(array_filter(array_map('trim', explode("\n", $_POST['departure_dates'] ?? ''))));

    // Itinerary
    $itinerary = [];
    $iDays   = $_POST['itin_day']    ?? [];
    $iTitles = $_POST['itin_title']  ?? [];
    $iDescs  = $_POST['itin_desc']   ?? [];
    $iMeals  = $_POST['itin_meals']  ?? [];
    foreach ($iDays as $k => $day) {
        if (empty($iTitles[$k])) continue;
        $itinerary[] = ['day'=>(int)$day,'title'=>trim($iTitles[$k]),'description'=>trim($iDescs[$k]??''),'meals'=>trim($iMeals[$k]??'')];
    }

    // Add-ons
    $addons = [];
    $aNames  = $_POST['addon_name']  ?? [];
    $aPrices = $_POST['addon_price'] ?? [];
    foreach ($aNames as $k => $name) {
        if (!trim($name)) continue;
        $addons[] = ['name'=>trim($name),'price'=>(float)($aPrices[$k]??0)];
    }

    // FAQs
    $faqs = [];
    $fQs = $_POST['faq_q'] ?? [];
    $fAs = $_POST['faq_a'] ?? [];
    foreach ($fQs as $k => $q) {
        if (!trim($q)) continue;
        $faqs[] = ['q'=>trim($q),'a'=>trim($fAs[$k]??'')];
    }

    // Hero image upload
    $hero_image = $pkg['hero_image'] ?? '';
    if (!empty($_FILES['hero_image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['hero_image'], 'packages');
        if ($uploaded) $hero_image = $uploaded;
        else $errors[] = 'Failed to upload hero image.';
    }

    // Gallery (multiple)
    $gallery = jd($pkg['gallery'] ?? '[]', []);
    if (!empty($_FILES['gallery']['tmp_name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $k => $tmp) {
            if (!$tmp) continue;
            $gFile = ['name'=>$_FILES['gallery']['name'][$k],'type'=>$_FILES['gallery']['type'][$k],'tmp_name'=>$tmp,'error'=>$_FILES['gallery']['error'][$k],'size'=>$_FILES['gallery']['size'][$k]];
            $up = uploadImage($gFile, 'packages/gallery');
            if ($up) $gallery[] = $up;
        }
    }
    // Remove gallery items
    if (!empty($_POST['remove_gallery'])) {
        foreach ($_POST['remove_gallery'] as $idx) {
            unset($gallery[(int)$idx]);
        }
        $gallery = array_values($gallery);
    }

    // Validate
    if (!$title)       $errors[] = 'Package title is required.';
    if (!$base_price)  $errors[] = 'Base price is required.';
    if ($duration_days < 1) $errors[] = 'Duration must be at least 1 day.';

    // Check slug unique
    $existing = DB::row("SELECT id FROM packages WHERE slug=? AND id!=?", [$slug_val, $id]);
    if ($existing) {
        $slug_val .= '-' . time();
    }

    if (!$errors) {
        $data = [
            'title'          => $title,
            'slug'           => $slug_val,
            'type'           => $type,
            'category_id'   => $category_id ?: null,
            'destination_id'=> $destination_id ?: null,
            'tagline'        => $tagline,
            'overview'       => $overview,
            'duration_days'  => $duration_days,
            'duration_nights'=> $duration_nights,
            'min_pax'        => $min_pax,
            'max_pax'        => $max_pax,
            'base_price'     => $base_price,
            'sale_price'     => $sale_price,
            'child_price'    => $child_price,
            'accommodation'  => $accommodation,
            'transport'      => $transport,
            'meals'          => $meals,
            'difficulty'     => $difficulty,
            'physical_req'   => $physical_req,
            'included'       => json_encode($included),
            'excluded'       => json_encode($excluded),
            'itinerary'      => json_encode($itinerary),
            'addons'         => json_encode($addons),
            'faqs'           => json_encode($faqs),
            'gallery'        => json_encode($gallery),
            'departure_dates'=> json_encode($departure_dates),
            'hero_image'     => $hero_image,
            'is_featured'    => $is_featured,
            'is_active'      => $is_active,
        ];

        if ($isEdit) {
            DB::update('packages', $data, ['id'=>$id]);
            auditLog('update', 'packages', $id, $pkg, $data);
            flash('success', 'Package updated successfully.');
        } else {
            $newId = DB::insert('packages', $data);
            auditLog('create', 'packages', $newId, [], $data);
            flash('success', 'Package created successfully.');
            redirect(url('admin/package-edit.php?id='.$newId));
        }
        $success = true;
        $pkg = DB::row("SELECT * FROM packages WHERE id=?", [$isEdit ? $id : $newId]);
    }
}

// Pre-populate from saved data
$p = $pkg ?? [];
$itin    = jd($p['itinerary']??'[]', []);
$addonsD = jd($p['addons']??'[]', []);
$faqsD   = jd($p['faqs']??'[]', []);
$galleryD= jd($p['gallery']??'[]', []);
$inc     = implode("\n", jd($p['included']??'[]', []));
$exc     = implode("\n", jd($p['excluded']??'[]', []));
$depDates= implode("\n", jd($p['departure_dates']??'[]', []));

$pageTitle   = ($isEdit ? 'Edit Package' : 'New Package') . ' | MT Safaris Admin';
$extraCss    = ['admin.css'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <?= seoMeta($pageTitle,'','') ?>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-wrapper">
<header class="admin-header">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <div class="admin-header-title"><?= $isEdit ? 'Edit Package: '.h(excerpt($pkg['title']??'',50)) : 'Add New Package' ?></div>
  <div class="admin-header-actions">
    <?php if ($isEdit): ?><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" target="_blank" class="btn btn-admin-outline btn-sm"><i class="fas fa-eye"></i> Preview</a><?php endif; ?>
    <a href="<?= url('admin/packages.php') ?>" class="btn btn-admin-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</header>
<main class="admin-main">

<?php if ($errors): ?><div class="alert alert-danger"><ul style="margin:0;padding-left:20px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Package saved successfully.</div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="packageForm">
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">
    <!-- Left Column -->
    <div>
      <!-- Basic Info -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header"><h3>Basic Information</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Package Title *</label>
            <input type="text" name="title" id="package_title" class="admin-input" required value="<?= h($p['title']??'') ?>" placeholder="e.g. Masai Mara 7-Day Safari">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">URL Slug *</label>
            <div style="display:flex;gap:8px">
              <input type="text" name="slug" id="package_slug" class="admin-input" value="<?= h($p['slug']??'') ?>" placeholder="auto-generated from title">
              <button type="button" onclick="document.getElementById('package_slug').value=generateSlug(document.getElementById('package_title').value)" class="btn btn-admin-outline btn-sm">Refresh</button>
            </div>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Tagline / Short Description</label>
            <input type="text" name="tagline" class="admin-input" value="<?= h($p['tagline']??'') ?>" placeholder="A short catchy tagline for the package">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Overview / Full Description</label>
            <div data-editor style="min-height:200px"><?= $p['overview']??'' ?></div>
            <input type="hidden" name="overview" id="overview_field" value="<?= h($p['overview']??'') ?>">
          </div>
        </div>
      </div>

      <!-- Itinerary Builder -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
          <h3>Day-by-Day Itinerary</h3>
          <button type="button" id="addItin" class="btn btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Add Day</button>
        </div>
        <div class="admin-card-body">
          <div id="itineraryList">
            <?php foreach ($itin as $k => $day): ?>
            <div class="itin-item" style="border:1px solid var(--admin-border);border-radius:8px;padding:16px;margin-bottom:12px;position:relative">
              <button type="button" class="itin-remove" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#ef4444;cursor:pointer"><i class="fas fa-times"></i></button>
              <div style="display:grid;grid-template-columns:80px 1fr;gap:12px;margin-bottom:10px">
                <div><label class="admin-label">Day</label><input type="number" name="itin_day[]" class="admin-input" value="<?= (int)$day['day'] ?>" min="1"></div>
                <div><label class="admin-label">Title</label><input type="text" name="itin_title[]" class="admin-input" value="<?= h($day['title']) ?>" required></div>
              </div>
              <div class="admin-form-group"><label class="admin-label">Description</label><textarea name="itin_desc[]" class="admin-input" rows="3"><?= h($day['description']??'') ?></textarea></div>
              <div><label class="admin-label">Meals (e.g. Breakfast, Lunch, Dinner)</label><input type="text" name="itin_meals[]" class="admin-input" value="<?= h($day['meals']??'') ?>"></div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php if (!$itin): ?><p style="color:var(--admin-muted);font-size:.875rem">No itinerary days added yet. Click "Add Day" to begin building the itinerary.</p><?php endif; ?>
        </div>
      </div>

      <!-- Included / Excluded -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header"><h3>What's Included / Excluded</h3></div>
        <div class="admin-card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div>
              <label class="admin-label">Included (one per line)</label>
              <textarea name="included" class="admin-input" rows="8" placeholder="Game drives&#10;Accommodation&#10;All meals&#10;Park fees"><?= h($inc) ?></textarea>
            </div>
            <div>
              <label class="admin-label">Not Included (one per line)</label>
              <textarea name="excluded" class="admin-input" rows="8" placeholder="International flights&#10;Travel insurance&#10;Visa fees&#10;Tips"><?= h($exc) ?></textarea>
            </div>
          </div>
        </div>
      </div>

      <!-- Add-ons -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
          <h3>Optional Add-ons</h3>
          <button type="button" id="addAddon" class="btn btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Add</button>
        </div>
        <div class="admin-card-body">
          <div id="addonList">
            <?php foreach ($addonsD as $a): ?>
            <div class="addon-item" style="display:grid;grid-template-columns:1fr 140px auto;gap:10px;align-items:end;margin-bottom:10px">
              <div><label class="admin-label">Add-on Name</label><input type="text" name="addon_name[]" class="admin-input" value="<?= h($a['name']) ?>"></div>
              <div><label class="admin-label">Price (USD)</label><input type="number" name="addon_price[]" class="admin-input" value="<?= $a['price'] ?>" min="0" step="0.01"></div>
              <button type="button" class="btn btn-danger btn-sm addon-remove" style="margin-bottom:0;align-self:flex-end"><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
            <?php if (!$addonsD): ?><p class="no-addons" style="color:var(--admin-muted);font-size:.875rem">No add-ons added.</p><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- FAQs -->
      <div class="admin-card" style="margin-bottom:24px">
        <div class="admin-card-header" style="display:flex;justify-content:space-between;align-items:center">
          <h3>Frequently Asked Questions</h3>
          <button type="button" id="addFaq" class="btn btn-admin-primary btn-sm"><i class="fas fa-plus"></i> Add FAQ</button>
        </div>
        <div class="admin-card-body">
          <div id="faqList">
            <?php foreach ($faqsD as $f): ?>
            <div class="faq-item" style="border:1px solid var(--admin-border);border-radius:8px;padding:14px;margin-bottom:10px;position:relative">
              <button type="button" class="faq-remove" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#ef4444;cursor:pointer"><i class="fas fa-times"></i></button>
              <div class="admin-form-group"><label class="admin-label">Question</label><input type="text" name="faq_q[]" class="admin-input" value="<?= h($f['q']) ?>"></div>
              <div><label class="admin-label">Answer</label><textarea name="faq_a[]" class="admin-input" rows="3"><?= h($f['a']) ?></textarea></div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column -->
    <div>
      <!-- Publish -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Publish Settings</h3></div>
        <div class="admin-card-body">
          <label class="admin-label">Status</label>
          <div style="display:flex;gap:20px;margin-bottom:16px">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
              <input type="checkbox" name="is_active" <?= ($p['is_active']??1) ? 'checked' : '' ?>>
              <span>Active (visible on site)</span>
            </label>
          </div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
            <input type="checkbox" name="is_featured" <?= ($p['is_featured']??0) ? 'checked' : '' ?>>
            <span>Featured (show on homepage)</span>
          </label>
          <hr style="margin:16px 0">
          <button type="submit" class="btn btn-admin-primary btn-block"><i class="fas fa-save"></i> <?= $isEdit ? 'Update Package' : 'Create Package' ?></button>
          <?php if ($isEdit): ?><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" target="_blank" class="btn btn-admin-outline btn-block" style="margin-top:8px"><i class="fas fa-eye"></i> View Live</a><?php endif; ?>
        </div>
      </div>

      <!-- Classification -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Classification</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Package Type</label>
            <select name="type" class="admin-select">
              <?php foreach (['group'=>'Group Tour','private'=>'Private Tour','luxury'=>'Luxury','budget'=>'Budget','corporate'=>'Corporate','honeymoon'=>'Honeymoon','family'=>'Family'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($p['type']??'group')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Category</label>
            <select name="category_id" class="admin-select">
              <option value="">-- Select Category --</option>
              <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= ($p['category_id']??0)==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Destination</label>
            <select name="destination_id" class="admin-select">
              <option value="">-- Select Destination --</option>
              <?php foreach ($destinations as $dest): ?>
              <option value="<?= $dest['id'] ?>" <?= ($p['destination_id']??0)==$dest['id']?'selected':'' ?>><?= h($dest['name']) ?>, <?= h($dest['country']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Difficulty Level</label>
            <select name="difficulty" class="admin-select">
              <?php foreach (['easy'=>'Easy','moderate'=>'Moderate','challenging'=>'Challenging','extreme'=>'Extreme'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($p['difficulty']??'easy')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Duration & Group -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Duration & Group Size</h3></div>
        <div class="admin-card-body">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div><label class="admin-label">Days *</label><input type="number" name="duration_days" class="admin-input" value="<?= h($p['duration_days']??1) ?>" min="1" required></div>
            <div><label class="admin-label">Nights</label><input type="number" name="duration_nights" class="admin-input" value="<?= h($p['duration_nights']??0) ?>" min="0"></div>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div><label class="admin-label">Min Pax</label><input type="number" name="min_pax" class="admin-input" value="<?= h($p['min_pax']??1) ?>" min="1"></div>
            <div><label class="admin-label">Max Pax</label><input type="number" name="max_pax" class="admin-input" value="<?= h($p['max_pax']??'') ?>" min="1" placeholder="Unlimited"></div>
          </div>
        </div>
      </div>

      <!-- Pricing -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Pricing</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Base Price (USD) *</label>
            <input type="number" name="base_price" class="admin-input" value="<?= h($p['base_price']??'') ?>" min="0" step="0.01" required>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Sale Price (USD) <small style="color:var(--admin-muted)">leave blank for no discount</small></label>
            <input type="number" name="sale_price" class="admin-input" value="<?= h($p['sale_price']??'') ?>" min="0" step="0.01">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Child Price (USD)</label>
            <input type="number" name="child_price" class="admin-input" value="<?= h($p['child_price']??'') ?>" min="0" step="0.01">
          </div>
        </div>
      </div>

      <!-- Logistics -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Logistics</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group"><label class="admin-label">Accommodation</label><input type="text" name="accommodation" class="admin-input" value="<?= h($p['accommodation']??'') ?>" placeholder="e.g. 5-star lodges"></div>
          <div class="admin-form-group"><label class="admin-label">Transport</label><input type="text" name="transport" class="admin-input" value="<?= h($p['transport']??'') ?>" placeholder="e.g. 4x4 Safari Vehicle"></div>
          <div class="admin-form-group"><label class="admin-label">Meals</label><input type="text" name="meals" class="admin-input" value="<?= h($p['meals']??'') ?>" placeholder="e.g. Full Board"></div>
          <div class="admin-form-group"><label class="admin-label">Physical Requirements</label><textarea name="physical_req" class="admin-input" rows="2" placeholder="Any physical requirements or health advisories"><?= h($p['physical_req']??'') ?></textarea></div>
        </div>
      </div>

      <!-- Departure Dates -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Departure Dates</h3></div>
        <div class="admin-card-body">
          <label class="admin-label">Dates (one per line, YYYY-MM-DD format)</label>
          <textarea name="departure_dates" class="admin-input" rows="5" placeholder="2025-01-15&#10;2025-02-01&#10;2025-03-10"><?= h($depDates) ?></textarea>
        </div>
      </div>

      <!-- Hero Image -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Hero Image</h3></div>
        <div class="admin-card-body">
          <?php if (!empty($p['hero_image'])): ?>
          <img src="<?= h($p['hero_image']) ?>" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:12px" alt="current hero">
          <?php endif; ?>
          <div class="upload-area" id="heroUpload">
            <i class="fas fa-image upload-icon"></i>
            <p>Drag & drop or <span style="color:var(--admin-primary)">browse</span></p>
            <small>JPG, PNG, WebP — Max 5MB</small>
            <input type="file" name="hero_image" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer">
          </div>
        </div>
      </div>

      <!-- Gallery -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Gallery</h3></div>
        <div class="admin-card-body">
          <?php if ($galleryD): ?>
          <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:12px">
            <?php foreach ($galleryD as $gi => $img): ?>
            <div style="position:relative">
              <img src="<?= h($img) ?>" style="width:100%;height:72px;object-fit:cover;border-radius:6px" alt="">
              <label style="position:absolute;top:4px;right:4px;background:rgba(239,68,68,.9);border-radius:50%;width:20px;height:20px;display:grid;place-items:center;cursor:pointer">
                <input type="checkbox" name="remove_gallery[]" value="<?= $gi ?>" style="display:none" onchange="this.parentElement.style.opacity=this.checked?'.4':'1'">
                <i class="fas fa-times" style="color:#fff;font-size:.6rem"></i>
              </label>
            </div>
            <?php endforeach; ?>
          </div>
          <small style="color:var(--admin-muted)">Click <i class="fas fa-times" style="color:#ef4444"></i> on an image to mark it for removal.</small><br>
          <?php endif; ?>
          <div class="upload-area" style="margin-top:12px;position:relative">
            <i class="fas fa-images upload-icon"></i>
            <p>Add gallery images</p>
            <input type="file" name="gallery[]" accept="image/*" multiple style="position:absolute;inset:0;opacity:0;cursor:pointer">
          </div>
        </div>
      </div>
    </div>
  </div><!-- /grid -->
</form>
</main>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function generateSlug(str) {
  return str.toLowerCase().trim().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'');
}
document.getElementById('package_title').addEventListener('input', function(){
  if (!<?= $isEdit ? 'true' : 'false' ?> || !document.getElementById('package_slug').value) {
    document.getElementById('package_slug').value = generateSlug(this.value);
  }
});

// Sync rich editor to hidden input on form submit
document.getElementById('packageForm').addEventListener('submit', function(){
  const ed = document.querySelector('[data-editor]');
  if (ed) document.getElementById('overview_field').value = ed.innerHTML;
});

// Itinerary add
let itinCount = <?= count($itin) ?>;
document.getElementById('addItin').addEventListener('click', function(){
  itinCount++;
  const div = document.createElement('div');
  div.className = 'itin-item';
  div.style.cssText = 'border:1px solid var(--admin-border);border-radius:8px;padding:16px;margin-bottom:12px;position:relative';
  div.innerHTML = `<button type="button" class="itin-remove" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#ef4444;cursor:pointer"><i class="fas fa-times"></i></button>
    <div style="display:grid;grid-template-columns:80px 1fr;gap:12px;margin-bottom:10px">
      <div><label class="admin-label">Day</label><input type="number" name="itin_day[]" class="admin-input" value="${itinCount}" min="1"></div>
      <div><label class="admin-label">Title</label><input type="text" name="itin_title[]" class="admin-input" required></div>
    </div>
    <div class="admin-form-group"><label class="admin-label">Description</label><textarea name="itin_desc[]" class="admin-input" rows="3"></textarea></div>
    <div><label class="admin-label">Meals</label><input type="text" name="itin_meals[]" class="admin-input"></div>`;
  document.getElementById('itineraryList').appendChild(div);
  div.querySelector('.itin-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.itin-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.itin-item').remove()));

// Add-on add
document.getElementById('addAddon').addEventListener('click', function(){
  document.querySelector('.no-addons')?.remove();
  const div = document.createElement('div');
  div.className = 'addon-item';
  div.style.cssText = 'display:grid;grid-template-columns:1fr 140px auto;gap:10px;align-items:end;margin-bottom:10px';
  div.innerHTML = `<div><label class="admin-label">Add-on Name</label><input type="text" name="addon_name[]" class="admin-input"></div>
    <div><label class="admin-label">Price (USD)</label><input type="number" name="addon_price[]" class="admin-input" value="0" min="0" step="0.01"></div>
    <button type="button" class="btn btn-danger btn-sm addon-remove" style="align-self:flex-end"><i class="fas fa-trash"></i></button>`;
  document.getElementById('addonList').appendChild(div);
  div.querySelector('.addon-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.addon-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.addon-item').remove()));

// FAQ add
document.getElementById('addFaq').addEventListener('click', function(){
  const div = document.createElement('div');
  div.className = 'faq-item';
  div.style.cssText = 'border:1px solid var(--admin-border);border-radius:8px;padding:14px;margin-bottom:10px;position:relative';
  div.innerHTML = `<button type="button" class="faq-remove" style="position:absolute;top:8px;right:8px;background:none;border:none;color:#ef4444;cursor:pointer"><i class="fas fa-times"></i></button>
    <div class="admin-form-group"><label class="admin-label">Question</label><input type="text" name="faq_q[]" class="admin-input"></div>
    <div><label class="admin-label">Answer</label><textarea name="faq_a[]" class="admin-input" rows="3"></textarea></div>`;
  document.getElementById('faqList').appendChild(div);
  div.querySelector('.faq-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.faq-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.faq-item').remove()));
</script>
</body>
</html>
