<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$errors  = [];
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['save'])) {
        $data = [
            'code'         => strtoupper(trim($_POST['code'] ?? '')),
            'type'         => in_array($_POST['type'],['percentage','fixed']) ? $_POST['type'] : 'percentage',
            'value'        => (float)($_POST['value'] ?? 0),
            'min_order'    => (float)($_POST['min_order'] ?? 0),
            'max_discount' => trim($_POST['max_discount'] ?? '') !== '' ? (float)$_POST['max_discount'] : null,
            'usage_limit'  => trim($_POST['usage_limit'] ?? '') !== '' ? (int)$_POST['usage_limit'] : null,
            'valid_from'   => $_POST['valid_from'] ?? null,
            'valid_to'     => $_POST['valid_to'] ?? null,
            'is_active'    => isset($_POST['is_active']) ? 1 : 0,
        ];
        if (!$data['code'])  $errors[] = 'Coupon code is required.';
        if ($data['value'] <= 0) $errors[] = 'Discount value must be > 0.';
        if ($data['type'] === 'percentage' && $data['value'] > 100) $errors[] = 'Percentage must be ≤ 100.';

        if (!$errors) {
            if ($id) {
                DB::update('coupons', $data, ['id' => $id]);
                auditLog('update', 'coupons', $id, [], $data);
                flash('success', 'Coupon updated.');
            } else {
                if (DB::value("SELECT COUNT(*) FROM coupons WHERE code=?", [$data['code']])) {
                    $errors[] = 'Coupon code already exists.';
                } else {
                    $newId = DB::insert('coupons', $data);
                    auditLog('create', 'coupons', $newId, [], $data);
                    flash('success', 'Coupon created.');
                }
            }
            if (!$errors) redirect(url('admin/coupons.php'));
        }
    }

    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM coupons WHERE id=?", [$id]);
        if ($row) DB::update('coupons', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/coupons.php'));
    }
    if (isset($_POST['delete'])) {
        DB::delete('coupons', ['id' => $id]);
        auditLog('delete', 'coupons', $id, [], []);
        flash('success', 'Coupon deleted.');
        redirect(url('admin/coupons.php'));
    }
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = $editId ? DB::row("SELECT * FROM coupons WHERE id=?", [$editId]) : null;
$search  = trim($_GET['search'] ?? '');
$active  = $_GET['active'] ?? '';
$page    = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = 'code LIKE ?'; $params[] = "%$search%"; }
if ($active !== '') { $where[] = 'is_active=?'; $params[] = (int)$active; }

$sql     = "SELECT * FROM coupons WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$result  = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$coupons = $result['rows'];

