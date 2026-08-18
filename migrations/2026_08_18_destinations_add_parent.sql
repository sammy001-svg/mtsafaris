ALTER TABLE `destinations` ADD COLUMN `parent_id` INT UNSIGNED NULL AFTER `region_id`;

ALTER TABLE `destinations` ADD COLUMN `group_label` VARCHAR(80) NULL AFTER `parent_id`;

ALTER TABLE `destinations` ADD INDEX `idx_dest_parent` (`parent_id`);
