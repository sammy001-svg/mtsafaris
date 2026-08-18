-- ---------------------------------------------------------------------------
-- Package categories from the MTS Website document (Kenya Package Category).
--
-- Five existing rows are RENAMED IN PLACE rather than replaced, because
-- packages reference them by id and inserting new rows would orphan those
-- packages:
--     1 Corporate Travel   -> Corporate & Business Packages
--     3 Honeymoon          -> Honeymoon & Romantic Escapes
--     5 Educational Tours  -> Educational & Student Tours
--     7 Adventure          -> Adventure & Outdoor Escapes
--     8 Safari             -> Wildlife & Safari Adventures
-- The remaining seven categories are inserted.
--
-- Categories absent from the document are DEACTIVATED, not deleted, so the
-- packages pointing at them keep a valid foreign key.
-- NOTE: "Maldives Luxury Overwater Retreat" sits on Luxury Tours (id 6), which
-- has no equivalent in the document. It is deactivated here and that package
-- should be reassigned to a category from the new list.
--
-- Idempotent: slug is UNIQUE and the updates are absolute.
-- ---------------------------------------------------------------------------

UPDATE categories SET name='Corporate & Business Packages', slug='corporate-business', icon='fas fa-briefcase',
  description='Conferences, meetings, team building, retreats, incentive travel, gala dinners and safari extensions. Suggested duration 1-7+ days.',
  is_active=1, sort_order=8 WHERE id=1;
UPDATE categories SET name='Honeymoon & Romantic Escapes', slug='honeymoon-romantic', icon='fas fa-heart',
  description='Safari lodges, private conservancies, Diani/Watamu/Lamu, romantic dinners and experiences. Suggested duration 4-10 days.',
  is_active=1, sort_order=11 WHERE id=3;
UPDATE categories SET name='Educational & Student Tours', slug='educational-student', icon='fas fa-graduation-cap',
  description='Wildlife, conservation, geography, culture, history, agriculture and community projects. Suggested duration 2-10 days.',
  is_active=1, sort_order=12 WHERE id=5;
UPDATE categories SET name='Adventure & Outdoor Escapes', slug='adventure-outdoor', icon='fas fa-hiking',
  description='Hiking, cycling, rafting, camping, ziplining, rock climbing and nature walks. Suggested duration 1-5 days.',
  is_active=1, sort_order=5 WHERE id=7;
UPDATE categories SET name='Wildlife & Safari Adventures', slug='wildlife-safari', icon='fas fa-binoculars',
  description='Maasai Mara, Amboseli, Tsavo, Samburu, Lake Nakuru and private conservancies. Suggested duration 2-10 days.',
  is_active=1, sort_order=1 WHERE id=8;

INSERT INTO categories (name, slug, icon, description, is_active, sort_order) VALUES
 ('Coastal & Beach Holidays','coastal-beach','fas fa-umbrella-beach','Diani, Mombasa, Watamu, Lamu, Malindi, dhow cruises, snorkeling and diving. Suggested duration 3-10 days.',1,2),
 ('Mountain & Hiking Adventures','mountain-hiking','fas fa-mountain','Mount Kenya, Aberdare, Mount Elgon, Ngong Hills, Hell''s Gate and Taita Hills. Suggested duration 1-7+ days.',1,3),
 ('Cultural & Heritage Tours','cultural-heritage','fas fa-landmark','Maasai, Samburu and coastal Swahili experiences, villages, museums, heritage sites and local cuisine. Suggested duration 1-7 days.',1,4),
 ('Eco & Nature','eco-nature','fas fa-leaf','Forests, waterfalls, walking safaris, conservancies, lakes, community-based tourism and conservation experiences. Suggested duration 1-7 days.',1,6),
 ('Nairobi City & Weekend Tours','nairobi-city-weekend','fas fa-city','Nairobi National Park, Giraffe Centre, Karen, museums, markets, restaurants and nightlife. Suggested duration 1-3 days.',1,7),
 ('Wellness & Retreat Packages','wellness-retreat','fas fa-spa','Beach retreats, forest escapes, yoga, spa experiences and wellness weekends. Suggested duration 2-5 days.',1,9),
 ('Family Holidays','family-holidays','fas fa-users','Safari, beach, wildlife encounters, educational and cultural activities. Suggested duration 3-10 days.',1,10)
ON DUPLICATE KEY UPDATE name=VALUES(name), icon=VALUES(icon), description=VALUES(description),
  is_active=VALUES(is_active), sort_order=VALUES(sort_order);

-- Retire categories the document does not list. Rows are kept so package
-- foreign keys stay valid.
UPDATE categories SET is_active=0 WHERE id IN (2,4,6,9,10);

SELECT COUNT(*) AS active_categories FROM categories WHERE is_active=1;
