-- =============================================================
-- MT Safaris — add packages columns missing from live databases
--
-- WHY THIS EXISTS
-- Commit d5ca9ae expanded the `packages` table in database.sql from 44 to
-- 50 columns and updated admin/package-edit.php to write the new ones.
-- Databases created from the earlier schema were never migrated, so saving
-- a package raises:
--     SQLSTATE[42S22]: Column not found: 1054 Unknown column 'child_price'
-- which surfaces in the admin as "can't add or update packages".
--
-- Column definitions and ordering below match database.sql exactly.
-- Safe to re-run: each statement is guarded by an existence check, so
-- columns already present are skipped rather than erroring.
-- =============================================================

DELIMITER //

DROP PROCEDURE IF EXISTS mts_add_column_if_missing //
CREATE PROCEDURE mts_add_column_if_missing(
    IN tbl  VARCHAR(64),
    IN col  VARCHAR(64),
    IN ddl  TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME   = tbl
          AND COLUMN_NAME  = col
    ) THEN
        SET @s = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
        PREPARE stmt FROM @s;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //

DELIMITER ;

CALL mts_add_column_if_missing('packages', 'child_price',
    '`child_price` DECIMAL(10,2) AFTER `departure_city`');
CALL mts_add_column_if_missing('packages', 'accommodation',
    '`accommodation` VARCHAR(300) AFTER `child_price`');
CALL mts_add_column_if_missing('packages', 'transport',
    '`transport` VARCHAR(300) AFTER `accommodation`');
CALL mts_add_column_if_missing('packages', 'meals',
    '`meals` VARCHAR(300) AFTER `transport`');
CALL mts_add_column_if_missing('packages', 'physical_req',
    '`physical_req` TEXT AFTER `meals`');
CALL mts_add_column_if_missing('packages', 'addons',
    '`addons` JSON AFTER `physical_req`');

DROP PROCEDURE IF EXISTS mts_add_column_if_missing;

-- Verify: should report 0 missing.
SELECT 6 - COUNT(*) AS still_missing
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'packages'
  AND COLUMN_NAME IN ('child_price','accommodation','transport','meals','physical_req','addons');
