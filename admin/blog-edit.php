<?php
require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';
requireAdmin();

$id         = (int)($_GET['id'] ?? 0);
$post       = $id ? DB::row("SELECT * FROM blog_posts WHERE id=?", [$id]) : null;
$isEdit     = (bool)$post;
$categories = DB::rows("SELECT * FROM blog_categories ORDER BY sort_order");
$errors     = [];
$success    = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $title        = trim($_POST['title'] ?? '');
    $slug_in      = trim($_POST['slug'] ?? '');
    $slug_val     = $slug_in ?: slug($title);
    $excerpt_text = trim($_POST['excerpt'] ?? '');
    $content      = $_POST['content'] ?? '';
    $category_id  = (int)($_POST['category_id'] ?? 0);
    $status       = in_array($_POST['status'] ?? 'draft', ['published','draft','archived']) ? $_POST['status'] : 'draft';
    $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
    $meta_title   = trim($_POST['meta_title'] ?? '');
    $meta_desc    = trim($_POST['meta_description'] ?? '');
    $tags_raw     = trim($_POST['tags'] ?? '');
    $tags_arr     = array_values(array_filter(array_map('trim', explode(',', $tags_raw))));

    $featured_image = $post['featured_image'] ?? '';
    if (!empty($_FILES['featured_image']['tmp_name'])) {
        $uploaded = uploadImage($_FILES['featured_image'], 'blog');
        if ($uploaded) $featured_image = $uploaded;
        else $errors[] = 'Failed to upload featured image.';
    }

    if (!$title)   $errors[] = 'Post title is required.';
    if (!$content) $errors[] = 'Post content is required.';

    $existingSlug = DB::row("SELECT id FROM blog_posts WHERE slug=? AND id!=?", [$slug_val, $id]);
    if ($existingSlug) $slug_val .= '-' . substr(uniqid(), 8);

    if (!$errors) {
        $published_at = null;
        if ($status === 'published') $published_at = $post['published_at'] ?? date('Y-m-d H:i:s');

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
            redirect(url('admin/blog-edit.php?id=' . $newId));
        }
        $success = true;
        $post = DB::row("SELECT * FROM blog_posts WHERE id=?", [$isEdit ? $id : $newId]);
    }
}

