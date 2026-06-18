<?php
// =============================================================
// MT Safaris — Application Configuration
// =============================================================

define('APP_NAME',    'MT Safaris');
define('APP_TAGLINE', 'Discover Exceptional Travel Experiences Worldwide');
define('APP_VERSION', '1.0.0');
define('APP_ENV',     getenv('APP_ENV') ?: 'development');
define('APP_URL',     getenv('APP_URL') ?: 'http://localhost/Mtsafaris');
define('APP_PATH',    dirname(__DIR__));

// --- Database ---
define('DB_HOST',     getenv('DB_HOST') ?: 'localhost');
define('DB_PORT',     getenv('DB_PORT') ?: '3306');
define('DB_NAME',     getenv('DB_NAME') ?: 'mtsafaris');
define('DB_USER',     getenv('DB_USER') ?: 'root');
define('DB_PASS',     getenv('DB_PASS') ?: '');
define('DB_CHARSET',  'utf8mb4');

// --- Session ---
define('SESSION_NAME',     'mtsafaris_sess');
define('SESSION_LIFETIME', 7200);     // 2 hours
define('REMEMBER_DAYS',    30);

// --- File Uploads ---
define('UPLOAD_PATH',     APP_PATH . '/assets/uploads/');
define('UPLOAD_URL',      APP_URL  . '/assets/uploads/');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// --- Email ---
define('MAIL_FROM',      getenv('MAIL_FROM')      ?: 'noreply@mtsafaris.com');
define('MAIL_FROM_NAME', getenv('MAIL_FROM_NAME') ?: 'MT Safaris');
define('MAIL_SMTP_HOST', getenv('MAIL_SMTP_HOST') ?: '');
define('MAIL_SMTP_PORT', getenv('MAIL_SMTP_PORT') ?: 587);
define('MAIL_SMTP_USER', getenv('MAIL_SMTP_USER') ?: '');
define('MAIL_SMTP_PASS', getenv('MAIL_SMTP_PASS') ?: '');

// --- Payments ---
define('STRIPE_PUBLIC_KEY',  getenv('STRIPE_PUBLIC_KEY')  ?: '');
define('STRIPE_SECRET_KEY',  getenv('STRIPE_SECRET_KEY')  ?: '');
define('PAYPAL_CLIENT_ID',   getenv('PAYPAL_CLIENT_ID')   ?: '');
define('PAYPAL_SECRET',      getenv('PAYPAL_SECRET')      ?: '');
define('PAYPAL_SANDBOX',     true);

// --- API Keys ---
define('GOOGLE_MAPS_KEY',   getenv('GOOGLE_MAPS_KEY')   ?: '');
define('GOOGLE_ANALYTICS',  getenv('GOOGLE_ANALYTICS')  ?: '');
define('RECAPTCHA_SITE',    getenv('RECAPTCHA_SITE')    ?: '');
define('RECAPTCHA_SECRET',  getenv('RECAPTCHA_SECRET')  ?: '');

// --- Contact ---
define('CONTACT_EMAIL',    'info@mtsafaris.com');
define('CONTACT_PHONE',    '+254 700 000 000');
define('CONTACT_WHATSAPP', '+254700000000');
define('CONTACT_ADDRESS',  'Westlands, Nairobi, Kenya');

// --- Pagination ---
define('PER_PAGE',       12);
define('BLOG_PER_PAGE',  9);
define('ADMIN_PER_PAGE', 20);

// --- Tax ---
define('TAX_RATE',         16.0);   // percentage
define('BOOKING_DEPOSIT',  30.0);   // percentage

// --- Roles ---
define('ROLES', [
    'super_admin'      => 'Super Admin',
    'travel_manager'   => 'Travel Manager',
    'booking_officer'  => 'Booking Officer',
    'content_editor'   => 'Content Editor',
    'finance'          => 'Finance',
    'customer_support' => 'Customer Support',
    'customer'         => 'Customer',
]);

define('ADMIN_ROLES', ['super_admin', 'travel_manager', 'booking_officer', 'content_editor', 'finance', 'customer_support']);

// --- Error display ---
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// --- Timezone ---
date_default_timezone_set('Africa/Nairobi');

// --- Session setup ---
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name',            SESSION_NAME);
    ini_set('session.gc_maxlifetime',  SESSION_LIFETIME);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    if (APP_ENV === 'production') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}
