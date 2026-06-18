<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

// Handle status toggle / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    if (isset($_POST['toggle_status'])) {
        $pkg = DB::row("SELECT is_active FROM packages WHERE id=?", [(int)$_POST['id']]);
        if ($pkg) { DB::update('packages', ['is_active' => $pkg['is_active'] ? 0 : 1], ['id' => (int)$_POST['id']]); }
        redirect(url('admin/packages.php'));
    }
    if (isset($_POST['delete'])) {
        DB::update('packages', ['is_active' => 0], ['id' => (int)$_POST['id']]);
        flash('success', 'Package archived.');
        redirect(url('admin/packages.php'));
    }
}

$search = trim($_GET['search'] ?? '');
$type   = $_GET['type'] ?? '';
$catId  = (int)($_GET['category'] ?? 0);
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(p.title LIKE ? OR p.slug LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($type)   { $where[] = 'p.type = ?'; $params[] = $type; }
if ($catId)  { $where[] = 'p.category_id = ?'; $params[] = $catId; }

$sql = "SELECT p.*, d.name AS destination_name, c.name AS category_name
        FROM packages p
        LEFT JOIN destinations d ON p.destination_id = d.id
        LEFT JOIN categories c   ON p.category_id   = c.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY p.created_at DESC";

$result   = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$packages = $result['rows'];
$categories = getCategories();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Packages — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Packages</span></div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/package-edit.php') ?>" class="btn-admin btn-admin-primary">
        <i class="fas fa-plus"></i> Add Package
      </a>
    </div>
  </header>

  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Tour Packages</div>
    <div class="admin-page-sub"><?= $result['total'] ?> packages total</div>

    <!-- Filters -->
    <div class="admin-card">
      <div class="admin-card-body" style="padding:16px">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:center">
          <div style="position:relative;flex:1;min-width:200px">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search packages…" class="form-control" style="padding-left:36px;font-size:.82rem">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          </div>
          <select name="type" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All Types</option>
            <?php foreach (['safari','holiday','honeymoon','corporate','adventure','luxury','group','educational','religious','custom'] as $t): ?>
            <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="category" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $catId==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm"><i class="fas fa-search"></i> Filter</button>
          <?php if ($search||$type||$catId): ?><a href="<?= url('admin/packages.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm">Clear</a><?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th><input type="checkbox" id="selectAll"></th>
              <th>Package</th>
              <th>Type</th>
              <th>Destination</th>
              <th>Price</th>
              <th>Duration</th>
              <th>Bookings</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($packages as $pkg): ?>
            <tr>
              <td><input type="checkbox" class="row-select" value="<?= $pkg['id'] ?>"></td>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <img src="<?= h($pkg['hero_image']?:'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=100&q=60') ?>" class="thumb" alt="">
                  <div>
                    <p style="font-size:.82rem;font-weight:600;color:var(--clr-primary)"><?= h($pkg['title']) ?></p>
                    <p style="font-size:.68rem;color:var(--clr-muted)"><?= h($pkg['slug']) ?></p>
                    <?php if ($pkg['is_featured']): ?><span class="status-badge sb-active" style="font-size:.6rem">Featured</span><?php endif; ?>
                  </div>
                </div>
              </td>
              <td><span class="status-badge sb-active" style="text-transform:capitalize"><?= h($pkg['type']) ?></span></td>
              <td style="font-size:.78rem"><?= h($pkg['destination_name']??'—') ?></td>
              <td style="font-size:.78rem;font-weight:700">$<?= number_format($pkg['base_price'],0) ?><?= $pkg['sale_price']?'<br><span style="color:var(--clr-danger);font-size:.65rem">Sale: $'.number_format($pkg['sale_price'],0).'</span>':'' ?></td>
              <td style="font-size:.78rem"><?= $pkg['duration_days'] ?>D / <?= $pkg['duration_nights'] ?>N</td>
              <td style="font-size:.78rem;text-align:center"><?= number_format($pkg['booking_count']) ?></td>
              <td>
                <form method="POST" style="display:inline">
                  <?= csrfField() ?>
                  <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                  <button type="submit" name="toggle_status" class="status-badge <?= $pkg['is_active']?'sb-active':'sb-inactive' ?>" style="border:none;cursor:pointer">
                    <?= $pkg['is_active'] ? 'Active' : 'Inactive' ?>
                  </button>
                </form>
              </td>
              <td>
                <div class="table-actions">
                  <a href="<?= url('package-detail.php?slug='.h($pkg['slug'])) ?>" target="_blank" class="action-btn view" title="Preview"><i class="fas fa-eye"></i></a>
                  <a href="<?= url('admin/package-edit.php?id='.$pkg['id']) ?>" class="action-btn edit" title="Edit"><i class="fas fa-edit"></i></a>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Archive this package?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= $pkg['id'] ?>">
                    <button type="submit" name="delete" class="action-btn delete" title="Archive"><i class="fas fa-archive"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$packages): ?><tr><td colspan="9" style="text-align:center;padding:40px;color:var(--clr-muted)">No packages found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($result['pages'] > 1): ?>
      <div style="padding:16px 20px;border-top:1px solid var(--clr-border);display:flex;align-items:center;justify-content:space-between;font-size:.78rem;color:var(--clr-muted)">
        <span>Showing <?= count($packages) ?> of <?= $result['total'] ?></span>
        <div style="display:flex;gap:6px">
          <?php for ($i=1;$i<=$result['pages'];$i++): ?>
          <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&type=<?= urlencode($type) ?>&category=<?= $catId ?>"
             style="padding:5px 10px;border:1px solid var(--clr-border);border-radius:4px;font-size:.75rem;color:<?= $i===$page?'#fff':'var(--clr-text)' ?>;background:<?= $i===$page?'var(--clr-primary)':'' ?>"><?= $i ?></a>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
