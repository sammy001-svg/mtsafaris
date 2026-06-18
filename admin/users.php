<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin(); requireRole('super_admin');

$search = trim($_GET['search'] ?? '');
$role   = $_GET['role'] ?? '';
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($role)   { $where[] = 'role = ?'; $params[] = $role; }
if ($status) { $where[] = 'status = ?'; $params[] = $status; }

$sql    = "SELECT u.*, (SELECT COUNT(*) FROM bookings b WHERE b.user_id=u.id) AS booking_count
           FROM users u WHERE " . implode(' AND ', $where) . " ORDER BY u.created_at DESC";
$result = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$users  = $result['rows'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Users</span></div>
    </div>
  </header>
  <div class="admin-content">
    <div class="admin-page-title">User Management</div>
    <div class="admin-page-sub"><?= $result['total'] ?> registered users</div>

    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-body" style="padding:14px 16px">
        <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
          <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search name or email…" class="form-control" style="flex:1;font-size:.82rem;min-width:180px">
          <select name="role" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All Roles</option>
            <?php foreach (ROLES as $k=>$v): ?><option value="<?= $k ?>" <?= $role===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
          </select>
          <select name="status" class="form-control" style="width:auto;font-size:.82rem">
            <option value="">All Status</option>
            <?php foreach (['active','inactive','suspended','pending'] as $s): ?><option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn-admin btn-admin-primary btn-admin-sm">Search</button>
          <?php if ($search||$role||$status): ?><a href="<?= url('admin/users.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm">Clear</a><?php endif; ?>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr><th>User</th><th>Email</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
              <td>
                <div style="display:flex;align-items:center;gap:10px">
                  <div style="width:34px;height:34px;border-radius:50%;background:var(--clr-primary);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.78rem;font-weight:700;flex-shrink:0">
                    <?= strtoupper(substr($u['first_name'],0,1).substr($u['last_name'],0,1)) ?>
                  </div>
                  <div>
                    <p style="font-size:.82rem;font-weight:600"><?= h($u['first_name'].' '.$u['last_name']) ?></p>
                    <p style="font-size:.68rem;color:var(--clr-muted)">ID #<?= $u['id'] ?></p>
                  </div>
                </div>
              </td>
              <td style="font-size:.78rem"><?= h($u['email']) ?></td>
              <td style="font-size:.78rem"><?= h($u['phone'] ?? '—') ?></td>
              <td><span class="status-badge sb-active" style="text-transform:capitalize;font-size:.65rem"><?= h(ROLES[$u['role']]??$u['role']) ?></span></td>
              <td style="text-align:center;font-size:.82rem"><?= $u['booking_count'] ?></td>
              <td><span class="status-badge <?= $u['status']==='active'?'sb-active':'sb-inactive' ?>"><?= ucfirst($u['status']) ?></span></td>
              <td style="font-size:.72rem;color:var(--clr-muted)"><?= formatDate($u['created_at'],'M j, Y') ?></td>
              <td>
                <div class="table-actions">
                  <a href="<?= url('admin/user-view.php?id='.$u['id']) ?>" class="action-btn view"><i class="fas fa-eye"></i></a>
                  <a href="<?= url('admin/user-edit.php?id='.$u['id']) ?>" class="action-btn edit"><i class="fas fa-edit"></i></a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?><tr><td colspan="8" style="text-align:center;padding:40px;color:var(--clr-muted)">No users found.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
