<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
requireRole(['super_admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $fields = $_POST['settings'] ?? [];
    foreach ($fields as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        if (!$key) continue;
        $existing = DB::row("SELECT id FROM settings WHERE `key`=?", [$key]);
        if ($existing) {
            DB::update('settings', ['value'=>trim($value)], ['key'=>$key]);
        } else {
            DB::insert('settings', ['key'=>$key,'value'=>trim($value)]);
        }
    }
    // Logo upload
    if (!empty($_FILES['site_logo']['tmp_name'])) {
        $logo = uploadImage($_FILES['site_logo'], 'settings');
        if ($logo) {
            $existing = DB::row("SELECT id FROM settings WHERE `key`='site_logo'");
            if ($existing) DB::update('settings', ['value'=>$logo], ['key'=>'site_logo']);
            else DB::insert('settings', ['key'=>'site_logo','value'=>$logo]);
        }
    }
    auditLog('update', 'settings', 0, [], $fields);
    flash('success', 'Settings saved successfully.');
    redirect(url('admin/settings.php'));
}

// Load all settings
$settingsRaw = DB::rows("SELECT `key`, `value` FROM settings");
$cfg = [];
foreach ($settingsRaw as $s) $cfg[$s['key']] = $s['value'];
function sv($key, $default='') { global $cfg; return $cfg[$key] ?? $default; }

$pageTitle = 'Site Settings | MT Safaris Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-wrapper">
<header class="admin-header">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <div class="admin-header-title">Site Settings</div>
</header>
<main class="admin-main">
<?php echo renderFlash(); ?>

<form method="POST" enctype="multipart/form-data">
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:200px 1fr;gap:24px;align-items:start">
    <!-- Tab Nav -->
    <div style="position:sticky;top:calc(var(--admin-header-h)+16px)">
      <?php $tabs = [
        'general'  => ['fas fa-cog','General'],
        'contact'  => ['fas fa-address-card','Contact'],
        'social'   => ['fab fa-share-alt','Social Media'],
        'email'    => ['fas fa-envelope','Email / SMTP'],
        'payments' => ['fas fa-credit-card','Payments'],
        'seo'      => ['fas fa-search','SEO & Analytics'],
      ]; ?>
      <?php foreach ($tabs as $tid=>[$icon,$label]): ?>
      <a href="#<?= $tid ?>" onclick="showTab('<?= $tid ?>')" class="settings-tab-btn" id="tab-<?= $tid ?>" style="display:flex;align-items:center;gap:8px;padding:10px 16px;border-radius:8px;text-decoration:none;color:var(--admin-text);font-size:.875rem;margin-bottom:4px;transition:background .15s">
        <i class="<?= $icon ?>" style="width:18px"></i> <?= $label ?>
      </a>
      <?php endforeach; ?>
      <div style="margin-top:16px">
        <button type="submit" class="btn btn-admin-primary btn-block"><i class="fas fa-save"></i> Save All</button>
      </div>
    </div>

    <!-- Tab Panels -->
    <div>
      <!-- General -->
      <div class="settings-panel" id="panel-general">
        <div class="admin-card" style="margin-bottom:20px">
          <div class="admin-card-header"><h3>General Settings</h3></div>
          <div class="admin-card-body">
            <div class="admin-form-group"><label class="admin-label">Site Name</label><input type="text" name="settings[site_name]" class="admin-input" value="<?= h(sv('site_name','MT Safaris')) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Site Tagline</label><input type="text" name="settings[site_tagline]" class="admin-input" value="<?= h(sv('site_tagline','Premium Travel Experiences')) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Site Logo</label>
              <?php if (sv('site_logo')): ?><img src="<?= h(sv('site_logo')) ?>" style="height:48px;margin-bottom:10px;display:block" alt="logo"><?php endif; ?>
              <input type="file" name="site_logo" class="admin-input" accept="image/*">
            </div>
            <div class="admin-form-group"><label class="admin-label">Default Currency</label>
              <select name="settings[currency]" class="admin-select">
                <?php foreach (['USD'=>'US Dollar','EUR'=>'Euro','GBP'=>'British Pound','KES'=>'Kenyan Shilling'] as $c=>$l): ?>
                <option value="<?= $c ?>" <?= sv('currency','USD')===$c?'selected':'' ?>><?= $l ?> (<?= $c ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="admin-form-group"><label class="admin-label">Tax Rate (%)</label><input type="number" name="settings[tax_rate]" class="admin-input" value="<?= h(sv('tax_rate','16')) ?>" step="0.1" min="0"></div>
            <div class="admin-form-group"><label class="admin-label">Booking Deposit (%)</label><input type="number" name="settings[booking_deposit]" class="admin-input" value="<?= h(sv('booking_deposit','30')) ?>" min="0" max="100"></div>
            <div><label class="admin-label">Maintenance Mode</label>
              <label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="settings[maintenance_mode]" value="1" <?= sv('maintenance_mode')?'checked':'' ?>> Enable maintenance mode</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact -->
      <div class="settings-panel" id="panel-contact" style="display:none">
        <div class="admin-card">
          <div class="admin-card-header"><h3>Contact Information</h3></div>
          <div class="admin-card-body">
            <div class="admin-form-group"><label class="admin-label">Contact Email</label><input type="email" name="settings[contact_email]" class="admin-input" value="<?= h(sv('contact_email',CONTACT_EMAIL)) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Contact Phone</label><input type="text" name="settings[contact_phone]" class="admin-input" value="<?= h(sv('contact_phone',CONTACT_PHONE)) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">WhatsApp Number <small style="color:var(--admin-muted)">(with country code, no +)</small></label><input type="text" name="settings[contact_whatsapp]" class="admin-input" value="<?= h(sv('contact_whatsapp',CONTACT_WHATSAPP)) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Physical Address</label><textarea name="settings[contact_address]" class="admin-input" rows="3"><?= h(sv('contact_address',CONTACT_ADDRESS)) ?></textarea></div>
            <div class="admin-form-group"><label class="admin-label">Office Hours</label><input type="text" name="settings[office_hours]" class="admin-input" value="<?= h(sv('office_hours','Mon-Sat: 8AM - 6PM EAT')) ?>"></div>
            <div><label class="admin-label">Google Maps Embed URL</label><input type="text" name="settings[google_maps_url]" class="admin-input" value="<?= h(sv('google_maps_url')) ?>" placeholder="https://maps.google.com/..."></div>
          </div>
        </div>
      </div>

      <!-- Social -->
      <div class="settings-panel" id="panel-social" style="display:none">
        <div class="admin-card">
          <div class="admin-card-header"><h3>Social Media Links</h3></div>
          <div class="admin-card-body">
            <?php foreach ([
              ['fab fa-facebook','Facebook URL','social_facebook'],
              ['fab fa-instagram','Instagram URL','social_instagram'],
              ['fab fa-twitter','Twitter/X URL','social_twitter'],
              ['fab fa-youtube','YouTube URL','social_youtube'],
              ['fab fa-linkedin','LinkedIn URL','social_linkedin'],
              ['fab fa-tiktok','TikTok URL','social_tiktok'],
            ] as [$icon,$label,$key]): ?>
            <div class="admin-form-group">
              <label class="admin-label"><i class="<?= $icon ?>" style="width:18px"></i> <?= $label ?></label>
              <input type="url" name="settings[<?= $key ?>]" class="admin-input" value="<?= h(sv($key)) ?>" placeholder="https://...">
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Email -->
      <div class="settings-panel" id="panel-email" style="display:none">
        <div class="admin-card">
          <div class="admin-card-header"><h3>Email / SMTP Settings</h3></div>
          <div class="admin-card-body">
            <div style="background:#fffbf0;border:1px solid #fcd34d;border-radius:8px;padding:12px 16px;margin-bottom:20px;font-size:.875rem;color:#92400e"><i class="fas fa-info-circle"></i> SMTP settings in this panel override the values in <code>config.php</code>.</div>
            <div class="admin-form-group"><label class="admin-label">SMTP Host</label><input type="text" name="settings[smtp_host]" class="admin-input" value="<?= h(sv('smtp_host',MAIL_SMTP_HOST)) ?>"></div>
            <div style="display:grid;grid-template-columns:1fr 120px;gap:12px;margin-bottom:16px">
              <div><label class="admin-label">SMTP Username</label><input type="text" name="settings[smtp_user]" class="admin-input" value="<?= h(sv('smtp_user',MAIL_SMTP_USER)) ?>"></div>
              <div><label class="admin-label">Port</label><input type="number" name="settings[smtp_port]" class="admin-input" value="<?= h(sv('smtp_port',MAIL_SMTP_PORT)) ?>"></div>
            </div>
            <div class="admin-form-group"><label class="admin-label">SMTP Password</label><input type="password" name="settings[smtp_pass]" class="admin-input" placeholder="Leave blank to keep current"></div>
            <div class="admin-form-group"><label class="admin-label">From Name</label><input type="text" name="settings[mail_from_name]" class="admin-input" value="<?= h(sv('mail_from_name',APP_NAME)) ?>"></div>
            <div><label class="admin-label">From Email</label><input type="email" name="settings[mail_from_email]" class="admin-input" value="<?= h(sv('mail_from_email',CONTACT_EMAIL)) ?>"></div>
          </div>
        </div>
      </div>

      <!-- Payments -->
      <div class="settings-panel" id="panel-payments" style="display:none">
        <div class="admin-card">
          <div class="admin-card-header"><h3>Payment Gateway Settings</h3></div>
          <div class="admin-card-body">
            <h4 style="color:var(--admin-text);margin-bottom:12px"><i class="fab fa-stripe" style="color:#635bff"></i> Stripe</h4>
            <div class="admin-form-group"><label class="admin-label">Publishable Key</label><input type="text" name="settings[stripe_pub_key]" class="admin-input" value="<?= h(sv('stripe_pub_key')) ?>" placeholder="pk_..."></div>
            <div class="admin-form-group"><label class="admin-label">Secret Key</label><input type="password" name="settings[stripe_secret]" class="admin-input" placeholder="Leave blank to keep current"></div>
            <hr style="margin:20px 0">
            <h4 style="color:var(--admin-text);margin-bottom:12px"><i class="fab fa-paypal" style="color:#003087"></i> PayPal</h4>
            <div class="admin-form-group"><label class="admin-label">Client ID</label><input type="text" name="settings[paypal_client_id]" class="admin-input" value="<?= h(sv('paypal_client_id')) ?>"></div>
            <div class="admin-form-group">
              <label class="admin-label">Mode</label>
              <select name="settings[paypal_mode]" class="admin-select">
                <option value="sandbox" <?= sv('paypal_mode','sandbox')==='sandbox'?'selected':'' ?>>Sandbox (Testing)</option>
                <option value="live" <?= sv('paypal_mode')==='live'?'selected':'' ?>>Live</option>
              </select>
            </div>
            <hr style="margin:20px 0">
            <h4 style="color:var(--admin-text);margin-bottom:12px">Bank Transfer Details</h4>
            <div><textarea name="settings[bank_details]" class="admin-input" rows="5" placeholder="Bank Name:&#10;Account Name:&#10;Account Number:&#10;Swift Code:"><?= h(sv('bank_details')) ?></textarea></div>
          </div>
        </div>
      </div>

      <!-- SEO -->
      <div class="settings-panel" id="panel-seo" style="display:none">
        <div class="admin-card">
          <div class="admin-card-header"><h3>SEO & Analytics</h3></div>
          <div class="admin-card-body">
            <div class="admin-form-group"><label class="admin-label">Default Meta Title</label><input type="text" name="settings[meta_title]" class="admin-input" value="<?= h(sv('meta_title',APP_NAME.' | Premium Travel')) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Default Meta Description</label><textarea name="settings[meta_description]" class="admin-input" rows="3"><?= h(sv('meta_description')) ?></textarea></div>
            <div class="admin-form-group"><label class="admin-label">Google Analytics ID <small style="color:var(--admin-muted)">(e.g. G-XXXXXXXX)</small></label><input type="text" name="settings[ga_id]" class="admin-input" value="<?= h(sv('ga_id',GOOGLE_ANALYTICS)) ?>"></div>
            <div class="admin-form-group"><label class="admin-label">Google Maps API Key</label><input type="text" name="settings[google_maps_key]" class="admin-input" value="<?= h(sv('google_maps_key',GOOGLE_MAPS_KEY)) ?>"></div>
            <div><label class="admin-label">reCAPTCHA Site Key</label><input type="text" name="settings[recaptcha_site_key]" class="admin-input" value="<?= h(sv('recaptcha_site_key',RECAPTCHA_SITE)) ?>"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function showTab(id) {
  document.querySelectorAll('.settings-panel').forEach(p => p.style.display='none');
  document.querySelectorAll('.settings-tab-btn').forEach(b => b.style.background='');
  document.getElementById('panel-'+id).style.display='block';
  document.getElementById('tab-'+id).style.background='var(--admin-primary)';
  document.getElementById('tab-'+id).style.color='#fff';
}
// Init first tab active
showTab('general');
// Handle hash
const hash = location.hash.replace('#','');
if (hash && document.getElementById('panel-'+hash)) showTab(hash);
</script>
</main>
</div>
</body>
</html>
