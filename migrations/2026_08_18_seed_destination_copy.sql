-- ---------------------------------------------------------------------------
-- Short descriptions for the seeded destinations, so each detail page has
-- copy instead of an empty "About" section. Only fills rows that are still
-- blank, so anything written in the admin is never overwritten.
-- Safe to re-run.
--
-- PREREQUISITE: run 2026_08_18_seed_destinations.sql first. This file reads
-- parent_id and group_label, so on a database that still has the original
-- `destinations` layout it fails with
--     ERROR 1054 (42S22): Unknown column 'parent_id' in 'field list'
-- ---------------------------------------------------------------------------

UPDATE destinations SET description = CONCAT(
  name,
  CASE
    WHEN parent_id IS NULL THEN CONCAT(' is one of our headline destinations. Browse the regions and experiences below, or talk to our team about a tailor-made itinerary across ', country, '.')
    ELSE CONCAT(' is one of the standout experiences we arrange in ', country,
                CASE WHEN group_label IS NULL OR group_label = '' THEN '.'
                     ELSE CONCAT(', part of our ', group_label, ' collection.') END,
                ' Get in touch and we will build the trip around your dates, pace and budget.')
  END)
WHERE (description IS NULL OR description = '');

SELECT SUM(description IS NULL OR description = '') AS still_blank,
       COUNT(*) AS total_active
FROM destinations WHERE is_active = 1;
