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

    $title          = trim($_POST['title'] ?? '');
    $slug_in        = trim($_POST['slug'] ?? '');
    $slug_val       = $slug_in ?: slug($title);
    $type           = $_POST['type'] ?? 'group';
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $destination_id = (int)($_POST['destination_id'] ?? 0);
    $tagline        = trim($_POST['tagline'] ?? '');
    $overview       = trim($_POST['overview'] ?? '');
    $duration_days  = (int)($_POST['duration_days'] ?? 1);
    $duration_nights= (int)($_POST['duration_nights'] ?? 0);
    $min_pax        = (int)($_POST['min_pax'] ?? 1);
    $max_pax        = (int)($_POST['max_pax'] ?? 0) ?: null;
    $base_price     = (float)($_POST['base_price'] ?? 0);
    $sale_price     = strlen(trim($_POST['sale_price'] ?? '')) ? (float)$_POST['sale_price'] : null;
    $child_price    = strlen(trim($_POST['child_price'] ?? '')) ? (float)$_POST['child_price'] : null;
    $accommodation  = trim($_POST['accommodation'] ?? '');
    $transport      = trim($_POST['transport'] ?? '');
    $meals          = trim($_POST['meals'] ?? '');
    $difficulty     = trim($_POST['difficulty'] ?? 'easy');
    $physical_req   = trim($_POST['physical_req'] ?? '');
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $is_active      = isset($_POST['is_active']) ? 1 : 0;

    $included       = array_values(array_filter(array_map('trim', explode("\n", $_POST['included'] ?? ''))));
    $excluded       = array_values(array_filter(array_map('trim', explode("\n", $_POST['excluded'] ?? ''))));
    $departure_dates= array_values(array_filter(array_map('trim', explode("\n", $_POST['departure_dates'] ?? ''))));

    $itinerary = [];
    $iDays   = $_POST['itin_day']   ?? [];
    $iTitles = $_POST['itin_title'] ?? [];
    $iDescs  = $_POST['itin_desc']  ?? [];
    $iMeals  = $_POST['itin_meals'] ?? [];
    foreach ($iDays as $k => $day) {
        if (empty($iTitles[$k])) continue;
        $itinerary[] = ['day'=>(int)$day,'title'=>trim($iTitles[$k]),'description'=>trim($iDescs[$k]??''),'meals'=>trim($iMeals[$k]??'')];
    }

    $addons  = [];
    $aNames  = $_POST['addon_name']  ?? [];
    $aPrices = $_POST['addon_price'] ?? [];
    foreach ($aNames as $k => $name) {
        if (!trim($name)) continue;
        $addons[] = ['name'=>trim($name),'price'=>(float)($aPrices[$k]??0)];
    }

    $faqs = [];
    $fQs  = $_POST['faq_q'] ?? [];
    $fAs  = $_POST['faq_a'] ?? [];
    foreach ($fQs as $k => $q) {
        if (!trim($q)) continue;
        $faqs[] = ['q'=>trim($q),'a'=>trim($fAs[$k]??'')];
    }

    $hero_image = $pkg['hero_image'] ?? '';
    if (!empty($_FILES['hero_image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['hero_image'], 'packages');
        if ($uploaded) $hero_image = $uploaded;
        else $errors[] = 'Failed to upload hero image.';
    }

    $gallery = jd($pkg['gallery'] ?? '[]', []);
    if (!empty($_FILES['gallery']['tmp_name'][0])) {
        foreach ($_FILES['gallery']['tmp_name'] as $k => $tmp) {
            if (!$tmp) continue;
            $gFile = ['name'=>$_FILES['gallery']['name'][$k],'type'=>$_FILES['gallery']['type'][$k],'tmp_name'=>$tmp,'error'=>$_FILES['gallery']['error'][$k],'size'=>$_FILES['gallery']['size'][$k]];
            $up = uploadImage($gFile, 'packages/gallery');
            if ($up) $gallery[] = $up;
        }
    }
    if (!empty($_POST['remove_gallery'])) {
        foreach ($_POST['remove_gallery'] as $idx) { unset($gallery[(int)$idx]); }
        $gallery = array_values($gallery);
    }

    if (!$title)       $errors[] = 'Package title is required.';
    if (!$base_price)  $errors[] = 'Base price is required.';
    if ($duration_days < 1) $errors[] = 'Duration must be at least 1 day.';

    $existing = DB::row("SELECT id FROM packages WHERE slug=? AND id!=?", [$slug_val, $id]);
    if ($existing) $slug_val .= '-' . time();

    if (!$errors) {
        $data = [
            'title'           => $title,
            'slug'            => $slug_val,
            'type'            => $type,
            'category_id'    => $category_id ?: null,
            'destination_id' => $destination_id ?: null,
            'tagline'         => $tagline,
            'overview'        => $overview,
            'duration_days'   => $duration_days,
            'duration_nights' => $duration_nights,
            'min_pax'         => $min_pax,
            'max_pax'         => $max_pax,
            'base_price'      => $base_price,
            'sale_price'      => $sale_price,
            'child_price'     => $child_price,
            'accommodation'   => $accommodation,
            'transport'       => $transport,
            'meals'           => $meals,
            'difficulty'      => $difficulty,
            'physical_req'    => $physical_req,
            'included'        => json_encode($included),
            'excluded'        => json_encode($excluded),
            'itinerary'       => json_encode($itinerary),
            'addons'          => json_encode($addons),
            'faqs'            => json_encode($faqs),
            'gallery'         => json_encode($gallery),
            'departure_dates' => json_encode($departure_dates),
            'hero_image'      => $hero_image,
            'is_featured'     => $is_featured,
            'is_active'       => $is_active,
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

$p        = $pkg ?? [];
$itin     = jd($p['itinerary']      ?? '[]', []);
$addonsD  = jd($p['addons']         ?? '[]', []);
$faqsD    = jd($p['faqs']           ?? '[]', []);
$galleryD = jd($p['gallery']        ?? '[]', []);
$inc      = implode("\n", jd($p['included']       ?? '[]', []));
$exc      = implode("\n", jd($p['excluded']       ?? '[]', []));
$depDates = implode("\n", jd($p['departure_dates']?? '[]', []));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $isEdit ? 'Edit Package' : 'New Package' ?> — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/packages.php') ?>">Packages</a>
        <i class="fas fa-chevron-right"></i>
        <span><?= $isEdit ? h(excerpt($pkg['title'] ?? 'Edit', 35)) : 'New Package' ?></span>
      </div>
    </div>
    <div class="admin-header-right">
      <?php if ($isEdit): ?>
      <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>" target="_blank"
         class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-eye"></i> Preview
      </a>
      <?php endif; ?>
      <a href="<?= url('admin/packages.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-arrow-left"></i> Back
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="margin:0;padding-left:18px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title"><?= $isEdit ? 'Edit Package' : 'New Package' ?></div>
        <div class="page-subtitle"><?= $isEdit ? 'Update itinerary, pricing, logistics, and publishing settings' : 'Build a new tour package with full itinerary' ?></div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="packageForm">
      <?= csrfField() ?>

      <!-- Sticky save bar -->
      <div class="save-bar">
        <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
          <label class="admin-toggle" style="margin:0">
            <input type="checkbox" name="is_active" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>>
            <span class="admin-toggle-slider"></span>
            <span class="admin-toggle-label">Active</span>
          </label>
          <label class="admin-toggle" style="margin:0">
            <input type="checkbox" name="is_featured" <?= ($p['is_featured'] ?? 0) ? 'checked' : '' ?>>
            <span class="admin-toggle-slider"></span>
            <span class="admin-toggle-label">Featured</span>
          </label>
        </div>
        <div style="display:flex;gap:10px">
          <a href="<?= url('admin/packages.php') ?>" class="btn-admin btn-admin-secondary">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn-admin btn-admin-primary">
            <i class="fas fa-save"></i> <?= $isEdit ? 'Update Package' : 'Create Package' ?>
          </button>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start">

        <!-- ===== LEFT COLUMN ===== -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Basic Info -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-box-open" style="color:var(--clr-gold)"></i> Basic Information
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Package Title <span style="color:var(--clr-danger)">*</span></label>
                <input type="text" name="title" id="package_title" class="form-control" required
                       value="<?= h($p['title'] ?? '') ?>" placeholder="e.g. Masai Mara 7-Day Wildlife Safari">
              </div>
              <div class="form-group">
                <label class="form-label">URL Slug</label>
                <div style="display:flex;gap:8px">
                  <div class="input-group" style="flex:1">
                    <i class="ig-icon fas fa-link"></i>
                    <input type="text" name="slug" id="package_slug" class="form-control"
                           value="<?= h($p['slug'] ?? '') ?>" placeholder="auto-generated from title">
                  </div>
                  <button type="button" onclick="document.getElementById('package_slug').value=generateSlug(document.getElementById('package_title').value)"
                          class="btn-admin btn-admin-secondary">
                    <i class="fas fa-refresh"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Tagline</label>
                <input type="text" name="tagline" class="form-control"
                       value="<?= h($p['tagline'] ?? '') ?>"
                       placeholder="A short catchy tagline for the package">
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Overview / Full Description</label>
                <div id="overviewEditor" class="pkg-editor-content" contenteditable="true"
                     data-placeholder="Write a detailed overview of this package…"><?= $p['overview'] ?? '' ?></div>
                <input type="hidden" name="overview" id="overview_field" value="<?= h($p['overview'] ?? '') ?>">
              </div>
            </div>
          </div>

          <!-- Itinerary -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-route" style="color:var(--clr-gold)"></i> Day-by-Day Itinerary
              </span>
              <button type="button" id="addItin" class="btn-admin btn-admin-primary btn-admin-sm">
                <i class="fas fa-plus"></i> Add Day
              </button>
            </div>
            <div class="admin-card-body">
              <div id="itineraryList">
                <?php foreach ($itin as $k => $day): ?>
                <div class="itin-item">
                  <button type="button" class="itin-remove" title="Remove day">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="form-row" style="margin-bottom:10px">
                    <div class="form-group" style="max-width:90px">
                      <label class="form-label">Day</label>
                      <input type="number" name="itin_day[]" class="form-control" value="<?= (int)$day['day'] ?>" min="1">
                    </div>
                    <div class="form-group">
                      <label class="form-label">Title <span style="color:var(--clr-danger)">*</span></label>
                      <input type="text" name="itin_title[]" class="form-control" value="<?= h($day['title']) ?>" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="itin_desc[]" class="form-control" rows="3"><?= h($day['description'] ?? '') ?></textarea>
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Meals</label>
                    <input type="text" name="itin_meals[]" class="form-control" value="<?= h($day['meals'] ?? '') ?>" placeholder="Breakfast, Lunch, Dinner">
                  </div>
                </div>
                <?php endforeach; ?>
                <?php if (!$itin): ?>
                <p class="no-itin" style="color:var(--clr-muted);font-size:.875rem">No itinerary days added yet. Click "Add Day" above to begin building the itinerary.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Included / Excluded -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-list-check" style="color:var(--clr-gold)"></i> What's Included / Excluded
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-row">
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label" style="color:var(--clr-success)"><i class="fas fa-check"></i> Included</label>
                  <textarea name="included" class="form-control" rows="8"
                            placeholder="Game drives&#10;Accommodation&#10;All meals&#10;Park fees"><?= h($inc) ?></textarea>
                  <span class="form-hint">One item per line</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label" style="color:var(--clr-danger)"><i class="fas fa-times"></i> Not Included</label>
                  <textarea name="excluded" class="form-control" rows="8"
                            placeholder="International flights&#10;Travel insurance&#10;Visa fees&#10;Tips"><?= h($exc) ?></textarea>
                  <span class="form-hint">One item per line</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Add-ons -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-puzzle-piece" style="color:var(--clr-gold)"></i> Optional Add-ons
              </span>
              <button type="button" id="addAddon" class="btn-admin btn-admin-primary btn-admin-sm">
                <i class="fas fa-plus"></i> Add
              </button>
            </div>
            <div class="admin-card-body">
              <div id="addonList">
                <?php foreach ($addonsD as $a): ?>
                <div class="addon-item">
                  <div class="form-group">
                    <label class="form-label">Add-on Name</label>
                    <input type="text" name="addon_name[]" class="form-control" value="<?= h($a['name']) ?>">
                  </div>
                  <div class="form-group" style="width:140px;flex-shrink:0">
                    <label class="form-label">Price (USD)</label>
                    <input type="number" name="addon_price[]" class="form-control" value="<?= $a['price'] ?>" min="0" step="0.01">
                  </div>
                  <button type="button" class="addon-remove" title="Remove">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
                <?php endforeach; ?>
                <?php if (!$addonsD): ?>
                <p class="no-addons" style="color:var(--clr-muted);font-size:.875rem">No add-ons yet.</p>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- FAQs -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-question-circle" style="color:var(--clr-gold)"></i> Package FAQs
              </span>
              <button type="button" id="addFaq" class="btn-admin btn-admin-primary btn-admin-sm">
                <i class="fas fa-plus"></i> Add FAQ
              </button>
            </div>
            <div class="admin-card-body">
              <div id="faqList">
                <?php foreach ($faqsD as $f): ?>
                <div class="faq-item">
                  <button type="button" class="faq-remove" title="Remove">
                    <i class="fas fa-times"></i>
                  </button>
                  <div class="form-group">
                    <label class="form-label">Question</label>
                    <input type="text" name="faq_q[]" class="form-control" value="<?= h($f['q']) ?>">
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Answer</label>
                    <textarea name="faq_a[]" class="form-control" rows="3"><?= h($f['a']) ?></textarea>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        </div><!-- /left -->

        <!-- ===== RIGHT COLUMN ===== -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Publish -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Publish</span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:12px">
              <label class="admin-toggle">
                <input type="checkbox" name="is_active" <?= ($p['is_active'] ?? 1) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Active (visible on site)</span>
              </label>
              <label class="admin-toggle">
                <input type="checkbox" name="is_featured" <?= ($p['is_featured'] ?? 0) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Featured on homepage</span>
              </label>
              <button type="submit" class="btn-admin btn-admin-primary btn-block" style="margin-top:4px">
                <i class="fas fa-save"></i> <?= $isEdit ? 'Update Package' : 'Create Package' ?>
              </button>
              <?php if ($isEdit): ?>
              <a href="<?= url('package-detail.php?slug=' . h($pkg['slug'])) ?>" target="_blank"
                 class="btn-admin btn-admin-secondary btn-block" style="text-align:center">
                <i class="fas fa-eye"></i> View Live
              </a>
              <?php endif; ?>
            </div>
          </div>

          <!-- Classification -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Classification</span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:12px">
              <div class="form-group" style="margin:0">
                <label class="form-label">Package Type</label>
                <select name="type" class="form-control">
                  <?php foreach (['group'=>'Group Tour','private'=>'Private Tour','luxury'=>'Luxury','budget'=>'Budget','corporate'=>'Corporate','honeymoon'=>'Honeymoon','family'=>'Family'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($p['type'] ?? 'group') === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-control">
                  <option value="">— Select Category —</option>
                  <?php foreach ($categories as $cat): ?>
                  <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>><?= h($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Destination</label>
                <select name="destination_id" class="form-control">
                  <option value="">— Select Destination —</option>
                  <?php foreach ($destinations as $dest): ?>
                  <option value="<?= $dest['id'] ?>" <?= ($p['destination_id'] ?? 0) == $dest['id'] ? 'selected' : '' ?>><?= h($dest['name']) ?>, <?= h($dest['country']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Difficulty Level</label>
                <select name="difficulty" class="form-control">
                  <?php foreach (['easy'=>'Easy','moderate'=>'Moderate','challenging'=>'Challenging','extreme'=>'Extreme'] as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= ($p['difficulty'] ?? 'easy') === $v ? 'selected' : '' ?>><?= $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Duration & Group -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Duration &amp; Group</span>
            </div>
            <div class="admin-card-body">
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Days <span style="color:var(--clr-danger)">*</span></label>
                  <input type="number" name="duration_days" class="form-control"
                         value="<?= h($p['duration_days'] ?? 1) ?>" min="1" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Nights</label>
                  <input type="number" name="duration_nights" class="form-control"
                         value="<?= h($p['duration_nights'] ?? 0) ?>" min="0">
                </div>
              </div>
              <div class="form-row" style="margin-bottom:0">
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Min Pax</label>
                  <input type="number" name="min_pax" class="form-control"
                         value="<?= h($p['min_pax'] ?? 1) ?>" min="1">
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Max Pax</label>
                  <input type="number" name="max_pax" class="form-control"
                         value="<?= h($p['max_pax'] ?? '') ?>" min="1" placeholder="Unlimited">
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-dollar-sign" style="color:var(--clr-gold)"></i> Pricing (USD)
              </span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:12px">
              <div class="form-group" style="margin:0">
                <label class="form-label">Base Price <span style="color:var(--clr-danger)">*</span></label>
                <div class="input-group">
                  <i class="ig-icon fas fa-dollar-sign"></i>
                  <input type="number" name="base_price" class="form-control"
                         value="<?= h($p['base_price'] ?? '') ?>" min="0" step="0.01" required>
                </div>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Sale Price <span class="form-hint" style="display:inline">(blank = no discount)</span></label>
                <div class="input-group">
                  <i class="ig-icon fas fa-tag"></i>
                  <input type="number" name="sale_price" class="form-control"
                         value="<?= h($p['sale_price'] ?? '') ?>" min="0" step="0.01">
                </div>
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Child Price</label>
                <div class="input-group">
                  <i class="ig-icon fas fa-child"></i>
                  <input type="number" name="child_price" class="form-control"
                         value="<?= h($p['child_price'] ?? '') ?>" min="0" step="0.01">
                </div>
              </div>
            </div>
          </div>

          <!-- Logistics -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Logistics</span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:12px">
              <div class="form-group" style="margin:0">
                <label class="form-label">Accommodation</label>
                <input type="text" name="accommodation" class="form-control"
                       value="<?= h($p['accommodation'] ?? '') ?>" placeholder="e.g. 5-star lodges">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Transport</label>
                <input type="text" name="transport" class="form-control"
                       value="<?= h($p['transport'] ?? '') ?>" placeholder="e.g. 4×4 Safari Vehicle">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Meals</label>
                <input type="text" name="meals" class="form-control"
                       value="<?= h($p['meals'] ?? '') ?>" placeholder="e.g. Full Board">
              </div>
              <div class="form-group" style="margin:0">
                <label class="form-label">Physical Requirements</label>
                <textarea name="physical_req" class="form-control" rows="2"
                          placeholder="Any health advisories or fitness requirements"><?= h($p['physical_req'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Departure Dates -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-calendar-days" style="color:var(--clr-gold)"></i> Departure Dates
              </span>
            </div>
            <div class="admin-card-body">
              <textarea name="departure_dates" class="form-control" rows="5"
                        placeholder="2025-01-15&#10;2025-02-01&#10;2025-03-10"><?= h($depDates) ?></textarea>
              <span class="form-hint">One date per line in YYYY-MM-DD format</span>
            </div>
          </div>

          <!-- Hero Image -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Hero Image</span>
            </div>
            <div class="admin-card-body">
              <?php if (!empty($p['hero_image'])): ?>
              <img src="<?= h($p['hero_image']) ?>" alt=""
                   style="width:100%;height:150px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;border:1.5px solid var(--clr-border)">
              <?php endif; ?>
              <div class="upload-area">
                <i class="fas fa-image upload-icon"></i>
                <p>Drag &amp; drop or <span style="color:var(--clr-primary)">browse</span></p>
                <small>JPG, PNG, WebP — Max 5MB</small>
                <input type="file" name="hero_image" accept="image/*"
                       style="position:absolute;inset:0;opacity:0;cursor:pointer"
                       onchange="previewHero(this)">
              </div>
              <img id="heroPreview" src="" alt=""
                   style="display:none;width:100%;height:130px;object-fit:cover;border-radius:var(--radius-sm);margin-top:10px;border:1.5px solid var(--clr-border)">
            </div>
          </div>

          <!-- Gallery -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Gallery</span>
            </div>
            <div class="admin-card-body">
              <?php if ($galleryD): ?>
              <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;margin-bottom:12px">
                <?php foreach ($galleryD as $gi => $img): ?>
                <div style="position:relative;border-radius:var(--radius-sm);overflow:hidden">
                  <img src="<?= h($img) ?>"
                       style="width:100%;height:72px;object-fit:cover;display:block" alt="">
                  <label style="position:absolute;top:3px;right:3px;background:rgba(239,68,68,.9);border-radius:50%;width:20px;height:20px;display:grid;place-items:center;cursor:pointer;z-index:1">
                    <input type="checkbox" name="remove_gallery[]" value="<?= $gi ?>"
                           style="display:none" onchange="this.closest('div').style.opacity=this.checked?'.35':'1'">
                    <i class="fas fa-times" style="color:#fff;font-size:.6rem"></i>
                  </label>
                </div>
                <?php endforeach; ?>
              </div>
              <span class="form-hint" style="display:block;margin-bottom:10px">Click <i class="fas fa-times" style="color:var(--clr-danger)"></i> to mark image for removal.</span>
              <?php endif; ?>
              <div class="upload-area">
                <i class="fas fa-images upload-icon"></i>
                <p>Add gallery images</p>
                <small>Select multiple files</small>
                <input type="file" name="gallery[]" accept="image/*" multiple
                       style="position:absolute;inset:0;opacity:0;cursor:pointer">
              </div>
            </div>
          </div>

        </div><!-- /right -->
      </div><!-- /grid -->
    </form>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function generateSlug(str) {
  return str.toLowerCase().trim().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'');
}

document.getElementById('package_title').addEventListener('input', function() {
  const slugEl = document.getElementById('package_slug');
  if (!<?= $isEdit ? 'true' : 'false' ?> || !slugEl.value) {
    slugEl.value = generateSlug(this.value);
  }
});

// Sync rich text editor to hidden field
document.getElementById('packageForm').addEventListener('submit', function() {
  const ed = document.getElementById('overviewEditor');
  if (ed) document.getElementById('overview_field').value = ed.innerHTML;
});

// Hero preview
function previewHero(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('heroPreview');
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Itinerary
let itinCount = <?= count($itin) ?>;
document.getElementById('addItin').addEventListener('click', function() {
  itinCount++;
  document.querySelector('.no-itin')?.remove();
  const div = document.createElement('div');
  div.className = 'itin-item';
  div.innerHTML = `<button type="button" class="itin-remove" title="Remove day"><i class="fas fa-times"></i></button>
    <div class="form-row" style="margin-bottom:10px">
      <div class="form-group" style="max-width:90px">
        <label class="form-label">Day</label>
        <input type="number" name="itin_day[]" class="form-control" value="${itinCount}" min="1">
      </div>
      <div class="form-group">
        <label class="form-label">Title <span style="color:var(--clr-danger)">*</span></label>
        <input type="text" name="itin_title[]" class="form-control" required>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description</label>
      <textarea name="itin_desc[]" class="form-control" rows="3"></textarea>
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label">Meals</label>
      <input type="text" name="itin_meals[]" class="form-control" placeholder="Breakfast, Lunch, Dinner">
    </div>`;
  document.getElementById('itineraryList').appendChild(div);
  div.querySelector('.itin-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.itin-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.itin-item').remove()));

// Add-ons
document.getElementById('addAddon').addEventListener('click', function() {
  document.querySelector('.no-addons')?.remove();
  const div = document.createElement('div');
  div.className = 'addon-item';
  div.innerHTML = `<div class="form-group">
    <label class="form-label">Add-on Name</label>
    <input type="text" name="addon_name[]" class="form-control">
  </div>
  <div class="form-group" style="width:140px;flex-shrink:0">
    <label class="form-label">Price (USD)</label>
    <input type="number" name="addon_price[]" class="form-control" value="0" min="0" step="0.01">
  </div>
  <button type="button" class="addon-remove" title="Remove"><i class="fas fa-trash"></i></button>`;
  document.getElementById('addonList').appendChild(div);
  div.querySelector('.addon-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.addon-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.addon-item').remove()));

// FAQs
document.getElementById('addFaq').addEventListener('click', function() {
  const div = document.createElement('div');
  div.className = 'faq-item';
  div.innerHTML = `<button type="button" class="faq-remove" title="Remove"><i class="fas fa-times"></i></button>
    <div class="form-group">
      <label class="form-label">Question</label>
      <input type="text" name="faq_q[]" class="form-control">
    </div>
    <div class="form-group" style="margin-bottom:0">
      <label class="form-label">Answer</label>
      <textarea name="faq_a[]" class="form-control" rows="3"></textarea>
    </div>`;
  document.getElementById('faqList').appendChild(div);
  div.querySelector('.faq-remove').addEventListener('click', () => div.remove());
});
document.querySelectorAll('.faq-remove').forEach(btn => btn.addEventListener('click', () => btn.closest('.faq-item').remove()));
</script>

<style>
/* Package/Blog editor */
.pkg-editor-content, .blog-editor-content {
  min-height: 200px; padding: 16px;
  border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm);
  outline: none; font-size: .9rem; line-height: 1.7; color: var(--clr-text);
  transition: border-color .2s;
}
.pkg-editor-content:focus { border-color: var(--clr-primary); box-shadow: 0 0 0 3px rgba(13,59,102,.08); }
.pkg-editor-content:empty::before { content: attr(data-placeholder); color: var(--clr-muted); pointer-events: none; }

/* Itinerary item */
.itin-item {
  border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm);
  padding: 16px; margin-bottom: 12px; position: relative;
  background: #fff; transition: border-color .2s;
}
.itin-item:hover { border-color: var(--clr-primary); }
.itin-remove {
  position: absolute; top: 10px; right: 10px;
  background: none; border: none; color: var(--clr-danger);
  cursor: pointer; width: 26px; height: 26px;
  border-radius: 50%; display: grid; place-items: center;
  transition: background .15s;
}
.itin-remove:hover { background: #fee2e2; }

/* Add-on item */
.addon-item {
  display: flex; gap: 10px; align-items: flex-end; margin-bottom: 10px;
  padding: 12px; border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm);
}
.addon-remove {
  background: #fff5f5; border: 1.5px solid var(--clr-danger); border-radius: var(--radius-sm);
  color: var(--clr-danger); cursor: pointer; width: 36px; height: 40px;
  display: grid; place-items: center; flex-shrink: 0; align-self: flex-end;
  margin-bottom: 0; transition: all .15s;
}
.addon-remove:hover { background: var(--clr-danger); color: #fff; }

/* FAQ item */
.faq-item {
  border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm);
  padding: 14px 14px 14px 14px; margin-bottom: 10px; position: relative;
}
.faq-item:hover { border-color: var(--clr-primary); }
.faq-remove {
  position: absolute; top: 10px; right: 10px;
  background: none; border: none; color: var(--clr-danger);
  cursor: pointer; width: 24px; height: 24px;
  border-radius: 50%; display: grid; place-items: center;
}
.faq-remove:hover { background: #fee2e2; }

/* Upload area */
.upload-area {
  position: relative; border: 2px dashed var(--clr-border);
  border-radius: var(--radius-sm); padding: 24px 16px;
  text-align: center; cursor: pointer; transition: border-color .2s;
}
.upload-area:hover { border-color: var(--clr-primary); }
.upload-icon { font-size: 1.6rem; color: var(--clr-border); margin-bottom: 6px; display: block; }
.upload-area p { font-size: .82rem; color: var(--clr-muted); margin: 0; }
.upload-area small { font-size: .72rem; color: var(--clr-muted); }

/* Editor toolbar (blog) */
.editor-toolbar { display: flex; gap: 4px; flex-wrap: wrap; padding: 8px 12px; border-top: 1px solid var(--clr-border); border-bottom: 1px solid var(--clr-border); background: var(--clr-light); }
.editor-toolbar button { background: none; border: 1px solid transparent; border-radius: 4px; padding: 5px 8px; cursor: pointer; font-size: .78rem; color: var(--clr-text); }
.editor-toolbar button:hover { background: #fff; border-color: var(--clr-border); color: var(--clr-primary); }
.toolbar-sep { color: var(--clr-border); padding: 0 4px; align-self: center; }
</style>
</body>
</html>
