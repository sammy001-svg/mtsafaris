-- =============================================================
-- MT SAFARIS - Tour & Travel Management System
-- Database Schema - MySQL 8.0+
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

CREATE DATABASE IF NOT EXISTS `mtsafaris` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mtsafaris`;

-- =============================================================
-- USERS & AUTHENTICATION
-- =============================================================

CREATE TABLE `users` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `uuid`            CHAR(36) NOT NULL UNIQUE DEFAULT (UUID()),
  `first_name`      VARCHAR(100) NOT NULL,
  `last_name`       VARCHAR(100) NOT NULL,
  `email`           VARCHAR(191) NOT NULL UNIQUE,
  `phone`           VARCHAR(30),
  `password_hash`   VARCHAR(255) NOT NULL,
  `role`            ENUM('super_admin','travel_manager','booking_officer','content_editor','finance','customer_support','customer') NOT NULL DEFAULT 'customer',
  `status`          ENUM('active','inactive','suspended','pending') NOT NULL DEFAULT 'pending',
  `avatar`          VARCHAR(500),
  `email_verified`  TINYINT(1) NOT NULL DEFAULT 0,
  `email_verify_token` VARCHAR(100),
  `two_fa_enabled`  TINYINT(1) NOT NULL DEFAULT 0,
  `two_fa_secret`   VARCHAR(100),
  `remember_token`  VARCHAR(100),
  `last_login_at`   DATETIME,
  `last_login_ip`   VARCHAR(50),
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_email`  (`email`),
  INDEX `idx_role`   (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB;

CREATE TABLE `password_resets` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`      VARCHAR(191) NOT NULL,
  `token`      VARCHAR(100) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used`       TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_email` (`email`),
  INDEX `idx_token` (`token`)
) ENGINE=InnoDB;

CREATE TABLE `user_sessions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `token`      VARCHAR(255) NOT NULL UNIQUE,
  `ip_address` VARCHAR(50),
  `user_agent` VARCHAR(500),
  `expires_at` DATETIME NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `user_documents` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED NOT NULL,
  `type`        ENUM('passport','national_id','visa','insurance','other') NOT NULL,
  `title`       VARCHAR(200) NOT NULL,
  `file_path`   VARCHAR(500) NOT NULL,
  `expiry_date` DATE,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- DESTINATIONS
-- =============================================================

CREATE TABLE `regions` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `destinations` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `region_id`       INT UNSIGNED,
  `name`            VARCHAR(150) NOT NULL,
  `slug`            VARCHAR(180) NOT NULL UNIQUE,
  `country`         VARCHAR(100) NOT NULL,
  `continent`       VARCHAR(60),
  `description`     TEXT,
  `highlights`      JSON,
  `climate_info`    TEXT,
  `best_time`       VARCHAR(200),
  `hero_image`      VARCHAR(500),
  `gallery`         JSON,
  `latitude`        DECIMAL(10,7),
  `longitude`       DECIMAL(10,7),
  `is_featured`     TINYINT(1) NOT NULL DEFAULT 0,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `meta_title`      VARCHAR(200),
  `meta_description` VARCHAR(400),
  `sort_order`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`region_id`) REFERENCES `regions`(`id`) ON DELETE SET NULL,
  INDEX `idx_country`     (`country`),
  INDEX `idx_is_featured` (`is_featured`),
  INDEX `idx_is_active`   (`is_active`)
) ENGINE=InnoDB;

-- =============================================================
-- TOUR PACKAGES
-- =============================================================

