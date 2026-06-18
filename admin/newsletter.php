<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $id = (int)($_POST['id'] ?? 0);

    if (isset($_POST['unsubscribe'])) {
        DB::update('newsletter_subscribers', ['is_active' => 0], ['id' => $id]);
        flash('success', 'Subscriber deactivated.');
        redirect(url('admin/newsletter.php'));
    }
    if (isset($_POST['reactivate'])) {
        DB::update('newsletter_subscribers', ['is_active' => 1], ['id' => $id]);
        flash('success', 'Subscriber reactivated.');
        redirect(url('admin/newsletter.php'));
    }
    if (isset($_POST['delete_sub'])) {
        DB::delete('newsletter_subscribers', ['id' => $id]);
        flash('success', 'Subscriber deleted.');
        redirect(url('admin/newsletter.php'));
    }

    // Campaign actions
    $cid = (int)($_POST['cid'] ?? 0);
    if (isset($_POST['save_campaign'])) {
        $data = [
            'name'         => trim($_POST['campaign_name'] ?? ''),
            'subject'      => trim($_POST['campaign_subject'] ?? ''),
            'body'         => trim($_POST['campaign_body'] ?? ''),
            'status'       => 'draft',
            'created_by'   => currentUser()['id'],
        ];
        if ($data['name'] && $data['subject'] && $data['body']) {
            if ($cid) { DB::update('email_campaigns', $data, ['id' => $cid]); flash('success', 'Campaign updated.'); }
            else { DB::insert('email_campaigns', $data); flash('success', 'Campaign saved as draft.'); }
        }
        redirect(url('admin/newsletter.php'));
    }
    if (isset($_POST['delete_campaign'])) {
        DB::delete('email_campaigns', ['id' => $cid]);
        flash('success', 'Campaign deleted.');
        redirect(url('admin/newsletter.php'));
    }
    // Simulated "send" (marks as sent, sets count to active subscribers)
    if (isset($_POST['send_campaign'])) {
        $count = DB::value("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active=1");
        DB::update('email_campaigns', ['status' => 'sent', 'sent_at' => date('Y-m-d H:i:s'), 'sent_to' => $count], ['id' => $cid]);
        flash('success', "Campaign marked as sent to $count subscribers.");
        redirect(url('admin/newsletter.php'));
    }
}

$tab    = $_GET['tab'] ?? 'subscribers';
$search = trim($_GET['search'] ?? '');
$active = $_GET['active'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));

