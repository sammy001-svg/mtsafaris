<?php
$pageTitle       = 'Privacy Policy | MT Safaris';
$pageDescription = 'How MT Safaris collects, uses, and protects your personal information.';
$headerClass     = 'solid';
require_once 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb"><a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Privacy Policy</span></div>
      <h1>Privacy <span style="color:var(--clr-gold)">Policy</span></h1>
      <p>Last updated: January 1, 2025</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="max-width:860px;margin:0 auto">
      <div style="background:#fff;border-radius:var(--radius-lg);padding:48px;box-shadow:var(--shadow-sm)">
        <?php $sections = [
          ['Information We Collect', 'We collect information you provide directly to us, including name, email address, phone number, passport details, and payment information when you make a booking. We also collect usage data through cookies and analytics tools to improve our services.'],
          ['How We Use Your Information', 'We use your information to process bookings and payments, send booking confirmations and travel documents, provide customer support, send marketing communications (with your consent), improve our website and services, and comply with legal obligations.'],
          ['Information Sharing', 'We share your information with airlines, hotels, and ground operators to fulfill your booking; payment processors (Stripe, PayPal) to process transactions; government authorities when required by law; and marketing partners only with your explicit consent.'],
          ['Data Security', 'We implement industry-standard security measures including SSL encryption, secure password hashing, and regular security audits. Payment details are processed through PCI-DSS compliant payment gateways. We never store full credit card numbers on our servers.'],
          ['Cookies', 'We use cookies for session management, remembering your preferences, and analytics. You can control cookies through your browser settings. Disabling cookies may affect some website functionality.'],
          ['Your Rights', 'You have the right to access, correct, or delete your personal data. You may opt out of marketing emails at any time using the unsubscribe link. To exercise your rights, contact us at '.CONTACT_EMAIL.'.'],
          ['Data Retention', 'We retain your data for as long as necessary to provide services and comply with legal obligations. Booking data is retained for 7 years for tax and legal purposes.'],
          ['Third-Party Links', 'Our website may contain links to third-party sites. We are not responsible for the privacy practices of these sites.'],
          ['Updates to This Policy', 'We may update this policy periodically. Significant changes will be communicated by email or prominent notice on our website.'],
          ['Contact Us', 'For privacy-related questions, contact our Data Protection Officer at '.CONTACT_EMAIL.' or by post at '.CONTACT_ADDRESS.'.'],
        ]; ?>
        <?php foreach ($sections as $i => [$title, $content]): ?>
        <div style="margin-bottom:32px;padding-bottom:32px;border-bottom:<?= $i<count($sections)-1?'1px solid var(--clr-border)':'none' ?>">
          <h2 style="color:var(--clr-primary);font-size:1.25rem;margin-bottom:12px"><?= $i+1 ?>. <?= $title ?></h2>
          <p style="color:#4b5563;line-height:1.8;margin:0"><?= $content ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
