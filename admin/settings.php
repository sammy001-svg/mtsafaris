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
    $old    = [];
    foreach ($fields as $key => $value) {
        $key = preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        if (!$key) continue;
        $existing = DB::row("SELECT id, value FROM settings WHERE `key`=?", [$key]);
        $old[$key] = $existing['value'] ?? '';
        if ($existing) {
            DB::update('settings', ['value' => trim($value)], ['key' => $key]);
        } else {
            DB::insert('settings', ['key' => $key, 'value' => trim($value)]);
        }
    }

    // Logo / favicon uploads
    foreach (['site_logo' => 'settings', 'site_favicon' => 'settings'] as $field => $dir) {
        if (!empty($_FILES[$field]['tmp_name'])) {
            $up = uploadImage($_FILES[$field], $dir);
            if ($up) {
                $ex = DB::row("SELECT id FROM settings WHERE `key`=?", [$field]);
                if ($ex) DB::update('settings', ['value' => $up], ['key' => $field]);
                else     DB::insert('settings', ['key' => $field, 'value' => $up]);
            }
        }
    }

    auditLog('update', 'settings', 0, $old, $fields);
    flash('success', 'Settings saved successfully.');
    redirect(url('admin/settings.php' . (isset($_POST['_tab']) ? '#' . h($_POST['_tab']) : '')));
}

