<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isLoggedIn()) redirect(url('portal/'));

$step    = trim($_GET['step'] ?? 'request');
$token   = trim($_GET['token'] ?? '');
$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($step === 'request') {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $user = DB::row("SELECT id FROM users WHERE email=? AND status='active'", [$email]);
            if ($user) {
                // Create reset token
                $resetToken  = generateToken(32);
                $expires     = date('Y-m-d H:i:s', strtotime('+1 hour'));
                DB::query("DELETE FROM password_resets WHERE email=?", [$email]);
                DB::insert('password_resets', ['email'=>$email,'token'=>$resetToken,'expires_at'=>$expires]);

                // In production: send email with reset link
                // For now we redirect to reset form directly (dev convenience)
                $resetUrl = url('portal/forgot-password.php?step=reset&token='.$resetToken);
                // TODO: send email
                // mail($email, 'Password Reset - MT Safaris', "Reset link: $resetUrl");
            }
            // Always show success to prevent email enumeration
            $success = true;
        }
    } elseif ($step === 'reset') {
        $newPass  = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $token    = $_POST['token'] ?? '';

        $reset = DB::row("SELECT * FROM password_resets WHERE token=? AND expires_at > NOW()", [$token]);
        if (!$reset) {
            $error = 'This reset link has expired or is invalid. Please request a new one.';
        } elseif (strlen($newPass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost'=>12]);
            DB::update('users', ['password_hash'=>$hash], ['email'=>$reset['email']]);
            DB::query("DELETE FROM password_resets WHERE email=?", [$reset['email']]);
            flash('success', 'Password reset successfully. Please log in with your new password.');
            redirect(url('portal/login.php'));
        }
    }
}

// Validate reset token for GET
if ($step === 'reset' && $token) {
    $reset = DB::row("SELECT * FROM password_resets WHERE token=? AND expires_at > NOW()", [$token]);
    if (!$reset) {
        $error = 'This reset link has expired or is invalid.';
        $step = 'request';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Reset Password | MT Safaris</title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
  body{min-height:100vh;display:grid;place-items:center;background:linear-gradient(135deg,var(--clr-primary),var(--clr-sky));padding:20px}
  .auth-card{background:#fff;border-radius:var(--radius-lg);padding:48px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.15)}
  </style>
</head>
<body>
<div class="auth-card">
  <div style="text-align:center;margin-bottom:32px">
    <a href="<?= url() ?>" style="display:inline-flex;justify-content:center;text-decoration:none;margin-bottom:24px">
      <img src="<?= url('assets/images/logo.png') ?>" alt="Mountain Top Safaris Adventures" style="height:68px;width:auto">
    </a>
    <h2 style="color:var(--clr-primary);margin-bottom:8px"><?= $step==='reset'?'Set New Password':'Forgot Password?' ?></h2>
    <p style="color:var(--clr-muted);font-size:.875rem"><?= $step==='reset'?'Enter your new password below.':'Enter your email to receive a password reset link.' ?></p>
  </div>

  <?php if ($error): ?><div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:12px 16px;border-radius:var(--radius);margin-bottom:20px;font-size:.875rem"><i class="fas fa-exclamation-triangle"></i> <?= h($error) ?></div><?php endif; ?>

  <?php if ($success): ?>
  <div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:20px;border-radius:var(--radius);text-align:center">
    <i class="fas fa-envelope" style="font-size:2.5rem;color:#059669;margin-bottom:12px;display:block"></i>
    <strong>Check your inbox!</strong>
    <p style="margin:8px 0 0;font-size:.875rem">If an account exists for that email, we've sent a password reset link. Check your spam folder too.</p>
  </div>
  <a href="<?= url('portal/login.php') ?>" class="btn btn-primary btn-block" style="margin-top:24px">Back to Login</a>

  <?php elseif ($step === 'reset'): ?>
  <form method="POST" action="?step=reset&token=<?= urlencode($token) ?>">
    <?= csrfField() ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">
    <div class="form-group" style="margin-bottom:16px">
      <label class="form-label">New Password</label>
      <div style="position:relative">
        <input type="password" name="password" id="newPass" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
        <button type="button" onclick="togglePwd('newPass',this)" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--clr-muted)"><i class="fas fa-eye"></i></button>
      </div>
    </div>
    <div class="form-group" style="margin-bottom:24px">
      <label class="form-label">Confirm New Password</label>
      <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg">Reset Password</button>
  </form>

  <?php else: ?>
  <form method="POST" action="?step=request">
    <?= csrfField() ?>
    <div class="form-group" style="margin-bottom:24px">
      <label class="form-label">Email Address</label>
      <input type="email" name="email" class="form-control" required autofocus placeholder="you@example.com" value="<?= h($_POST['email']??'') ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-block btn-lg"><i class="fas fa-paper-plane"></i> Send Reset Link</button>
  </form>
  <?php endif; ?>

  <p style="text-align:center;margin-top:24px;font-size:.875rem;color:var(--clr-muted)">Remember your password? <a href="<?= url('portal/login.php') ?>" style="color:var(--clr-primary);font-weight:600">Sign In</a></p>
</div>
<script>
function togglePwd(id,btn){const el=document.getElementById(id);el.type=el.type==='password'?'text':'password';btn.innerHTML=el.type==='password'?'<i class="fas fa-eye"></i>':'<i class="fas fa-eye-slash"></i>';}
</script>
</body>
</html>