CREATE TABLE `categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `icon`       VARCHAR(100),
  `image`      VARCHAR(500),
  `color`      VARCHAR(20),
  `description` TEXT,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `packages` (
  `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`         INT UNSIGNED,
  `destination_id`      INT UNSIGNED,
  `title`               VARCHAR(250) NOT NULL,
  `slug`                VARCHAR(280) NOT NULL UNIQUE,
  `tagline`             VARCHAR(300),
  `description`         TEXT NOT NULL,
  `overview`            LONGTEXT,
  `itinerary`           JSON,
  `included`            JSON,
  `excluded`            JSON,
  `hero_image`          VARCHAR(500),
  `gallery`             JSON,
  `duration_days`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `duration_nights`     TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `min_pax`             TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `max_pax`             TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `base_price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sale_price`          DECIMAL(10,2),
  `price_per`           ENUM('person','group','package') NOT NULL DEFAULT 'person',
  `currency`            CHAR(3) NOT NULL DEFAULT 'USD',
  `difficulty`          ENUM('easy','moderate','challenging','extreme') DEFAULT 'easy',
  `type`                ENUM('corporate','holiday','honeymoon','group','educational','luxury','adventure','safari','religious','custom') NOT NULL DEFAULT 'holiday',
  `accommodation_level` ENUM('budget','standard','superior','luxury','ultra_luxury') DEFAULT 'standard',
  `departure_city`      VARCHAR(150),
  `departure_dates`     JSON,
  `hotels`              JSON,
  `faqs`                JSON,
  `map_embed`           TEXT,
  `video_url`           VARCHAR(500),
  `is_featured`         TINYINT(1) NOT NULL DEFAULT 0,
  `is_active`           TINYINT(1) NOT NULL DEFAULT 1,
  `availability`        TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `seats_left`          TINYINT UNSIGNED,
  `rating`              DECIMAL(3,2) DEFAULT 0.00,
  `review_count`        SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `booking_count`       INT UNSIGNED NOT NULL DEFAULT 0,
  `sort_order`          SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `meta_title`          VARCHAR(200),
  `meta_description`    VARCHAR(400),
  `meta_keywords`       VARCHAR(300),
  `created_by`          INT UNSIGNED,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`)    REFERENCES `categories`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`)  ON DELETE SET NULL,
  FOREIGN KEY (`created_by`)     REFERENCES `users`(`id`)         ON DELETE SET NULL,
  INDEX `idx_type`        (`type`),
  INDEX `idx_is_featured` (`is_featured`),
  INDEX `idx_is_active`   (`is_active`),
  INDEX `idx_base_price`  (`base_price`),
  FULLTEXT `ft_search`    (`title`, `tagline`, `description`)
) ENGINE=InnoDB;

CREATE TABLE `package_addons` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `package_id`  INT UNSIGNED NOT NULL,
  `name`        VARCHAR(200) NOT NULL,
  `description` TEXT,
  `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `package_pricing` (
  `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `package_id`   INT UNSIGNED NOT NULL,
  `label`        VARCHAR(100) NOT NULL,
  `pax_from`     TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `pax_to`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `price`        DECIMAL(10,2) NOT NULL,
  `season`       ENUM('low','mid','high','peak') DEFAULT 'mid',
  `valid_from`   DATE,
  `valid_to`     DATE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- BOOKINGS
-- =============================================================

CREATE TABLE `bookings` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `reference`       VARCHAR(20) NOT NULL UNIQUE,
  `user_id`         INT UNSIGNED,
  `package_id`      INT UNSIGNED NOT NULL,
  `status`          ENUM('pending','confirmed','paid','cancelled','completed','refunded') NOT NULL DEFAULT 'pending',
  `travel_date`     DATE NOT NULL,
  `return_date`     DATE,
  `adults`          TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `children`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `infants`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `special_requests` TEXT,
  `lead_traveler`   JSON NOT NULL,
  `travelers`       JSON,
  `addons`          JSON,
  `subtotal`        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount`    DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `currency`        CHAR(3) NOT NULL DEFAULT 'USD',
  `coupon_code`     VARCHAR(50),
  `notes`           TEXT,
  `cancel_reason`   TEXT,
  `cancelled_at`    DATETIME,
  `confirmed_at`    DATETIME,
  `completed_at`    DATETIME,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE RESTRICT,
  INDEX `idx_reference` (`reference`),
  INDEX `idx_status`    (`status`),
  INDEX `idx_travel_date` (`travel_date`)
) ENGINE=InnoDB;

