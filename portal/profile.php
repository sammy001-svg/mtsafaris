<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireLogin();
$user = currentUser();

$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (!empty($_POST['current_password'])) {
        $fullUser = DB::row("SELECT password_hash FROM users WHERE id=?", [$user['id']]);
        if (!password_verify($_POST['current_password'], $fullUser['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $error = 'New passwords do not match.';
        } elseif (strlen($_POST['new_password']) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            DB::update('users', ['password_hash' => password_hash($_POST['new_password'], PASSWORD_BCRYPT, ['cost'=>12])], ['id'=>$user['id']]);
            $success = 'Password updated successfully.';
        }
    } else {
        DB::update('users', [
            'first_name' => trim($_POST['first_name']??$user['first_name']),
            'last_name'  => trim($_POST['last_name']??$user['last_name']),
            'phone'      => trim($_POST['phone']??''),
        ], ['id' => $user['id']]);
        $success = 'Profile updated successfully.';
    }
    unset($user); // refresh
    $user = DB::row("SELECT id, first_name, last_name, email, phone, role, avatar FROM users WHERE id=?", [$_SESSION['user_id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body>
<div class="portal-layout">
  <aside class="portal-sidebar">
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:10px;margin-bottom:28px;text-decoration:none">
      <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
      <div class="logo-text">MT Safaris</div>
    </a>
    <nav class="portal-nav">
      <a href="<?= url('portal/') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
      <a href="<?= url('portal/bookings.php') ?>"><i class="fas fa-ticket-alt"></i> My Bookings</a>
      <a href="<?= url('portal/wishlist.php') ?>"><i class="fas fa-heart"></i> Wishlist</a>
      <a href="<?= url('portal/profile.php') ?>" class="active"><i class="fas fa-user"></i> My Profile</a>
      <a href="<?= url('portal/logout.php') ?>" style="color:rgba(255,80,80,.8)"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    </nav>
  </aside>
  <main class="portal-main">
    <h1 style="font-size:1.4rem;color:var(--clr-primary);margin-bottom:4px">My Profile</h1>
    <p style="color:var(--clr-muted);font-size:.82rem;margin-bottom:24px">Manage your account details and security</p>

    <?php if ($success): ?>
    <div class="flash-msg flash-success"><i class="fas fa-check-circle"></i><span><?= h($success) ?></span></div>
    <?php elseif ($error): ?>
    <div class="flash-msg flash-error"><i class="fas fa-exclamation-circle"></i><span><?= h($error) ?></span></div>
    <?php endif; ?>

    <div class="grid-2" style="gap:24px;align-items:start">

      <!-- Profile Form -->
      <div class="card">
        <div class="card-header"><h3>Personal Information</h3></div>
        <div class="card-body">
          <!-- Avatar -->
          <div style="text-align:center;margin-bottom:24px">
            <div style="width:80px;height:80px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#fff;font-size:1.8rem;font-weight:800;font-family:var(--ff-head)">
              <?= strtoupper(substr($user['first_name'],0,1).substr($user['last_name'],0,1)) ?>
            </div>
            <p style="font-size:.78rem;color:var(--clr-muted)">Photo upload coming soon</p>
          </div>
          <form method="POST">
            <?= csrfField() ?>
            <div class="form-row">
              <div class="form-group">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" value="<?= h($user['first_name']) ?>" required>
              </div>
              <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" value="<?= h($user['last_name']) ?>" required>
              </div>
            </div>
            <div class="form-group">
              <label>Email Address</label>
              <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled style="opacity:.7">
              <p class="form-hint">Email cannot be changed here. Contact support.</p>
            </div>
            <div class="form-group">
              <label>Phone Number</label>
              <input type="tel" name="phone" class="form-control" value="<?= h($user['phone']??'') ?>">
            </div>
            <div class="form-group">
              <label>Account Type</label>
              <input type="text" class="form-control" value="<?= h(ROLES[$user['role']]??$user['role']) ?>" disabled style="opacity:.7">
            </div>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </form>
        </div>
      </div>

      <!-- Password Form -->
      <div class="card">
        <div class="card-header"><h3><i class="fas fa-lock" style="color:var(--clr-gold);margin-right:8px"></i>Change Password</h3></div>
        <div class="card-body">
          <form method="POST">
            <?= csrfField() ?>
            <div class="form-group">
              <label>Current Password</label>
              <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
            </div>
            <div class="form-group">
              <label>New Password</label>
              <input type="password" name="new_password" class="form-control" minlength="8" placeholder="At least 8 characters">
            </div>
            <div class="form-group">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
            </div>
            <button type="submit" class="btn btn-outline">
              <i class="fas fa-key"></i> Update Password
            </button>
          </form>

          <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--clr-border)">
            <h4 style="font-size:.9rem;margin-bottom:12px">Security Tips</h4>
            <ul style="list-style:disc;padding-left:16px;font-size:.82rem;color:var(--clr-muted);line-height:1.7">
              <li>Use at least 8 characters</li>
              <li>Include uppercase, lowercase, numbers and symbols</li>
              <li>Don't reuse passwords from other sites</li>
              <li>Enable two-factor authentication</li>
            </ul>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
