<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) redirect(url('blog.php'));

$post = getBlogPostBySlug($slug);
if (!$post) redirect(url('blog.php'));

// Comments (if table exists)
$comments = DB::rows("SELECT * FROM blog_comments WHERE post_id=? AND status='approved' ORDER BY created_at ASC", [$post['id']]);

// Comment POST
$commentSuccess = false;
$commentError   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    verifyCsrf();
    $cName    = trim($_POST['comment_name'] ?? '');
    $cEmail   = trim($_POST['comment_email'] ?? '');
    $cBody    = trim($_POST['comment_body'] ?? '');
    if (!$cName || !$cEmail || !$cBody) {
        $commentError = 'Please fill in all fields.';
    } elseif (!filter_var($cEmail, FILTER_VALIDATE_EMAIL)) {
        $commentError = 'Please enter a valid email address.';
    } else {
        DB::insert('blog_comments', [
            'post_id' => $post['id'],
            'name'    => $cName,
            'email'   => $cEmail,
            'body'    => $cBody,
            'status'  => 'pending',
        ]);
        $commentSuccess = true;
    }
}

$recentPosts = getRecentPosts(4);
$categories  = DB::rows("SELECT bc.*, COUNT(bp.id) AS post_count FROM blog_categories bc LEFT JOIN blog_posts bp ON bc.id=bp.category_id AND bp.status='published' GROUP BY bc.id ORDER BY post_count DESC");
$tags        = jd($post['tags'], []);

$pageTitle       = $post['meta_title'] ?: $post['title'];
$pageDescription = $post['meta_description'] ?: excerpt(strip_tags($post['body']), 160);
$pageImage       = $post['featured_image'] ?: '';
$ogType          = 'article';
$headerClass     = 'solid';
$post['author_name'] = $post['author_name'] ?? APP_NAME;
$jsonLd = schemaBlogPost($post)
        . schemaBreadcrumb([
            ['name'=>'Home', 'url'=> url()],
            ['name'=>'Blog', 'url'=> url('blog.php')],
            ['name'=>$post['title'], 'url'=> url('blog-detail.php?slug='.$post['slug'])],
          ]);
require_once 'includes/header.php';
?>

<section class="page-hero" style="background-color:var(--clr-primary);padding:56px 0 0">
  <div class="container">
    <div class="breadcrumb" style="color:rgba(255,255,255,.7);margin-bottom:16px">
      <a href="<?= url() ?>" style="color:rgba(255,255,255,.7)">Home</a>
      <i class="fas fa-chevron-right"></i>
      <a href="<?= url('blog.php') ?>" style="color:rgba(255,255,255,.7)">Blog</a>
      <i class="fas fa-chevron-right"></i>
      <span style="color:#fff"><?= h(excerpt($post['title'], 60)) ?></span>
    </div>
    <div style="max-width:860px">
      <?php if ($post['category_name']): ?><a href="<?= url('blog.php?category='.$post['category_id']) ?>" class="btn btn-gold btn-sm" style="margin-bottom:16px"><?= h($post['category_name']) ?></a><?php endif; ?>
      <h1 style="color:#fff;font-size:clamp(1.75rem,4vw,2.75rem);line-height:1.25;margin-bottom:20px"><?= h($post['title']) ?></h1>
      <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;padding-bottom:32px">
        <div style="display:flex;align-items:center;gap:10px">
          <div style="width:40px;height:40px;border-radius:50%;background:var(--clr-gold);display:grid;place-items:center;font-weight:700;color:var(--clr-primary);font-size:.9rem">
            <?= strtoupper(substr($post['author_name']??'A',0,1)) ?>
          </div>
          <div>
            <div style="color:#fff;font-weight:600;font-size:.875rem"><?= h($post['author_name']??'MT Safaris') ?></div>
            <div style="color:rgba(255,255,255,.6);font-size:.75rem"><?= formatDate($post['published_at']??$post['created_at'],'F j, Y') ?></div>
          </div>
        </div>
        <div style="color:rgba(255,255,255,.6);font-size:.875rem;display:flex;gap:16px">
          <span><i class="fas fa-eye"></i> <?= number_format($post['view_count']) ?> views</span>
          <span><i class="fas fa-comments"></i> <?= count($comments) ?> comments</span>
          <span><i class="fas fa-clock"></i> <?= max(1, (int)(str_word_count(strip_tags($post['body']))/200)) ?> min read</span>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($post['featured_image']): ?>
