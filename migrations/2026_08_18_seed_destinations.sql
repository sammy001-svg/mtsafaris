-- Destination tree from the MTS Website document.
-- Idempotent: slug is UNIQUE, so re-running updates in place.
--
-- SELF-CONTAINED: this file adds the parent_id / group_label columns itself if
-- they are not already present, so it can be imported on its own. Running it
-- against a database that still has the original `destinations` layout used to
-- fail on the very first statement with
--     ERROR 1054 (42S22): Unknown column 'parent_id' in 'field list'
-- and insert nothing at all.

-- No DELIMITER / stored procedure is used below, on purpose: DELIMITER is a
-- client-side directive and not every import tool honours it. Plain statements
-- import reliably everywhere.

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

INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Kenya', 'kenya', 'Kenya', 1, NULL, NULL, 'https://images.unsplash.com/photo-1433086966358-54859d0ed716?w=1200&q=80', 1, 1, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Maasai Mara', 'masai-mara', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1200&q=80', 1, 0, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Amboseli', 'amboseli', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=1200&q=80', 1, 0, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Tsavo', 'tsavo', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1200&q=80', 1, 0, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Samburu', 'samburu', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1200&q=80', 1, 0, 40)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Nakuru / Naivasha', 'nakuru-naivasha', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200&q=80', 1, 0, 50)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Mount Kenya', 'mount-kenya', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Safari', 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1200&q=80', 1, 0, 60)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Diani', 'diani', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Beach Holidays', 'https://images.unsplash.com/photo-1493246507139-91e8fad9978e?w=1200&q=80', 1, 0, 70)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('North Coast', 'north-coast', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Beach Holidays', 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21?w=1200&q=80', 1, 0, 80)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Malindi', 'malindi', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Beach Holidays', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=1200&q=80', 1, 0, 90)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Lamu', 'lamu', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Beach Holidays', 'https://images.unsplash.com/photo-1502085671122-2d218cd434e6?w=1200&q=80', 1, 0, 100)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Watamu', 'watamu', 'Kenya', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='kenya') p), 'Beach Holidays', 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=1200&q=80', 1, 0, 110)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Tanzania', 'tanzania', 'Tanzania', 1, NULL, NULL, 'https://images.unsplash.com/photo-1504198266287-1659872e6590?w=1200&q=80', 1, 1, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Serengeti', 'serengeti', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1504432842672-1a79f78e4084?w=1200&q=80', 1, 0, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Ngorongoro Crater', 'ngorongoro-crater', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200&q=80', 1, 0, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Tarangire', 'tarangire', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&q=80', 1, 0, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Lake Manyara', 'lake-manyara', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1512100356356-de1b84283e18?w=1200&q=80', 1, 0, 40)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Nyerere', 'nyerere', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=80', 1, 0, 50)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Ruaha', 'ruaha', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1200&q=80', 1, 0, 60)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Mikumi', 'mikumi', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Wildlife Safari Packages', 'https://images.unsplash.com/photo-1518495973542-4542c06a5843?w=1200&q=80', 1, 0, 70)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Zanzibar', 'zanzibar', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=1200&q=80', 1, 0, 80)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Pemba', 'pemba', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1518684079-3c830dcef090?w=1200&q=80', 1, 0, 90)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Mafia Island', 'mafia-island', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1518998053901-5348d3961a04?w=1200&q=80', 1, 0, 100)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Dar es Salaam', 'dar-es-salaam', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1519681393784-d120267933ba?w=1200&q=80', 1, 0, 110)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Bagamoyo', 'bagamoyo', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=1200&q=80', 1, 0, 120)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Kilwa', 'kilwa', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Coastal & Beach Holidays', 'https://images.unsplash.com/photo-1523592121529-f6dde35f079e?w=1200&q=80', 1, 0, 130)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Mount Kilimanjaro', 'kilimanjaro', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Mountain & Hiking Adventures', 'https://images.unsplash.com/photo-1523805009345-7448845a9e53?w=1200&q=80', 1, 0, 140)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Mount Meru', 'mount-meru', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Mountain & Hiking Adventures', 'https://images.unsplash.com/photo-1524492412937-b28074a5d7da?w=1200&q=80', 1, 0, 150)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Usambara Mountains', 'usambara-mountains', 'Tanzania', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='tanzania') p), 'Mountain & Hiking Adventures', 'https://images.unsplash.com/photo-1526392060635-9d6019884377?w=1200&q=80', 1, 0, 160)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Uganda', 'uganda', 'Uganda', 1, NULL, NULL, 'https://images.unsplash.com/photo-1528181304800-259b08848526?w=1200&q=80', 1, 1, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Bwindi', 'bwindi', 'Uganda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='uganda') p), 'Gorilla Trekking', 'https://images.unsplash.com/photo-1533105079780-92b9be482077?w=1200&q=80', 1, 0, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Murchison Falls', 'murchison-falls', 'Uganda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='uganda') p), 'Wildlife Safari', 'https://images.unsplash.com/photo-1534177616072-ef7dc120449d?w=1200&q=80', 1, 0, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Queen Elizabeth National Park', 'queen-elizabeth-np', 'Uganda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='uganda') p), 'Wildlife Safari', 'https://images.unsplash.com/photo-1535941339077-2dd1c7963098?w=1200&q=80', 1, 0, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Rwanda', 'rwanda', 'Rwanda', 1, NULL, NULL, 'https://images.unsplash.com/photo-1540202404-a2f29016b523?w=1200&q=80', 1, 1, 40)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Kigali', 'kigali', 'Rwanda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='rwanda') p), 'City Tour', 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1200&q=80', 1, 0, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Akagera National Park', 'akagera', 'Rwanda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='rwanda') p), 'Wildlife Safari', 'https://images.unsplash.com/photo-1547471080-7cc2caa01a7e?w=1200&q=80', 1, 0, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Volcanoes National Park', 'volcanoes-np', 'Rwanda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='rwanda') p), 'Gorilla Trekking', 'https://images.unsplash.com/photo-1547981609-4b6bfe67ca0b?w=1200&q=80', 1, 0, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Nyungwe Forest', 'nyungwe-forest', 'Rwanda', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='rwanda') p), 'Chimpanzee Safari', 'https://images.unsplash.com/photo-1552465011-b4e21bf6e79a?w=1200&q=80', 1, 0, 40)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('South Africa', 'south-africa', 'South Africa', 1, NULL, NULL, 'https://images.unsplash.com/photo-1555217851-6141535bd771?w=1200&q=80', 1, 1, 50)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Cape Town', 'cape-town', 'South Africa', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='south-africa') p), 'City Break', 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=1200&q=80', 1, 0, 10)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Kruger National Park', 'kruger', 'South Africa', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='south-africa') p), 'Safari', 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=1200&q=80', 1, 0, 20)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Pilanesberg', 'pilanesberg', 'South Africa', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='south-africa') p), 'Safari', 'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=1200&q=80', 1, 0, 30)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Garden Route', 'garden-route', 'South Africa', 1, (SELECT id FROM (SELECT id FROM destinations WHERE slug='south-africa') p), 'Scenic Route', 'https://images.unsplash.com/photo-1589553416260-f586c8f1514f?w=1200&q=80', 1, 0, 40)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=VALUES(parent_id), group_label=VALUES(group_label), is_active=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Dubai', 'dubai', 'UAE', 2, NULL, NULL, 'https://images.unsplash.com/photo-1590523277543-a94d2e4eb00b?w=1200&q=80', 1, 1, 60)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Malaysia', 'malaysia', 'Malaysia', 4, NULL, NULL, 'https://images.unsplash.com/photo-1433086966358-54859d0ed716?w=1200&q=80', 1, 1, 70)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Thailand', 'thailand', 'Thailand', 4, NULL, NULL, 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1200&q=80', 1, 1, 80)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Israel', 'israel', 'Israel', 2, NULL, NULL, 'https://images.unsplash.com/photo-1447752875215-b2761acb3c5d?w=1200&q=80', 1, 1, 90)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Indonesia', 'indonesia', 'Indonesia', 4, NULL, NULL, 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=1200&q=80', 1, 1, 100)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Bangkok', 'bangkok', 'Thailand', 4, NULL, NULL, 'https://images.unsplash.com/photo-1469474968028-56623f02e42e?w=1200&q=80', 1, 1, 110)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));
INSERT INTO destinations (name, slug, country, region_id, parent_id, group_label, hero_image, is_active, is_featured, sort_order)
VALUES ('Lithuania & the Baltic States', 'lithuania-baltics', 'Lithuania', 3, NULL, NULL, 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200&q=80', 1, 1, 120)
ON DUPLICATE KEY UPDATE name=VALUES(name), country=VALUES(country), region_id=VALUES(region_id),
  parent_id=NULL, group_label=NULL, is_active=1, is_featured=1, sort_order=VALUES(sort_order),
  hero_image=COALESCE(NULLIF(hero_image,''), VALUES(hero_image));

-- Destinations not present in the document are retired rather than deleted,
-- because packages still reference them and deleting would break those rows.
UPDATE destinations SET is_active=0, is_featured=0 WHERE slug IN ('paris','maldives');

SELECT COUNT(*) AS total, SUM(parent_id IS NULL) AS parents, SUM(parent_id IS NOT NULL) AS children FROM destinations WHERE is_active=1;
