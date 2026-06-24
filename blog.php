<?php
$pageTitle       = 'Travel Blog — Destination Guides, Safari Tips & Travel Stories';
$pageDescription = 'Read MT Safaris travel blog for expert destination guides, safari tips, corporate travel insights, and inspiring travel stories from East Africa and beyond.';
$headerClass     = 'solid';
require_once 'includes/config.php';
require_once 'includes/functions.php';
$_blogSchema = DB::rows("SELECT title, slug FROM blog_posts WHERE status='published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 20");
$jsonLd = schemaItemList($_blogSchema, url('blog.php'), 'MT Safaris Travel Blog', 'blog-detail.php?slug=')
        . schemaBreadcrumb([
            ['name' => 'Home', 'url' => url()],
            ['name' => 'Blog', 'url' => url('blog.php')],
          ]);
unset($_blogSchema);
require_once 'includes/header.php';

$blogCategories = DB::rows("SELECT bc.*, COUNT(bp.id) AS post_count
                             FROM blog_categories bc
                             LEFT JOIN blog_posts bp ON bc.id = bp.category_id AND bp.status = 'published'
                             WHERE bc.is_active = 1
                             GROUP BY bc.id
                             ORDER BY post_count DESC");

$catId   = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));

$where  = ["bp.status = 'published' AND bp.published_at <= NOW()"];
$params = [];
if ($catId)  { $where[] = 'bp.category_id = ?'; $params[] = $catId; }
if ($search) { $where[] = 'MATCH(bp.title, bp.excerpt, bp.body) AGAINST(? IN BOOLEAN MODE)'; $params[] = $search . '*'; }

$sql = "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug,
               CONCAT(u.first_name,' ',u.last_name) AS author_name
        FROM blog_posts bp
        LEFT JOIN blog_categories bc ON bp.category_id = bc.id
        LEFT JOIN users u ON bp.author_id = u.id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY bp.is_featured DESC, bp.published_at DESC";

$result = DB::paginate($sql, $params, $page, BLOG_PER_PAGE);
$posts  = $result['rows'];

