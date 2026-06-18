<?php
$pageTitle       = 'Terms of Service | MT Safaris';
$pageDescription = 'Read MT Safaris terms and conditions of service for booking travel packages and using our platform.';
$headerClass     = 'solid';
require_once 'includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-content">
      <div class="breadcrumb"><a href="<?= url() ?>">Home</a><i class="fas fa-chevron-right"></i><span>Terms of Service</span></div>
      <h1>Terms of <span style="color:var(--clr-gold)">Service</span></h1>
      <p>Last updated: January 1, 2025</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div style="max-width:860px;margin:0 auto">
      <div style="background:#fff;border-radius:var(--radius-lg);padding:48px;box-shadow:var(--shadow-sm)">

        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:var(--radius);padding:16px 20px;margin-bottom:32px">
          <p style="margin:0;font-size:.875rem;color:#1e40af"><i class="fas fa-info-circle"></i> Please read these terms carefully before booking any travel package with MT Safaris. By making a booking, you agree to these terms.</p>
        </div>

        <?php $sections = [
          ['Booking & Confirmation', 'All bookings are subject to availability and will be confirmed only upon receipt of a deposit payment of '.BOOKING_DEPOSIT.'% of the total tour price. MT Safaris reserves the right to decline any booking at its discretion. A booking confirmation email will be sent within 24 hours of payment receipt.'],
          ['Payment Terms', 'A deposit of '.BOOKING_DEPOSIT.'% is required to secure your booking. The balance is due 30 days prior to departure. For bookings made within 30 days of departure, full payment is required at the time of booking. Payments can be made via credit/debit card (Stripe), PayPal, bank transfer, or mobile money.'],
          ['Cancellation & Refund Policy', 'Cancellations made 60+ days before departure: full refund minus processing fee. 30-59 days: 50% refund. 15-29 days: 25% refund. Less than 14 days: no refund. Refunds are processed within 14 business days to the original payment method.'],
          ['Amendments', 'Any changes to confirmed bookings are subject to availability and may incur amendment fees. Changes made within 30 days of departure may be treated as cancellations.'],
          ['Travel Insurance', 'We strongly recommend that all travelers obtain comprehensive travel insurance covering cancellation, medical emergencies, evacuation, and personal liability before departure. MT Safaris is not responsible for losses that could have been covered by travel insurance.'],
          ['Health & Safety', 'Travelers are responsible for ensuring they meet health and vaccination requirements for their destination. MT Safaris is not liable for any health-related issues arising from failure to comply with health requirements.'],
          ['Passports & Visas', 'It is the traveler\'s responsibility to ensure they hold valid travel documents. MT Safaris can assist with visa applications but is not liable for visa refusals.'],
          ['Liability', 'MT Safaris acts as an agent for transport, accommodation, and other service providers. Our liability is limited to the travel services we directly provide. We are not liable for acts of nature, government actions, civil unrest, or force majeure events.'],
          ['Photography & Privacy', 'MT Safaris may use photos/videos taken during tours for marketing purposes. Please inform your guide if you do not wish to be photographed. See our Privacy Policy for details on data handling.'],
          ['Governing Law', 'These terms are governed by the laws of Kenya. Any disputes shall be resolved under Kenyan jurisdiction.'],
        ]; ?>

        <?php foreach ($sections as $i => [$title, $content]): ?>
        <div style="margin-bottom:32px;padding-bottom:32px;border-bottom:<?= $i<count($sections)-1?'1px solid var(--clr-border)':'none' ?>">
          <h2 style="color:var(--clr-primary);font-size:1.25rem;margin-bottom:12px"><?= $i+1 ?>. <?= $title ?></h2>
          <p style="color:#4b5563;line-height:1.8;margin:0"><?= $content ?></p>
        </div>
        <?php endforeach; ?>

        <div style="background:var(--clr-primary);color:#fff;border-radius:var(--radius-lg);padding:24px;margin-top:8px;text-align:center">
          <p style="margin:0 0 12px;opacity:.9">Questions about our terms? We're here to help.</p>
          <a href="<?= url('contact.php') ?>" class="btn btn-gold">Contact Us</a>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
