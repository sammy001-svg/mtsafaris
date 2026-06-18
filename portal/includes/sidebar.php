<?php
// Ensure user is available
$user = $user ?? currentUser();
$unread = (int)DB::value("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0", [$user['id']??0]);
$currentPath = basename($_SERVER['PHP_SELF']);
function portalActive($file) { global $currentPath; return $currentPath === $file ? 'active' : ''; }
?>
<aside class="portal-sidebar">
  <div class="portal-sidebar-brand">
    <a href="<?= url() ?>" style="display:flex;align-items:center;gap:8px;text-decoration:none">
      <i class="fas fa-globe-africa" style="font-size:1.5rem;color:var(--clr-gold)"></i>
      <span style="font-family:var(--ff-head);font-weight:700;font-size:1rem;color:var(--clr-primary)">MT Safaris</span>
    </a>
  </div>

  <div class="portal-user-card">
    <div class="portal-avatar"><?= strtoupper(substr($user['first_name']??'U',0,1)) ?></div>
    <div class="portal-user-info">
      <div class="portal-user-name"><?= h(($user['first_name']??'').' '.($user['last_name']??'')) ?></div>
      <div class="portal-user-email"><?= h(substr($user['email']??'',0,24)) ?><?= strlen($user['email']??'')>24?'...':'' ?></div>
    </div>
  </div>

  <nav class="portal-nav">
    <a href="<?= url('portal/') ?>" class="portal-nav-item <?= portalActive('index.php') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
    <a href="<?= url('portal/bookings.php') ?>" class="portal-nav-item <?= portalActive('bookings.php') ?>"><i class="fas fa-suitcase"></i> My Bookings</a>
    <a href="<?= url('portal/wishlist.php') ?>" class="portal-nav-item <?= portalActive('wishlist.php') ?>"><i class="fas fa-heart"></i> Wishlist</a>
    <a href="<?= url('portal/documents.php') ?>" class="portal-nav-item <?= portalActive('documents.php') ?>"><i class="fas fa-folder"></i> Documents</a>
    <a href="<?= url('portal/notifications.php') ?>" class="portal-nav-item <?= portalActive('notifications.php') ?>">
      <i class="fas fa-bell"></i> Notifications
      <?php if ($unread): ?><span class="portal-nav-badge"><?= $unread ?></span><?php endif; ?>
    </a>
    <a href="<?= url('portal/profile.php') ?>" class="portal-nav-item <?= portalActive('profile.php') ?>"><i class="fas fa-user-cog"></i> Profile</a>
    <div class="portal-nav-divider"></div>
    <a href="<?= url('packages.php') ?>" class="portal-nav-item"><i class="fas fa-compass"></i> Explore Packages</a>
    <a href="<?= url('contact.php') ?>" class="portal-nav-item"><i class="fas fa-headset"></i> Support</a>
    <?php if (isAdmin()): ?><a href="<?= url('admin/') ?>" class="portal-nav-item" style="color:var(--clr-gold)"><i class="fas fa-shield-alt"></i> Admin Panel</a><?php endif; ?>
    <div class="portal-nav-divider"></div>
    <a href="<?= url('portal/logout.php') ?>" class="portal-nav-item" style="color:#ef4444"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </nav>
</aside>