$featured = DB::row("SELECT bp.*, bc.name AS category_name, CONCAT(u.first_name,' ',u.last_name) AS author_name
                     FROM blog_posts bp
                     LEFT JOIN blog_categories bc ON bp.category_id=bc.id
                     LEFT JOIN users u ON bp.author_id=u.id
                     WHERE bp.is_featured=1 AND bp.status='published' AND bp.published_at<=NOW()
                     ORDER BY bp.published_at DESC LIMIT 1");
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Blog</span>
      </div>
      <h1>Travel <span style="color:var(--clr-gold)">Stories</span> & Guides</h1>
      <p>Expert insights, destination guides, and travel inspiration from our team on the ground.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="display:grid;grid-template-columns:1fr 320px;gap:40px;align-items:start">

      <!-- BLOG POSTS -->
      <div>
        <!-- Featured Post -->
        <?php if ($featured && $page === 1 && !$catId && !$search): ?>
        <article style="background:#fff;border-radius:20px;overflow:hidden;border:1px solid var(--clr-border);margin-bottom:40px;box-shadow:var(--shadow-md)" data-animate>
          <div style="display:grid;grid-template-columns:1fr 1fr">
            <div style="aspect-ratio:1;overflow:hidden">
              <img src="<?= h($featured['featured_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=700&q=85') ?>"
                   alt="<?= h($featured['title']) ?>" style="width:100%;height:100%;object-fit:cover" decoding="async" fetchpriority="high">
            </div>
            <div style="padding:36px;display:flex;flex-direction:column;justify-content:center">
              <span style="background:var(--clr-gold);color:#fff;font-size:.68rem;font-weight:700;padding:4px 12px;border-radius:20px;display:inline-block;margin-bottom:14px;text-transform:uppercase;letter-spacing:.08em">Featured</span>
              <div class="blog-cat"><?= h($featured['category_name'] ?? '') ?></div>
              <h2 style="font-size:1.3rem;color:var(--clr-primary);margin-bottom:12px">
                <a href="<?= url('blog-detail.php?slug=' . h($featured['slug'])) ?>"><?= h($featured['title']) ?></a>
              </h2>
              <p style="color:var(--clr-muted);font-size:.875rem;margin-bottom:20px"><?= h(excerpt($featured['excerpt']??'', 160)) ?></p>
              <div class="blog-meta" style="margin-bottom:20px">
                <span><i class="far fa-user"></i> <?= h($featured['author_name'] ?? 'MT Safaris') ?></span>
                <span><i class="far fa-calendar-alt"></i> <?= formatDate($featured['published_at']) ?></span>
              </div>
              <a href="<?= url('blog-detail.php?slug=' . h($featured['slug'])) ?>" class="btn btn-primary" style="align-self:start">Read Article</a>
            </div>
          </div>
        </article>
        <?php endif; ?>

        <!-- Search -->
        <form method="GET" style="display:flex;gap:10px;margin-bottom:28px">
          <?php if ($catId): ?><input type="hidden" name="category" value="<?= $catId ?>"> <?php endif; ?>
          <div style="position:relative;flex:1">
            <input type="text" name="search" value="<?= h($search) ?>" placeholder="Search articles…" class="form-control" style="padding-left:40px">
            <i class="fas fa-search" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--clr-muted)"></i>
          </div>
          <button type="submit" class="btn btn-primary">Search</button>
          <?php if ($search || $catId): ?>
          <a href="<?= url('blog.php') ?>" class="btn btn-outline">Clear</a>
          <?php endif; ?>
        </form>

        <!-- Results count -->
        <?php if ($posts): ?>
        <p style="color:var(--clr-muted);font-size:.82rem;margin-bottom:20px"><?= $result['total'] ?> article<?= $result['total']!==1?'s':'' ?> found</p>
        <div class="grid-auto" style="grid-template-columns:repeat(auto-fill,minmax(280px,1fr))">
          <?php foreach ($posts as $i => $post): ?>
          <article class="blog-card" data-animate data-delay="<?= $i*60 ?>">
            <div class="blog-card-img">
              <a href="<?= url('blog-detail.php?slug=' . h($post['slug'])) ?>">
                <img src="<?= h($post['featured_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=500&q=80') ?>"
                     alt="<?= h($post['title']) ?>" loading="lazy" decoding="async">
              </a>
            </div>
            <div class="blog-card-body">
              <div class="blog-cat">
                <a href="?category=<?= $post['category_slug']??'' ?>"><?= h($post['category_name']??'Travel') ?></a>
              </div>
              <h3 class="blog-title">
                <a href="<?= url('blog-detail.php?slug=' . h($post['slug'])) ?>"><?= h($post['title']) ?></a>
              </h3>
              <p class="blog-excerpt"><?= h(excerpt($post['excerpt']??$post['body'], 110)) ?></p>
              <div class="blog-meta">
                <span><i class="far fa-user"></i> <?= h($post['author_name']??'MT Safaris') ?></span>
                <span><i class="far fa-calendar-alt"></i> <?= formatDate($post['published_at']??$post['created_at']) ?></span>
                <span><i class="far fa-eye"></i> <?= number_format($post['view_count']) ?></span>
              </div>
            </div>
          </article>
          <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="text-center" style="padding:60px 20px">
          <i class="fas fa-pen-nib" style="font-size:3rem;color:var(--clr-border);display:block;margin-bottom:16px"></i>
          <h3 style="color:var(--clr-primary);margin-bottom:8px">No articles found</h3>
          <p class="text-muted">Check back soon for travel stories and guides.</p>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php echo paginationHtml($result['total'], $result['pages'], $result['page'], url('blog.php?' . http_build_query(['category'=>$catId,'search'=>$search]))); ?>
      </div>

      <!-- SIDEBAR -->
      <aside>
        <!-- Categories -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><h3>Categories</h3></div>
          <div class="card-body" style="padding:8px 0">
            <a href="<?= url('blog.php') ?>" style="display:flex;justify-content:space-between;padding:10px 20px;font-size:.875rem;color:var(--clr-text);border-bottom:1px solid var(--clr-border);transition:background .2s" onmouseover="this.style.background='var(--clr-light)'" onmouseout="this.style.background=''">
              All Categories <span style="color:var(--clr-muted)"><?= $result['total'] ?></span>
            </a>
            <?php foreach ($blogCategories as $bc): ?>
            <a href="?category=<?= $bc['id'] ?>" style="display:flex;justify-content:space-between;padding:10px 20px;font-size:.875rem;color:<?= $catId==$bc['id']?'var(--clr-primary)':'var(--clr-text)' ?>;background:<?= $catId==$bc['id']?'rgba(13,59,102,.06)':'' ?>;border-bottom:1px solid var(--clr-border);transition:background .2s;font-weight:<?= $catId==$bc['id']?'600':'400' ?>" onmouseover="this.style.background='var(--clr-light)'" onmouseout="this.style.background='<?= $catId==$bc['id']?'rgba(13,59,102,.06)':'' ?>'">
              <?= h($bc['name']) ?> <span style="color:var(--clr-muted)"><?= $bc['post_count'] ?></span>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Popular Posts -->
        <div class="card" style="margin-bottom:20px">
          <div class="card-header"><h3>Popular Posts</h3></div>
          <div class="card-body" style="padding:12px 16px">
            <?php
            $popular = DB::rows("SELECT slug, title, featured_image, view_count FROM blog_posts WHERE status='published' ORDER BY view_count DESC LIMIT 5");
            foreach ($popular as $p): ?>
            <a href="<?= url('blog-detail.php?slug='.h($p['slug'])) ?>" style="display:flex;gap:12px;margin-bottom:14px;text-decoration:none">
              <img src="<?= h($p['featured_image'] ?: 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=100&q=70') ?>"
                   style="width:60px;height:50px;object-fit:cover;border-radius:6px;flex-shrink:0" alt="" loading="lazy" decoding="async">
              <div>
                <p style="font-size:.8rem;font-weight:600;color:var(--clr-primary);display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= h($p['title']) ?></p>
                <span style="font-size:.72rem;color:var(--clr-muted)"><i class="far fa-eye"></i> <?= number_format($p['view_count']) ?></span>
              </div>
            </a>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- CTA -->
        <div style="background:var(--clr-primary);border-radius:16px;padding:24px;text-align:center">
          <i class="fas fa-compass" style="font-size:2rem;color:var(--clr-gold);display:block;margin-bottom:12px"></i>
          <h4 style="color:#fff;margin-bottom:8px">Ready to Travel?</h4>
          <p style="color:rgba(255,255,255,.7);font-size:.82rem;margin-bottom:16px">Turn inspiration into reality with our expert consultants.</p>
          <a href="<?= url('packages.php') ?>" class="btn btn-gold btn-sm btn-block">Explore Packages</a>
        </div>
      </aside>

    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
