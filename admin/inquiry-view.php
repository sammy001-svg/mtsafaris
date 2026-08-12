<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id  = (int)($_GET['id'] ?? 0);
if (!$id) redirect(url('admin/inquiries.php'));

$inq = DB::row("SELECT * FROM inquiries WHERE id=?", [$id]);
if (!$inq) redirect(url('admin/inquiries.php'));

// Auto-mark as read
if ($inq['status'] === 'new') {
    DB::update('inquiries', ['status' => 'read'], ['id' => $id]);
    $inq['status'] = 'read';
}

// Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    verifyCsrf();
    $s = in_array($_POST['status'], ['new','read','replied','closed']) ? $_POST['status'] : 'read';
    DB::update('inquiries', ['status'=>$s,'notes'=>$_POST['admin_notes']??''], ['id'=>$id]);
    flash('success', 'Inquiry updated.');
    redirect(url('admin/inquiry-view.php?id='.$id));
}

$pageTitle = 'Inquiry from '.h($inq['name']).' | Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $pageTitle ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/includes/sidebar.php'; ?>
<div class="admin-main">
<header class="admin-header">
  <div class="admin-header-left">
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
    <div class="breadcrumb-admin">
      <a href="<?= url('admin/') ?>">Admin</a>
      <i class="fas fa-chevron-right"></i>
      <a href="<?= url('admin/inquiries.php') ?>">Inquiries</a>
      <i class="fas fa-chevron-right"></i>
      <span><?= h($inq['name']) ?></span>
    </div>
  </div>
  <div class="admin-header-right">
    <a href="<?= url('admin/inquiries.php') ?>" class="btn-admin btn-admin-outline btn-admin-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</header>
<div class="admin-content">

<div class="page-header">
  <div class="page-header-info">
    <h1 class="page-title">Inquiry from <?= h($inq['name']) ?></h1>
    <p class="page-subtitle">Received <?= formatDate($inq['created_at'], 'F j, Y \a\t g:ia') ?></p>
  </div>
</div>

<?php echo renderFlash(); ?>

<div style="display:grid;grid-template-columns:1fr 280px;gap:24px;align-items:start">
  <div>
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3><?= h($inq['subject']??'Inquiry') ?></h3><span class="sb-<?= $inq['status']==='replied'?'confirmed':'draft' ?>"><?= ucfirst($inq['status']) ?></span></div>
      <div class="admin-card-body">
        <div style="background:#f8fafc;border-radius:8px;padding:20px;white-space:pre-wrap;line-height:1.8;color:var(--admin-text)"><?= h($inq['message']) ?></div>
      </div>
    </div>

    <!-- Reply via email -->
    <div class="admin-card">
      <div class="admin-card-header"><h3><i class="fas fa-reply"></i> Reply</h3></div>
      <div class="admin-card-body">
        <p style="color:var(--admin-muted);font-size:.875rem;margin-bottom:16px">Replies are sent via your email client. Click below to open a pre-filled reply.</p>
        <a href="mailto:<?= h($inq['email']) ?>?subject=Re: <?= urlencode($inq['subject']??'Your Inquiry - MT Safaris') ?>&body=<?= urlencode("Dear ".$inq['name'].",\n\nThank you for reaching out to MT Safaris.\n\n\n\nBest regards,\nMT Safaris Team\n".CONTACT_EMAIL) ?>" class="btn btn-admin-primary"><i class="fas fa-envelope"></i> Reply via Email</a>
        <?php if ($inq['phone']): ?><a href="tel:<?= h($inq['phone']) ?>" class="btn btn-admin-outline" style="margin-left:8px"><i class="fas fa-phone"></i> Call <?= h($inq['phone']) ?></a><?php endif; ?>
        <?php if ($inq['phone']): ?><a href="https://wa.me/<?= preg_replace('/\D/','',$inq['phone']) ?>" target="_blank" class="btn" style="background:#25D366;color:#fff;margin-left:8px"><i class="fab fa-whatsapp"></i> WhatsApp</a><?php endif; ?>
      </div>
    </div>
  </div>

  <div>
    <div class="admin-card" style="margin-bottom:20px">
      <div class="admin-card-header"><h3>Contact Details</h3></div>
      <div class="admin-card-body">
        <?php foreach ([
          ['fas fa-user','Name',$inq['name']],
          ['fas fa-envelope','Email',$inq['email']],
          ['fas fa-phone','Phone',$inq['phone']??'—'],
          ['fas fa-tag','Type',ucfirst($inq['type']??'general')],
          ['fas fa-calendar','Received',formatDate($inq['created_at'],'M j, Y g:ia')],
        ] as $row): ?>
        <div style="display:flex;gap:10px;margin-bottom:14px">
          <i class="<?= $row[0] ?>" style="color:var(--admin-primary);width:16px;margin-top:2px;flex-shrink:0"></i>
          <div>
            <div style="font-size:.7rem;color:var(--admin-muted)"><?= $row[1] ?></div>
            <div style="font-size:.85rem;font-weight:500;color:var(--admin-text)"><?= h($row[2]) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-header"><h3>Update Status</h3></div>
      <div class="admin-card-body">
        <form method="POST">
          <?= csrfField() ?>
          <div class="form-group">
            <select name="status" class="form-control">
              <?php foreach (['read','replied','closed'] as $s): ?>
              <option value="<?= $s ?>" <?= $inq['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Internal Notes</label>
            <textarea name="admin_notes" class="form-control" rows="4" placeholder="Notes for your team..."><?= h($inq['notes']??'') ?></textarea>
          </div>
          <button type="submit" name="update_status" class="btn btn-admin-primary btn-block"><i class="fas fa-save"></i> Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
</div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
