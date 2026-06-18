<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) redirect(url('portal/'));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $result = login(
        $_POST['email'] ?? '',
        $_POST['password'] ?? '',
        !empty($_POST['remember'])
    );
    if ($result['success']) {
        $dest = $_SESSION['login_redirect'] ?? url('portal/');
        unset($_SESSION['login_redirect']);
        redirect($dest);
    }
    $error = $result['message'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style>
    body { background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-primary-l) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .auth-card { background: #fff; border-radius: 20px; padding: 44px 40px; max-width: 440px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
    .auth-card .logo { justify-content: center; margin-bottom: 28px; }
    .divider { display: flex; align-items: center; gap: 12px; margin: 20px 0; color: var(--clr-muted); font-size: .8rem; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--clr-border); }
    .social-btn { width: 100%; display: flex; align-items: center; justify-content: center; gap: 10px; padding: 11px; border: 1.5px solid var(--clr-border); border-radius: var(--radius-sm); font-size: .875rem; font-weight: 500; cursor: pointer; transition: all .2s; background: #fff; margin-bottom: 10px; }
    .social-btn:hover { border-color: var(--clr-primary); background: var(--clr-light); }
  </style>
</head>
<body>
<div class="auth-card">
  <a href="<?= url() ?>" class="logo" style="display:flex;align-items:center;gap:10px;justify-content:center;margin-bottom:28px;text-decoration:none">
    <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
    <div class="logo-text" style="color:var(--clr-primary)">MT Safaris</div>
  </a>
  <h2 style="text-align:center;color:var(--clr-primary);margin-bottom:6px;font-size:1.4rem">Welcome Back</h2>
  <p style="text-align:center;color:var(--clr-muted);font-size:.875rem;margin-bottom:28px">Sign in to manage your trips</p>

  <?php if ($error): ?>
  <div class="flash-msg flash-error"><i class="fas fa-exclamation-circle"></i><span><?= h($error) ?></span></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrfField() ?>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" class="form-control" required value="<?= h($_POST['email']??'') ?>" placeholder="your@email.com" autofocus>
    </div>
    <div class="form-group">
      <label style="display:flex;justify-content:space-between">
        Password
        <a href="<?= url('portal/forgot-password.php') ?>" style="font-size:.78rem;color:var(--clr-gold)">Forgot password?</a>
      </label>
      <div style="position:relative">
        <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
        <button type="button" onclick="const i=document.getElementById('password');i.type=i.type==='password'?'text':'password';this.querySelector('i').className='fas fa-eye'+(i.type==='password'?'':'-slash')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--clr-muted)">
          <i class="fas fa-eye"></i>
        </button>
      </div>
    </div>
    <label class="filter-option" style="margin-bottom:20px;font-size:.875rem">
      <input type="checkbox" name="remember"> Remember me for 30 days
    </label>
    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-bottom:16px">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
  </form>

  <div class="divider">or continue with</div>
  <a href="<?= url('portal/auth/google.php') ?>" class="social-btn">
    <img src="https://www.google.com/favicon.ico" width="18" alt="Google"> Continue with Google
  </a>

  <p style="text-align:center;font-size:.875rem;color:var(--clr-muted);margin-top:20px">
    Don't have an account? <a href="<?= url('portal/register.php') ?>" style="color:var(--clr-gold);font-weight:600">Create one free</a>
  </p>
</div>
</body>
</html>