// Subscribers
$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(email LIKE ? OR name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($active !== '') { $where[] = 'is_active=?'; $params[] = (int)$active; }
$sql    = "SELECT * FROM newsletter_subscribers WHERE " . implode(' AND ', $where) . " ORDER BY subscribed_at DESC";
$result = DB::paginate($sql, $params, $page, ADMIN_PER_PAGE);
$subs   = $result['rows'];

$totalSubs  = DB::value("SELECT COUNT(*) FROM newsletter_subscribers WHERE is_active=1");
$totalAll   = DB::value("SELECT COUNT(*) FROM newsletter_subscribers");
$campaigns  = DB::rows("SELECT ec.*, u.first_name, u.last_name FROM email_campaigns ec LEFT JOIN users u ON ec.created_by=u.id ORDER BY ec.created_at DESC");

$editCampaign = null;
if (isset($_GET['edit_campaign'])) {
    $editCampaign = DB::row("SELECT * FROM email_campaigns WHERE id=?", [(int)$_GET['edit_campaign']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Newsletter — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>Newsletter</span></div>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Newsletter</div>

    <!-- Stats -->
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
      <div class="admin-card"><div class="admin-card-body" style="text-align:center">
        <div style="font-size:2rem;font-weight:800;color:var(--clr-primary)"><?= number_format($totalSubs) ?></div>
        <div style="font-size:.8rem;color:var(--clr-muted)">Active Subscribers</div>
      </div></div>
      <div class="admin-card"><div class="admin-card-body" style="text-align:center">
        <div style="font-size:2rem;font-weight:800;color:var(--clr-gold)"><?= count($campaigns) ?></div>
        <div style="font-size:.8rem;color:var(--clr-muted)">Email Campaigns</div>
      </div></div>
      <div class="admin-card"><div class="admin-card-body" style="text-align:center">
        <div style="font-size:2rem;font-weight:800;color:var(--clr-success)"><?= DB::value("SELECT COUNT(*) FROM email_campaigns WHERE status='sent'") ?></div>
        <div style="font-size:.8rem;color:var(--clr-muted)">Campaigns Sent</div>
      </div></div>
    </div>

    <!-- Tab nav -->
    <div style="display:flex;gap:0;border-bottom:2px solid var(--clr-border);margin-bottom:20px">
      <?php foreach (['subscribers'=>'Subscribers','campaigns'=>'Campaigns'] as $t=>$label): ?>
      <a href="?tab=<?= $t ?>" style="padding:10px 22px;font-weight:600;font-size:.875rem;text-decoration:none;color:<?= $tab===$t?'var(--clr-primary)':'var(--clr-muted)' ?>;border-bottom:2px solid <?= $tab===$t?'var(--clr-gold)':'transparent' ?>;margin-bottom:-2px;transition:all .2s">
        <?= $label ?>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if ($tab === 'subscribers'): ?>
    <!-- Subscribers panel -->
    <div class="admin-card" style="margin-bottom:16px">
      <div class="admin-card-body" style="padding:12px">
        <form method="GET" style="display:flex;gap:10px;align-items:center">
          <input type="hidden" name="tab" value="subscribers">
          <div style="position:relative;flex:1">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search email or name…" class="form-control" style="padding-left:36px">
            <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          </div>
          <select name="active" class="form-control" style="width:auto">
            <option value="">All (<?= $totalAll ?>)</option>
            <option value="1" <?= $active==='1'?'selected':'' ?>>Active (<?= $totalSubs ?>)</option>
            <option value="0" <?= $active==='0'?'selected':'' ?>>Inactive (<?= $totalAll-$totalSubs ?>)</option>
          </select>
          <button type="submit" class="btn-admin btn-admin-secondary"><i class="fas fa-filter"></i></button>
        </form>
      </div>
    </div>

    <div class="admin-card">
      <div class="admin-card-body" style="padding:0">
        <table class="admin-table">
          <thead><tr><th>Email</th><th>Name</th><th>Subscribed</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php if ($subs): foreach ($subs as $s): ?>
            <tr>
              <td><a href="mailto:<?= h($s['email']) ?>" style="color:var(--clr-primary)"><?= h($s['email']) ?></a></td>
              <td><?= h($s['name'] ?? '—') ?></td>
              <td style="font-size:.78rem;color:var(--clr-muted)"><?= formatDate($s['subscribed_at'],'M j, Y') ?></td>
              <td><span class="badge <?= $s['is_active']?'badge-success':'badge-danger' ?>" style="padding:3px 10px;border-radius:20px;font-size:.72rem"><?= $s['is_active']?'Active':'Inactive' ?></span></td>
              <td>
                <div style="display:flex;gap:6px">
                  <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <?php if ($s['is_active']): ?>
                    <button type="submit" name="unsubscribe" class="btn-icon-admin" title="Deactivate"><i class="fas fa-user-slash"></i></button>
                    <?php else: ?>
                    <button type="submit" name="reactivate" class="btn-icon-admin" title="Reactivate"><i class="fas fa-user-check"></i></button>
                    <?php endif; ?>
                  </form>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button type="submit" name="delete_sub" class="btn-icon-admin btn-icon-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--clr-muted)">No subscribers found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <?php if ($result['pages'] > 1): ?>
      <div class="admin-card-body" style="border-top:1px solid var(--clr-border)">
        <?= paginationHtml($result['total'], $result['pages'], $result['page'], url('admin/newsletter.php?tab=subscribers&'.http_build_query(array_filter(compact('search','active'))))) ?>
      </div>
      <?php endif; ?>
    </div>

    <?php else: ?>
    <!-- Campaigns panel -->
    <div style="display:grid;grid-template-columns:1fr 400px;gap:24px;align-items:start">
      <!-- Campaign list -->
      <div class="admin-card">
        <div class="admin-card-body" style="padding:0">
          <table class="admin-table">
            <thead><tr><th>Campaign</th><th>Status</th><th>Sent</th><th>Date</th><th></th></tr></thead>
            <tbody>
              <?php if ($campaigns): foreach ($campaigns as $c): ?>
              <tr>
                <td>
                  <div style="font-weight:600;color:var(--clr-primary)"><?= h($c['name']) ?></div>
                  <div style="font-size:.75rem;color:var(--clr-muted)"><?= h(excerpt($c['subject'], 60)) ?></div>
                </td>
                <td>
                  <?php $statusColor = ['draft'=>'#718096','scheduled'=>'#3182ce','sending'=>'#d69e2e','sent'=>'#38a169']; ?>
                  <span class="badge" style="background:<?= $statusColor[$c['status']]??'#718096' ?>20;color:<?= $statusColor[$c['status']]??'#718096' ?>;padding:3px 10px;border-radius:20px;font-size:.72rem;font-weight:600"><?= ucfirst($c['status']) ?></span>
                </td>
                <td style="font-size:.8rem"><?= $c['sent_to'] ? number_format($c['sent_to']) : '—' ?></td>
                <td style="font-size:.78rem;color:var(--clr-muted)"><?= $c['sent_at'] ? formatDate($c['sent_at'],'M j') : formatDate($c['created_at'],'M j') ?></td>
                <td>
                  <div style="display:flex;gap:6px">
                    <?php if ($c['status'] === 'draft'): ?>
                    <a href="?tab=campaigns&edit_campaign=<?= $c['id'] ?>" class="btn-icon-admin"><i class="fas fa-edit"></i></a>
                    <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="cid" value="<?= $c['id'] ?>">
                      <button type="submit" name="send_campaign" class="btn-icon-admin" title="Mark as sent" onclick="return confirm('Mark this campaign as sent to all active subscribers?')"><i class="fas fa-paper-plane" style="color:var(--clr-success)"></i></button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="cid" value="<?= $c['id'] ?>">
                      <button type="submit" name="delete_campaign" class="btn-icon-admin btn-icon-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </div>
                </td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--clr-muted)">No campaigns yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Campaign form -->
      <div class="admin-card">
        <div class="admin-card-header"><i class="fas fa-mail-bulk" style="color:var(--clr-gold)"></i> <?= $editCampaign?'Edit':'New' ?> Campaign</div>
        <div class="admin-card-body">
          <form method="POST">
            <?= csrfField() ?>
            <?php if ($editCampaign): ?><input type="hidden" name="cid" value="<?= $editCampaign['id'] ?>"><?php endif; ?>
            <input type="hidden" name="tab" value="campaigns">
            <div class="form-group">
              <label class="form-label">Campaign Name</label>
              <input type="text" name="campaign_name" value="<?= h($editCampaign['name']??'') ?>" class="form-control" required placeholder="e.g. March Deals Newsletter">
            </div>
            <div class="form-group">
              <label class="form-label">Email Subject</label>
              <input type="text" name="campaign_subject" value="<?= h($editCampaign['subject']??'') ?>" class="form-control" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email Body <small style="color:var(--clr-muted)">(HTML allowed)</small></label>
              <textarea name="campaign_body" class="form-control" rows="10" required><?= h($editCampaign['body']??'') ?></textarea>
            </div>
            <div style="background:#fff8e1;border-radius:8px;padding:12px;margin-bottom:14px;font-size:.8rem;color:#92400e">
              <i class="fas fa-info-circle"></i> Will send to <strong><?= number_format($totalSubs) ?></strong> active subscribers.
            </div>
            <button type="submit" name="save_campaign" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> Save as Draft
            </button>
            <?php if ($editCampaign): ?><a href="?tab=campaigns" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center;margin-top:8px"><i class="fas fa-plus"></i> New Campaign</a><?php endif; ?>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
