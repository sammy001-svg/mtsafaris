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

$editId    = (int)($_GET['edit'] ?? 0);
$editing   = $editId ? DB::row("SELECT * FROM faqs WHERE id=?", [$editId]) : null;
$filterCat = $_GET['category'] ?? '';

$where  = ['1=1'];
$params = [];
if ($filterCat) { $where[] = 'category=?'; $params[] = $filterCat; }

$faqs      = DB::rows("SELECT * FROM faqs WHERE " . implode(' AND ', $where) . " ORDER BY sort_order ASC, id ASC", $params);
$catRows   = DB::rows("SELECT DISTINCT category FROM faqs ORDER BY category");
$faqLabels = ['general'=>'General','booking'=>'Booking','payment'=>'Payment','safety'=>'Safety','visa'=>'Visa & Travel','corporate'=>'Corporate'];
$totalFaqs = DB::value("SELECT COUNT(*) FROM faqs");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
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
      <div class="breadcrumb-admin">
        <a href="<?= url('admin/') ?>">Admin</a>
        <i class="fas fa-chevron-right"></i>
        <span>FAQs</span>
      </div>
    </div>
    <div class="admin-header-right">
      <a href="<?= url('admin/faqs.php') ?>" class="btn-admin btn-admin-primary btn-admin-sm">
        <i class="fas fa-plus"></i> New FAQ
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
        <div class="page-title">Frequently Asked Questions</div>
        <div class="page-subtitle">Manage the FAQ content shown to visitors on the public site</div>
      </div>
      <div class="page-header-actions">
        <span style="font-size:.82rem;color:var(--clr-muted);background:var(--clr-light);border:1px solid var(--clr-border);padding:6px 14px;border-radius:var(--radius-full)"><?= $totalFaqs ?> total</span>
      </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 380px;gap:24px;align-items:start">

      <!-- FAQ list -->
      <div>
        <!-- Category filter -->
        <div class="admin-tabs" style="margin-bottom:16px">
          <a href="<?= url('admin/faqs.php') ?>"
             class="admin-tab <?= !$filterCat ? 'active' : '' ?>">
            <i class="fas fa-list"></i> All
            <span class="tab-count"><?= $totalFaqs ?></span>
          </a>
          <?php foreach ($catRows as $c):
            $slug  = $c['category'];
            $label = $faqLabels[$slug] ?? ucfirst($slug);
            $cnt   = DB::value("SELECT COUNT(*) FROM faqs WHERE category=?", [$slug]);
          ?>
          <a href="?category=<?= h($slug) ?>"
             class="admin-tab <?= $filterCat === $slug ? 'active' : '' ?>">
            <?= h($label) ?>
            <span class="tab-count"><?= $cnt ?></span>
          </a>
          <?php endforeach; ?>
        </div>

        <div class="admin-card">
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead>
                <tr>
                  <th style="width:48px">#</th>
                  <th>Question / Answer</th>
                  <th style="width:120px">Category</th>
                  <th style="width:100px">Status</th>
                  <th style="width:80px"></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($faqs): foreach ($faqs as $faq): ?>
                <tr>
                  <td>
                    <span style="display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;background:var(--clr-light);border-radius:6px;font-size:.78rem;font-weight:700;color:var(--clr-muted)"><?= $faq['sort_order'] ?: '—' ?></span>
                  </td>
                  <td>
                    <div class="td-primary" style="white-space:normal"><?= h(excerpt($faq['question'], 90)) ?></div>
                    <div class="td-secondary" style="white-space:normal;margin-top:3px"><?= h(excerpt($faq['answer'], 110)) ?></div>
                  </td>
                  <td>
                    <span class="status-badge" style="background:#e0f2fe;color:#0369a1">
                      <?= h($faqLabels[$faq['category']] ?? ucfirst($faq['category'])) ?>
                    </span>
                  </td>
                  <td>
                    <form method="POST" style="display:inline">
                      <?= csrfField() ?>
                      <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                      <button type="submit" name="toggle"
                              class="status-badge <?= $faq['is_active'] ? 'sb-active' : 'sb-inactive' ?>"
                              style="border:none;cursor:pointer;font-family:inherit">
                        <?= $faq['is_active'] ? 'Active' : 'Hidden' ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <div class="tbl-actions">
                      <a href="<?= url('admin/faqs.php?edit=' . $faq['id']) ?>" class="btn-tbl" title="Edit">
                        <i class="fas fa-pencil"></i>
                      </a>
                      <form method="POST" style="display:contents" onsubmit="return confirm('Delete this FAQ?')">
                        <?= csrfField() ?>
                        <input type="hidden" name="id" value="<?= $faq['id'] ?>">
                        <button type="submit" name="delete" class="btn-tbl btn-tbl-danger" title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
                <?php endforeach; else: ?>
                <tr>
                  <td colspan="5" style="text-align:center;padding:48px;color:var(--clr-muted)">
                    <i class="fas fa-question-circle" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.25"></i>
                    No FAQs<?= $filterCat ? ' in this category' : '' ?>. <?= !$filterCat ? 'Create your first one →' : '' ?>
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="admin-card">
        <div class="admin-card-header">
          <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
            <i class="fas fa-<?= $editing ? 'pencil' : 'plus-circle' ?>" style="color:var(--clr-gold)"></i>
            <?= $editing ? 'Edit FAQ' : 'New FAQ' ?>
          </span>
          <?php if ($editing): ?>
          <a href="<?= url('admin/faqs.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
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
              <label class="form-label">Category</label>
              <select name="category" class="form-control">
                <?php foreach ($faqLabels as $k => $v): ?>
                <option value="<?= $k ?>" <?= ($editing['category'] ?? 'general') === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Question <span style="color:var(--clr-danger)">*</span></label>
              <textarea name="question" class="form-control" rows="3" required
                        placeholder="e.g. What documents do I need for a Kenya safari?"><?= h($editing['question'] ?? '') ?></textarea>
            </div>

            <div class="form-group">
              <label class="form-label">Answer <span style="color:var(--clr-danger)">*</span></label>
              <textarea name="answer" class="form-control" rows="5" required
                        placeholder="Write a clear, helpful answer…"><?= h($editing['answer'] ?? '') ?></textarea>
            </div>

            <div class="form-row" style="align-items:end">
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?= (int)($editing['sort_order'] ?? 0) ?>"
                       class="form-control" min="0">
                <span class="form-hint">Lower = appears first</span>
              </div>
              <div class="form-group">
                <label class="admin-toggle">
                  <input type="checkbox" name="is_active" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>>
                  <span class="admin-toggle-slider"></span>
                  <span class="admin-toggle-label">Visible on site</span>
                </label>
              </div>
            </div>

            <button type="submit" name="save" class="btn-admin btn-admin-primary btn-block" style="margin-top:8px">
              <i class="fas fa-save"></i> <?= $editing ? 'Update FAQ' : 'Create FAQ' ?>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="<?= url('assets/js/admin.js') ?>"></script>
</body>
</html>
