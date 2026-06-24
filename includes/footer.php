<?php
$recentPosts = getRecentPosts(3);
$categories  = getCategories();
?>
</main>

<!-- FOOTER -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">

      <!-- Brand -->
      <div class="footer-brand">
        <a href="<?= url() ?>" class="logo">
          <img src="<?= url('assets/images/logo.png') ?>" alt="Mountain Top Safaris Adventures" style="height:64px;width:auto">
        </a>
        <p>Crafting exceptional travel experiences across Africa and the world since 2005. We are East Africa's most trusted travel partner for corporate, leisure, and adventure journeys.</p>
        <div class="footer-social">
          <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
          <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
          <a href="#" aria-label="Twitter"><i class="fab fa-x-twitter"></i></a>
          <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
          <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= url('about.php') ?>">About Us</a></li>
          <li><a href="<?= url('packages.php') ?>">Tour Packages</a></li>
          <li><a href="<?= url('destinations.php') ?>">Destinations</a></li>
          <li><a href="<?= url('corporate.php') ?>">Corporate Travel</a></li>
          <li><a href="<?= url('blog.php') ?>">Travel Blog</a></li>
          <li><a href="<?= url('contact.php') ?>">Contact Us</a></li>
          <li><a href="<?= url('faq.php') ?>">FAQs</a></li>
          <li><a href="<?= url('review-submit.php') ?>">Leave a Review</a></li>
          <li><a href="<?= url('portal/register.php') ?>">Create Account</a></li>
        </ul>
      </div>

      <!-- Tour Types -->
      <div class="footer-col">
        <h4>Tour Types</h4>
        <ul>
          <?php foreach (array_slice($categories, 0, 8) as $cat): ?>
          <li><a href="<?= url('packages.php?category=' . h($cat['slug'])) ?>"><?= h($cat['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h4>Get in Touch</h4>
        <ul class="footer-contact">
          <li><i class="fas fa-map-marker-alt"></i><span><?= CONTACT_ADDRESS ?></span></li>
          <li><i class="fas fa-phone"></i><a href="tel:<?= CONTACT_PHONE ?>"><?= CONTACT_PHONE ?></a></li>
          <li><i class="fas fa-envelope"></i><a href="mailto:<?= CONTACT_EMAIL ?>"><?= CONTACT_EMAIL ?></a></li>
          <li><i class="fab fa-whatsapp"></i><a href="https://wa.me/<?= CONTACT_WHATSAPP ?>">WhatsApp Chat</a></li>
          <li><i class="far fa-clock"></i><span>Mon–Fri 8AM–6PM EAT</span></li>
        </ul>
        <div style="margin-top:20px">
          <p style="font-size:.78rem;color:rgba(255,255,255,.5);margin-bottom:10px">CERTIFIED BY</p>
          <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
            <span style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);padding:6px 14px;border-radius:4px;font-size:.72rem;font-weight:600">KATO</span>
            <span style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);padding:6px 14px;border-radius:4px;font-size:.72rem;font-weight:600">ATTA</span>
            <span style="background:rgba(255,255,255,.08);color:rgba(255,255,255,.7);padding:6px 14px;border-radius:4px;font-size:.72rem;font-weight:600">IATA</span>
          </div>
        </div>
      </div>

    </div>

    <!-- Newsletter strip -->
    <div style="background:rgba(255,255,255,.05);border-radius:12px;padding:28px 32px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:24px;flex-wrap:wrap">
      <div>
        <p style="color:#fff;font-weight:700;font-family:var(--ff-head);font-size:1.05rem;margin-bottom:4px">Stay Inspired. Subscribe to Our Newsletter.</p>
        <p style="font-size:.82rem;color:rgba(255,255,255,.6)">Travel deals, destination guides, and expert tips delivered to your inbox.</p>
      </div>
      <form class="newsletter-form" style="display:flex;gap:10px;min-width:320px">
        <input type="email" placeholder="Enter your email" required style="flex:1;padding:12px 16px;border-radius:999px;border:none;font-size:.875rem;outline:none">
        <button type="submit" class="btn btn-gold btn-sm">Subscribe</button>
      </form>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
      <span>© <?= date('Y') ?> MT Safaris. All rights reserved.</span>
      <div style="display:flex;gap:20px">
        <a href="<?= url('privacy.php') ?>">Privacy Policy</a>
        <a href="<?= url('terms.php') ?>">Terms of Service</a>
        <a href="<?= url('sitemap.xml') ?>">Sitemap</a>
      </div>
      <span>Crafted with <i class="fas fa-heart" style="color:var(--clr-gold)"></i> in Nairobi</span>
    </div>

  </div>
</footer>

<!-- WhatsApp Float -->
<div class="whatsapp-float">
  <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>?text=Hello%2C%20I%27m%20interested%20in%20your%20travel%20packages." target="_blank" rel="noopener">
    <i class="fab fa-whatsapp"></i>
    <span>Chat with Us</span>
  </a>
</div>

<!-- Back to Top -->
<button class="back-to-top" id="backToTop" aria-label="Back to top">
  <i class="fas fa-arrow-up"></i>
</button>

<!-- Scripts -->
<script>window.APP_URL = '<?= APP_URL ?>';</script>
<script src="<?= url('assets/js/main.js') ?>?v=<?= filemtime(APP_PATH . '/assets/js/main.js') ?>"></script>
<?php if (isset($extraJs)): foreach ($extraJs as $js): ?>
<script src="<?= h($js) ?>"></script>
<?php endforeach; endif; ?>
<?php if (!empty(GOOGLE_ANALYTICS)): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= h(GOOGLE_ANALYTICS) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ dataLayer.push(arguments); }
  gtag('js', new Date());
  gtag('config', '<?= h(GOOGLE_ANALYTICS) ?>');
</script>
<?php endif; ?>
</body>
</html>
