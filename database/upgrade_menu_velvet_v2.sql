-- Safe upgrade: allow long image URLs (Unsplash / CDN). Run once on existing DBs.
ALTER TABLE `menu_items` MODIFY `image_path` VARCHAR(512) DEFAULT NULL;

-- To load the 28 Velvet Plate dishes with photos: back up, then either
-- (1) re-import database/schema.sql on a fresh database, or
-- (2) truncate order_items + orders, delete from menu_items, and run the
--     INSERT INTO `menu_items` block from schema.sql.
