-- ---------------------------------------------------------------------------
-- Adds six columns the `packages` table in database.sql defines but which were
-- never present in databases created from the earlier schema. Without them the
-- admin "Create Package" insert fails with:
--   SQLSTATE[42S22]: Unknown column 'child_price' in 'field list'
--
-- Column definitions and ordering match database.sql:162-221 exactly.
-- Run once per database (local, staging, production).
-- ---------------------------------------------------------------------------

ALTER TABLE `packages`
  ADD COLUMN `child_price`   DECIMAL(10,2) AFTER `departure_city`,
  ADD COLUMN `accommodation` VARCHAR(300)  AFTER `child_price`,
  ADD COLUMN `transport`     VARCHAR(300)  AFTER `accommodation`,
  ADD COLUMN `meals`         VARCHAR(300)  AFTER `transport`,
  ADD COLUMN `physical_req`  TEXT          AFTER `meals`,
  ADD COLUMN `addons`        JSON          AFTER `physical_req`;