// Load all settings
$settingsRaw = DB::rows("SELECT `key`, `value` FROM settings");
$cfg = [];
foreach ($settingsRaw as $s) $cfg[$s['key']] = $s['value'];
function sv(string $key, string $default = ''): string {
    global $cfg;
    return $cfg[$key] ?? $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Site Settings — Admin | MT Safaris</title>
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
        <span>Site Settings</span>
      </div>
    </div>
    <div class="admin-header-right">
      <span style="font-size:.8rem;color:var(--clr-muted)"><i class="fas fa-lock" style="margin-right:4px"></i>Super Admin only</span>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>">
      <i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">Site Settings</div>
        <div class="page-subtitle">Configure site-wide options — contact details, payments, SEO, social media, and homepage content.</div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="settingsForm">
      <?= csrfField() ?>
      <input type="hidden" name="_tab" id="activeTabInput" value="general">

      <div class="settings-layout">

        <!-- ── Sidebar Tab Nav ── -->
        <div class="settings-nav">
          <?php
          $tabs = [
            'general'  => ['fas fa-sliders',          'General'],
            'contact'  => ['fas fa-address-card',      'Contact'],
            'social'   => ['fas fa-share-nodes',       'Social Media'],
            'homepage' => ['fas fa-house',             'Homepage'],
            'email'    => ['fas fa-envelope',          'Email / SMTP'],
            'payments' => ['fas fa-credit-card',       'Payments'],
            'seo'      => ['fas fa-magnifying-glass',  'SEO & Analytics'],
          ];
          foreach ($tabs as $tid => [$icon, $label]): ?>
          <button type="button" class="settings-tab-btn" id="tab-<?= $tid ?>" data-tab="<?= $tid ?>">
            <i class="<?= $icon ?>"></i> <?= $label ?>
          </button>
          <?php endforeach; ?>
          <div style="margin-top:auto;padding-top:16px">
            <button type="submit" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> Save Settings
            </button>
          </div>
        </div>

        <!-- ── Tab Panels ── -->
        <div class="settings-panels">

          <!-- ════════ GENERAL ════════ -->
          <div class="settings-panel" id="panel-general">
            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-sliders" style="color:var(--clr-gold)"></i> General Settings
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" name="settings[site_name]" class="form-control"
                           value="<?= h(sv('site_name', APP_NAME)) ?>">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Default Currency</label>
                    <select name="settings[currency]" class="form-control">
                      <?php foreach (['USD' => 'USD — US Dollar', 'KES' => 'KES — Kenyan Shilling', 'EUR' => 'EUR — Euro', 'GBP' => 'GBP — British Pound'] as $c => $l): ?>
                      <option value="<?= $c ?>" <?= sv('currency', 'USD') === $c ? 'selected' : '' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Site Tagline</label>
                  <input type="text" name="settings[site_tagline]" class="form-control"
                         value="<?= h(sv('site_tagline', APP_TAGLINE)) ?>">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Tax Rate (%)</label>
                    <input type="number" name="settings[tax_rate]" class="form-control"
                           value="<?= h(sv('tax_rate', (string)TAX_RATE)) ?>" step="0.1" min="0" max="100">
                    <span class="form-hint">Applied on bookings at checkout.</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Booking Deposit (%)</label>
                    <input type="number" name="settings[booking_deposit]" class="form-control"
                           value="<?= h(sv('booking_deposit', (string)BOOKING_DEPOSIT)) ?>" min="0" max="100">
                    <span class="form-hint">Minimum deposit to confirm a booking.</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Site Logo</label>
                  <?php if (sv('site_logo')): ?>
                  <div style="margin-bottom:10px">
                    <img src="<?= h(sv('site_logo')) ?>" alt="Logo" style="height:56px;width:auto;border:1px solid var(--clr-border);border-radius:var(--radius-sm);padding:6px;background:#fff">
                  </div>
                  <?php endif; ?>
                  <input type="file" name="site_logo" class="form-control" accept="image/*">
                  <span class="form-hint">Replaces the logo.png displayed site-wide.</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Favicon</label>
                  <?php if (sv('site_favicon')): ?>
                  <div style="margin-bottom:10px">
                    <img src="<?= h(sv('site_favicon')) ?>" alt="Favicon" style="height:32px;width:32px;border:1px solid var(--clr-border);border-radius:4px;padding:2px;background:#fff">
                  </div>
                  <?php endif; ?>
                  <input type="file" name="site_favicon" class="form-control" accept="image/x-icon,image/png,image/svg+xml">
                  <span class="form-hint">ICO, PNG, or SVG — displayed in browser tabs.</span>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Maintenance Mode</label>
                  <label class="admin-toggle">
                    <input type="checkbox" name="settings[maintenance_mode]" value="1" <?= sv('maintenance_mode') ? 'checked' : '' ?>>
                    <span class="admin-toggle-slider"></span>
                    <span class="admin-toggle-label">Enable maintenance mode (public site shows "Coming Soon" page)</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- ════════ CONTACT ════════ -->
          <div class="settings-panel" id="panel-contact" style="display:none">
            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-address-card" style="color:var(--clr-gold)"></i> Contact Information
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Contact Email</label>
                    <div class="input-group">
                      <i class="ig-icon fas fa-envelope"></i>
                      <input type="email" name="settings[contact_email]" class="form-control"
                             value="<?= h(sv('contact_email', CONTACT_EMAIL)) ?>">
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Contact Phone</label>
                    <div class="input-group">
                      <i class="ig-icon fas fa-phone"></i>
                      <input type="text" name="settings[contact_phone]" class="form-control"
                             value="<?= h(sv('contact_phone', CONTACT_PHONE)) ?>">
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">WhatsApp Number</label>
                    <div class="input-group">
                      <i class="ig-icon fab fa-whatsapp"></i>
                      <input type="text" name="settings[contact_whatsapp]" class="form-control"
                             value="<?= h(sv('contact_whatsapp', CONTACT_WHATSAPP)) ?>"
                             placeholder="+254700000000">
                    </div>
                    <span class="form-hint">Include country code, no spaces (e.g. +254700000000).</span>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Office Hours</label>
                    <div class="input-group">
                      <i class="ig-icon fas fa-clock"></i>
                      <input type="text" name="settings[office_hours]" class="form-control"
                             value="<?= h(sv('office_hours', 'Mon–Sat: 8AM – 6PM EAT')) ?>">
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Physical Address</label>
                  <textarea name="settings[contact_address]" class="form-control" rows="2"><?= h(sv('contact_address', CONTACT_ADDRESS)) ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Google Maps Embed URL</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-map-pin"></i>
                    <input type="url" name="settings[google_maps_url]" class="form-control"
                           value="<?= h(sv('google_maps_url')) ?>" placeholder="https://maps.google.com/maps?...">
                  </div>
                  <span class="form-hint">Used on the Contact page map embed.</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ════════ SOCIAL MEDIA ════════ -->
          <div class="settings-panel" id="panel-social" style="display:none">
            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-share-nodes" style="color:var(--clr-gold)"></i> Social Media Links
                </span>
              </div>
              <div class="admin-card-body">
                <?php
                $socials = [
                  ['fab fa-facebook',   '#1877F2', 'Facebook',   'social_facebook',   'https://facebook.com/mtsafaris'],
                  ['fab fa-instagram',  '#E1306C', 'Instagram',  'social_instagram',  'https://instagram.com/mtsafaris'],
                  ['fab fa-x-twitter',  '#000',    'Twitter / X','social_twitter',    'https://x.com/mtsafaris'],
                  ['fab fa-youtube',    '#FF0000', 'YouTube',    'social_youtube',    'https://youtube.com/@mtsafaris'],
                  ['fab fa-linkedin',   '#0077B5', 'LinkedIn',   'social_linkedin',   'https://linkedin.com/company/mtsafaris'],
                  ['fab fa-tiktok',     '#000',    'TikTok',     'social_tiktok',     'https://tiktok.com/@mtsafaris'],
                  ['fab fa-whatsapp',   '#25D366', 'WhatsApp Channel', 'social_whatsapp', 'https://wa.me/c/...'],
                ];
                foreach ($socials as [$icon, $clr, $label, $key, $placeholder]): ?>
                <div class="form-group">
                  <label class="form-label">
                    <i class="<?= $icon ?>" style="color:<?= $clr ?>;width:18px"></i> <?= $label ?>
                  </label>
                  <input type="url" name="settings[<?= $key ?>]" class="form-control"
                         value="<?= h(sv($key)) ?>" placeholder="<?= $placeholder ?>">
                </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- ════════ HOMEPAGE ════════ -->
          <div class="settings-panel" id="panel-homepage" style="display:none">
            <div class="admin-card" style="margin-bottom:20px">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-chart-bar" style="color:var(--clr-gold)"></i> Hero Stats
                </span>
              </div>
              <div class="admin-card-body">
                <p class="form-hint" style="margin-bottom:16px">These numbers appear in the hero section and the stats strip on the homepage.</p>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Happy Travelers</label>
                    <input type="number" name="settings[stat_travelers]" class="form-control"
                           value="<?= h(sv('stat_travelers', '5000')) ?>" min="0">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Destinations</label>
                    <input type="number" name="settings[stat_destinations]" class="form-control"
                           value="<?= h(sv('stat_destinations', '150')) ?>" min="0">
                  </div>
                </div>
                <div class="form-row" style="margin-bottom:0">
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Years Experience</label>
                    <input type="number" name="settings[stat_years]" class="form-control"
                           value="<?= h(sv('stat_years', '18')) ?>" min="0">
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Client Satisfaction (%)</label>
                    <input type="number" name="settings[stat_satisfaction]" class="form-control"
                           value="<?= h(sv('stat_satisfaction', '98')) ?>" min="0" max="100">
                  </div>
                </div>
              </div>
            </div>

            <div class="admin-card" style="margin-bottom:20px">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-star" style="color:var(--clr-gold)"></i> Hero Badge &amp; Headline
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label">Hero Badge Text</label>
                  <input type="text" name="settings[hero_badge]" class="form-control"
                         value="<?= h(sv('hero_badge', '#1 Rated Travel Company in East Africa')) ?>"
                         placeholder="#1 Rated Travel Company in East Africa">
                </div>
                <div class="form-group">
                  <label class="form-label">Hero Subtitle</label>
                  <textarea name="settings[hero_subtitle]" class="form-control" rows="3"><?= h(sv('hero_subtitle', 'From iconic African safaris to luxury island retreats, corporate travel solutions, and bespoke adventures — we craft journeys that inspire and endure.')) ?></textarea>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Newsletter Tagline</label>
                  <input type="text" name="settings[newsletter_tagline]" class="form-control"
                         value="<?= h(sv('newsletter_tagline', 'Get exclusive travel deals, destination guides, and travel tips delivered straight to your inbox. Join 10,000+ subscribers.')) ?>">
                </div>
              </div>
            </div>

            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-handshake" style="color:var(--clr-gold)"></i> Partners &amp; Certifications
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Partner Names</label>
                  <textarea name="settings[partners]" class="form-control" rows="3"><?= h(sv('partners', 'KATO,ATTA,IATA,Kenya Tourism Board,Tripadvisor')) ?></textarea>
                  <span class="form-hint">Comma-separated list of partner / certification names displayed at the bottom of the homepage.</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ════════ EMAIL / SMTP ════════ -->
          <div class="settings-panel" id="panel-email" style="display:none">
            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-envelope" style="color:var(--clr-gold)"></i> Email / SMTP Settings
                </span>
              </div>
              <div class="admin-card-body">
                <div class="flash-msg flash-warning" style="margin-bottom:20px">
                  <i class="fas fa-info-circle"></i>
                  <span>These values override the SMTP defaults set in <code>config.php</code> / <code>.env</code>.</span>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="settings[smtp_host]" class="form-control"
                           value="<?= h(sv('smtp_host', MAIL_SMTP_HOST)) ?>" placeholder="smtp.gmail.com">
                  </div>
                  <div class="form-group" style="max-width:120px">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="settings[smtp_port]" class="form-control"
                           value="<?= h(sv('smtp_port', (string)MAIL_SMTP_PORT)) ?>" placeholder="587">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="settings[smtp_user]" class="form-control"
                           value="<?= h(sv('smtp_user', MAIL_SMTP_USER)) ?>" placeholder="noreply@mtsafaris.com">
                  </div>
                  <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="settings[smtp_pass]" class="form-control"
                           placeholder="Leave blank to keep current">
                  </div>
                </div>
                <div class="form-row" style="margin-bottom:0">
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">From Name</label>
                    <input type="text" name="settings[mail_from_name]" class="form-control"
                           value="<?= h(sv('mail_from_name', APP_NAME)) ?>">
                  </div>
                  <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">From Email</label>
                    <input type="email" name="settings[mail_from_email]" class="form-control"
                           value="<?= h(sv('mail_from_email', CONTACT_EMAIL)) ?>">
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- ════════ PAYMENTS ════════ -->
          <div class="settings-panel" id="panel-payments" style="display:none">

            <!-- Stripe -->
            <div class="admin-card" style="margin-bottom:20px">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fab fa-stripe" style="color:#635bff"></i> Stripe
                </span>
                <label class="admin-toggle" style="margin:0">
                  <input type="checkbox" name="settings[stripe_enabled]" value="1" <?= sv('stripe_enabled') ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Enabled</span>
                </label>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label">Publishable Key</label>
                  <input type="text" name="settings[stripe_pub_key]" class="form-control"
                         value="<?= h(sv('stripe_pub_key', STRIPE_PUBLIC_KEY)) ?>" placeholder="pk_live_...">
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Secret Key</label>
                  <input type="password" name="settings[stripe_secret]" class="form-control"
                         placeholder="Leave blank to keep current (sk_live_...)">
                </div>
              </div>
            </div>

            <!-- PayPal -->
            <div class="admin-card" style="margin-bottom:20px">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fab fa-paypal" style="color:#003087"></i> PayPal
                </span>
                <label class="admin-toggle" style="margin:0">
                  <input type="checkbox" name="settings[paypal_enabled]" value="1" <?= sv('paypal_enabled') ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Enabled</span>
                </label>
              </div>
              <div class="admin-card-body">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="settings[paypal_client_id]" class="form-control"
                           value="<?= h(sv('paypal_client_id', PAYPAL_CLIENT_ID)) ?>">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Mode</label>
                    <select name="settings[paypal_mode]" class="form-control">
                      <option value="sandbox" <?= sv('paypal_mode', 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (Testing)</option>
                      <option value="live"    <?= sv('paypal_mode') === 'live'    ? 'selected' : '' ?>>Live</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>

            <!-- Bank Transfer -->
            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-building-columns" style="color:var(--clr-gold)"></i> Bank Transfer Details
                </span>
                <label class="admin-toggle" style="margin:0">
                  <input type="checkbox" name="settings[bank_enabled]" value="1" <?= sv('bank_enabled', '1') ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Enabled</span>
                </label>
              </div>
              <div class="admin-card-body">
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Bank Details</label>
                  <textarea name="settings[bank_details]" class="form-control" rows="6"
                            placeholder="Bank Name: Equity Bank Kenya&#10;Account Name: Mountain Top Safaris Ltd&#10;Account Number: 123456789&#10;Branch: Westlands&#10;Swift Code: EQBLKENA"><?= h(sv('bank_details')) ?></textarea>
                  <span class="form-hint">Displayed to customers when they choose bank transfer at checkout.</span>
                </div>
              </div>
            </div>

          </div>

          <!-- ════════ SEO & ANALYTICS ════════ -->
          <div class="settings-panel" id="panel-seo" style="display:none">
            <div class="admin-card" style="margin-bottom:20px">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fas fa-magnifying-glass" style="color:var(--clr-gold)"></i> SEO Defaults
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label">Default Meta Title</label>
                  <input type="text" name="settings[meta_title]" id="seoTitle" class="form-control"
                         value="<?= h(sv('meta_title', APP_NAME . ' | Premium Safari & Travel')) ?>"
                         maxlength="70" placeholder="Used when a page has no specific title">
                  <div style="display:flex;justify-content:space-between;margin-top:4px">
                    <span class="form-hint">Recommended: 50–60 characters</span>
                    <span id="seoTitleCount" style="font-size:.75rem;color:var(--clr-muted)">0 / 60</span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Default Meta Description</label>
                  <textarea name="settings[meta_description]" id="seoDesc" class="form-control" rows="3"
                            maxlength="400" placeholder="Used when a page has no specific description"><?= h(sv('meta_description')) ?></textarea>
                  <div style="display:flex;justify-content:space-between;margin-top:4px">
                    <span class="form-hint">Recommended: 150–160 characters</span>
                    <span id="seoDescCount" style="font-size:.75rem;color:var(--clr-muted)">0 / 160</span>
                  </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">Default OG / Share Image URL</label>
                  <input type="url" name="settings[og_image]" class="form-control"
                         value="<?= h(sv('og_image')) ?>" placeholder="https://mtsafaris.com/assets/images/og-image.jpg">
                  <span class="form-hint">Used when no page image is available. Recommended: 1200×630 px.</span>
                </div>
              </div>
            </div>

            <div class="admin-card">
              <div class="admin-card-header">
                <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                  <i class="fab fa-google" style="color:var(--clr-gold)"></i> API Keys &amp; Analytics
                </span>
              </div>
              <div class="admin-card-body">
                <div class="form-group">
                  <label class="form-label">Google Analytics 4 ID</label>
                  <div class="input-group">
                    <i class="ig-icon fab fa-google"></i>
                    <input type="text" name="settings[ga_id]" class="form-control"
                           value="<?= h(sv('ga_id', GOOGLE_ANALYTICS)) ?>" placeholder="G-XXXXXXXXXX">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Google Search Console Verification</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-magnifying-glass-chart"></i>
                    <input type="text" name="settings[search_console]" class="form-control"
                           value="<?= h(sv('search_console', GOOGLE_SEARCH_CONSOLE)) ?>"
                           placeholder="Verification code from Search Console">
                  </div>
                  <span class="form-hint">The value= part of the <code>&lt;meta name="google-site-verification"&gt;</code> tag only.</span>
                </div>
                <div class="form-group">
                  <label class="form-label">Google Maps API Key</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-map-location-dot"></i>
                    <input type="text" name="settings[google_maps_key]" class="form-control"
                           value="<?= h(sv('google_maps_key', GOOGLE_MAPS_KEY)) ?>" placeholder="AIzaSy...">
                  </div>
                </div>
                <div class="form-group" style="margin-bottom:0">
                  <label class="form-label">reCAPTCHA Site Key</label>
                  <div class="input-group">
                    <i class="ig-icon fas fa-shield-halved"></i>
                    <input type="text" name="settings[recaptcha_site_key]" class="form-control"
                           value="<?= h(sv('recaptcha_site_key', RECAPTCHA_SITE)) ?>" placeholder="6LcXXXX...">
                  </div>
                </div>
              </div>
            </div>

          </div>

        </div><!-- /panels -->
      </div><!-- /settings-layout -->
    </form>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Tab switching
function showTab(id) {
  document.querySelectorAll('.settings-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.settings-tab-btn').forEach(b => {
    b.classList.remove('active');
    b.removeAttribute('aria-current');
  });
  const panel = document.getElementById('panel-' + id);
  const btn   = document.getElementById('tab-'   + id);
  if (panel) panel.style.display = 'block';
  if (btn)   { btn.classList.add('active'); btn.setAttribute('aria-current', 'page'); }
  document.getElementById('activeTabInput').value = id;
  history.replaceState(null, '', '#' + id);
}

document.querySelectorAll('.settings-tab-btn').forEach(btn => {
  btn.addEventListener('click', () => showTab(btn.dataset.tab));
});

// Activate from hash or default
const initTab = location.hash.replace('#', '') || 'general';
showTab(document.getElementById('panel-' + initTab) ? initTab : 'general');

// SEO character counters
function charCounter(inputId, countId, limit) {
  const el = document.getElementById(inputId);
  const ct = document.getElementById(countId);
  if (!el || !ct) return;
  const update = () => {
    const len = el.value.length;
    ct.textContent = len + ' / ' + limit;
    ct.style.color = len > limit ? 'var(--clr-danger)' : len > limit * .85 ? 'var(--clr-gold)' : 'var(--clr-muted)';
  };
  el.addEventListener('input', update);
  update();
}
charCounter('seoTitle', 'seoTitleCount', 60);
charCounter('seoDesc',  'seoDescCount',  160);
</script>

<style>
.settings-layout {
  display: grid;
  grid-template-columns: 220px 1fr;
  gap: 24px;
  align-items: start;
}
@media (max-width: 768px) { .settings-layout { grid-template-columns: 1fr; } }

.settings-nav {
  position: sticky;
  top: calc(var(--header-h, 64px) + 16px);
  background: var(--clr-surface);
  border: 1px solid var(--clr-border);
  border-radius: var(--radius-md);
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-height: 200px;
}

.settings-tab-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  border: none;
  background: transparent;
  color: var(--clr-text);
  font-size: .875rem;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: background .15s, color .15s;
  width: 100%;
}
.settings-tab-btn i { width: 16px; text-align: center; color: var(--clr-muted); transition: color .15s; }
.settings-tab-btn:hover { background: var(--clr-light); color: var(--clr-primary); }
.settings-tab-btn:hover i, .settings-tab-btn.active i { color: var(--clr-gold); }
.settings-tab-btn.active {
  background: rgba(12,38,20,.07);
  color: var(--clr-primary);
  font-weight: 600;
}
</style>
</body>
</html>
