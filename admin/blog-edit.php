<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$id     = (int)($_GET['id'] ?? 0);
$post   = $id ? DB::row("SELECT * FROM blog_posts WHERE id=?", [$id]) : null;
$isEdit = (bool)$post;

$categories = DB::rows("SELECT * FROM blog_categories ORDER BY sort_order");
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title          = trim($_POST['title'] ?? '');
    $slug_in        = trim($_POST['slug'] ?? '');
    $slug_val       = $slug_in ?: slug($title);
    $excerpt_text   = trim($_POST['excerpt'] ?? '');
    $content        = $_POST['content'] ?? '';
    $category_id    = (int)($_POST['category_id'] ?? 0);
    $status         = in_array($_POST['status']??'draft', ['published','draft','archived']) ? $_POST['status'] : 'draft';
    $is_featured    = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title     = trim($_POST['meta_title'] ?? '');
    $meta_desc      = trim($_POST['meta_description'] ?? '');
    $tags_raw       = trim($_POST['tags'] ?? '');
    $tags_arr       = array_values(array_filter(array_map('trim', explode(',', $tags_raw))));

    // Featured image
    $featured_image = $post['featured_image'] ?? '';
    if (!empty($_FILES['featured_image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['featured_image'], 'blog');
        if ($uploaded) $featured_image = $uploaded;
        else $errors[] = 'Failed to upload featured image.';
    }

    if (!$title)   $errors[] = 'Post title is required.';
    if (!$content) $errors[] = 'Post content is required.';

    // Unique slug
    $existingSlug = DB::row("SELECT id FROM blog_posts WHERE slug=? AND id!=?", [$slug_val, $id]);
    if ($existingSlug) $slug_val .= '-' . substr(uniqid(),8);

    if (!$errors) {
        $published_at = null;
        if ($status === 'published') {
            $published_at = $post['published_at'] ?? date('Y-m-d H:i:s');
        }

        $data = [
            'title'            => $title,
            'slug'             => $slug_val,
            'excerpt'          => $excerpt_text,
            'content'          => $content,
            'featured_image'   => $featured_image,
            'category_id'      => $category_id ?: null,
            'status'           => $status,
            'is_featured'      => $is_featured,
            'meta_title'       => $meta_title,
            'meta_description' => $meta_desc,
            'tags'             => json_encode($tags_arr),
            'published_at'     => $published_at,
            'author_id'        => currentUser()['id'],
        ];

        if ($isEdit) {
            DB::update('blog_posts', $data, ['id' => $id]);
            auditLog('update', 'blog_posts', $id, $post, $data);
            flash('success', 'Blog post updated.');
        } else {
            $newId = DB::insert('blog_posts', $data);
            auditLog('create', 'blog_posts', $newId, [], $data);
            flash('success', 'Blog post created.');
            redirect(url('admin/blog-edit.php?id='.$newId));
        }
        $success = true;
        $post = DB::row("SELECT * FROM blog_posts WHERE id=?", [$isEdit ? $id : $newId]);
    }
}

$p    = $post ?? [];
$tags = implode(', ', jd($p['tags'] ?? '[]', []));

$pageTitle = ($isEdit ? 'Edit Post' : 'New Blog Post') . ' | MT Safaris Admin';
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
  <div class="admin-header-title"><?= $isEdit ? 'Edit: '.h(excerpt($p['title']??'',50)) : 'New Blog Post' ?></div>
  <div class="admin-header-actions">
    <?php if ($isEdit && ($p['status']??'')==='published'): ?>
    <a href="<?= url('blog-detail.php?slug='.h($p['slug'])) ?>" target="_blank" class="btn btn-admin-outline btn-sm"><i class="fas fa-eye"></i> View Post</a>
    <?php endif; ?>
    <a href="<?= url('admin/blog.php') ?>" class="btn btn-admin-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  </div>
</header>
<main class="admin-main">

<?php if ($errors): ?><div class="alert alert-danger"><ul style="margin:0;padding-left:20px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> Post saved successfully.</div><?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="blogForm">
  <?= csrfField() ?>
  <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">
    <!-- Main -->
    <div>
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Post Title *</label>
            <input type="text" name="title" id="blog_title" class="admin-input" required value="<?= h($p['title']??'') ?>" placeholder="Enter post title...">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">URL Slug</label>
            <input type="text" name="slug" id="blog_slug" class="admin-input" value="<?= h($p['slug']??'') ?>" placeholder="auto-generated">
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Excerpt <small style="color:var(--admin-muted)">(appears in listings and SEO)</small></label>
            <textarea name="excerpt" class="admin-input" rows="3" placeholder="Short summary of the post..."><?= h($p['excerpt']??'') ?></textarea>
          </div>
        </div>
      </div>

      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header">
          <h3>Content</h3>
          <div class="editor-toolbar">
            <button type="button" onclick="fmt('bold')" title="Bold"><i class="fas fa-bold"></i></button>
            <button type="button" onclick="fmt('italic')" title="Italic"><i class="fas fa-italic"></i></button>
            <button type="button" onclick="fmt('underline')" title="Underline"><i class="fas fa-underline"></i></button>
            <span class="toolbar-sep">|</span>
            <button type="button" onclick="fmt('formatBlock','H2')" title="Heading 2">H2</button>
            <button type="button" onclick="fmt('formatBlock','H3')" title="Heading 3">H3</button>
            <button type="button" onclick="fmt('formatBlock','P')" title="Paragraph">P</button>
            <span class="toolbar-sep">|</span>
            <button type="button" onclick="fmt('insertUnorderedList')" title="Bullet list"><i class="fas fa-list-ul"></i></button>
            <button type="button" onclick="fmt('insertOrderedList')" title="Numbered list"><i class="fas fa-list-ol"></i></button>
            <span class="toolbar-sep">|</span>
            <button type="button" onclick="insertLink()" title="Insert link"><i class="fas fa-link"></i></button>
            <button type="button" onclick="fmt('insertHorizontalRule')" title="Horizontal rule"><i class="fas fa-minus"></i></button>
            <span class="toolbar-sep">|</span>
            <button type="button" onclick="fmt('removeFormat')" title="Clear formatting"><i class="fas fa-eraser"></i></button>
          </div>
        </div>
        <div class="admin-card-body" style="padding:0">
          <div id="blogEditor" class="editor-content" contenteditable="true" data-placeholder="Start writing your blog post..."><?= $p['content']??'' ?></div>
          <input type="hidden" name="content" id="contentField" value="<?= h($p['content']??'') ?>">
        </div>
      </div>

      <!-- SEO -->
      <div class="admin-card">
        <div class="admin-card-header"><h3><i class="fas fa-search"></i> SEO Settings</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Meta Title <small style="color:var(--admin-muted)">(leave blank to use post title)</small></label>
            <input type="text" name="meta_title" class="admin-input" value="<?= h($p['meta_title']??'') ?>" placeholder="SEO title...">
            <small id="metaTitleCount" style="color:var(--admin-muted)">0/60 chars</small>
          </div>
          <div class="admin-form-group">
            <label class="admin-label">Meta Description</label>
            <textarea name="meta_description" id="metaDesc" class="admin-input" rows="3" placeholder="SEO description (150-160 chars)..."><?= h($p['meta_description']??'') ?></textarea>
            <small id="metaDescCount" style="color:var(--admin-muted)">0/160 chars</small>
          </div>
          <div>
            <label class="admin-label">Tags <small style="color:var(--admin-muted)">(comma-separated)</small></label>
            <input type="text" name="tags" class="admin-input" value="<?= h($tags) ?>" placeholder="safari, kenya, wildlife, adventure">
          </div>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div>
      <!-- Publish -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Publish</h3></div>
        <div class="admin-card-body">
          <div class="admin-form-group">
            <label class="admin-label">Status</label>
            <select name="status" class="admin-select">
              <option value="draft" <?= ($p['status']??'draft')==='draft'?'selected':'' ?>>Draft</option>
              <option value="published" <?= ($p['status']??'')==='published'?'selected':'' ?>>Published</option>
              <option value="archived" <?= ($p['status']??'')==='archived'?'selected':'' ?>>Archived</option>
            </select>
          </div>
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;margin-bottom:16px">
            <input type="checkbox" name="is_featured" <?= ($p['is_featured']??0)?'checked':'' ?>>
            <span>Feature this post</span>
          </label>
          <?php if (!empty($p['published_at'])): ?>
          <div style="font-size:.8rem;color:var(--admin-muted);margin-bottom:12px"><i class="fas fa-calendar"></i> Published: <?= formatDate($p['published_at'],'M j, Y g:ia') ?></div>
          <?php endif; ?>
          <button type="submit" class="btn btn-admin-primary btn-block"><i class="fas fa-save"></i> <?= $isEdit ? 'Update Post' : 'Publish Post' ?></button>
        </div>
      </div>

      <!-- Category -->
      <div class="admin-card" style="margin-bottom:20px">
        <div class="admin-card-header"><h3>Category</h3></div>
        <div class="admin-card-body">
          <select name="category_id" class="admin-select">
            <option value="">-- No Category --</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($p['category_id']??0)==$cat['id']?'selected':'' ?>><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Featured Image -->
      <div class="admin-card">
        <div class="admin-card-header"><h3>Featured Image</h3></div>
        <div class="admin-card-body">
          <?php if (!empty($p['featured_image'])): ?>
          <img src="<?= h($p['featured_image']) ?>" id="featuredPreview" style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:12px" alt="">
          <?php else: ?>
          <img id="featuredPreview" src="" style="display:none;width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:12px" alt="">
          <?php endif; ?>
          <div class="upload-area" style="position:relative">
            <i class="fas fa-image upload-icon"></i>
            <p>Upload featured image</p>
            <small>JPG, PNG, WebP — max 5MB</small>
            <input type="file" name="featured_image" id="featuredInput" accept="image/*" style="position:absolute;inset:0;opacity:0;cursor:pointer" onchange="previewImage(this)">
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
</main>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function fmt(cmd, val=null) {
  document.getElementById('blogEditor').focus();
  document.execCommand(cmd, false, val);
}
function insertLink() {
  const url = prompt('Enter URL:');
  if (url) document.execCommand('createLink', false, url);
}
function previewImage(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.getElementById('featuredPreview');
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

// Slug auto-gen
document.getElementById('blog_title').addEventListener('input', function(){
  if (!<?= $isEdit ? 'true' : 'false' ?> || !document.getElementById('blog_slug').value) {
    document.getElementById('blog_slug').value = this.value.toLowerCase().trim().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-');
  }
});

// Sync editor content before submit
document.getElementById('blogForm').addEventListener('submit', function(){
  document.getElementById('contentField').value = document.getElementById('blogEditor').innerHTML;
});

// Char counters
const metaTitleInput = document.querySelector('[name="meta_title"]');
const metaDescInput  = document.getElementById('metaDesc');
function updateCount(el, countEl, max){
  const len = el.value.length;
  countEl.textContent = `${len}/${max} chars`;
  countEl.style.color = len > max ? '#ef4444' : 'var(--admin-muted)';
}
if (metaTitleInput) {
  metaTitleInput.addEventListener('input', () => updateCount(metaTitleInput, document.getElementById('metaTitleCount'), 60));
  updateCount(metaTitleInput, document.getElementById('metaTitleCount'), 60);
}
if (metaDescInput) {
  metaDescInput.addEventListener('input', () => updateCount(metaDescInput, document.getElementById('metaDescCount'), 160));
  updateCount(metaDescInput, document.getElementById('metaDescCount'), 160);
}
</script>
<style>
#blogEditor{min-height:400px;padding:20px;outline:none;font-size:1rem;line-height:1.8;color:#374151}
#blogEditor:empty:before{content:attr(data-placeholder);color:#9ca3af}
#blogEditor h2,#blogEditor h3{color:var(--admin-text);margin:1.25em 0 .5em}
#blogEditor p{margin-bottom:1em}
#blogEditor ul,#blogEditor ol{margin:1em 0 1em 1.5em}
#blogEditor blockquote{border-left:4px solid var(--admin-primary);padding:12px 16px;background:#f8fafc;margin:1em 0;color:var(--admin-muted);font-style:italic}
.editor-toolbar{display:flex;gap:4px;flex-wrap:wrap;padding:8px 16px;border-bottom:1px solid var(--admin-border)}
.editor-toolbar button{background:none;border:1px solid transparent;border-radius:4px;padding:4px 8px;cursor:pointer;font-size:.8rem;color:var(--admin-text)}
.editor-toolbar button:hover{background:#f1f5f9;border-color:var(--admin-border)}
.toolbar-sep{color:var(--admin-border);padding:0 2px;align-self:center}
.alert{padding:14px 18px;border-radius:8px;margin-bottom:16px}
.alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.alert-danger{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
</style>
</body>
</html>
