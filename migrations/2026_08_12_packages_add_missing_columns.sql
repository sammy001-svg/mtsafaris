-- ---------------------------------------------------------------------------
-- Adds six columns the `packages` table in database.sql defines but which were
-- never present in databases created from the earlier schema. Without them the
-- admin "Create Package" insert fails with:
--   SQLSTATE[42S22]: Unknown column 'child_price' in 'field list'
--
-- Column definitions and ordering match database.sql:162-221 exactly.
--
-- Each column is added only if absent, so this is safe to re-run and safe on
-- a database where some columns were already added by hand. A plain ALTER
-- would abort with "Duplicate column name" partway through.
-- ---------------------------------------------------------------------------

DELIMITER //

DROP PROCEDURE IF EXISTS mts_add_column_if_missing //
CREATE PROCEDURE mts_add_column_if_missing(
    IN tbl VARCHAR(64),
    IN col VARCHAR(64),
    IN ddl TEXT
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

CALL mts_add_column_if_missing('packages','child_price',
    '`child_price` DECIMAL(10,2) AFTER `departure_city`');
CALL mts_add_column_if_missing('packages','accommodation',
    '`accommodation` VARCHAR(300) AFTER `child_price`');
CALL mts_add_column_if_missing('packages','transport',
    '`transport` VARCHAR(300) AFTER `accommodation`');
CALL mts_add_column_if_missing('packages','meals',
    '`meals` VARCHAR(300) AFTER `transport`');
CALL mts_add_column_if_missing('packages','physical_req',
    '`physical_req` TEXT AFTER `meals`');
CALL mts_add_column_if_missing('packages','addons',
    '`addons` JSON AFTER `physical_req`');

DROP PROCEDURE IF EXISTS mts_add_column_if_missing;

-- Verify: should report 0.
SELECT 6 - COUNT(*) AS still_missing
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'packages'
  AND COLUMN_NAME IN ('child_price','accommodation','transport','meals','physical_req','addons');
