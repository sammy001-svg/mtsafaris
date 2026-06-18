<?php
$pageTitle       = 'Contact Us — MT Safaris';
$pageDescription = 'Get in touch with MT Safaris. Request a quote, book a consultation, or speak with our expert travel consultants. We\'re here 24/7.';
$headerClass     = 'solid';

require_once 'includes/header.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf()) {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $company     = trim($_POST['company'] ?? '');
    $type        = $_POST['type'] ?? 'general';
    $destination = trim($_POST['destination'] ?? '');
    $travel_date = $_POST['travel_date'] ?? null;
    $travelers   = (int)($_POST['travelers'] ?? 0);
    $budget      = trim($_POST['budget'] ?? '');
    $message     = trim($_POST['message'] ?? '');

    if (!$name || !$email || !$message) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        DB::insert('inquiries', [
            'type'        => $type,
            'name'        => $name,
            'email'       => $email,
            'phone'       => $phone,
            'company'     => $company,
            'destination' => $destination,
            'travel_date' => $travel_date ?: null,
            'travelers'   => $travelers ?: null,
            'budget'      => $budget,
            'message'     => $message,
        ]);
        $success = 'Thank you, ' . h($name) . '! Your message has been received. We\'ll be in touch within 24 hours.';
    }
}
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb">
        <a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Contact Us</span>
      </div>
      <h1>Let's Plan Your <span style="color:var(--clr-gold)">Dream Trip</span></h1>
      <p>Our expert consultants are ready to create an unforgettable travel experience just for you.</p>
    </div>
  </div>
</section>

<!-- Contact Cards -->
<section class="section-sm" style="background:var(--clr-light);border-bottom:1px solid var(--clr-border)">
  <div class="container">
    <div class="grid-4">
      <?php
      $contacts = [
        ['fas fa-phone-alt','Call Us',CONTACT_PHONE,'tel:'.CONTACT_PHONE,'Available 24/7'],
        ['fab fa-whatsapp','WhatsApp','Chat with us','https://wa.me/'.CONTACT_WHATSAPP,'Quick responses'],
        ['fas fa-envelope','Email Us',CONTACT_EMAIL,'mailto:'.CONTACT_EMAIL,'Reply within 2h'],
        ['fas fa-map-marker-alt','Visit Us',CONTACT_ADDRESS,'#map','Mon–Fri 8AM–6PM'],
      ];
      foreach ($contacts as $c): ?>
      <a href="<?= h($c[3]) ?>" class="contact-card" style="text-decoration:none">
        <div class="contact-icon"><i class="<?= $c[0] ?>"></i></div>
        <h4 style="font-size:.95rem;color:var(--clr-primary);margin-bottom:6px"><?= $c[1] ?></h4>
        <p style="font-size:.875rem;color:var(--clr-muted)"><?= h($c[2]) ?></p>
        <p style="font-size:.75rem;color:var(--clr-gold);margin-top:6px;font-weight:600"><?= $c[4] ?></p>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Contact Form & Map -->
