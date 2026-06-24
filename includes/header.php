<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$user        = currentUser();
$categories  = getCategories();
$headerClass = $headerClass ?? 'transparent';
$headerData  = $headerClass === 'transparent' ? 'data-transparent' : '';

// Cache-busting version stamps (file modification time)
$cssV = filemtime(APP_PATH . '/assets/css/style.css');
$jsV  = filemtime(APP_PATH . '/assets/js/main.js');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?= seoMeta($pageTitle ?? '', $pageDescription ?? '', $pageImage ?? '', $ogType ?? 'website') ?>
  <meta name="csrf-token" content="<?= csrfToken() ?>">
  <meta name="theme-color" content="#0C2614">
  <link rel="icon" href="<?= url('assets/images/favicon.ico') ?>" type="image/x-icon">
  <!-- Resource hints -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
  <link rel="dns-prefetch" href="https://images.unsplash.com">
  <!-- Critical CSS preload -->
  <link rel="preload" href="<?= url('assets/css/style.css') ?>?v=<?= $cssV ?>" as="style">
  <!-- Font Awesome — async load to avoid render-blocking -->
  <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" as="style" crossorigin="anonymous"
        onload="this.onload=null;this.rel='stylesheet'">
  <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous"></noscript>
  <!-- Main Styles -->
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $cssV ?>">
  <?php if (isset($extraCss)): foreach ($extraCss as $css): ?>
  <link rel="stylesheet" href="<?= h($css) ?>">
  <?php endforeach; endif; ?>
  <!-- JSON-LD: Organization + Website (all pages) -->
  <?= schemaOrganization() ?>
  <?php if (isset($jsonLd)) echo $jsonLd; ?>
</head>
<body>

<!-- Page Loader -->
<div class="page-loader" id="pageLoader">
  <div class="loader-ring"></div>
  <span class="loader-text">Loading experience…</span>
</div>

<!-- Header -->
<header id="header" class="<?= h($headerClass) ?>" <?= $headerData ?>>
  <div class="container">
    <div class="header-inner">

      <!-- Logo -->
      <a href="<?= url() ?>" class="logo">
        <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
        <div class="logo-text">
          MT Safaris
          <span class="logo-sub">Premium Travel</span>
        </div>
      </a>

      <!-- Main Nav -->
      <nav class="main-nav">
        <a href="<?= url() ?>" class="nav-link <?= isActive('index.php') ?>">Home</a>

        <div class="dropdown">
          <a href="<?= url('packages.php') ?>" class="nav-link <?= isActive('packages.php') ?>">
            Packages <i class="fas fa-chevron-down" style="font-size:.65rem;margin-left:3px"></i>
          </a>
          <div class="dropdown-menu">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= url('packages.php?category=' . h($cat['slug'])) ?>">
              <i class="fas <?= h($cat['icon']) ?>"></i> <?= h($cat['name']) ?>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="dropdown">
          <a href="<?= url('destinations.php') ?>" class="nav-link <?= isActive('destinations.php') ?>">
            Destinations <i class="fas fa-chevron-down" style="font-size:.65rem;margin-left:3px"></i>
          </a>
          <div class="dropdown-menu">
            <a href="<?= url('destinations.php?region=africa') ?>"><i class="fas fa-map-marker-alt"></i> Africa</a>
            <a href="<?= url('destinations.php?region=middle-east') ?>"><i class="fas fa-map-marker-alt"></i> Middle East</a>
            <a href="<?= url('destinations.php?region=europe') ?>"><i class="fas fa-map-marker-alt"></i> Europe</a>
            <a href="<?= url('destinations.php?region=asia') ?>"><i class="fas fa-map-marker-alt"></i> Asia</a>
            <a href="<?= url('destinations.php?region=indian-ocean') ?>"><i class="fas fa-map-marker-alt"></i> Indian Ocean</a>
          </div>
        </div>

        <a href="<?= url('corporate.php') ?>" class="nav-link <?= isActive('corporate.php') ?>">Corporate</a>
        <a href="<?= url('blog.php') ?>" class="nav-link <?= isActive('blog.php') ?>">Blog</a>
        <a href="<?= url('about.php') ?>" class="nav-link <?= isActive('about.php') ?>">About</a>
        <a href="<?= url('contact.php') ?>" class="nav-link <?= isActive('contact.php') ?>">Contact</a>
      </nav>

      <!-- Header Actions -->
      <div class="header-actions">
        <!-- Live Search Toggle -->
        <button class="btn-icon nav-search-btn" id="navSearchBtn" aria-label="Search" style="background:transparent;border:none;color:inherit;cursor:pointer;font-size:1.1rem;padding:8px"><i class="fas fa-search"></i></button>

        <?php if ($user): ?>
        <div class="dropdown">
          <a href="#" class="nav-link" style="display:flex;align-items:center;gap:8px">
            <span style="background:var(--clr-gold);color:#fff;width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700">
              <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
            </span>
            <?= h($user['first_name']) ?>
            <i class="fas fa-chevron-down" style="font-size:.65rem"></i>
          </a>
          <div class="dropdown-menu" style="right:0;left:auto;transform:translateX(0)">
            <a href="<?= url('portal/') ?>"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="<?= url('portal/bookings.php') ?>"><i class="fas fa-ticket-alt"></i> My Bookings</a>
            <a href="<?= url('portal/wishlist.php') ?>"><i class="fas fa-heart"></i> Wishlist</a>
            <a href="<?= url('portal/profile.php') ?>"><i class="fas fa-user"></i> Profile</a>
            <?php if (isAdmin()): ?>
            <a href="<?= url('admin/') ?>" style="color:var(--clr-gold)"><i class="fas fa-cog"></i> Admin Panel</a>
            <?php endif; ?>
            <a href="<?= url('portal/logout.php') ?>" style="color:var(--clr-danger)"><i class="fas fa-sign-out-alt"></i> Logout</a>
          </div>
        </div>
        <?php else: ?>
        <a href="<?= url('portal/login.php') ?>" class="nav-link">Login</a>
        <a href="<?= url('contact.php#quote') ?>" class="btn btn-gold btn-sm">Get a Quote</a>
        <?php endif; ?>
        <button class="mobile-toggle" aria-label="Open menu"><i class="fas fa-bars"></i></button>
      </div>

    </div>
  </div>
