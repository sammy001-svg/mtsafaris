<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireLogin();
$user = currentUser();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_doc'])) {
    verifyCsrf();
    $docType = $_POST['doc_type'] ?? '';
    $allowed = ['passport','visa','ticket','voucher','insurance','other'];
    if (!in_array($docType, $allowed)) $errors[] = 'Invalid document type.';

    if (!$errors && !empty($_FILES['document']['tmp_name'])) {
        $file = $_FILES['document'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['pdf','jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowedExt)) $errors[] = 'Only PDF and image files allowed.';
        elseif ($file['size'] > 10 * 1024 * 1024) $errors[] = 'File size must be under 10MB.';
        else {
            $filename = $user['id'].'_'.$docType.'_'.time().'.'.$ext;
            $dest = UPLOAD_PATH . 'documents/' . $filename;
            if (!is_dir(dirname($dest))) mkdir(dirname($dest), 0755, true);
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                DB::insert('user_documents', [
                    'user_id'  => $user['id'],
                    'type'     => $docType,
                    'filename' => $filename,
                    'path'     => 'documents/' . $filename,
                    'label'    => $_POST['doc_label'] ?? $docType,
                ]);
                flash('success', 'Document uploaded successfully.');
                redirect(url('portal/documents.php'));
            } else {
                $errors[] = 'Upload failed. Please try again.';
            }
        }
    } elseif (!$errors) {
        $errors[] = 'Please select a file to upload.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_doc'])) {
    verifyCsrf();
    $doc = DB::row("SELECT * FROM user_documents WHERE id=? AND user_id=?", [(int)$_POST['doc_id'], $user['id']]);
    if ($doc) {
        $fullPath = UPLOAD_PATH . $doc['path'];
        if (file_exists($fullPath)) @unlink($fullPath);
        DB::delete('user_documents', ['id'=>$doc['id']]);
        flash('success', 'Document deleted.');
        redirect(url('portal/documents.php'));
    }
}

$documents = DB::rows("SELECT * FROM user_documents WHERE user_id=? ORDER BY created_at DESC", [$user['id']]);

$docTypeLabels = ['passport'=>'Passport','visa'=>'Visa','ticket'=>'Flight Ticket','voucher'=>'Hotel Voucher','insurance'=>'Travel Insurance','other'=>'Other'];
$pageTitle = 'My Documents | MT Safaris';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body>
<div class="portal-layout">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="portal-main">
    <?php echo renderFlash(); ?>
    <div class="portal-header">
      <h1>Travel Documents</h1>
      <p>Securely store your passports, visas, tickets, and other travel documents.</p>
    </div>

    <?php if ($errors): ?>
    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:14px 18px;border-radius:var(--radius);margin-bottom:20px">
      <?php foreach ($errors as $e): ?><div><i class="fas fa-exclamation-triangle"></i> <?= h($e) ?></div><?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 320px;gap:28px;align-items:start">
      <!-- Documents List -->
      <div>
        <?php if ($documents): ?>
        <div style="display:flex;flex-direction:column;gap:16px">
          <?php foreach ($documents as $doc):
            $ext = strtolower(pathinfo($doc['path'], PATHINFO_EXTENSION));
            $isPdf = $ext === 'pdf';
          ?>
          <div class="card" style="display:flex;gap:16px;align-items:center;padding:0">
            <div class="card-body" style="display:flex;gap:16px;align-items:center;flex:1">
              <div style="width:52px;height:52px;background:<?= $isPdf?'#fee2e2':'#dbeafe' ?>;border-radius:var(--radius);display:grid;place-items:center;flex-shrink:0">
                <i class="fas <?= $isPdf?'fa-file-pdf':'fa-file-image' ?>" style="font-size:1.5rem;color:<?= $isPdf?'#ef4444':'#2563eb' ?>"></i>
              </div>
              <div style="flex:1">
                <div style="font-weight:600;color:var(--clr-primary)"><?= h($doc['label']?:$doc['type']) ?></div>
                <div style="font-size:.8rem;color:var(--clr-muted)"><?= $docTypeLabels[$doc['type']]??ucfirst($doc['type']) ?> &bull; <?= strtoupper($ext) ?> &bull; Uploaded <?= timeAgo($doc['created_at']) ?></div>
              </div>
              <div style="display:flex;gap:8px">
                <a href="<?= url('assets/uploads/'.$doc['path']) ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
                <a href="<?= url('assets/uploads/'.$doc['path']) ?>" download class="btn btn-outline btn-sm"><i class="fas fa-download"></i></a>
                <form method="POST" onsubmit="return confirm('Delete this document?')">
                  <?= csrfField() ?>
                  <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                  <button type="submit" name="delete_doc" class="btn btn-sm" style="background:#fee2e2;color:#ef4444;border-color:#fca5a5"><i class="fas fa-trash"></i></button>
                </form>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="portal-empty">
          <i class="fas fa-folder-open" style="font-size:3rem;color:var(--clr-gold);margin-bottom:16px"></i>
          <h3>No documents uploaded</h3>
          <p>Upload your travel documents to keep them organized and accessible anywhere.</p>
        </div>
        <?php endif; ?>
      </div>

      <!-- Upload Form -->
      <div class="card" style="position:sticky;top:calc(80px+24px)">
        <div class="card-header"><h3><i class="fas fa-upload"></i> Upload Document</h3></div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <?= csrfField() ?>
            <div class="form-group" style="margin-bottom:14px">
              <label class="form-label">Document Type</label>
              <select name="doc_type" class="form-control" required>
                <?php foreach ($docTypeLabels as $v=>$l): ?><option value="<?= $v ?>"><?= $l ?></option><?php endforeach; ?>
              </select>
            </div>
            <div class="form-group" style="margin-bottom:14px">
              <label class="form-label">Label <small style="color:var(--clr-muted)">(optional)</small></label>
              <input type="text" name="doc_label" class="form-control" placeholder="e.g. My Kenya Visa 2025">
            </div>
            <div class="form-group" style="margin-bottom:20px">
              <label class="form-label">File</label>
              <div style="border:2px dashed var(--clr-border);border-radius:var(--radius);padding:20px;text-align:center;position:relative">
                <i class="fas fa-cloud-upload-alt" style="font-size:1.75rem;color:var(--clr-sky);margin-bottom:8px;display:block"></i>
                <p style="font-size:.875rem;color:var(--clr-muted);margin:0">PDF, JPG, PNG up to 10MB</p>
                <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp" required style="position:absolute;inset:0;opacity:0;cursor:pointer" onchange="document.getElementById('fname').textContent=this.files[0]?.name||''">
                <div id="fname" style="margin-top:8px;font-size:.8rem;color:var(--clr-primary);font-weight:500"></div>
              </div>
            </div>
            <button type="submit" name="upload_doc" class="btn btn-primary btn-block"><i class="fas fa-upload"></i> Upload Document</button>
          </form>

          <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--clr-border)">
            <h4 style="font-size:.875rem;color:var(--clr-primary);margin-bottom:10px"><i class="fas fa-shield-alt"></i> Security Notice</h4>
            <ul style="font-size:.8rem;color:var(--clr-muted);padding-left:18px;line-height:1.8;margin:0">
              <li>Documents are stored securely and encrypted</li>
              <li>Only you can access your documents</li>
              <li>We never share your documents with third parties</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<script src="<?= url('assets/js/main.js') ?>"></script>
</body>
</html>
