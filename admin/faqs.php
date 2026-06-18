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
            'category'   => trim($_POST['category'] ?? 'general'),
            'question'   => trim($_POST['question'] ?? ''),
            'answer'     => trim($_POST['answer'] ?? ''),
            'is_active'  => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
        ];
        if (!$data['question'] || !$data['answer']) $errors[] = 'Question and answer are required.';

        if (!$errors) {
            if ($id) { DB::update('faqs', $data, ['id' => $id]); flash('success', 'FAQ updated.'); }
            else { DB::insert('faqs', $data); flash('success', 'FAQ created.'); }
            redirect(url('admin/faqs.php'));
        }
    }
    if (isset($_POST['toggle'])) {
        $row = DB::row("SELECT is_active FROM faqs WHERE id=?", [$id]);
        if ($row) DB::update('faqs', ['is_active' => $row['is_active'] ? 0 : 1], ['id' => $id]);
        redirect(url('admin/faqs.php'));
    }
    if (isset($_POST['delete'])) {
        DB::delete('faqs', ['id' => $id]);
        flash('success', 'FAQ deleted.');
        redirect(url('admin/faqs.php'));
    }
}

$editId  = (int)($_GET['edit'] ?? 0);
$editing = $editId ? DB::row("SELECT * FROM faqs WHERE id=?", [$editId]) : null;
$filterCat = $_GET['category'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterCat) { $where[] = 'category=?'; $params[] = $filterCat; }

$faqs = DB::rows("SELECT * FROM faqs WHERE " . implode(' AND ', $where) . " ORDER BY sort_order ASC, id ASC", $params);
$categories = DB::rows("SELECT DISTINCT category FROM faqs ORDER BY category");
$faqCategories = ['general'=>'General','booking'=>'Booking','payment'=>'Payment','safety'=>'Safety','visa'=>'Visa & Travel','corporate'=>'Corporate'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FAQs — Admin | MT Safaris</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>
<?php require_once 'includes/sidebar.php'; ?>
<div class="admin-main">
  <header class="admin-header">
    <div class="admin-header-left">
      <button class="sidebar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
      <div class="breadcrumb-admin">Admin <i class="fas fa-chevron-right" style="font-size:.65rem"></i> <span>FAQs</span></div>
    </div>
  </header>
  <div class="admin-content">
    <?php $flash = getFlash(); if ($flash): ?><div class="flash-msg flash-<?= h($flash['type']) ?>" style="margin-bottom:16px"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div><?php endif; ?>
    <?php if ($errors): ?><div class="flash-msg flash-danger" style="margin-bottom:16px"><i class="fas fa-exclamation-circle"></i><span><?= implode(' ', array_map('h',$errors)) ?></span></div><?php endif; ?>

    <div class="admin-page-title">Frequently Asked Questions</div>

    <div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start">
      <!-- List -->
      <div>
        <!-- Category filter tabs -->
        <div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap">
          <a href="<?= url('admin/faqs.php') ?>" style="padding:6px 16px;border-radius:20px;text-decoration:none;font-size:.82rem;font-weight:600;background:<?= !$filterCat?'var(--clr-primary)':'#f1f5f9' ?>;color:<?= !$filterCat?'#fff':'var(--clr-muted)' ?>">All (<?= count($faqs) ?>)</a>
          <?php foreach ($categories as $c): $count = DB::value("SELECT COUNT(*) FROM faqs WHERE category=?", [$c['category']]); ?>
          <a href="?category=<?= h($c['category']) ?>" style="padding:6px 16px;border-radius:20px;text-decoration:none;font-size:.82rem;font-weight:600;background:<?= $filterCat===$c['category']?'var(--clr-primary)':'#f1f5f9' ?>;color:<?= $filterCat===$c['category']?'#fff':'var(--clr-muted)' ?>">
            <?= h($faqCategories[$c['category']] ?? ucfirst($c['category'])) ?> (<?= $count ?>)
          </a>
          <?php endforeach; ?>
        </div>

        <div class="admin-card">
          <div class="admin-card-body" style="padding:0">
            <table class="admin-table">
              <thead><tr><th>#</th><th>Question</th><th>Category</th><th>Status</th><th></th></tr></thead>
              <tbody>
                <?php if ($faqs): foreach ($faqs as $faq): ?>
                <tr>
                  <td style="color:var(--clr-muted);font-size:.8rem"><?= $faq['sort_order'] ?></td>
                  <td>
                    <div style="font-weight:500;margin-bottom:4px"><?= h(excerpt($faq['question'], 90)) ?></div>
                    <div style="font-size:.78rem;color:var(--clr-muted)"><?= h(excerpt($faq['answer'], 100)) ?></div>
                  </td>
                  <td><span style="background:#e0f2fe;color:#0369a1;padding:3px 8px;border-radius:20px;font-size:.72rem"><?= h($faqCategories[$faq['category']] ?? $faq['category']) ?></span></td>
                  <td>
                    <form method="POST" style="display:inline"><?= csrfField() ?><input type="hidden" name="id" value="<?= $faq['id'] ?>">
                      <button type="submit" name="toggle" class="badge <?= $faq['is_active']?'badge-success':'badge-danger' ?>" style="border:none;cursor:pointer;padding:4px 10px;border-radius:20px;font-size:.72rem"><?= $faq['is_active']?'Active':'Hidden' ?></button>
                    </form>
                  </td>
                  <td>
                    <div style="display:flex;gap:6px">
                      <a href="<?= url('admin/faqs.php?edit='.$faq['id']) ?>" class="btn-icon-admin"><i class="fas fa-edit"></i></a>
                      <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><?= csrfField() ?><input type="hidden" name="id" value="<?= $faq['id'] ?>">
                        <button type="submit" name="delete" class="btn-icon-admin btn-icon-danger"><i class="fas fa-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--clr-muted)">No FAQs found.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-header"><i class="fas fa-question-circle" style="color:var(--clr-gold)"></i> <?= $editing?'Edit FAQ':'Add FAQ' ?></div>
        <div class="admin-card-body">
          <form method="POST">
            <?= csrfField() ?>
            <?php if ($editing): ?><input type="hidden" name="id" value="<?= $editing['id'] ?>"><?php endif; ?>
            <div class="form-group">
              <label class="form-label">Category</label>
              <select name="category" class="form-control">
                <?php foreach ($faqCategories as $k=>$v): ?>
                <option value="<?= $k ?>" <?= ($editing['category']??'general')===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Question <span class="text-danger">*</span></label>
              <textarea name="question" class="form-control" rows="3" required><?= h($editing['question']??'') ?></textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Answer <span class="text-danger">*</span></label>
              <textarea name="answer" class="form-control" rows="5" required><?= h($editing['answer']??'') ?></textarea>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" value="<?= (int)($editing['sort_order']??0) ?>" class="form-control" min="0">
              </div>
              <div class="form-group" style="padding-top:28px">
                <label class="admin-toggle"><input type="checkbox" name="is_active" <?= ($editing['is_active']??1)?'checked':'' ?>><span class="admin-toggle-slider"></span><span>Active</span></label>
              </div>
            </div>
            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block">
              <i class="fas fa-save"></i> <?= $editing?'Update':'Create' ?> FAQ
            </button>
            <?php if ($editing): ?><a href="<?= url('admin/faqs.php') ?>" class="btn-admin btn-block" style="background:#f1f5f9;color:var(--clr-primary);text-align:center;margin-top:8px"><i class="fas fa-plus"></i> Add New</a><?php endif; ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