<section class="section" id="quote">
  <div class="container">
    <div class="grid-2" style="gap:56px;align-items:start">

      <!-- Form -->
      <div data-animate>
        <span class="section-badge"><i class="fas fa-paper-plane" style="margin-right:5px"></i>Get in Touch</span>
        <h2 class="section-title" style="margin-top:12px">Send Us a <span>Message</span></h2>
        <p class="lead" style="margin-bottom:32px">Fill in the form below and we'll get back to you within 24 hours with a personalized travel proposal.</p>

        <?php if ($success): ?>
        <div class="flash-msg flash-success"><i class="fas fa-check-circle"></i><span><?= $success ?></span></div>
        <?php elseif ($error): ?>
        <div class="flash-msg flash-error"><i class="fas fa-exclamation-circle"></i><span><?= h($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="contact.php#quote">
          <?= csrfField() ?>

          <!-- Inquiry Type -->
          <div class="form-group">
            <label>Inquiry Type</label>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <?php foreach (['general'=>'General','quote'=>'Get Quote','corporate'=>'Corporate','package'=>'Package Inquiry','callback'=>'Request Callback'] as $val=>$label): ?>
              <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;cursor:pointer;padding:8px 14px;border:2px solid var(--clr-border);border-radius:20px;transition:all .2s"
                     onclick="this.parentElement.querySelectorAll('label').forEach(l=>l.style.background='');l.parentElement.querySelectorAll('label').forEach(l=>l.style.borderColor='var(--clr-border)');this.style.background='rgba(13,59,102,.08)';this.style.borderColor='var(--clr-primary)'">
                <input type="radio" name="type" value="<?= $val ?>" <?= ($val==='general')?'checked':'' ?> style="display:none">
                <?= $label ?>
              </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Your Name <span class="required">*</span></label>
              <input type="text" name="name" class="form-control" required placeholder="John Kamau" value="<?= h($_POST['name']??'') ?>">
            </div>
            <div class="form-group">
              <label>Email Address <span class="required">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="john@company.com" value="<?= h($_POST['email']??'') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Phone Number</label>
              <input type="tel" name="phone" class="form-control" placeholder="+254 700 000 000" value="<?= h($_POST['phone']??'') ?>">
            </div>
            <div class="form-group">
              <label>Company / Organization</label>
              <input type="text" name="company" class="form-control" placeholder="Company Name" value="<?= h($_POST['company']??'') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Preferred Destination</label>
              <input type="text" name="destination" class="form-control" placeholder="e.g., Masai Mara, Kenya" value="<?= h($_POST['destination']??'') ?>">
            </div>
            <div class="form-group">
              <label>Travel Date</label>
              <input type="date" name="travel_date" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= h($_POST['travel_date']??'') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Number of Travelers</label>
              <select name="travelers" class="form-control">
                <option value="">Select</option>
                <option value="1">1 Person</option>
                <option value="2">2 People</option>
                <option value="3">3–5 People</option>
                <option value="6">6–10 People</option>
                <option value="11">10+ People</option>
              </select>
            </div>
            <div class="form-group">
              <label>Budget Range (USD)</label>
              <select name="budget" class="form-control">
                <option value="">Any Budget</option>
                <option value="Under $1,000">Under $1,000</option>
                <option value="$1,000 – $2,500">$1,000 – $2,500</option>
                <option value="$2,500 – $5,000">$2,500 – $5,000</option>
                <option value="$5,000 – $10,000">$5,000 – $10,000</option>
                <option value="$10,000+">$10,000+</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Your Message <span class="required">*</span></label>
            <textarea name="message" class="form-control" rows="5" required
                      placeholder="Tell us about your dream trip — destination, duration, special requirements, accommodation preferences…"><?= h($_POST['message']??'') ?></textarea>
          </div>

          <button type="submit" class="btn btn-primary btn-lg btn-block">
            <i class="fas fa-paper-plane"></i> Send Message
          </button>
        </form>
      </div>

      <!-- Map & Info -->
      <div data-animate data-delay="150">
        <!-- Map -->
        <div style="background:var(--clr-light);border-radius:16px;overflow:hidden;height:360px;margin-bottom:32px;border:1px solid var(--clr-border)" id="map">
          <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3988.774!2d36.7819!3d-1.2579!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zV2VzdGxhbmRzLCBOYWlyb2Jp!5e0!3m2!1sen!2ske!4v0"
            width="100%" height="100%" style="border:0" allowfullscreen loading="lazy"></iframe>
        </div>

        <!-- Office Info -->
        <div class="card">
          <div class="card-header"><h3>Our Office</h3></div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:16px">
              <div style="display:flex;gap:14px;align-items:start">
                <i class="fas fa-map-marker-alt" style="color:var(--clr-gold);font-size:1.1rem;margin-top:2px;width:20px"></i>
                <div>
                  <div style="font-weight:600;color:var(--clr-primary);margin-bottom:2px">Nairobi Head Office</div>
                  <div style="font-size:.875rem;color:var(--clr-muted)"><?= CONTACT_ADDRESS ?></div>
                </div>
              </div>
              <div style="display:flex;gap:14px;align-items:center">
                <i class="fas fa-phone-alt" style="color:var(--clr-gold);font-size:1.1rem;width:20px"></i>
                <a href="tel:<?= CONTACT_PHONE ?>" style="color:var(--clr-text);font-size:.875rem"><?= CONTACT_PHONE ?></a>
              </div>
              <div style="display:flex;gap:14px;align-items:center">
                <i class="fas fa-envelope" style="color:var(--clr-gold);font-size:1.1rem;width:20px"></i>
                <a href="mailto:<?= CONTACT_EMAIL ?>" style="color:var(--clr-text);font-size:.875rem"><?= CONTACT_EMAIL ?></a>
              </div>
              <div style="display:flex;gap:14px;align-items:center">
                <i class="far fa-clock" style="color:var(--clr-gold);font-size:1.1rem;width:20px"></i>
                <div style="font-size:.875rem;color:var(--clr-muted)">Monday – Friday: 8:00 AM – 6:00 PM EAT<br>Saturday: 9:00 AM – 2:00 PM EAT</div>
              </div>
            </div>
          </div>
        </div>

        <div style="background:linear-gradient(135deg,var(--clr-primary),var(--clr-primary-l));border-radius:16px;padding:24px;margin-top:16px;text-align:center">
          <i class="fab fa-whatsapp" style="font-size:2.2rem;color:#25D366;display:block;margin-bottom:12px"></i>
          <h4 style="color:#fff;margin-bottom:8px">Chat with Us on WhatsApp</h4>
          <p style="color:rgba(255,255,255,.75);font-size:.875rem;margin-bottom:16px">Get instant responses from our travel experts</p>
          <a href="https://wa.me/<?= CONTACT_WHATSAPP ?>?text=Hello!+I%27d+like+to+plan+a+trip+with+MT+Safaris." target="_blank" class="btn btn-gold">
            <i class="fab fa-whatsapp"></i> Start WhatsApp Chat
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- FAQ Strip -->
<section class="section-sm" style="background:var(--clr-light)">
  <div class="container">
    <h3 style="color:var(--clr-primary);margin-bottom:24px;text-align:center">Quick Answers</h3>
    <div style="max-width:720px;margin:0 auto">
      <?php
      $faqs = DB::rows("SELECT * FROM faqs WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
      foreach ($faqs as $faq): ?>
      <div class="accordion-item">
        <div class="accordion-header"><?= h($faq['question']) ?> <i class="fas fa-chevron-down"></i></div>
        <div class="accordion-body"><p><?= h($faq['answer']) ?></p></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
