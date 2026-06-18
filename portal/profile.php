<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    // Password change
    if (!empty($_POST['current_password'])) {
        $fullUser = DB::row("SELECT password_hash FROM users WHERE id=?", [$user['id']]);
        if (!password_verify($_POST['current_password'], $fullUser['password_hash'])) {
            $flash = ['type' => 'error', 'message' => 'Current password is incorrect.'];
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $flash = ['type' => 'error', 'message' => 'New passwords do not match.'];
        } elseif (strlen($_POST['new_password']) < 8) {
            $flash = ['type' => 'error', 'message' => 'Password must be at least 8 characters.'];
        } else {
            DB::update('users', [
                'password_hash' => password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost' => 12])
            ], ['id' => $user['id']]);
            flash('success', 'Password updated successfully.');
            redirect(url('portal/profile.php'));
        }
    } else {
        // Profile update
        DB::update('users', [
            'first_name' => trim($_POST['first_name'] ?? $user['first_name']),
            'last_name'  => trim($_POST['last_name']  ?? $user['last_name']),
            'phone'      => trim($_POST['phone'] ?? ''),
        ], ['id' => $user['id']]);
        flash('success', 'Profile updated successfully.');
        redirect(url('portal/profile.php'));
    }
}

// Refresh after update
$user = DB::row("SELECT id, first_name, last_name, email, phone, role, avatar FROM users WHERE id=?", [$_SESSION['user_id']]);
$sessionFlash = getFlash();
if ($sessionFlash) $flash = $sessionFlash;

