<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) redirect(url('portal/'));

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if ($_POST['password'] !== $_POST['password_confirm']) {
        $errors[] = 'Passwords do not match.';
    } elseif (strlen($_POST['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    } else {
        $result = register($_POST);
        if ($result['success']) {
            login($_POST['email'], $_POST['password']);
            flash('success', 'Account created! Welcome to MT Safaris.');
            redirect(url('portal/'));
        }
        $errors[] = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <style>
    body { background: linear-gradient(135deg, var(--clr-primary) 0%, var(--clr-primary-l) 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
    .auth-card { background: #fff; border-radius: 20px; padding: 44px 40px; max-width: 480px; width: 100%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
  </style>
</head>
<body>
<div class="auth-card">
  <a href="<?= url() ?>" class="logo" style="display:flex;justify-content:center;margin-bottom:24px;text-decoration:none">
    <img src="<?= url('assets/images/logo.png') ?>" alt="Mountain Top Safaris Adventures" style="height:72px;width:auto">
  </a>
  <h2 style="text-align:center;color:var(--clr-primary);margin-bottom:6px;font-size:1.4rem">Create Your Account</h2>
  <p style="text-align:center;color:var(--clr-muted);font-size:.875rem;margin-bottom:24px">Join 5,000+ happy travelers</p>

  <?php if ($errors): ?>
  <div class="flash-msg flash-error"><i class="fas fa-exclamation-circle"></i><div><ul style="padding-left:16px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div></div>
  <?php endif; ?>

  <form method="POST">
    <?= csrfField() ?>
    <div class="form-row">
      <div class="form-group">
        <label>First Name <span class="required">*</span></label>
        <input type="text" name="first_name" class="form-control" required value="<?= h($_POST['first_name']??'') ?>" autofocus>
      </div>
      <div class="form-group">
        <label>Last Name <span class="required">*</span></label>
        <input type="text" name="last_name" class="form-control" required value="<?= h($_POST['last_name']??'') ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Email Address <span class="required">*</span></label>
      <input type="email" name="email" class="form-control" required value="<?= h($_POST['email']??'') ?>" placeholder="your@email.com">
    </div>
    <div class="form-group">
      <label>Phone Number</label>
      <input type="tel" name="phone" class="form-control" value="<?= h($_POST['phone']??'') ?>" placeholder="+254 700 000 000">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Password <span class="required">*</span></label>
        <input type="password" name="password" class="form-control" required minlength="8">
        <p class="form-hint">At least 8 characters</p>
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="password_confirm" class="form-control" required>
      </div>
    </div>
    <label class="filter-option" style="margin-bottom:20px;font-size:.82rem">
      <input type="checkbox" name="agree" required> I agree to MT Safaris' <a href="<?= url('terms.php') ?>" target="_blank" style="color:var(--clr-gold)">Terms of Service</a> and <a href="<?= url('privacy.php') ?>" target="_blank" style="color:var(--clr-gold)">Privacy Policy</a>.
    </label>
    <button type="submit" class="btn btn-gold btn-block btn-lg">
      <i class="fas fa-user-plus"></i> Create Account
    </button>
  </form>

  <p style="text-align:center;font-size:.875rem;color:var(--clr-muted);margin-top:20px">
    Already have an account? <a href="<?= url('portal/login.php') ?>" style="color:var(--clr-gold);font-weight:600">Sign in</a>
  </p>
</div>
</body>
</html>
