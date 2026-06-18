<?php $adminUser = currentUser(); ?>
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="sidebar-brand-icon"><i class="fas fa-globe-africa"></i></div>
    <div>
      <div class="sidebar-brand-text">MT Safaris</div>
      <span class="sidebar-brand-sub">Admin Panel</span>
    </div>
  </div>

  <div class="sidebar-user">
    <div class="sidebar-user-avatar"><?= strtoupper(substr($adminUser['first_name'],0,1).substr($adminUser['last_name'],0,1)) ?></div>
    <div>
      <div class="sidebar-user-name"><?= h($adminUser['first_name'].' '.$adminUser['last_name']) ?></div>
      <div class="sidebar-user-role"><?= h(ROLES[$adminUser['role']]??$adminUser['role']) ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section">Dashboard</div>
    <a href="<?= url('admin/') ?>" <?= basename($_SERVER['PHP_SELF'])==='index.php'&&dirname($_SERVER['PHP_SELF'])==='/admin'?'class="active"':'' ?>>
      <i class="fas fa-tachometer-alt"></i> Overview
    </a>

    <div class="nav-section">Packages & Tours</div>
    <a href="<?= url('admin/packages.php') ?>" <?= isActive('packages.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-box-open"></i> Packages
    </a>
    <a href="<?= url('admin/destinations.php') ?>" <?= isActive('destinations.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-map-marked-alt"></i> Destinations
    </a>
    <a href="<?= url('admin/categories.php') ?>" <?= isActive('categories.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-tags"></i> Categories
    </a>

    <div class="nav-section">Bookings</div>
    <a href="<?= url('admin/bookings.php') ?>" <?= isActive('bookings.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-ticket-alt"></i> All Bookings
      <?php
      $pending = DB::value("SELECT COUNT(*) FROM bookings WHERE status='pending'");
      if ($pending > 0): ?><span class="nav-badge"><?= $pending ?></span><?php endif; ?>
    </a>
    <a href="<?= url('admin/inquiries.php') ?>" <?= isActive('inquiries.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-envelope"></i> Inquiries
      <?php $newInq = DB::value("SELECT COUNT(*) FROM inquiries WHERE status='new'"); if ($newInq): ?><span class="nav-badge"><?= $newInq ?></span><?php endif; ?>
    </a>

    <div class="nav-section">Content</div>
    <a href="<?= url('admin/blog.php') ?>" <?= isActive('blog.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-pen-nib"></i> Blog Posts
    </a>
    <a href="<?= url('admin/reviews.php') ?>" <?= isActive('reviews.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-star"></i> Reviews
    </a>
    <a href="<?= url('admin/testimonials.php') ?>" <?= isActive('testimonials.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-quote-left"></i> Testimonials
    </a>
    <a href="<?= url('admin/faqs.php') ?>" <?= isActive('faqs.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-question-circle"></i> FAQs
    </a>
    <a href="<?= url('admin/banners.php') ?>" <?= isActive('banners.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-images"></i> Banners
    </a>

    <div class="nav-section">Marketing</div>
    <a href="<?= url('admin/coupons.php') ?>" <?= isActive('coupons.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-tag"></i> Coupons
    </a>
    <a href="<?= url('admin/newsletter.php') ?>" <?= isActive('newsletter.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-mail-bulk"></i> Newsletter
    </a>

    <div class="nav-section">Users</div>
    <a href="<?= url('admin/users.php') ?>" <?= isActive('users.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-users"></i> All Users
    </a>

    <div class="nav-section">Reports</div>
    <a href="<?= url('admin/reports.php') ?>" <?= isActive('reports.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-chart-bar"></i> Reports
    </a>

    <div class="nav-section">System</div>
    <a href="<?= url('admin/settings.php') ?>" <?= isActive('settings.php') === 'active' ? 'class="active"' : '' ?>>
      <i class="fas fa-cog"></i> Settings
    </a>
    <a href="<?= url('admin/audit-log.php') ?>"><i class="fas fa-history"></i> Audit Log</a>
    <a href="<?= url() ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
    <a href="<?= url('portal/logout.php') ?>" style="color:rgba(255,80,80,.8)"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
  </nav>
</aside>