$totalAll    = DB::value("SELECT COUNT(*) FROM coupons");
$totalActive = DB::value("SELECT COUNT(*) FROM coupons WHERE is_active=1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Coupons — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/') ?>">Admin</a>
        <i class="fas fa-chevron-right"></i>
        <span>Coupons</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/coupons.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New Coupon
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger"><i class="fas fa-exclamation-circle"></i><span><?= implode(' ', array_map('h', $errors)) ?></span></div>
    <?php endif; ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title">Discount Coupons</div>
        <div class="page-subtitle">Create and manage promotional codes for customer bookings</div>
      </div>
      <div class="page-header-actions">
        <span style="font-size:.82rem;color:var(--clr-muted);background:var(--clr-light);border:1px solid var(--clr-border);padding:6px 14px;border-radius:var(--radius-full)">
          <?= $totalActive ?> active &nbsp;/&nbsp; <?= $totalAll ?> total
        </span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

      <!-- List + filter -->
      <div>
        <!-- Filter bar -->
        <div class="admin-card" style="margin-bottom:16px">
          <div class="admin-card-body" style="padding:12px 16px">
            <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              <div class="input-group" style="flex:1;min-width:160px">
                <i class="ig-icon fas fa-tag"></i>
                <input type="text" name="search" value="<?= h($search) ?>"
                       placeholder="Search coupon code…" class="form-control">
              </div>
              <select name="active" class="form-control" style="width:130px">
                <option value="">All status</option>
                <option value="1" <?= $active === '1' ? 'selected' : '' ?>>Active only</option>
                <option value="0" <?= $active === '0' ? 'selected' : '' ?>>Inactive only</option>
              </select>
              <button type="submit" class="btn-admin btn-admin-secondary">
                <i class="fas fa-filter"></i> Filter
              </button>
              <?php if ($search || $active !== ''): ?>
              <a href="<?= url('admin/coupons.php') ?>" class="btn-admin btn-admin-secondary">
                <i class="fas fa-times"></i> Clear
              </a>
              <?php endif; ?>
            </form>
          </div>
        </div>

        <!-- Table -->
        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Code</th>
                  <th style="width:100px">Discount</th>
                  <th style="width:120px">Usage</th>
                  <th style="width:150px">Validity</th>
                  <th style="width:100px">Status</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($coupons): foreach ($coupons as $c):
                  $expired  = $c['valid_to']   && $c['valid_to']   < date('Y-m-d');
                  $notYet   = $c['valid_from'] && $c['valid_from'] > date('Y-m-d');
                  $depleted = $c['usage_limit'] && $c['used_count'] >= $c['usage_limit'];
                  $effective = $c['is_active'] && !$expired && !$depleted;
                ?>
                <tr>
                  <td>
                    <div class="td-mono"><?= h($c['code']) ?></div>
                    <?php if ($c['min_order']): ?>
                    <div class="td-secondary">Min order: <?= money($c['min_order']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size:1.05rem;font-weight:800;color:var(--clr-gold)">
                      <?= $c['type'] === 'percentage' ? $c['value'].'%' : money($c['value']) ?>
                    </div>
                    <?php if ($c['max_discount']): ?>
                    <div class="td-secondary">Max: <?= money($c['max_discount']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size:.82rem;font-weight:600"><?= $c['used_count'] ?> / <?= $c['usage_limit'] ?? '∞' ?></div>
                    <?php if ($c['usage_limit']): ?>
                    <div style="width:80px;height:4px;background:#e2e8f0;border-radius:2px;margin-top:5px">
                      <div style="width:<?= min(100, round($c['used_count'] / $c['usage_limit'] * 100)) ?>%;height:100%;background:<?= $depleted ? 'var(--clr-danger)' : 'var(--clr-success)' ?>;border-radius:2px;transition:width .3s"></div>
                    </div>
                    <?php endif; ?>
                  </td>
                  <td style="font-size:.78rem">
                    <div><?= $c['valid_from'] ? formatDate($c['valid_from'], 'M j') : 'Any start' ?></div>
                    <div style="color:var(--clr-muted)"><?= $c['valid_to'] ? '→ '.formatDate($c['valid_to'], 'M j, Y') : 'No end' ?></div>
                    <?php if ($expired): ?>
                    <div style="color:var(--clr-danger);font-weight:600;font-size:.72rem">Expired</div>
                    <?php elseif ($notYet): ?>
                    <div style="color:var(--clr-warning);font-weight:600;font-size:.72rem">Scheduled</div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <form method="POST" style="display:inline">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $c['id'] ?>">
                      <button type="submit" name="toggle"
                              class="status-badge <?= $effective ? 'sb-active' : 'sb-inactive' ?>"
                              style="border:none;cursor:pointer;font-family:inherit">
                        <?= $effective ? 'Active' : 'Inactive' ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <div class="tbl-actions">
                      <a href="<?= url('admin/coupons.php?edit=' . $c['id']) ?>" class="btn-tbl" title="Edit coupon">
                        <i class="fas fa-pencil"></i>
                      </a>
                      <form method="POST" style="display:contents" onsubmit="return confirm('Delete coupon «<?= h($c['code']) ?>»?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" name="delete" class="btn-tbl btn-tbl-danger" title="Delete coupon">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:48px;color:var(--clr-muted)">
                    <i class="fas fa-tag" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.25"></i>
                    <?= $search ? 'No coupons match your search.' : 'No coupons yet. Create your first one →' ?>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if ($result['pages'] > 1): ?>
          <div style="padding:14px 20px;border-top:1px solid var(--clr-border)">
            <?= paginationHtml($result['total'], $result['pages'], $result['page'], url('admin/coupons.php?' . http_build_query(array_filter(compact('search', 'active'))))) ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card" style="position:sticky;top:80px">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-<?= $editing ? 'pencil' : 'plus-circle' ?>" style="color:var(--clr-gold)"></i>
            <?= $editing ? 'Edit Coupon' : 'New Coupon' ?>
          </span>
          <?php if ($editing): ?>
          <a href="<?= url('admin/coupons.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
            <i class="fas fa-plus"></i> New
          </a>
          <?php endif; ?>
        </div>
        <div class="admin-card-body">
          <form method="POST">
            <?= csrfField() ?>
            <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?= $editing['id'] ?>">
            <?php endif; ?>

            <div class="form-group">
              <label class="form-label">Coupon Code <span style="color:var(--clr-danger)">*</span></label>
              <div style="display:flex;gap:8px">
                <input type="text" name="code" id="couponCode"
                       value="<?= h($editing['code'] ?? '') ?>"
                       class="form-control"
                       style="text-transform:uppercase;font-family:monospace;font-weight:700;letter-spacing:.1em"
                       required>
                <button type="button" id="genCode" class="btn-admin btn-admin-secondary" title="Generate random code">
                  <i class="fas fa-random"></i>
                </button>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" id="couponType" class="form-control">
                  <option value="percentage" <?= ($editing['type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                  <option value="fixed" <?= ($editing['type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed Amount</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label">Value <span style="color:var(--clr-danger)">*</span></label>
                <div class="input-group">
                  <span id="valuePrefix" class="ig-icon" style="font-weight:700;font-size:.82rem;font-family:monospace">
                    <?= ($editing['type'] ?? 'percentage') === 'percentage' ? '%' : '$' ?>
                  </span>
                  <input type="number" name="value" value="<?= h($editing['value'] ?? '') ?>"
                         class="form-control" min="0.01" step="0.01" required>
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Min Order</label>
                <input type="number" name="min_order" value="<?= h($editing['min_order'] ?? '0') ?>"
                       class="form-control" min="0" step="0.01">
              </div>
              <div class="form-group">
                <label class="form-label">Max Discount</label>
                <input type="number" name="max_discount" value="<?= h($editing['max_discount'] ?? '') ?>"
                       class="form-control" min="0" step="0.01" placeholder="Unlimited">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Usage Limit</label>
              <input type="number" name="usage_limit" value="<?= h($editing['usage_limit'] ?? '') ?>"
                     class="form-control" min="1" placeholder="Unlimited">
              <?php if ($editing): ?>
              <span class="form-hint">Used <?= $editing['used_count'] ?> times so far</span>
              <?php endif; ?>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Valid From</label>
                <input type="date" name="valid_from" value="<?= h($editing['valid_from'] ?? '') ?>"
                       class="form-control">
              </div>
              <div class="form-group">
                <label class="form-label">Valid To</label>
                <input type="date" name="valid_to" value="<?= h($editing['valid_to'] ?? '') ?>"
                       class="form-control">
              </div>
            </div>

            <div class="form-group">
              <label class="admin-toggle">
                <input type="checkbox" name="is_active" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Active &amp; usable</span>
              </label>
            </div>

            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block" style="margin-top:8px">
              <i class="fas fa-save"></i> <?= $editing ? 'Update Coupon' : 'Create Coupon' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
// Toggle % / $ prefix
document.getElementById('couponType')?.addEventListener('change', function() {
  document.getElementById('valuePrefix').textContent = this.value === 'percentage' ? '%' : '$';
});

// Generate random code
document.getElementById('genCode')?.addEventListener('click', function() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = '';
  for (let i = 0; i < 8; i++) code += chars[Math.floor(Math.random() * chars.length)];
  document.getElementById('couponCode').value = code;
});
</script>
</body>
</html>
