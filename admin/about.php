<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

// Repeatable groups on the About page. Each is stored as one JSON settings row;
// the form posts parallel arrays (name[], role[], ...) which are zipped back
// into a list of records here.
$repeatables = [
    'about_team'       => ['name', 'role', 'bio', 'photo', 'linkedin', 'twitter'],
    'about_values'     => ['icon', 'title', 'text'],
    'about_milestones' => ['year', 'title', 'text'],
    'about_stats'      => ['value', 'label'],
    'about_awards'     => ['name'],
];

// A row is kept only if its first field has content, so blank rows left in the
// form are discarded rather than saved as empty cards.
function collectRows(string $prefix, array $fields): array {
    $first = $_POST[$prefix . '_' . $fields[0]] ?? [];
    if (!is_array($first)) return [];
    $rows = [];
    foreach ($first as $i => $_) {
        $row = [];
        foreach ($fields as $f) {
            $row[$f] = trim((string)($_POST[$prefix . '_' . $f][$i] ?? ''));
        }
        if ($row[$fields[0]] === '') continue;
        $rows[] = $row;
    }
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $current = allSettings();
    $old = $new = [];

    // Plain text fields
    foreach (($_POST['settings'] ?? []) as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        if (!$key || is_array($value)) continue;
        if (!str_starts_with($key, 'about_')) continue;   // this screen owns about_* only
        $value = trim((string)$value);
        $old[$key] = $current[$key] ?? '';
        $new[$key] = $value;
        setSetting($key, $value);
    }

    // Story images — a new upload replaces, no upload keeps what is stored.
    foreach (['about_img_main', 'about_img_sub1', 'about_img_sub2'] as $field) {
        if (!empty($_FILES[$field]['tmp_name'])) {
            $up = uploadImage($_FILES[$field], 'about');
            if ($up) { $old[$field] = $current[$field] ?? ''; $new[$field] = $up; setSetting($field, $up); }
            else     { flash('error', 'Could not upload ' . $field . '. Check the file type and size.'); }
        }
    }

    // Team photos: team_photo_upload[i] replaces the stored URL for that row.
    $teamPhotos = $_POST['about_team_photo'] ?? [];
    if (!empty($_FILES['about_team_photo_upload']['tmp_name'])) {
        foreach ($_FILES['about_team_photo_upload']['tmp_name'] as $i => $tmp) {
            if (!$tmp) continue;
            $one = [
                'name'     => $_FILES['about_team_photo_upload']['name'][$i],
                'type'     => $_FILES['about_team_photo_upload']['type'][$i],
                'tmp_name' => $tmp,
                'error'    => $_FILES['about_team_photo_upload']['error'][$i],
                'size'     => $_FILES['about_team_photo_upload']['size'][$i],
            ];
            $up = uploadImage($one, 'about/team');
            if ($up) $teamPhotos[$i] = $up;
        }
        $_POST['about_team_photo'] = $teamPhotos;
    }

    // Repeatable groups
    foreach ($repeatables as $key => $fields) {
        $rows = collectRows($key, $fields);
        $json = json_encode(array_values($rows), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $old[$key] = $current[$key] ?? '';
        $new[$key] = $json;
        setSetting($key, $json);
    }

    auditLog('update', 'settings', 0, $old, $new);
    flash('success', 'About page updated.');
    redirect(url('admin/about.php'));
}

$cfg = allSettings();
function av(string $key, string $default = ''): string {
    global $cfg;
    $v = $cfg[$key] ?? '';
    return trim($v) !== '' ? $v : $default;
}
function alist(string $key, array $default): array {
    global $cfg;
    $raw = $cfg[$key] ?? '';
    if (trim($raw) === '') return $default;
    $a = json_decode($raw, true);
    return (is_array($a) && $a !== []) ? $a : $default;
}

// Defaults mirror the fallbacks in about.php so the form is never empty.
$team = alist('about_team', [
  ['name'=>'Michael Tanaka','role'=>'Founder & CEO','bio'=>'Leading MT Safaris since 2005 with 25+ years in East African travel.','photo'=>'','linkedin'=>'','twitter'=>''],
  ['name'=>'Sarah Achieng','role'=>'Head of Operations','bio'=>'Ensuring every journey runs smoothly with meticulous attention to detail.','photo'=>'','linkedin'=>'','twitter'=>''],
]);
$values = alist('about_values', [
  ['icon'=>'fas fa-leaf','title'=>'Sustainability','text'=>'We are committed to responsible tourism that protects wildlife, preserves cultures, and benefits local communities.'],
  ['icon'=>'fas fa-heart','title'=>'Authenticity','text'=>'Every experience we create is genuine, locally-rooted, and thoughtfully designed.'],
]);
$milestones = alist('about_milestones', [
  ['year'=>'2005','title'=>'MT Safaris Founded','text'=>'Established in Nairobi with a vision to provide authentic, premium East African safari experiences.'],
]);
$stats = alist('about_stats', [
  ['value'=>'5,000+','label'=>'Happy Travelers'], ['value'=>'150+','label'=>'Destinations'],
  ['value'=>'18','label'=>'Years Experience'], ['value'=>'200+','label'=>'Corporate Clients'],
]);
$awards = alist('about_awards', [
  ['name'=>'KATO Certified'], ['name'=>'ATTA Member'], ['name'=>'Kenya Tourism Board'],
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>About Page — Admin | MT Safaris</title>
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
        <span>About Page</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('about.php') ?>" target="_blank" class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-eye"></i> View Live
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?= renderFlash() ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">About Page</div>
        <div class="page-subtitle">Edit every section of the public About Us page. Leave a field blank to fall back to the built-in text.</div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="aboutForm">
      <?= csrfField() ?>

      <div class="save-bar">
        <div class="save-bar-info">Changes appear on <strong>/about.php</strong> as soon as you save.</div>
        <div class="save-bar-actions">
          <button type="submit" class="btn-admin btn-admin-primary"><i class="fas fa-save"></i> Save About Page</button>
        </div>
      </div>

      <!-- HERO -->
      <div class="admin-card">
        <div class="admin-card-header"><h3><i class="fas fa-heading"></i> Page Header</h3></div>
        <div class="admin-card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Heading</label>
              <input type="text" name="settings[about_hero_title]" class="form-control" value="<?= h(av('about_hero_title','Our Story')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Sub-heading</label>
              <input type="text" name="settings[about_hero_subtitle]" class="form-control" value="<?= h(av('about_hero_subtitle','18 years of crafting extraordinary travel experiences across Africa and the world.')) ?>">
            </div>
          </div>
        </div>
      </div>

      <!-- STORY -->
      <div class="admin-card">
        <div class="admin-card-header"><h3><i class="fas fa-book-open"></i> Our Story</h3></div>
        <div class="admin-card-body">
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Badge</label>
              <input type="text" name="settings[about_story_badge]" class="form-control" value="<?= h(av('about_story_badge','Our Story')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Section Title</label>
              <input type="text" name="settings[about_story_title]" class="form-control" value="<?= h(av('about_story_title','Born from a Passion for Africa')) ?>">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">First Paragraph</label>
            <textarea name="settings[about_story_p1]" class="form-control" rows="4"><?= h(av('about_story_p1')) ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Second Paragraph</label>
            <textarea name="settings[about_story_p2]" class="form-control" rows="4"><?= h(av('about_story_p2')) ?></textarea>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Our Mission</label>
              <textarea name="settings[about_mission]" class="form-control" rows="3"><?= h(av('about_mission')) ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Our Vision</label>
              <textarea name="settings[about_vision]" class="form-control" rows="3"><?= h(av('about_vision')) ?></textarea>
            </div>
          </div>
          <div class="form-row-3">
            <?php foreach ([['about_img_main','Main Image'],['about_img_sub1','Small Image 1'],['about_img_sub2','Small Image 2']] as $img): ?>
            <div class="form-group">
              <label class="form-label"><?= $img[1] ?></label>
              <?php if (av($img[0])): ?>
              <img src="<?= h(av($img[0])) ?>" alt="" style="width:100%;height:110px;object-fit:cover;border-radius:8px;margin-bottom:8px">
              <?php endif; ?>
              <input type="file" name="<?= $img[0] ?>" class="form-control" accept="image/*">
              <span class="form-hint">Leave empty to keep the current image.</span>
            </div>
            <?php endforeach; ?>
          </div>
          <div class="form-row-3">
            <div class="form-group">
              <label class="form-label">Badge Number</label>
              <input type="text" name="settings[about_badge_num]" class="form-control" value="<?= h(av('about_badge_num','18+')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Badge Text</label>
              <input type="text" name="settings[about_badge_text]" class="form-control" value="<?= h(av('about_badge_text','Years of Excellence')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Button Label</label>
              <input type="text" name="settings[about_cta_text]" class="form-control" value="<?= h(av('about_cta_text','Talk to Our Team')) ?>">
            </div>
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Button Link</label>
            <input type="text" name="settings[about_cta_url]" class="form-control" value="<?= h(av('about_cta_url', url('contact.php'))) ?>">
          </div>
        </div>
      </div>

      <!-- STATS -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fas fa-chart-simple"></i> Stats Bar</h3>
          <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-add="stats"><i class="fas fa-plus"></i> Add Stat</button>
        </div>
        <div class="admin-card-body" id="rows-stats">
          <?php foreach ($stats as $s): ?>
          <div class="repeat-row">
            <input type="text" name="about_stats_value[]" class="form-control" placeholder="5,000+" value="<?= h($s['value'] ?? '') ?>">
            <input type="text" name="about_stats_label[]" class="form-control" placeholder="Happy Travelers" value="<?= h($s['label'] ?? '') ?>">
            <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- VALUES -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fas fa-gem"></i> Values</h3>
          <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-add="values"><i class="fas fa-plus"></i> Add Value</button>
        </div>
        <div class="admin-card-body">
          <div class="form-row" style="margin-bottom:16px">
            <div class="form-group">
              <label class="form-label">Section Badge</label>
              <input type="text" name="settings[about_values_badge]" class="form-control" value="<?= h(av('about_values_badge','Our Values')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Section Title</label>
              <input type="text" name="settings[about_values_title]" class="form-control" value="<?= h(av('about_values_title','What Drives Us')) ?>">
            </div>
          </div>
          <div id="rows-values">
            <?php foreach ($values as $v): ?>
            <div class="repeat-row">
              <input type="text" name="about_values_icon[]"  class="form-control" placeholder="fas fa-leaf" value="<?= h($v['icon'] ?? '') ?>" style="max-width:170px">
              <input type="text" name="about_values_title[]" class="form-control" placeholder="Sustainability" value="<?= h($v['title'] ?? '') ?>" style="max-width:200px">
              <input type="text" name="about_values_text[]"  class="form-control" placeholder="Short description" value="<?= h($v['text'] ?? '') ?>">
              <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
          <span class="form-hint">Icons use Font Awesome class names, e.g. <code>fas fa-leaf</code>.</span>
        </div>
      </div>

      <!-- TEAM -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fas fa-users"></i> Team</h3>
          <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-add="team"><i class="fas fa-plus"></i> Add Member</button>
        </div>
        <div class="admin-card-body">
          <div class="form-row-3" style="margin-bottom:16px">
            <div class="form-group">
              <label class="form-label">Section Badge</label>
              <input type="text" name="settings[about_team_badge]" class="form-control" value="<?= h(av('about_team_badge','Our People')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Section Title</label>
              <input type="text" name="settings[about_team_title]" class="form-control" value="<?= h(av('about_team_title','Meet the Team')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Section Subtitle</label>
              <input type="text" name="settings[about_team_subtitle]" class="form-control" value="<?= h(av('about_team_subtitle')) ?>">
            </div>
          </div>
          <div id="rows-team">
            <?php foreach ($team as $i => $m): ?>
            <div class="admin-card-flat repeat-block">
              <div class="form-row-3">
                <div class="form-group"><label class="form-label">Name</label>
                  <input type="text" name="about_team_name[]" class="form-control" value="<?= h($m['name'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Role</label>
                  <input type="text" name="about_team_role[]" class="form-control" value="<?= h($m['role'] ?? '') ?>"></div>
                <div class="form-group"><label class="form-label">Photo</label>
                  <input type="hidden" name="about_team_photo[]" value="<?= h($m['photo'] ?? '') ?>">
                  <input type="file" name="about_team_photo_upload[]" class="form-control" accept="image/*"></div>
              </div>
              <div class="form-group"><label class="form-label">Bio</label>
                <input type="text" name="about_team_bio[]" class="form-control" value="<?= h($m['bio'] ?? '') ?>"></div>
              <div class="form-row">
                <div class="form-group" style="margin-bottom:0"><label class="form-label">LinkedIn URL</label>
                  <input type="text" name="about_team_linkedin[]" class="form-control" value="<?= h($m['linkedin'] ?? '') ?>"></div>
                <div class="form-group" style="margin-bottom:0"><label class="form-label">X / Twitter URL</label>
                  <input type="text" name="about_team_twitter[]" class="form-control" value="<?= h($m['twitter'] ?? '') ?>"></div>
              </div>
              <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-remove style="margin-top:12px">
                <i class="fas fa-trash"></i> Remove Member
              </button>
            </div>
            <?php endforeach; ?>
          </div>
          <span class="form-hint">Members with no photo show their initial. Uploading a photo replaces the stored one.</span>
        </div>
      </div>

      <!-- MILESTONES -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fas fa-timeline"></i> Milestones</h3>
          <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-add="milestones"><i class="fas fa-plus"></i> Add Milestone</button>
        </div>
        <div class="admin-card-body">
          <div class="form-row" style="margin-bottom:16px">
            <div class="form-group">
              <label class="form-label">Section Badge</label>
              <input type="text" name="settings[about_milestones_badge]" class="form-control" value="<?= h(av('about_milestones_badge','Our Journey')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Section Title</label>
              <input type="text" name="settings[about_milestones_title]" class="form-control" value="<?= h(av('about_milestones_title','Our Milestones')) ?>">
            </div>
          </div>
          <div id="rows-milestones">
            <?php foreach ($milestones as $m): ?>
            <div class="repeat-row">
              <input type="text" name="about_milestones_year[]"  class="form-control" placeholder="2005" value="<?= h($m['year'] ?? '') ?>" style="max-width:110px">
              <input type="text" name="about_milestones_title[]" class="form-control" placeholder="Milestone title" value="<?= h($m['title'] ?? '') ?>" style="max-width:260px">
              <input type="text" name="about_milestones_text[]"  class="form-control" placeholder="What happened" value="<?= h($m['text'] ?? '') ?>">
              <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- AWARDS -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3><i class="fas fa-award"></i> Awards &amp; Certifications</h3>
          <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-add="awards"><i class="fas fa-plus"></i> Add Award</button>
        </div>
        <div class="admin-card-body">
          <div class="form-group">
            <label class="form-label">Section Title</label>
            <input type="text" name="settings[about_awards_title]" class="form-control" value="<?= h(av('about_awards_title','Awards & Certifications')) ?>">
          </div>
          <div id="rows-awards">
            <?php foreach ($awards as $a): $an = is_array($a) ? ($a['name'] ?? '') : (string)$a; ?>
            <div class="repeat-row">
              <input type="text" name="about_awards_name[]" class="form-control" placeholder="KATO Certified" value="<?= h($an) ?>">
              <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- SEO -->
      <div class="admin-card">
        <div class="admin-card-header"><h3><i class="fas fa-magnifying-glass"></i> SEO</h3></div>
        <div class="admin-card-body">
          <div class="form-group">
            <label class="form-label">Meta Title</label>
            <input type="text" name="settings[about_meta_title]" class="form-control" value="<?= h(av('about_meta_title')) ?>" placeholder="About MT Safaris — Our Story, Mission &amp; Team">
          </div>
          <div class="form-group" style="margin-bottom:0">
            <label class="form-label">Meta Description</label>
            <textarea name="settings[about_meta_description]" class="form-control" rows="3"><?= h(av('about_meta_description')) ?></textarea>
          </div>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-bottom:32px">
        <button type="submit" class="btn-admin btn-admin-primary"><i class="fas fa-save"></i> Save About Page</button>
      </div>

    </form>
  </div>
</div>

<style>
.repeat-row { display:flex; gap:10px; align-items:center; margin-bottom:10px; }
.repeat-row .form-control { flex:1; }
.repeat-block { position:relative; margin-bottom:16px; }
</style>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Blank row templates for each repeatable group.
const ROW_TEMPLATES = {
  stats: `<div class="repeat-row">
      <input type="text" name="about_stats_value[]" class="form-control" placeholder="5,000+">
      <input type="text" name="about_stats_label[]" class="form-control" placeholder="Happy Travelers">
      <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
    </div>`,
  values: `<div class="repeat-row">
      <input type="text" name="about_values_icon[]" class="form-control" placeholder="fas fa-leaf" style="max-width:170px">
      <input type="text" name="about_values_title[]" class="form-control" placeholder="Sustainability" style="max-width:200px">
      <input type="text" name="about_values_text[]" class="form-control" placeholder="Short description">
      <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
    </div>`,
  milestones: `<div class="repeat-row">
      <input type="text" name="about_milestones_year[]" class="form-control" placeholder="2005" style="max-width:110px">
      <input type="text" name="about_milestones_title[]" class="form-control" placeholder="Milestone title" style="max-width:260px">
      <input type="text" name="about_milestones_text[]" class="form-control" placeholder="What happened">
      <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
    </div>`,
  awards: `<div class="repeat-row">
      <input type="text" name="about_awards_name[]" class="form-control" placeholder="KATO Certified">
      <button type="button" class="btn-icon-admin btn-icon-danger" data-remove><i class="fas fa-trash"></i></button>
    </div>`,
  team: `<div class="admin-card-flat repeat-block">
      <div class="form-row-3">
        <div class="form-group"><label class="form-label">Name</label>
          <input type="text" name="about_team_name[]" class="form-control"></div>
        <div class="form-group"><label class="form-label">Role</label>
          <input type="text" name="about_team_role[]" class="form-control"></div>
        <div class="form-group"><label class="form-label">Photo</label>
          <input type="hidden" name="about_team_photo[]" value="">
          <input type="file" name="about_team_photo_upload[]" class="form-control" accept="image/*"></div>
      </div>
      <div class="form-group"><label class="form-label">Bio</label>
        <input type="text" name="about_team_bio[]" class="form-control"></div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0"><label class="form-label">LinkedIn URL</label>
          <input type="text" name="about_team_linkedin[]" class="form-control"></div>
        <div class="form-group" style="margin-bottom:0"><label class="form-label">X / Twitter URL</label>
          <input type="text" name="about_team_twitter[]" class="form-control"></div>
      </div>
      <button type="button" class="btn-admin btn-admin-secondary btn-admin-sm" data-remove style="margin-top:12px">
        <i class="fas fa-trash"></i> Remove Member
      </button>
    </div>`
};

document.querySelectorAll('[data-add]').forEach(btn => {
  btn.addEventListener('click', () => {
    const group = btn.dataset.add;
    const host  = document.getElementById('rows-' + group);
    if (!host || !ROW_TEMPLATES[group]) return;
    host.insertAdjacentHTML('beforeend', ROW_TEMPLATES[group]);
  });
});

// Delegated so rows added after load are covered too.
document.getElementById('aboutForm').addEventListener('click', e => {
  const btn = e.target.closest('[data-remove]');
  if (!btn) return;
  const row = btn.closest('.repeat-row, .repeat-block');
  if (row) row.remove();
});
</script>
</body>
</html>