<div style="width:100%;max-height:520px;overflow:hidden">
  <img src="<?= h($post['featured_image']) ?>" alt="<?= h($post['title']) ?>" style="width:100%;height:520px;object-fit:cover" decoding="async" fetchpriority="high">
</div>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:48px;align-items:start">
      <!-- Main Content -->
      <div>
        <article class="blog-content" style="line-height:1.9;color:#374151;font-size:1.0625rem">
          <?= $post['body'] ?>
        </article>

        <?php if ($tags): ?>
        <div style="margin-top:36px;padding-top:24px;border-top:1px solid var(--clr-border)">
          <strong style="color:var(--clr-primary);margin-right:8px">Tags:</strong>
          <?php foreach ($tags as $tag): ?>
          <a href="<?= url('blog.php?tag='.urlencode($tag)) ?>" style="display:inline-block;background:#f1f5f9;color:var(--clr-primary);padding:4px 12px;border-radius:20px;font-size:.8rem;margin:4px;text-decoration:none"><?= h($tag) ?></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Share -->
        <div style="margin-top:28px;padding:24px;background:#f8fafc;border-radius:var(--radius-lg);display:flex;align-items:center;gap:16px;flex-wrap:wrap">
          <strong style="color:var(--clr-primary)">Share this article:</strong>
          <a href="https://wa.me/?text=<?= urlencode($post['title'].' - '.currentUrl()) ?>" target="_blank" class="btn btn-sm" style="background:#25D366;color:#fff"><i class="fab fa-whatsapp"></i> WhatsApp</a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(currentUrl()) ?>" target="_blank" class="btn btn-sm" style="background:#1877F2;color:#fff"><i class="fab fa-facebook-f"></i> Facebook</a>
          <a href="https://twitter.com/intent/tweet?url=<?= urlencode(currentUrl()) ?>&text=<?= urlencode($post['title']) ?>" target="_blank" class="btn btn-sm" style="background:#1DA1F2;color:#fff"><i class="fab fa-twitter"></i> Twitter</a>
          <button onclick="navigator.clipboard.writeText(window.location.href);showToast('Link copied!','success')" class="btn btn-sm btn-outline"><i class="fas fa-link"></i> Copy Link</button>
        </div>

        <!-- Comments -->
        <div style="margin-top:48px" id="comments">
          <h3 style="color:var(--clr-primary);margin-bottom:24px"><?= count($comments) ?> Comment<?= count($comments)!=1?'s':'' ?></h3>

          <?php if ($comments): ?>
          <div style="display:flex;flex-direction:column;gap:24px;margin-bottom:40px">
            <?php foreach ($comments as $c): ?>
            <div style="display:flex;gap:14px">
              <div style="width:42px;height:42px;border-radius:50%;background:var(--clr-primary);color:#fff;display:grid;place-items:center;font-weight:700;flex-shrink:0"><?= strtoupper(substr($c['name'],0,1)) ?></div>
              <div>
                <div style="display:flex;gap:12px;align-items:baseline;margin-bottom:6px">
                  <strong style="color:var(--clr-primary)"><?= h($c['name']) ?></strong>
                  <span style="font-size:.75rem;color:var(--clr-muted)"><?= timeAgo($c['created_at']) ?></span>
                </div>
                <p style="color:#374151;line-height:1.7;margin:0"><?= nl2br(h($c['body'])) ?></p>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>

          <!-- Comment Form -->
          <div style="background:#f8fafc;border-radius:var(--radius-lg);padding:32px">
            <h4 style="color:var(--clr-primary);margin-bottom:20px">Leave a Comment</h4>
            <?php if ($commentSuccess): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> Your comment has been submitted and is awaiting moderation.</div>
            <?php elseif ($commentError): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> <?= h($commentError) ?></div>
            <?php endif; ?>
            <form method="POST" action="#comments">
              <?= csrfField() ?>
              <div class="grid-2" style="gap:16px;margin-bottom:16px">
                <div class="form-group">
                  <label class="form-label">Name *</label>
                  <input type="text" name="comment_name" class="form-control" required value="<?= h($_POST['comment_name']??($user['first_name']??'').' '.($user['last_name']??'')) ?>">
                </div>
                <div class="form-group">
                  <label class="form-label">Email * <small style="color:var(--clr-muted)">(not published)</small></label>
                  <input type="email" name="comment_email" class="form-control" required value="<?= h($_POST['comment_email']??($user['email']??'')) ?>">
                </div>
              </div>
              <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Comment *</label>
                <textarea name="comment_body" class="form-control" rows="5" required placeholder="Share your thoughts..."><?= h($_POST['comment_body']??'') ?></textarea>
              </div>
              <button type="submit" name="submit_comment" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button>
            </form>
          </div>
        </div>
      </div>

      <!-- Sidebar -->
      <aside style="position:sticky;top:calc(var(--header-h) + 24px)">
        <!-- Author Box -->
        <?php if ($post['author_name']): ?>
        <div class="card" style="margin-bottom:24px">
          <div class="card-body" style="text-align:center">
            <div style="width:72px;height:72px;border-radius:50%;background:var(--clr-primary);color:#fff;display:grid;place-items:center;font-size:1.75rem;font-weight:700;margin:0 auto 12px">
              <?= strtoupper(substr($post['author_name'],0,1)) ?>
            </div>
            <h4 style="color:var(--clr-primary);margin-bottom:4px"><?= h($post['author_name']) ?></h4>
            <p style="color:var(--clr-muted);font-size:.875rem">MT Safaris Travel Writer</p>
          </div>
        </div>
        <?php endif; ?>

        <!-- Categories -->
        <div class="card" style="margin-bottom:24px">
          <div class="card-header"><h4>Categories</h4></div>
          <div class="card-body" style="padding:0">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= url('blog.php?category='.$cat['id']) ?>" style="display:flex;justify-content:space-between;align-items:center;padding:12px 20px;border-bottom:1px solid var(--clr-border);text-decoration:none;color:var(--clr-text);font-size:.875rem;transition:background .15s" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
              <span><?= h($cat['name']) ?></span>
              <span style="background:var(--clr-primary);color:#fff;border-radius:20px;padding:1px 8px;font-size:.7rem"><?= $cat['post_count'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Recent Posts -->
        <div class="card" style="margin-bottom:24px">
          <div class="card-header"><h4>Recent Articles</h4></div>
          <div class="card-body">
            <?php foreach ($recentPosts as $rp): if ($rp['id']==$post['id']) continue; ?>
            <div style="display:flex;gap:12px;margin-bottom:16px">
              <img src="<?= h($rp['featured_image']?:'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=80&q=60') ?>" style="width:64px;height:56px;object-fit:cover;border-radius:var(--radius)" alt="" loading="lazy" decoding="async">
              <div>
                <a href="<?= url('blog-detail.php?slug='.h($rp['slug'])) ?>" style="color:var(--clr-primary);font-size:.8125rem;font-weight:600;line-height:1.4;text-decoration:none;display:block;margin-bottom:4px"><?= h(excerpt($rp['title'],65)) ?></a>
                <span style="color:var(--clr-muted);font-size:.7rem"><?= formatDate($rp['published_at']??$rp['created_at'],'M j, Y') ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Newsletter CTA -->
        <div style="background:var(--clr-primary);border-radius:var(--radius-lg);padding:28px;text-align:center">
          <i class="fas fa-envelope" style="color:var(--clr-gold);font-size:2rem;margin-bottom:12px"></i>
          <h4 style="color:#fff;margin-bottom:8px">Travel Inspiration</h4>
          <p style="color:rgba(255,255,255,.7);font-size:.875rem;margin-bottom:16px">Get our weekly travel tips and deals delivered to your inbox.</p>
          <form id="sidebarNewsletter">
            <input type="email" class="form-control" placeholder="Your email" style="margin-bottom:10px" id="sidebarEmail">
            <button type="submit" class="btn btn-gold btn-block">Subscribe</button>
          </form>
        </div>
      </aside>
    </div>

    <!-- Related Posts -->
    <?php
    $related = DB::rows("SELECT bp.*, bc.name AS category_name, u.first_name AS author_name FROM blog_posts bp LEFT JOIN blog_categories bc ON bp.category_id=bc.id LEFT JOIN users u ON bp.author_id=u.id WHERE bp.status='published' AND bp.id!=? AND (bp.category_id=? OR FIND_IN_SET(?,IFNULL(bp.tags,''))) LIMIT 3", [$post['id'],$post['category_id'],$post['category_name']??'']);
    if ($related): ?>
    <div style="margin-top:64px">
      <h2 style="color:var(--clr-primary);margin-bottom:32px">Related Articles</h2>
      <div class="grid-3">
        <?php foreach ($related as $r): ?>
        <article class="blog-card">
          <div class="blog-card-img"><a href="<?= url('blog-detail.php?slug='.h($r['slug'])) ?>"><img src="<?= h($r['featured_image']?:'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=600&q=80') ?>" alt="<?= h($r['title']) ?>" loading="lazy"></a></div>
          <div class="blog-card-body">
            <?php if ($r['category_name']): ?><span class="blog-cat"><?= h($r['category_name']) ?></span><?php endif; ?>
            <h3 class="blog-title"><a href="<?= url('blog-detail.php?slug='.h($r['slug'])) ?>"><?= h($r['title']) ?></a></h3>
            <div class="blog-meta"><span><i class="fas fa-calendar"></i> <?= formatDate($r['published_at']??$r['created_at'],'M j, Y') ?></span></div>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<!-- CTA -->
<section class="section" style="background:linear-gradient(135deg,var(--clr-primary),var(--clr-sky));padding:64px 0">
  <div class="container" style="text-align:center">
    <h2 style="color:#fff;margin-bottom:12px">Ready to Start Your Adventure?</h2>
    <p style="color:rgba(255,255,255,.85);margin-bottom:28px">Turn your travel dreams into reality with MT Safaris.</p>
    <div style="display:flex;gap:16px;justify-content:center;flex-wrap:wrap">
      <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-lg">Explore Packages</a>
      <a href="<?= url('contact.php') ?>" class="btn btn-outline-white btn-lg">Get a Quote</a>
    </div>
  </div>
</section>

<script>
document.getElementById('sidebarNewsletter').addEventListener('submit', async function(e){
  e.preventDefault();
  const email = document.getElementById('sidebarEmail').value;
  const res = await fetch('<?= url('api/newsletter.php') ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({email})});
  const d = await res.json();
  showToast(d.message, d.success?'success':'error');
});
</script>

<style>
.blog-content h2,.blog-content h3,.blog-content h4{color:var(--clr-primary);margin:1.75em 0 .75em}
.blog-content p{margin-bottom:1.25em}
.blog-content img{max-width:100%;height:auto;border-radius:var(--radius-lg);margin:1em 0}
.blog-content blockquote{border-left:4px solid var(--clr-gold);margin:1.5em 0;padding:16px 20px;background:#fffbf0;color:var(--clr-primary);font-style:italic;border-radius:0 var(--radius) var(--radius) 0}
.blog-content ul,.blog-content ol{margin:1em 0 1.25em 1.5em;line-height:1.9}
.blog-content a{color:var(--clr-sky);text-decoration:underline}
.blog-content table{width:100%;border-collapse:collapse;margin:1.5em 0;font-size:.9em}
.blog-content table th,.blog-content table td{padding:10px 14px;border:1px solid var(--clr-border);text-align:left}
.blog-content table th{background:var(--clr-primary);color:#fff}
.alert{padding:14px 18px;border-radius:var(--radius);margin-bottom:16px}
.alert-success{background:#d1fae5;border:1px solid #6ee7b7;color:#065f46}
.alert-danger{background:#fee2e2;border:1px solid #fca5a5;color:#991b1b}
</style>

<?php require_once 'includes/footer.php'; ?>