$p    = $post ?? [];
$tags = implode(', ', jd($p['tags'] ?? '[]', []));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= $isEdit ? 'Edit Post' : 'New Post' ?> — Admin | MT Safaris</title>
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
        <a href="<?= url('admin/blog.php') ?>">Blog</a>
        <i class="fas fa-chevron-right"></i>
        <span><?= $isEdit ? h(excerpt($p['title'] ?? 'Edit Post', 40)) : 'New Post' ?></span>
      </div>
    </div>
    <div class="admin-header-right">
      <?php if ($isEdit && ($p['status'] ?? '') === 'published'): ?>
      <a href="<?= url('blog-detail.php?slug=' . h($p['slug'])) ?>" target="_blank"
         class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-eye"></i> View Live
      </a>
      <?php endif; ?>
      <a href="<?= url('admin/blog.php') ?>" class="btn-admin btn-admin-secondary btn-admin-sm">
        <i class="fas fa-arrow-left"></i> Back
      </a>
    </div>
  </header>

  <div class="admin-content">

    <?php $flash = getFlash(); if ($flash): ?>
    <div class="flash-msg flash-<?= h($flash['type']) ?>"><i class="fas fa-check-circle"></i><span><?= h($flash['message']) ?></span></div>
    <?php endif; ?>
    <?php if ($errors): ?>
    <div class="flash-msg flash-danger">
      <i class="fas fa-exclamation-circle"></i>
      <ul style="margin:0;padding-left:18px"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="page-header">
      <div class="page-header-info">
        <div class="page-title"><?= $isEdit ? 'Edit Blog Post' : 'New Blog Post' ?></div>
        <div class="page-subtitle"><?= $isEdit ? 'Update post content, SEO settings, and publishing status' : 'Write and publish a new article' ?></div>
      </div>
    </div>

    <form method="POST" enctype="multipart/form-data" id="blogForm">
      <?= csrfField() ?>

      <!-- Sticky save bar -->
      <div class="save-bar">
        <div style="color:var(--clr-muted);font-size:.85rem">
          <?= $isEdit ? 'Editing: <strong>' . h(excerpt($p['title'] ?? '', 55)) . '</strong>' : 'New Blog Post' ?>
        </div>
        <button type="submit" class="btn-admin btn-admin-primary">
          <i class="fas fa-save"></i> <?= $isEdit ? 'Update Post' : 'Publish Post' ?>
        </button>
      </div>

      <div style="display:grid;grid-template-columns:1fr 300px;gap:24px;align-items:start">

        <!-- Main content -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Title & meta -->
          <div class="admin-card">
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">Post Title <span style="color:var(--clr-danger)">*</span></label>
                <input type="text" name="title" id="blog_title" class="form-control" required
                       value="<?= h($p['title'] ?? '') ?>" placeholder="Enter a compelling post title…">
              </div>
              <div class="form-group">
                <label class="form-label">URL Slug</label>
                <div class="input-group">
                  <i class="ig-icon fas fa-link"></i>
                  <input type="text" name="slug" id="blog_slug" class="form-control"
                         value="<?= h($p['slug'] ?? '') ?>" placeholder="auto-generated">
                </div>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Excerpt <span class="form-hint" style="display:inline">(shown in listings)</span></label>
                <textarea name="excerpt" class="form-control" rows="3"
                          placeholder="A short summary of the post for listings and SEO…"><?= h($p['excerpt'] ?? '') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Editor -->
          <div class="admin-card">
            <div class="admin-card-header" style="flex-direction:column;align-items:stretch;gap:0;padding-bottom:0">
              <span style="font-weight:600;color:var(--clr-primary);padding-bottom:10px">Content <span style="color:var(--clr-danger)">*</span></span>
              <div class="editor-toolbar">
                <button type="button" onclick="fmt('bold')" title="Bold"><i class="fas fa-bold"></i></button>
                <button type="button" onclick="fmt('italic')" title="Italic"><i class="fas fa-italic"></i></button>
                <button type="button" onclick="fmt('underline')" title="Underline"><i class="fas fa-underline"></i></button>
                <span class="toolbar-sep">|</span>
                <button type="button" onclick="fmt('formatBlock','H2')" title="Heading 2">H2</button>
                <button type="button" onclick="fmt('formatBlock','H3')" title="Heading 3">H3</button>
                <button type="button" onclick="fmt('formatBlock','P')"  title="Paragraph">P</button>
                <span class="toolbar-sep">|</span>
                <button type="button" onclick="fmt('insertUnorderedList')" title="Bullet list"><i class="fas fa-list-ul"></i></button>
                <button type="button" onclick="fmt('insertOrderedList')"   title="Numbered list"><i class="fas fa-list-ol"></i></button>
                <span class="toolbar-sep">|</span>
                <button type="button" onclick="insertLink()" title="Insert link"><i class="fas fa-link"></i></button>
                <button type="button" onclick="fmt('formatBlock','BLOCKQUOTE')" title="Blockquote"><i class="fas fa-quote-left"></i></button>
                <button type="button" onclick="fmt('insertHorizontalRule')" title="Divider"><i class="fas fa-minus"></i></button>
                <span class="toolbar-sep">|</span>
                <button type="button" onclick="fmt('removeFormat')" title="Clear formatting"><i class="fas fa-eraser"></i></button>
              </div>
            </div>
            <div style="padding:0">
              <div id="blogEditor" class="blog-editor-content" contenteditable="true"
                   data-placeholder="Start writing your blog post…"><?= $p['content'] ?? '' ?></div>
              <input type="hidden" name="content" id="contentField" value="<?= h($p['content'] ?? '') ?>">
            </div>
          </div>

          <!-- SEO -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary);display:flex;align-items:center;gap:8px">
                <i class="fas fa-search" style="color:#0ea5e9"></i> SEO Settings
              </span>
            </div>
            <div class="admin-card-body">
              <div class="form-group">
                <label class="form-label">
                  Meta Title
                  <span id="metaTitleCount" class="form-hint" style="display:inline;float:right">0/60</span>
                </label>
                <input type="text" name="meta_title" id="metaTitleEl" class="form-control"
                       value="<?= h($p['meta_title'] ?? '') ?>"
                       placeholder="Leave blank to use post title">
              </div>
              <div class="form-group">
                <label class="form-label">
                  Meta Description
                  <span id="metaDescCount" class="form-hint" style="display:inline;float:right">0/160</span>
                </label>
                <textarea name="meta_description" id="metaDescEl" class="form-control" rows="3"
                          placeholder="150–160 characters for best SEO results…"><?= h($p['meta_description'] ?? '') ?></textarea>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label">Tags <span class="form-hint" style="display:inline">(comma-separated)</span></label>
                <input type="text" name="tags" class="form-control"
                       value="<?= h($tags) ?>" placeholder="safari, kenya, wildlife, adventure">
              </div>
            </div>
          </div>

        </div><!-- /left -->

        <!-- Right sidebar -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Publish -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Publish</span>
            </div>
            <div class="admin-card-body" style="display:flex;flex-direction:column;gap:14px">
              <div class="form-group" style="margin:0">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                  <option value="draft"     <?= ($p['status'] ?? 'draft') === 'draft'     ? 'selected' : '' ?>>Draft</option>
                  <option value="published" <?= ($p['status'] ?? '')      === 'published' ? 'selected' : '' ?>>Published</option>
                  <option value="archived"  <?= ($p['status'] ?? '')      === 'archived'  ? 'selected' : '' ?>>Archived</option>
                </select>
              </div>
              <label class="admin-toggle">
                <input type="checkbox" name="is_featured" <?= ($p['is_featured'] ?? 0) ? 'checked' : '' ?>>
                <span class="admin-toggle-slider"></span>
                <span class="admin-toggle-label">Feature this post</span>
              </label>
              <?php if (!empty($p['published_at'])): ?>
              <div style="font-size:.78rem;color:var(--clr-muted)">
                <i class="fas fa-calendar-check"></i> Published <?= formatDate($p['published_at'], 'M j, Y g:ia') ?>
              </div>
              <?php endif; ?>
              <button type="submit" class="btn-admin btn-admin-primary btn-block">
                <i class="fas fa-save"></i> <?= $isEdit ? 'Update Post' : 'Publish Post' ?>
              </button>
            </div>
          </div>

          <!-- Category -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Category</span>
            </div>
            <div class="admin-card-body">
              <select name="category_id" class="form-control">
                <option value="">— No Category —</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= ($p['category_id'] ?? 0) == $cat['id'] ? 'selected' : '' ?>>
                  <?= h($cat['name']) ?>
                </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <!-- Featured image -->
          <div class="admin-card">
            <div class="admin-card-header">
              <span style="font-weight:600;color:var(--clr-primary)">Featured Image</span>
            </div>
            <div class="admin-card-body">
              <?php if (!empty($p['featured_image'])): ?>
              <img src="<?= h($p['featured_image']) ?>" id="featuredPreview"
                   style="width:100%;height:180px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;border:1.5px solid var(--clr-border)" alt="">
              <?php else: ?>
              <img id="featuredPreview" src=""
                   style="display:none;width:100%;height:180px;object-fit:cover;border-radius:var(--radius-sm);margin-bottom:12px;border:1.5px solid var(--clr-border)" alt="">
              <?php endif; ?>
              <div class="upload-area">
                <i class="fas fa-image upload-icon"></i>
                <p style="margin:6px 0 2px">Upload featured image</p>
                <small>JPG, PNG, WebP — max 5MB</small>
                <input type="file" name="featured_image" id="featuredInput" accept="image/*"
                       style="position:absolute;inset:0;opacity:0;cursor:pointer"
                       onchange="previewImage(this)">
              </div>
            </div>
          </div>

        </div><!-- /right -->
      </div>
    </form>
  </div>