</header>

<!-- Search Overlay -->
<div id="searchOverlay" style="display:none;position:fixed;inset:0;background:rgba(13,59,102,.96);z-index:2000;align-items:flex-start;justify-content:center;padding-top:120px">
  <div style="width:100%;max-width:700px;padding:0 24px">
    <form action="<?= url('search.php') ?>" method="GET" id="overlaySearchForm" style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:20px;top:50%;transform:translateY(-50%);color:rgba(255,255,255,.5);font-size:1.1rem;pointer-events:none"></i>
      <input type="text" name="q" id="overlaySearchInput" placeholder="Search packages, destinations, articles…"
             autocomplete="off"
             style="width:100%;background:rgba(255,255,255,.1);border:2px solid rgba(255,255,255,.2);border-radius:var(--radius-full);padding:18px 56px;font-size:1.125rem;color:#fff;outline:none;transition:border-color .2s"
             onfocus="this.style.borderColor='var(--clr-gold)'" onblur="this.style.borderColor='rgba(255,255,255,.2)'">
      <button type="button" id="closeSearch" style="position:absolute;right:18px;top:50%;transform:translateY(-50%);background:none;border:none;color:rgba(255,255,255,.6);font-size:1.25rem;cursor:pointer"><i class="fas fa-times"></i></button>
    </form>
    <!-- Autocomplete results -->
    <div id="searchDropdown" style="background:#fff;border-radius:var(--radius-md);margin-top:8px;overflow:hidden;box-shadow:var(--shadow-xl);display:none;max-height:420px;overflow-y:auto"></div>
    <!-- Quick links -->
    <div id="searchQuickLinks" style="margin-top:24px;display:flex;gap:10px;flex-wrap:wrap">
      <span style="color:rgba(255,255,255,.5);font-size:.8rem;align-self:center">Try:</span>
      <?php foreach (['Masai Mara Safari','Zanzibar Beach','Kilimanjaro','Serengeti'] as $s): ?>
      <a href="<?= url('search.php?q='.urlencode($s)) ?>" style="background:rgba(255,255,255,.1);color:rgba(255,255,255,.8);padding:6px 14px;border-radius:var(--radius-full);font-size:.8rem;border:1px solid rgba(255,255,255,.15)"><?= $s ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Mobile Nav Overlay -->
<nav class="mobile-nav" id="mobileNav">
  <button class="close-btn"><i class="fas fa-times"></i></button>
  <div style="margin-bottom:24px">
    <div class="logo">
      <div class="logo-icon"><i class="fas fa-globe-africa"></i></div>
      <div class="logo-text">MT Safaris <span class="logo-sub">Premium Travel</span></div>
    </div>
  </div>
  <a href="<?= url() ?>">Home</a>
  <a href="<?= url('packages.php') ?>">Tour Packages</a>
  <a href="<?= url('destinations.php') ?>">Destinations</a>
  <a href="<?= url('corporate.php') ?>">Corporate Travel</a>
  <a href="<?= url('blog.php') ?>">Blog</a>
  <a href="<?= url('about.php') ?>">About Us</a>
  <a href="<?= url('contact.php') ?>">Contact</a>
  <div style="margin-top:24px;display:flex;gap:10px;flex-direction:column">
    <?php if ($user): ?>
    <a href="<?= url('portal/') ?>" class="btn btn-outline-white">My Dashboard</a>
    <?php else: ?>
    <a href="<?= url('portal/login.php') ?>" class="btn btn-outline-white">Login</a>
    <a href="<?= url('portal/register.php') ?>" class="btn btn-gold">Sign Up</a>
    <?php endif; ?>
  </div>
  <div style="margin-top:32px;display:flex;gap:14px">
    <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>" style="color:rgba(255,255,255,.7)"><i class="fab fa-whatsapp fa-lg"></i></a>
    <a href="tel:<?= CONTACT_PHONE ?>" style="color:rgba(255,255,255,.7)"><i class="fas fa-phone fa-lg"></i></a>
  </div>
</nav>

<!-- Flash Message -->
<?php $flash = getFlash(); if ($flash): ?>
<div style="position:fixed;top:calc(var(--header-h) + 10px);left:50%;transform:translateX(-50%);z-index:800;min-width:320px">
  <?php
  $icons = ['success'=>'check-circle','error'=>'exclamation-circle','warning'=>'exclamation-triangle','info'=>'info-circle'];
  $icon  = $icons[$flash['type']] ?? 'info-circle';
  ?>
  <div class="flash-msg flash-<?= h($flash['type']) ?>" style="box-shadow:0 8px 32px rgba(0,0,0,.12)">
    <i class="fas fa-<?= $icon ?>"></i>
    <span><?= h($flash['message']) ?></span>
    <button onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>
  </div>
</div>
<?php endif; ?>

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close"><i class="fas fa-times"></i></button>
  <img src="" alt="Gallery image">
</div>

<main>
