<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$user = currentUser();

// Remove from wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove'])) {
    verifyCsrf();
    DB::delete('wishlists', ['user_id'=>$user['id'],'package_id'=>(int)$_POST['package_id']]);
    flash('success', 'Removed from wishlist.');
    redirect(url('portal/wishlist.php'));
}

$wishlist = DB::rows("SELECT p.*, w.created_at AS saved_at FROM wishlists w JOIN packages p ON w.package_id=p.id WHERE w.user_id=? AND p.is_active=1 ORDER BY w.created_at DESC", [$user['id']]);
$pageTitle = 'My Wishlist | MT Safaris';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="portal-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="portal-main">
    <?php echo renderFlash(); ?>
    <div class="portal-header">
      <h1>My Wishlist</h1>
      <p><?= count($wishlist) ?> saved package<?= count($wishlist)!=1?'s':'' ?></p>
    </div>

    <?php if ($wishlist): ?>
    <div class="grid-3" style="gap:24px">
      <?php foreach ($wishlist as $pkg): ?>
      <article class="package-card">
        <div class="package-card-img">
          <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>">
            <img src="<?= h($pkg['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=600&q=80') ?>" alt="<?= h($pkg['title']) ?>" loading="lazy">
          </a>
          <span class="package-badge"><?= ucfirst(h($pkg['type'])) ?></span>
          <form method="POST" style="position:absolute;top:10px;right:10px" onsubmit="return confirm('Remove from wishlist?')">
            <?= csrfField() ?>
            <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
            <button type="submit" name="remove" class="wishlist-btn active" title="Remove from wishlist"><i class="fas fa-heart"></i></button>
          </form>
        </div>
        <div class="package-card-body">
          <div class="package-meta">
            <span><i class="fas fa-clock"></i> <?= $pkg['duration_days'] ?> Days</span>
            <span><i class="fas fa-users"></i> <?= $pkg['min_pax'] ?>+ Pax</span>
          </div>
          <h3 class="package-title"><a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>"><?= h($pkg['title']) ?></a></h3>
          <p style="font-size:.8rem;color:var(--clr-muted);margin-bottom:12px">Saved <?= timeAgo($pkg['saved_at']) ?></p>
          <div class="package-footer">
            <div class="package-price">
              <?php if ($pkg['sale_price']): ?><span class="original"><?= money($pkg['base_price']) ?></span><span class="amount"><?= money($pkg['sale_price']) ?></span>
              <?php else: ?><span class="from">From</span><span class="amount"><?= money($pkg['base_price']) ?></span><?php endif; ?>
            </div>
            <a href="<?= url('booking.php?package='.h($pkg['slug'])) ?>" class="btn btn-gold btn-sm">Book Now</a>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="portal-empty">
      <i class="fas fa-heart" style="font-size:3rem;color:var(--clr-gold);margin-bottom:16px"></i>
      <h3>Your wishlist is empty</h3>
      <p>Save packages you love by clicking the heart icon on any package card.</p>
      <a href="<?= url('packages.php') ?>" class="btn btn-primary" style="margin-top:16px"><i class="fas fa-search"></i> Explore Packages</a>
    </div>
    <?php endif; ?>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
