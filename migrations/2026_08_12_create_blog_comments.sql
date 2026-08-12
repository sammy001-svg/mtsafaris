-- ---------------------------------------------------------------------------
-- Creates the `blog_comments` table. blog-detail.php reads it on every page
-- load (line 14) and writes to it on comment submission (line 29), but the
-- table was never in database.sql — so every public blog post page returns
-- HTTP 500 with:
--   SQLSTATE[42S02]: Base table or view not found: 1146 Table 'blog_comments'
--
-- Columns match exactly what blog-detail.php reads and writes: name, email,
-- body, status, created_at, keyed on post_id.
-- Run once per database (local, staging, production).
-- ---------------------------------------------------------------------------

CREATE TABLE `blog_comments` (
  `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id`    INT UNSIGNED NOT NULL,
  `user_id`    INT UNSIGNED,
  `name`       VARCHAR(150) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `body`       TEXT NOT NULL,
  `status`     ENUM('pending','approved','spam') NOT NULL DEFAULT 'pending',
  `ip_address` VARCHAR(50),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`)      ON DELETE SET NULL,
  INDEX `idx_post_status` (`post_id`, `status`)
) ENGINE=InnoDB;
