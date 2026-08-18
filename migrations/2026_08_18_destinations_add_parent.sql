-- ---------------------------------------------------------------------------
-- Adds parent_id / group_label to `destinations` so a country can own its
-- sub-destinations (Kenya -> Maasai Mara, Tanzania -> Serengeti, ...).
--
--   parent_id IS NULL  => top-level destination
--   parent_id = <id>   => sub-destination of that row
--   group_label        => heading it sits under ("Safari", "Beach Holidays")
--
-- Each change is applied only if missing, so this is safe to re-run and safe
-- on a database where the columns were already added. A plain ALTER aborts
-- with "Duplicate column name 'parent_id'" on the second run.
--
-- No DELIMITER or stored procedure is used: DELIMITER is a client-side
-- directive that not every import tool honours.
--
-- NOTE: 2026_08_18_seed_destinations.sql performs the same guarded check
-- itself, so importing the seed alone is sufficient. This file remains for
-- applying the schema change on its own.
-- ---------------------------------------------------------------------------

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'destinations'
             AND COLUMN_NAME = 'parent_id');
SET @s := IF(@c = 0,
    'ALTER TABLE `destinations` ADD COLUMN `parent_id` INT UNSIGNED NULL AFTER `region_id`',
    'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'destinations'
             AND COLUMN_NAME = 'group_label');
SET @s := IF(@c = 0,
    'ALTER TABLE `destinations` ADD COLUMN `group_label` VARCHAR(80) NULL AFTER `parent_id`',
    'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM information_schema.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'destinations'
             AND INDEX_NAME = 'idx_dest_parent');
SET @s := IF(@c = 0,
    'ALTER TABLE `destinations` ADD INDEX `idx_dest_parent` (`parent_id`)',
    'DO 0');
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Verify: should report 2.
SELECT COUNT(*) AS parent_cols_present
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'destinations'
  AND COLUMN_NAME IN ('parent_id', 'group_label');
