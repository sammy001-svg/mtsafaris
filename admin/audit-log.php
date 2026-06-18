<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();
requireRole(['super_admin']);

$page    = max(1, (int)($_GET['page'] ?? 1));
$search  = trim($_GET['s'] ?? '');
$action  = trim($_GET['action'] ?? '');
$model   = trim($_GET['model'] ?? '');
$perPage = 30;

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(a.action LIKE ? OR a.model LIKE ? OR u.email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($action) { $where[] = 'a.action=?'; $params[] = $action; }
if ($model)  { $where[] = 'a.model=?'; $params[] = $model; }

$baseQuery = "FROM audit_logs a LEFT JOIN users u ON a.user_id=u.id WHERE " . implode(' AND ', $where);
$total  = (int)DB::value("SELECT COUNT(*) $baseQuery", $params);
$pages  = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;
$logs   = DB::rows("SELECT a.*, u.first_name, u.last_name, u.email $baseQuery ORDER BY a.created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'Audit Log | MT Safaris Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="admin-body">
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-wrapper">
<header class="admin-header">
  <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
  <div class="admin-header-title">Audit Log</div>
</header>
<main class="admin-main">

<div class="admin-card" style="margin-bottom:20px">
  <div class="admin-card-body">
    <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
      <div style="flex:1;min-width:200px"><label class="admin-label">Search</label><input type="text" name="s" class="admin-input" value="<?= h($search) ?>" placeholder="Search action, model, user..."></div>
      <div><label class="admin-label">Action</label>
        <select name="action" class="admin-select">
          <option value="">All Actions</option>
          <?php foreach (['create','update','delete','update_status','toggle_status','login','logout'] as $a): ?>
          <option value="<?= $a ?>" <?= $action===$a?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$a)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label class="admin-label">Model</label>
        <select name="model" class="admin-select">
          <option value="">All Models</option>
          <?php foreach (['packages','bookings','users','blog_posts','inquiries','settings','coupons'] as $m): ?>
          <option value="<?= $m ?>" <?= $model===$m?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$m)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-admin-primary btn-sm">Filter</button>
      <a href="<?= url('admin/audit-log.php') ?>" class="btn btn-admin-outline btn-sm">Reset</a>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><h3>Activity Log <span class="badge"><?= number_format($total) ?> entries</span></h3></div>
  <div class="admin-card-body" style="padding:0">
    <table class="admin-table">
      <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Model</th><th>Record ID</th><th>IP Address</th><th>Details</th></tr></thead>
      <tbody>
        <?php if ($logs): ?>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td style="white-space:nowrap;font-size:.8rem">
            <div><?= formatDate($log['created_at'],'M j, Y') ?></div>
            <div style="color:var(--admin-muted)"><?= formatDate($log['created_at'],'g:ia') ?></div>
          </td>
          <td>
            <?php if ($log['email']): ?>
            <div style="font-size:.85rem;font-weight:500"><?= h($log['first_name'].' '.$log['last_name']) ?></div>
            <div style="font-size:.75rem;color:var(--admin-muted)"><?= h($log['email']) ?></div>
            <?php else: ?><span style="color:var(--admin-muted)">System</span><?php endif; ?>
          </td>
          <td>
            <?php
            $actionColors = ['create'=>'#059669','update'=>'#2563eb','delete'=>'#ef4444','login'=>'#7c3aed','logout'=>'#6b7280'];
            $color = $actionColors[$log['action']] ?? '#374151';
            ?>
            <span style="background:<?= $color ?>20;color:<?= $color ?>;padding:2px 8px;border-radius:4px;font-size:.75rem;font-weight:600"><?= h(strtoupper(str_replace('_',' ',$log['action']))) ?></span>
          </td>
          <td style="font-size:.85rem"><?= h(str_replace('_',' ',ucfirst($log['model']??''))) ?></td>
          <td style="font-size:.85rem;color:var(--admin-muted)"><?= $log['model_id']??'—' ?></td>
          <td style="font-size:.75rem;font-family:monospace;color:var(--admin-muted)"><?= h($log['ip_address']??'—') ?></td>
          <td>
            <?php if ($log['new_values']): ?>
            <button type="button" class="btn btn-admin-outline btn-xs" onclick="showAuditDetail(this)" data-old='<?= h($log['old_values']??'{}') ?>' data-new='<?= h($log['new_values']) ?>'>Details</button>
            <?php else: ?><span style="color:var(--admin-muted);font-size:.75rem">—</span><?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php else: ?>
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--admin-muted)"><i class="fas fa-history" style="font-size:2rem;display:block;margin-bottom:8px"></i>No audit log entries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div style="padding:16px 20px"><?= paginationHtml($total, $pages, $page, url('admin/audit-log.php?s='.urlencode($search).'&action='.$action.'&model='.$model)) ?></div>
    <?php endif; ?>
  </div>
</div>

<!-- Detail Modal -->
<div id="auditModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:12px;width:700px;max-width:90%;max-height:80vh;overflow:auto">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;border-bottom:1px solid var(--admin-border)">
      <h3 style="margin:0">Change Details</h3>
      <button onclick="document.getElementById('auditModal').style.display='none'" style="background:none;border:none;font-size:1.25rem;cursor:pointer"><i class="fas fa-times"></i></button>
    </div>
    <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
      <div><h4 style="color:#6b7280;margin-bottom:10px">Previous Values</h4><pre id="auditOld" style="background:#f8fafc;padding:14px;border-radius:8px;font-size:.75rem;overflow:auto;max-height:300px;white-space:pre-wrap"></pre></div>
      <div><h4 style="color:var(--admin-primary);margin-bottom:10px">New Values</h4><pre id="auditNew" style="background:#f0fdf4;padding:14px;border-radius:8px;font-size:.75rem;overflow:auto;max-height:300px;white-space:pre-wrap"></pre></div>
    </div>
  </div>
</div>
</main>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function showAuditDetail(btn) {
  try { document.getElementById('auditOld').textContent = JSON.stringify(JSON.parse(btn.dataset.old), null, 2); } catch(e) { document.getElementById('auditOld').textContent = btn.dataset.old; }
  try { document.getElementById('auditNew').textContent = JSON.stringify(JSON.parse(btn.dataset.new), null, 2); } catch(e) { document.getElementById('auditNew').textContent = btn.dataset.new; }
  document.getElementById('auditModal').style.display = 'flex';
}
document.getElementById('auditModal').addEventListener('click', function(e){ if(e.target===this) this.style.display='none'; });
</script>
</body>
</html>
