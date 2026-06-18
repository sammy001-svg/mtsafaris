<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin(); requireRole('super_admin');

$id   = (int)($_GET['id'] ?? 0);
$user = $id ? DB::row("SELECT * FROM users WHERE id=?", [$id]) : null;
if (!$user) { flash('danger', 'User not found.'); redirect(url('admin/users.php')); }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $data = [
        'first_name'     => trim($_POST['first_name'] ?? ''),
        'last_name'      => trim($_POST['last_name']  ?? ''),
        'email'          => trim($_POST['email']      ?? ''),
        'phone'          => trim($_POST['phone']      ?? ''),
        'role'           => in_array($_POST['role'], array_keys(ROLES)) ? $_POST['role'] : 'customer',
        'status'         => in_array($_POST['status'], ['active','inactive','suspended','pending']) ? $_POST['status'] : 'active',
        'email_verified' => isset($_POST['email_verified']) ? 1 : 0,
    ];

    if (!$data['first_name']) $errors[] = 'First name is required.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';

    // Check email unique (excluding self)
    $existing = DB::value("SELECT COUNT(*) FROM users WHERE email=? AND id!=?", [$data['email'], $id]);
    if ($existing) $errors[] = 'Email already in use by another user.';

    // Password reset
    $newPass = trim($_POST['new_password'] ?? '');
    if ($newPass) {
        if (strlen($newPass) < 8) $errors[] = 'Password must be at least 8 characters.';
        else $data['password_hash'] = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    // Avatar upload
    if (!empty($_FILES['avatar']['name'])) {
        $url = uploadImage($_FILES['avatar'], 'avatars');
        if ($url) $data['avatar'] = $url;
    }

    if (!$errors) {
        $old = $user;
        DB::update('users', $data, ['id' => $id]);
        auditLog('update', 'users', $id, [
            'first_name' => $old['first_name'],
            'last_name'  => $old['last_name'],
            'email'      => $old['email'],
            'role'       => $old['role'],
            'status'     => $old['status'],
        ], $data);
        flash('success', 'User updated successfully.');
        redirect(url('admin/user-view.php?id=' . $id));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit <?= h($user['first_name'].' '.$user['last_name']) ?> — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/users.php') ?>">Users</a>
        <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <a href="<?= url('admin/user-view.php?id='.$id) ?>"><?= h($user['first_name'].' '.$user['last_name']) ?></a>
        <i class="fas fa-chevron-right" style="font-size:.65rem"></i>
        <span>Edit</span>
      </div>
    </div>
  </header>
  <div class="admin-content">
    <?php if ($errors): ?><div class="flash-msg flash-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i><span><?= implode(' | ', array_map('h',$errors)) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Edit User</div>

    <form method="POST" enctype="multipart/form-data">
      <?= csrfField() ?>
      <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">

        <!-- Left -->
        <div style="display:flex;flex-direction:column;gap:20px">
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-user" style="color:var(--clr-gold)"></i> Personal Information</div>
            <div class="admin-card-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div class="form-group">
                  <label class="form-label">First Name <span class="text-danger">*</span></label>
                  <input type="text" name="first_name" value="<?= h($user['first_name']) ?>" class="form-control" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Last Name <span class="text-danger">*</span></label>
                  <input type="text" name="last_name" value="<?= h($user['last_name']) ?>" class="form-control" required>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email" name="email" value="<?= h($user['email']) ?>" class="form-control" required>
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" value="<?= h($user['phone'] ?? '') ?>" class="form-control" placeholder="+254 700 000 000">
              </div>
              <div class="form-group">
                <label class="form-label">Avatar Photo</label>
                <?php if ($user['avatar']): ?>
                <div style="margin-bottom:8px"><img src="<?= h($user['avatar']) ?>" style="width:60px;height:60px;border-radius:50%;object-fit:cover"></div>
                <?php endif; ?>
                <input type="file" name="avatar" accept="image/*" class="form-control">
              </div>
            </div>
          </div>

          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-lock" style="color:var(--clr-gold)"></i> Change Password</div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">New Password <small style="color:var(--clr-muted)">(leave blank to keep current)</small></label>
                <input type="password" name="new_password" class="form-control" minlength="8" placeholder="Min 8 characters">
              </div>
              <div style="background:#fff8e1;border-radius:8px;padding:10px 14px;font-size:.8rem;color:#92400e">
                <i class="fas fa-exclamation-triangle"></i> Changing the password will log the user out of all active sessions.
              </div>
            </div>
          </div>
        </div>

        <!-- Right -->
        <div style="display:flex;flex-direction:column;gap:20px">
          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-cog" style="color:var(--clr-gold)"></i> Account Settings</div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Role</label>
                <select name="role" class="form-control">
                  <?php foreach (ROLES as $k=>$v): ?>
                  <option value="<?= $k ?>" <?= $user['role']===$k?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Account Status</label>
                <select name="status" class="form-control">
                  <?php foreach (['active'=>'Active','inactive'=>'Inactive','suspended'=>'Suspended','pending'=>'Pending Verification'] as $k=>$v): ?>
                  <option value="<?= $k ?>" <?= $user['status']===$k?'selected':'' ?>><?= $v ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <label class="admin-toggle">
                <input type="checkbox" name="email_verified" <?= $user['email_verified']?'checked':'' ?>>
                <span class="admin-toggle-slider"></span>
                <span style="font-size:.85rem">Email Verified</span>
              </label>
            </div>
          </div>

          <div class="admin-card">
            <div class="admin-card-header"><i class="fas fa-info-circle" style="color:var(--clr-muted)"></i> Account Info</div>
            <div class="admin-card-body" style="font-size:.8rem">
              <?php foreach ([
                ['ID',"#{$user['id']}"],
                ['UUID', substr($user['uuid'],0,18).'…'],
                ['Joined', formatDate($user['created_at'],'M j, Y')],
                ['Last login', $user['last_login_at']?timeAgo($user['last_login_at']):'Never'],
                ['Last IP', $user['last_login_ip']??'—'],
              ] as [$l,$v]): ?>
              <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--clr-border)">
                <span style="color:var(--clr-muted)"><?= $l ?></span>
                <span style="font-weight:500;color:var(--clr-primary)"><?= h($v) ?></span>
              </div>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="admin-card">
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:10px">
              <button type="submit" class="btn-admin btn-admin-primary btn-block">
                <i class="fas fa-save"></i> Save Changes
              </button>
              <a href="<?= url('admin/user-view.php?id='.$id) ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
