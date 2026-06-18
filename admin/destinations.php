<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);
    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM destinations WHERE id=?", [$id]);
        if ($row) DB::update('destinations', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/destinations.php'));
    }
    if (isset($_POST['toggle_featured'])) {
        $row = DB::row("SELECT is_featured FROM destinations WHERE id=?", [$id]);
        if ($row) DB::update('destinations', ['is_featured' => $row['is_featured'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/destinations.php'));
    }
    if (isset($_POST['delete'])) {
        DB::update('destinations', ['is_active' => 0], ['id' => $id]);
        auditLog('delete', 'destinations', $id, [], []);
        flash('success', 'Destination archived.');
        redirect(url('admin/destinations.php'));
    }
}

$search   = trim($_GET['search'] ?? '');
$country  = $_GET['country'] ?? '';
$featured = $_GET['featured'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($search)  { $where[] = '(d.name LIKE ? OR d.country LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($country) { $where[] = 'd.country = ?'; $params[] = $country; }
if ($featured !== '') { $where[] = 'd.is_featured = ?'; $params[] = (int)$featured; }

$sql     = "SELECT d.*, r.name AS region_name,
                   (SELECT COUNT(*) FROM packages WHERE destination_id=d.id AND is_active=1) AS pkg_count
            FROM destinations d LEFT JOIN regions r ON d.region_id=r.id
            WHERE " . implode(' AND ', $where) . " ORDER BY d.sort_order ASC, d.name ASC";
$result  = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$dests   = $result['rows'];
$countries = DB::rows("SELECT DISTINCT country FROM destinations ORDER BY country");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Destinations — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Destinations</span></div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/destination-edit.php') ?>" class="btn-admin btn-admin-primary">
        <i class="fas fa-plus"></i> Add Destination
      </a>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>

    <div class="admin-page-title">Destinations</div>
    <div class="admin-page-sub"><?= $result['total'] ?> destinations total</div>

    <!-- Filters -->
    <div class="admin-card">
      <div class="admin-card-body" style="padding:16px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <div style="position:relative;flex:1;min-width:180px">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search destinations…" class="form-control" style="padding-left:36px;font-size:.82rem">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          </div>
          <select name="country" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All Countries</option>
            <?php foreach ($countries as $c): ?>
            <option value="<?= h($c['country']) ?>" <?= $country === $c['country'] ? 'selected' : '' ?>><?= h($c['country']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="featured" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All</option>
            <option value="1" <?= $featured==='1'?'selected':'' ?>>Featured</option>
            <option value="0" <?= $featured==='0'?'selected':'' ?>>Not Featured</option>
          </select>
          <button type="submit" class="btn-admin btn-admin-secondary"><i class="fas fa-filter"></i> Filter</button>
          <a href="<?= url('admin/destinations.php') ?>" class="btn-admin" style="background:none;border:1px solid #e2e8f0;color:#718096">Clear</a>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-card">
      <div class="admin-card-body" style="padding:0">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:60px">Image</th>
              <th>Destination</th>
              <th>Country / Region</th>
              <th>Packages</th>
              <th>Featured</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($dests): foreach ($dests as $d): ?>
            <tr>
              <td>
                <?php if ($d['hero_image']): ?>
                <img src="<?= h($d['hero_image']) ?>" alt="<?= h($d['name']) ?>"
                     style="width:48px;height:36px;object-fit:cover;border-radius:6px">
                <?php else: ?>
                <div style="width:48px;height:36px;background:#f1f5f9;border-radius:6px;display:grid;place-items:center">
                  <i class="fas fa-image" style="color:#cbd5e0"></i>
                </div>
                <?php endif; ?>
              </td>
              <td>
                <div style="font-weight:600;color:var(--clr-primary)"><?= h($d['name']) ?></div>
                <div style="font-size:.75rem;color:var(--clr-muted)"><?= h($d['continent'] ?? '') ?></div>
              </td>
              <td>
                <div><?= h($d['country']) ?></div>
                <div style="font-size:.75rem;color:var(--clr-muted)"><?= h($d['region_name'] ?? '—') ?></div>
              </td>
              <td>
                <span style="background:#e0f2fe;color:#0369a1;padding:3px 10px;border-radius:20px;font-size:.78rem;font-weight:600">
                  <?= $d['pkg_count'] ?> packages
                </span>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $d['id'] ?>">
                  <button type="submit" name="toggle_featured" title="Toggle featured"
                          style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:<?= $d['is_featured']?'var(--clr-gold)':'#cbd5e0' ?>">
                    <i class="fas fa-star"></i>
                  </button>
                </form>
              </td>
              <td>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $d['id'] ?>">
                  <button type="submit" name="toggle" class="badge <?= $d['is_active'] ? 'badge-success' : 'badge-danger' ?>"
                          style="border:none;cursor:pointer;padding:4px 10px;border-radius:20px;font-size:.75rem;font-weight:600">
                    <?= $d['is_active'] ? 'Active' : 'Inactive' ?>
                  </button>
                </form>
              </td>
              <td>
                <div style="display:flex;gap:6px">
                  <a href="<?= url('admin/destination-edit.php?id=' . $d['id']) ?>" class="btn-icon-admin" title="Edit">
                    <i class="fas fa-edit"></i>
                  </a>
                  <a href="<?= url('destinations.php?slug=' . h($d['slug'])) ?>" class="btn-icon-admin" title="View on site" target="_blank">
                    <i class="fas fa-eye"></i>
                  </a>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Archive this destination?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <button type="submit" name="delete" class="btn-icon-admin btn-icon-danger" title="Archive">
                      <i class="fas fa-archive"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--clr-muted)">No destinations found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($result['pages'] > 1): ?>
      <div class="admin-card-body" style="border-top:1px solid var(--clr-border)">
        <?= paginationHtml($result['total'], $result['pages'], $result['page'], url('admin/destinations.php?' . http_build_query(array_filter(compact('search','country','featured'))))) ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