CREATE TABLE `payments` (
  `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `booking_id`     INT UNSIGNED NOT NULL,
  `gateway`        ENUM('stripe','paypal','bank_transfer','mobile_money','cash') NOT NULL,
  `gateway_txn_id` VARCHAR(200),
  `amount`         DECIMAL(10,2) NOT NULL,
  `currency`       CHAR(3) NOT NULL DEFAULT 'USD',
  `status`         ENUM('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `type`           ENUM('deposit','full','refund') NOT NULL DEFAULT 'full',
  `metadata`       JSON,
  `paid_at`        DATETIME,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `coupons` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `code`            VARCHAR(50) NOT NULL UNIQUE,
  `type`            ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
  `value`           DECIMAL(10,2) NOT NULL,
  `min_order`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `max_discount`    DECIMAL(10,2),
  `usage_limit`     INT UNSIGNED,
  `used_count`      INT UNSIGNED NOT NULL DEFAULT 0,
  `valid_from`      DATE,
  `valid_to`        DATE,
  `is_active`       TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =============================================================
-- REVIEWS
-- =============================================================

CREATE TABLE `reviews` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `package_id`  INT UNSIGNED NOT NULL,
  `booking_id`  INT UNSIGNED,
  `user_id`     INT UNSIGNED,
  `name`        VARCHAR(150) NOT NULL,
  `email`       VARCHAR(191),
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `title`       VARCHAR(250),
  `body`        TEXT NOT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `reply`       TEXT,
  `replied_at`  DATETIME,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- BLOG
-- =============================================================

CREATE TABLE `blog_categories` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`       VARCHAR(100) NOT NULL,
  `slug`       VARCHAR(120) NOT NULL UNIQUE,
  `description` TEXT,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE `blog_posts` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id`     INT UNSIGNED,
  `author_id`       INT UNSIGNED,
  `title`           VARCHAR(300) NOT NULL,
  `slug`            VARCHAR(320) NOT NULL UNIQUE,
  `excerpt`         TEXT,
  `body`            LONGTEXT NOT NULL,
  `featured_image`  VARCHAR(500),
  `gallery`         JSON,
  `tags`            JSON,
  `status`          ENUM('draft','published','scheduled','archived') NOT NULL DEFAULT 'draft',
  `is_featured`     TINYINT(1) NOT NULL DEFAULT 0,
  `view_count`      INT UNSIGNED NOT NULL DEFAULT 0,
  `published_at`    DATETIME,
  `meta_title`      VARCHAR(200),
  `meta_description` VARCHAR(400),
  `meta_keywords`   VARCHAR(300),
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`author_id`)   REFERENCES `users`(`id`)           ON DELETE SET NULL,
  FULLTEXT `ft_blog` (`title`, `excerpt`, `body`)
) ENGINE=InnoDB;

-- =============================================================
-- WISHLIST & NOTIFICATIONS
-- =============================================================

CREATE TABLE `wishlists` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `package_id` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_user_package` (`user_id`, `package_id`),
  FOREIGN KEY (`user_id`)    REFERENCES `users`(`id`)    ON DELETE CASCADE,
  FOREIGN KEY (`package_id`) REFERENCES `packages`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `notifications` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `type`       VARCHAR(80) NOT NULL,
  `title`      VARCHAR(250) NOT NULL,
  `body`       TEXT,
  `url`        VARCHAR(500),
  `is_read`    TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_user_unread` (`user_id`, `is_read`)
) ENGINE=InnoDB;

-- =============================================================
-- INQUIRIES & LEADS
-- =============================================================

CREATE TABLE `inquiries` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `type`        ENUM('general','quote','corporate','package','callback') NOT NULL DEFAULT 'general',
  `name`        VARCHAR(200) NOT NULL,
  `email`       VARCHAR(191) NOT NULL,
  `phone`       VARCHAR(30),
  `company`     VARCHAR(200),
  `destination` VARCHAR(200),
  `travel_date` DATE,
  `travelers`   TINYINT UNSIGNED,
  `budget`      VARCHAR(100),
  `message`     TEXT NOT NULL,
  `package_id`  INT UNSIGNED,
  `status`      ENUM('new','contacted','in_progress','converted','closed') NOT NULL DEFAULT 'new',
  `assigned_to` INT UNSIGNED,
  `notes`       TEXT,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`package_id`)  REFERENCES `packages`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- CONTENT MANAGEMENT
-- =============================================================

CREATE TABLE `testimonials` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(150) NOT NULL,
  `position`    VARCHAR(150),
  `company`     VARCHAR(150),
  `avatar`      VARCHAR(500),
  `body`        TEXT NOT NULL,
  `rating`      TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `faqs` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category`   VARCHAR(80) NOT NULL DEFAULT 'general',
  `question`   VARCHAR(500) NOT NULL,
  `answer`     TEXT NOT NULL,
  `is_active`  TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `banners` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title`       VARCHAR(250) NOT NULL,
  `subtitle`    VARCHAR(400),
  `image`       VARCHAR(500) NOT NULL,
  `video_url`   VARCHAR(500),
  `cta_text`    VARCHAR(100),
  `cta_url`     VARCHAR(500),
  `position`    VARCHAR(80) NOT NULL DEFAULT 'home_hero',
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  TINYINT UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE `newsletter_subscribers` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `email`       VARCHAR(191) NOT NULL UNIQUE,
  `name`        VARCHAR(150),
  `is_active`   TINYINT(1) NOT NULL DEFAULT 1,
  `token`       VARCHAR(100),
  `subscribed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `settings` (
  `key`        VARCHAR(100) PRIMARY KEY,
  `value`      TEXT,
  `type`       VARCHAR(40) NOT NULL DEFAULT 'text',
  `group`      VARCHAR(60) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE `audit_logs` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`     INT UNSIGNED,
  `action`      VARCHAR(100) NOT NULL,
  `model`       VARCHAR(80),
  `model_id`    INT UNSIGNED,
  `old_values`  JSON,
  `new_values`  JSON,
  `ip_address`  VARCHAR(50),
  `user_agent`  VARCHAR(500),
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_id` (`user_id`),
  INDEX `idx_action`  (`action`)
) ENGINE=InnoDB;

CREATE TABLE `email_campaigns` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name`        VARCHAR(200) NOT NULL,
  `subject`     VARCHAR(300) NOT NULL,
  `body`        LONGTEXT NOT NULL,
  `status`      ENUM('draft','scheduled','sending','sent') NOT NULL DEFAULT 'draft',
  `sent_to`     INT UNSIGNED NOT NULL DEFAULT 0,
  `opened`      INT UNSIGNED NOT NULL DEFAULT 0,
  `clicked`     INT UNSIGNED NOT NULL DEFAULT 0,
  `scheduled_at` DATETIME,
  `sent_at`     DATETIME,
  `created_by`  INT UNSIGNED,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =============================================================
-- SEED DATA
-- =============================================================

INSERT INTO `regions` (`name`, `slug`, `sort_order`) VALUES
('Africa', 'africa', 1),
('Middle East', 'middle-east', 2),
('Europe', 'europe', 3),
('Asia', 'asia', 4),
('Americas', 'americas', 5),
('Indian Ocean Islands', 'indian-ocean', 6);

INSERT INTO `categories` (`name`, `slug`, `icon`, `color`, `description`, `sort_order`) VALUES
('Corporate Travel',    'corporate-travel',    'fa-briefcase',      '#0D3B66', 'Premium corporate travel solutions for businesses', 1),
('Holiday Packages',   'holiday-packages',    'fa-umbrella-beach', '#D4A017', 'Relaxing holiday getaways for families and groups', 2),
('Honeymoon',          'honeymoon',           'fa-heart',          '#E91E8C', 'Romantic escapes for newlyweds', 3),
('Group Tours',        'group-tours',         'fa-users',          '#3BAFDA', 'Exciting tours for large groups', 4),
('Educational Tours',  'educational-tours',   'fa-graduation-cap', '#4CAF50', 'Educational travel experiences', 5),
('Luxury Tours',       'luxury-tours',        'fa-gem',            '#9C27B0', 'Ultra-premium luxury travel experiences', 6),
('Adventure',          'adventure',           'fa-mountain',       '#FF5722', 'Thrilling adventure and outdoor activities', 7),
('Safari',             'safari',              'fa-paw',            '#8D6E63', 'Wildlife safaris in East Africa', 8),
('Religious Tours',    'religious-tours',     'fa-mosque',         '#607D8B', 'Spiritual pilgrimage and religious journeys', 9),
('Custom Packages',    'custom-packages',     'fa-magic',          '#795548', 'Tailor-made travel experiences', 10);

INSERT INTO `destinations` (`region_id`, `name`, `slug`, `country`, `continent`, `description`, `best_time`, `is_featured`, `sort_order`) VALUES
(1, 'Masai Mara', 'masai-mara', 'Kenya', 'Africa', 'Home to the greatest wildlife spectacle on Earth — the Great Migration. Witness millions of wildebeest cross the Mara River.', 'July – October', 1, 1),
(1, 'Serengeti', 'serengeti', 'Tanzania', 'Africa', 'Iconic savanna plains teeming with the Big Five and millions of migratory animals in a stunning landscape.', 'June – October', 1, 2),
(1, 'Zanzibar', 'zanzibar', 'Tanzania', 'Africa', 'Spice Island paradise with pristine white beaches, turquoise waters, and rich Swahili culture.', 'June – October', 1, 3),
(1, 'Amboseli', 'amboseli', 'Kenya', 'Africa', 'Stunning views of Mount Kilimanjaro and large elephant herds roaming the open plains.', 'June – October', 1, 4),
(1, 'Kilimanjaro', 'kilimanjaro', 'Tanzania', 'Africa', 'Africa''s highest peak — a legendary trekking destination with breathtaking views and diverse ecosystems.', 'January – March, June – October', 1, 5),
(6, 'Maldives', 'maldives', 'Maldives', 'Asia', 'Overwater bungalows, crystal-clear lagoons, and coral reefs make this a paradise for honeymooners and divers.', 'November – April', 1, 6),
(2, 'Dubai', 'dubai', 'UAE', 'Middle East', 'A glittering desert metropolis of futuristic architecture, luxury shopping, and world-class hospitality.', 'November – April', 1, 7),
(3, 'Paris', 'paris', 'France', 'Europe', 'The City of Light offers iconic landmarks, world-class cuisine, and timeless art for an unforgettable European experience.', 'April – June, September – October', 1, 8);

INSERT INTO `packages` (`category_id`, `destination_id`, `title`, `slug`, `tagline`, `description`, `duration_days`, `duration_nights`, `min_pax`, `max_pax`, `base_price`, `type`, `is_featured`, `rating`, `review_count`) VALUES
(8, 1, 'Masai Mara Classic Safari', 'masai-mara-classic-safari', 'Witness the Great Migration up close', 'Experience the world''s greatest wildlife spectacle with expert guides, luxury tented camps, and unforgettable game drives across the iconic Masai Mara.', 5, 4, 2, 12, 1299.00, 'safari', 1, 4.9, 124),
(8, 2, 'Serengeti & Ngorongoro Explorer', 'serengeti-ngorongoro-explorer', 'Tanzania''s crown jewels in one epic journey', 'Journey through Tanzania''s most iconic wildlife destinations — the vast Serengeti plains and the incredible Ngorongoro Crater — teeming with the Big Five.', 7, 6, 2, 10, 1899.00, 'safari', 1, 4.8, 98),
(3, 3, 'Zanzibar Romantic Escape', 'zanzibar-romantic-escape', 'A luxurious island honeymoon', 'Unwind on pristine white-sand beaches, explore Stone Town''s historic alleys, and enjoy candlelit dinners with ocean views in this tropical paradise.', 6, 5, 2, 2, 1499.00, 'honeymoon', 1, 4.9, 87),
(6, 6, 'Maldives Luxury Overwater Retreat', 'maldives-luxury-overwater-retreat', 'Ultimate island luxury experience', 'Stay in exclusive overwater bungalows, dive into vibrant coral reefs, indulge in spa treatments and gourmet dining in this pristine island paradise.', 8, 7, 2, 4, 3499.00, 'luxury', 1, 5.0, 62),
(7, 5, 'Kilimanjaro Summit Challenge', 'kilimanjaro-summit-challenge', 'Conquer Africa''s Roof', 'Trek through five ecological zones to the summit of Mount Kilimanjaro — Africa''s highest peak at 5,895m — with expert mountain guides and full support team.', 9, 8, 2, 8, 2199.00, 'adventure', 1, 4.7, 156),
(1, 7, 'Dubai Corporate Retreat', 'dubai-corporate-retreat', 'Elevate your team in Dubai', 'An all-inclusive corporate retreat package featuring conference facilities, team-building activities, luxury hotel accommodation, and Dubai city experiences.', 4, 3, 10, 50, 2499.00, 'corporate', 1, 4.8, 45);

INSERT INTO `testimonials` (`name`, `position`, `company`, `body`, `rating`, `sort_order`) VALUES
('Sarah Johnson', 'Marketing Director', 'TechCorp Africa', 'MT Safaris organized our entire corporate retreat to the Masai Mara. From logistics to accommodation, everything was flawless. Our team is still talking about it!', 5, 1),
('David Kamau', 'CEO', 'Eastlands Holdings', 'The Serengeti experience exceeded every expectation. Professional guides, stunning camps, and moments I''ll never forget. MT Safaris truly delivers excellence.', 5, 2),
('Emily Watson', 'Honeymooner', 'London, UK', 'Our Zanzibar honeymoon was absolutely magical. Every detail was thoughtfully arranged — the beach dinners, snorkeling, and the boutique hotel were beyond perfect.', 5, 3),
('Mohammed Al-Rashid', 'Travel Manager', 'Gulf Enterprises', 'We use MT Safaris for all our executive travel needs across East Africa. Their attention to detail, reliability, and network of contacts is unmatched in the region.', 5, 4),
('Anne Wanjiku', 'Adventure Traveler', 'Nairobi, Kenya', 'Summiting Kilimanjaro with MT Safaris was the most incredible adventure of my life. The guides were expert, motivating, and ensured our safety throughout.', 5, 5),
('Robert Chen', 'Family Traveler', 'Singapore', 'Our family holiday package to Kenya was incredible. Kids loved the game drives, and the team were so attentive to our needs. Will definitely book again!', 5, 6);

INSERT INTO `blog_categories` (`name`, `slug`, `description`) VALUES
('Travel Guides',       'travel-guides',        'Comprehensive guides to destinations around the world'),
('Corporate Travel',    'corporate-travel',      'Tips and insights for business travelers'),
('Wildlife & Safari',   'wildlife-safari',       'Wildlife encounters and safari experiences'),
('Destination Spotlights', 'destination-spotlights', 'In-depth looks at our top destinations'),
('Travel Tips',         'travel-tips',           'Practical travel advice and hacks'),
('Company News',        'company-news',          'Updates and news from MT Safaris');

INSERT INTO `faqs` (`category`, `question`, `answer`, `sort_order`) VALUES
('booking', 'How do I book a tour package?', 'Simply browse our packages, select your preferred tour, choose your travel dates and number of travelers, then follow the booking steps. You can also contact us directly for assistance.', 1),
('booking', 'Can I customize a package?', 'Yes! We specialize in tailor-made travel. Contact our consultants with your requirements and we will design a custom itinerary just for you.', 2),
('payment', 'What payment methods do you accept?', 'We accept Stripe, PayPal, bank transfer, and mobile money. A deposit may be required to confirm your booking.', 3),
('payment', 'What is your cancellation and refund policy?', 'Cancellations made 30+ days before departure receive a full refund minus processing fees. 15-29 days: 50% refund. Under 14 days: no refund. Travel insurance is strongly recommended.', 4),
('travel', 'Do you assist with visa applications?', 'Yes, we provide visa guidance and documentation support for all our destinations. Our team is well-versed in East Africa and international visa requirements.', 5),
('travel', 'Is travel insurance included?', 'Travel insurance is not included by default but is highly recommended. We partner with leading insurers and can arrange comprehensive coverage for you.', 6),
('safari', 'What is the best time for a Masai Mara safari?', 'The Great Migration typically occurs from July to October, making this the peak safari season. However, the Masai Mara offers excellent wildlife viewing year-round.', 7),
('safari', 'What should I pack for a safari?', 'Pack neutral-colored clothing, comfortable walking shoes, sunscreen, insect repellent, binoculars, a camera with zoom lens, and any personal medications. We provide a detailed packing list upon booking.', 8);

INSERT INTO `settings` (`key`, `value`, `type`, `group`) VALUES
('site_name',         'MT Safaris',                'text',  'general'),
('site_tagline',      'Discover Exceptional Travel Experiences Worldwide', 'text', 'general'),
('site_email',        'info@mtsafaris.com',         'text',  'general'),
('site_phone',        '+254 700 000 000',           'text',  'general'),
('site_whatsapp',     '+254700000000',              'text',  'general'),
('site_address',      'Westlands, Nairobi, Kenya',  'text',  'general'),
('currency_default',  'USD',                        'text',  'payment'),
('tax_rate',          '16',                         'number','payment'),
('booking_deposit',   '30',                         'number','payment'),
('smtp_host',         '',                           'text',  'email'),
('smtp_port',         '587',                        'number','email'),
('google_maps_key',   '',                           'text',  'integrations'),
('google_analytics',  '',                           'text',  'integrations'),
('stripe_public_key', '',                           'text',  'payment'),
('paypal_client_id',  '',                           'text',  'payment');

-- Admin super user (password: Admin@2024!)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `password_hash`, `role`, `status`, `email_verified`) VALUES
('Super', 'Admin', 'admin@mtsafaris.com', '$2y$12$EFyXllxaAslvRx8gytzHvOYOFghnvKKvzpBq3kBfElTQFy05esaUq', 'super_admin', 'active', 1),
('Travel', 'Manager', 'manager@mtsafaris.com', '$2y$12$EFyXllxaAslvRx8gytzHvOYOFghnvKKvzpBq3kBfElTQFy05esaUq', 'travel_manager', 'active', 1);

SET FOREIGN_KEY_CHECKS = 1;
