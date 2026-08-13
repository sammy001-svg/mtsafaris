-- =============================================================
-- MT Safaris — reconcile settings keys with the admin Settings form
--
-- WHY THIS EXISTS
-- The seeded settings rows and the admin Settings form use different names
-- for the same values: the seed stores `site_email` while the form posts
-- `settings[contact_email]`. Saving in admin therefore created a second,
-- parallel row and left the original untouched, so anything reading the old
-- name kept showing the old value.
--
-- Each statement copies a legacy value onto the canonical key only when the
-- canonical row does not already exist. `key` is the PRIMARY KEY, so
-- ON DUPLICATE KEY UPDATE touches only `updated_at` with its own value,
-- making the copy a no-op when a value has already been saved through the
-- admin — admin input always wins and is never overwritten. The legacy row
-- is read through a derived table so `key`/`value` are unambiguous between
-- the insert target and the select source. Legacy rows are dropped after.
--
-- Safe to re-run: after the first pass no legacy keys remain.
-- =============================================================

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'contact_email', src.v, 'text', 'contact'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'site_email') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'contact_phone', src.v, 'text', 'contact'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'site_phone') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'contact_whatsapp', src.v, 'text', 'contact'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'site_whatsapp') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'contact_address', src.v, 'text', 'contact'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'site_address') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'currency', src.v, 'text', 'general'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'currency_default') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'ga_id', src.v, 'text', 'seo'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'google_analytics') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

INSERT INTO settings (`key`, `value`, `type`, `group`)
  SELECT 'stripe_pub_key', src.v, 'text', 'payments'
    FROM (SELECT `value` AS v FROM settings WHERE `key` = 'stripe_public_key') AS src
  ON DUPLICATE KEY UPDATE `updated_at` = settings.`updated_at`;

-- Remove the superseded legacy rows.
DELETE FROM settings WHERE `key` IN (
  'site_email','site_phone','site_whatsapp','site_address',
  'currency_default','google_analytics','stripe_public_key'
);

-- Verify: should report 0.
SELECT COUNT(*) AS legacy_keys_remaining FROM settings WHERE `key` IN (
  'site_email','site_phone','site_whatsapp','site_address',
  'currency_default','google_analytics','stripe_public_key'
);
