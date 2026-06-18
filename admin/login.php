<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isAdmin()) redirect(url('admin/'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please enter your email and password.';
    } else {
        $result = login($email, $password, false);
        if ($result['success']) {
            if (isAdmin()) {
                flash('success', 'Welcome back, ' . currentUser()['first_name'] . '!');
                redirect(url('admin/'));
            } else {
                logout();
                $error = 'Access denied. Administrator credentials required.';
            }
        } else {
            $error = $result['message'] ?? 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --blue:   #0D3B66;
      --blue-l: #1a5c99;
      --gold:   #C9A84C;
      --red:    #dc2626;
      --border: #e2e8f0;
      --muted:  #64748b;
      --text:   #1e293b;
    }
    body {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
      font-family: 'Segoe UI', system-ui, sans-serif;
      background: #f8fafc;
    }

    /* ── Left panel ── */
    .login-panel {
      background: linear-gradient(160deg, var(--blue) 0%, #0a2d52 60%, #071e38 100%);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 48px 52px;
      position: relative;
      overflow: hidden;
    }
    .login-panel::before {
      content: '';
      position: absolute;
      width: 500px; height: 500px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.18) 0%, transparent 70%);
      top: -100px; left: -100px;
      pointer-events: none;
    }
    .login-panel::after {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      border-radius: 50%;
      background: radial-gradient(circle, rgba(201,168,76,.12) 0%, transparent 70%);
      bottom: -80px; right: -80px;
      pointer-events: none;
    }
    .panel-logo {
      display: flex;
      align-items: center;
      gap: 14px;
      text-decoration: none;
      position: relative;
      z-index: 1;
    }
    .panel-logo-icon {
      width: 48px; height: 48px;
      background: var(--gold);
      border-radius: 12px;
      display: grid;
      place-items: center;
    }
    .panel-logo-icon i { font-size: 1.4rem; color: #fff; }
    .panel-logo-text h1 { font-size: 1.25rem; color: #fff; font-weight: 800; letter-spacing: .02em; }
    .panel-logo-text p { font-size: .72rem; color: rgba(255,255,255,.55); }
    .panel-hero { position: relative; z-index: 1; }
    .panel-hero h2 {
      font-size: 2.4rem;
      color: #fff;
      font-weight: 800;
      line-height: 1.2;
      margin-bottom: 16px;
    }
    .panel-hero h2 span { color: var(--gold); }
    .panel-hero p { font-size: .95rem; color: rgba(255,255,255,.65); line-height: 1.7; max-width: 340px; }
    .panel-stats {
      display: flex;
      gap: 28px;
      position: relative;
      z-index: 1;
    }
    .panel-stat { text-align: center; }
    .panel-stat-num { font-size: 1.6rem; font-weight: 800; color: var(--gold); line-height: 1; }
    .panel-stat-label { font-size: .7rem; color: rgba(255,255,255,.5); text-transform: uppercase; letter-spacing: .08em; margin-top: 4px; }

    /* ── Right panel (form) ── */
    .form-panel {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 48px 40px;
      background: #fff;
    }
    .login-box { width: 100%; max-width: 400px; }
    .login-box h2 { font-size: 1.75rem; color: var(--text); font-weight: 800; margin-bottom: 6px; }
    .login-box > p { font-size: .9rem; color: var(--muted); margin-bottom: 32px; }

    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block;
      font-size: .75rem;
      font-weight: 700;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .07em;
      margin-bottom: 7px;
    }
    .form-input {
      width: 100%;
      padding: 12px 16px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      font-size: .9rem;
      color: var(--text);
      font-family: inherit;
      background: #f8fafc;
      transition: border-color .2s, box-shadow .2s;
      outline: none;
    }
    .form-input:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(13,59,102,.1); background: #fff; }
    .input-wrap { position: relative; }
    .input-wrap .form-input { padding-left: 42px; }
    .input-wrap .input-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: .85rem;
    }
    .input-wrap .toggle-pass {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      cursor: pointer;
      color: var(--muted);
      padding: 4px;
      line-height: 1;
    }
    .input-wrap .toggle-pass:hover { color: var(--blue); }

    .error-box {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-left: 4px solid var(--red);
      color: #991b1b;
      padding: 12px 14px;
      border-radius: 8px;
      font-size: .875rem;
      margin-bottom: 22px;
      line-height: 1.5;
    }

    .btn-signin {
      width: 100%;
      padding: 14px;
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: .95rem;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: 8px;
      transition: background .2s, transform .1s;
      font-family: inherit;
    }
    .btn-signin:hover { background: var(--blue-l); }
    .btn-signin:active { transform: scale(.98); }

    .login-footer {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 24px;
      padding-top: 20px;
      border-top: 1px solid var(--border);
      font-size: .82rem;
    }
    .login-footer a { color: var(--muted); text-decoration: none; display: flex; align-items: center; gap: 5px; transition: color .2s; }
    .login-footer a:hover { color: var(--blue); }

    /* Responsive */
    @media (max-width: 768px) {
      body { grid-template-columns: 1fr; }
      .login-panel { display: none; }
      .form-panel { min-height: 100vh; }
    }
  </style>
</head>
<body>

<!-- Left decorative panel -->
<div class="login-panel">
  <a href="<?= url() ?>" class="panel-logo">
    <div class="panel-logo-icon"><i class="fas fa-globe-africa"></i></div>
    <div class="panel-logo-text">
      <h1>MT Safaris</h1>
      <p>Management System</p>
    </div>
  </a>

  <div class="panel-hero">
    <h2>Welcome back to your <span>Admin Panel</span></h2>
    <p>Manage bookings, packages, customers, and content — all from one place.</p>
  </div>

  <div class="panel-stats">
    <?php
    $totalBookings = DB::value("SELECT COUNT(*) FROM bookings");
    $activePackages = DB::value("SELECT COUNT(*) FROM packages WHERE is_active=1");
    $totalUsers = DB::value("SELECT COUNT(*) FROM users WHERE role='customer'");
    foreach ([
      [$totalBookings, 'Bookings'],
      [$activePackages, 'Packages'],
      [$totalUsers, 'Customers'],
    ] as [$num, $label]):
    ?>
    <div class="panel-stat">
      <div class="panel-stat-num"><?= number_format($num) ?></div>
      <div class="panel-stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Right form panel -->
<div class="form-panel">
  <div class="login-box">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,var(--blue),var(--blue-l));border-radius:14px;display:grid;place-items:center;margin-bottom:24px;box-shadow:0 8px 24px rgba(13,59,102,.25)">
      <i class="fas fa-shield-alt" style="font-size:1.4rem;color:#fff"></i>
    </div>
    <h2>Sign In</h2>
    <p>Enter your administrator credentials to continue</p>

    <?php if ($error): ?>
    <div class="error-box">
      <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:1px"></i>
      <span><?= h($error) ?></span>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="on">
      <?= csrfField() ?>

      <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
          <i class="fas fa-envelope input-icon"></i>
          <input type="email" name="email" class="form-input" required autofocus
                 value="<?= h($_POST['email'] ?? '') ?>"
                 placeholder="admin@mtsafaris.com"
                 autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
          <i class="fas fa-lock input-icon"></i>
          <input type="password" name="password" id="passField" class="form-input"
                 required placeholder="Your password"
                 autocomplete="current-password">
          <button type="button" class="toggle-pass" id="togglePass" title="Show/hide password">
            <i class="fas fa-eye" id="toggleIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn-signin">
        <i class="fas fa-sign-in-alt"></i> Sign In to Admin
      </button>
    </form>

    <div class="login-footer">
      <a href="<?= url('portal/forgot-password.php') ?>">
        <i class="fas fa-key"></i> Forgot password?
      </a>
      <a href="<?= url() ?>">
        <i class="fas fa-globe-africa"></i> View Website
      </a>
    </div>
  </div>
</div>

<script>
document.getElementById('togglePass').addEventListener('click', function() {
  const f = document.getElementById('passField');
  const i = document.getElementById('toggleIcon');
  if (f.type === 'password') {
    f.type = 'text';
    i.className = 'fas fa-eye-slash';
  } else {
    f.type = 'password';
    i.className = 'fas fa-eye';
  }
});
</script>
</body>
</html>