$initials = strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style>
    .profile-avatar { width:80px;height:80px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#fff;font-size:1.8rem;font-weight:800;font-family:var(--ff-head);flex-shrink:0 }
    .strength-bar { height:4px;background:var(--clr-border);border-radius:2px;margin-top:6px }
    .strength-fill { height:100%;border-radius:2px;transition:width .3s,background .3s }
    .strength-label { font-size:.68rem;color:var(--clr-muted);margin-top:3px }
  </style>
</head>
<body>
<div class="portal-layout">
  <?php require_once 'includes/sidebar.php'; ?>

  <main class="portal-main">
    <div style="margin-bottom:24px">
      <h1 style="font-size:1.4rem;color:var(--clr-primary);margin-bottom:2px">My Profile</h1>
      <p style="color:var(--clr-muted);font-size:.82rem">Manage your account details and password</p>
    </div>

    <?php if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']==='error'?'danger':$flash['type']) ?>" style="margin-bottom:20px">
      <i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i>
      <span><?= h($flash['message']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Profile header card -->
    <div class="card" style="margin-bottom:24px">
      <div class="card-body" style="display:flex;align-items:center;gap:20px;padding:24px">
        <div class="profile-avatar"><?= $initials ?></div>
        <div>
          <h2 style="font-size:1.15rem;color:var(--clr-primary);margin-bottom:2px"><?= h($user['first_name'].' '.$user['last_name']) ?></h2>
          <p style="color:var(--clr-muted);font-size:.82rem;margin-bottom:6px"><?= h($user['email']) ?></p>
          <?php if (!empty($user['phone'])): ?><p style="color:var(--clr-muted);font-size:.82rem;margin:0"><i class="fas fa-phone" style="color:var(--clr-gold);font-size:.75rem"></i> <?= h($user['phone']) ?></p><?php endif; ?>
        </div>
        <div style="margin-left:auto;text-align:right">
          <span style="display:inline-block;padding:4px 12px;border-radius:20px;font-size:.72rem;font-weight:700;background:#dbeafe;color:#1e40af">
            <?= ucfirst($user['role']) ?>
          </span>
          <div style="font-size:.7rem;color:var(--clr-muted);margin-top:6px">Member since <?= date('M Y', strtotime($user['created_at'] ?? 'now')) ?></div>
        </div>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

      <!-- Personal Information -->
      <div class="card">
        <div class="card-header">
          <h3><i class="fas fa-user" style="color:var(--clr-gold);margin-right:6px"></i>Personal Information</h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= h($user['first_name']) ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= h($user['last_name']) ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Email Address</label>
              <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled style="opacity:.65;cursor:not-allowed">
              <p class="form-hint">Email address cannot be changed here. Contact support if needed.</p>
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number</label>
              <div style="position:relative">
                <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted);font-size:.85rem"><i class="fas fa-phone"></i></span>
                <input type="tel" name="phone" class="form-control" style="padding-left:34px" value="<?= h($user['phone'] ?? '') ?>" placeholder="+254 700 000 000">
              </div>
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </form>
        </div>
      </div>

      <!-- Change Password -->
      <div>
        <div class="card" style="margin-bottom:20px">
          <div class="card-header">
            <h3><i class="fas fa-lock" style="color:var(--clr-gold);margin-right:6px"></i>Change Password</h3>
          </div>
          <div class="card-body">
            <form method="POST" id="pwForm">
              <?= csrfField() ?>
              <div class="form-group">
                <label class="form-label">Current Password</label>
                <div style="position:relative">
                  <input type="password" name="current_password" id="pwCurrent" class="form-control" placeholder="Enter current password">
                  <button type="button" onclick="togglePw('pwCurrent','eyeCurrent')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--clr-muted)"><i id="eyeCurrent" class="fas fa-eye"></i></button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">New Password</label>
                <div style="position:relative">
                  <input type="password" name="new_password" id="pwNew" class="form-control" minlength="8" placeholder="At least 8 characters" oninput="checkStrength(this.value)">
                  <button type="button" onclick="togglePw('pwNew','eyeNew')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--clr-muted)"><i id="eyeNew" class="fas fa-eye"></i></button>
                </div>
                <div class="strength-bar"><div class="strength-fill" id="strengthFill" style="width:0;background:#ef4444"></div></div>
                <div class="strength-label" id="strengthLabel"></div>
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
              </div>
              <button type="submit" class="btn btn-outline">
                <i class="fas fa-key"></i> Update Password
              </button>
            </form>
          </div>
        </div>

        <!-- Security Tips -->
        <div class="card">
          <div class="card-header"><h3 style="font-size:.875rem"><i class="fas fa-shield-alt" style="color:var(--clr-gold);margin-right:6px"></i>Security Tips</h3></div>
          <div class="card-body">
            <?php foreach ([
              ['fas fa-check', 'Use at least 8 characters'],
              ['fas fa-check', 'Mix uppercase, lowercase, numbers & symbols'],
              ['fas fa-check', "Don't reuse passwords from other sites"],
              ['fas fa-check', 'Change your password regularly'],
            ] as [$icon, $tip]): ?>
            <div style="display:flex;gap:10px;padding:8px 0;border-bottom:1px solid var(--clr-border)">
              <i class="<?= $icon ?>" style="color:var(--clr-gold);font-size:.75rem;margin-top:3px;flex-shrink:0"></i>
              <span style="font-size:.82rem;color:var(--clr-muted)"><?= $tip ?></span>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>

    <!-- Danger Zone -->
    <div class="card" style="margin-top:24px;border-color:#fee2e2">
      <div class="card-header" style="background:#fff5f5">
        <h3 style="color:var(--clr-danger);font-size:.9rem"><i class="fas fa-exclamation-triangle" style="margin-right:6px"></i>Danger Zone</h3>
      </div>
      <div class="card-body">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
          <div>
            <div style="font-weight:600;color:var(--clr-primary);font-size:.9rem;margin-bottom:2px">Delete Account</div>
            <p style="font-size:.8rem;color:var(--clr-muted);margin:0">Permanently delete your account and all associated data. This cannot be undone.</p>
          </div>
          <a href="<?= url('contact.php') ?>" class="btn" style="background:#fff5f5;color:var(--clr-danger);border:1px solid #fca5a5;font-size:.82rem;white-space:nowrap">
            <i class="fas fa-trash"></i> Request Deletion
          </a>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="<?= url('assets/js/main.js') ?>"></script>
<script>
function togglePw(inputId, iconId) {
  const inp = document.getElementById(inputId);
  const ico = document.getElementById(iconId);
  if (inp.type === 'password') { inp.type = 'text'; ico.className = 'fas fa-eye-slash'; }
  else { inp.type = 'password'; ico.className = 'fas fa-eye'; }
}

function checkStrength(val) {
  const fill  = document.getElementById('strengthFill');
  const label = document.getElementById('strengthLabel');
  if (!val) { fill.style.width='0'; label.textContent=''; return; }
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  const levels = [
    [25, '#ef4444', 'Weak'],
    [50, '#f59e0b', 'Fair'],
    [75, '#3b82f6', 'Good'],
    [100,'#10b981', 'Strong'],
  ];
  const [w, bg, text] = levels[score - 1] || [10, '#ef4444', 'Too short'];
  fill.style.width = w + '%';
  fill.style.background = bg;
  label.textContent = text;
  label.style.color = bg;
}
</script>
</body>
</html>