</div>

<script src="<?= url('assets/js/admin.js') ?>"></script>
<script>
function fmt(cmd, val) {
  document.getElementById('blogEditor').focus();
  document.execCommand(cmd, false, val ?? null);
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

// Slug auto-gen (only for new posts unless user hasn't typed one yet)
const blogTitleEl = document.getElementById('blog_title');
const blogSlugEl  = document.getElementById('blog_slug');
<?php if (!$isEdit): ?>
blogTitleEl?.addEventListener('input', function() {
  if (!blogSlugEl.dataset.manual)
    blogSlugEl.value = this.value.toLowerCase().trim()
      .replace(/[^\w\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-|-$/g, '');
});
blogSlugEl?.addEventListener('input', function() { this.dataset.manual = '1'; });
<?php endif; ?>

// Sync editor → hidden field before submit
document.getElementById('blogForm').addEventListener('submit', function() {
  document.getElementById('contentField').value = document.getElementById('blogEditor').innerHTML;
});

// Meta char counters
function updateCount(elId, cntId, max) {
  const el  = document.getElementById(elId);
  const cnt = document.getElementById(cntId);
  if (!el || !cnt) return;
  const update = () => {
    cnt.textContent = el.value.length + '/' + max;
    cnt.style.color = el.value.length > max ? 'var(--clr-danger)' : 'var(--clr-muted)';
  };
  el.addEventListener('input', update); update();
}
updateCount('metaTitleEl', 'metaTitleCount', 60);
updateCount('metaDescEl',  'metaDescCount',  160);
</script>

<style>
.blog-editor-content { min-height: 420px; padding: 20px; outline: none; font-size: 1rem; line-height: 1.8; color: var(--clr-text); }
.blog-editor-content:empty::before { content: attr(data-placeholder); color: var(--clr-muted); pointer-events: none; }
.blog-editor-content h2, .blog-editor-content h3 { color: var(--clr-primary); margin: 1.25em 0 .5em; }
.blog-editor-content p { margin-bottom: 1em; }
.blog-editor-content ul, .blog-editor-content ol { margin: 1em 0 1em 1.5em; }
.blog-editor-content blockquote { border-left: 4px solid var(--clr-primary); padding: 12px 16px; background: #f8fafc; margin: 1em 0; color: var(--clr-muted); font-style: italic; }

.editor-toolbar { display: flex; gap: 4px; flex-wrap: wrap; padding: 8px 12px; border-top: 1px solid var(--clr-border); border-bottom: 1px solid var(--clr-border); background: var(--clr-light); }
.editor-toolbar button { background: none; border: 1px solid transparent; border-radius: 4px; padding: 5px 8px; cursor: pointer; font-size: .78rem; color: var(--clr-text); transition: all .1s; }
.editor-toolbar button:hover { background: #fff; border-color: var(--clr-border); color: var(--clr-primary); }
.toolbar-sep { color: var(--clr-border); padding: 0 4px; align-self: center; }

.upload-area { position: relative; border: 2px dashed var(--clr-border); border-radius: var(--radius-sm); padding: 24px 16px; text-align: center; cursor: pointer; transition: border-color .2s; }
.upload-area:hover { border-color: var(--clr-primary); }
.upload-icon { font-size: 1.8rem; color: var(--clr-border); margin-bottom: 8px; display: block; }
.upload-area p { font-size: .85rem; color: var(--clr-muted); margin: 0; }
.upload-area small { font-size: .72rem; color: var(--clr-muted); }
</style>
</body>
</html>
