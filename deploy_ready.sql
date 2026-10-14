SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `menu_items`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `tables`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(128) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'kitchen', 'staff', 'customer') NOT NULL DEFAULT 'customer',
  `full_name` VARCHAR(128) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `tables` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label` VARCHAR(32) NOT NULL,
  `capacity` SMALLINT UNSIGNED NOT NULL DEFAULT 4,
  `status` ENUM('available', 'occupied') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tables_label` (`label`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(64) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `menu_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(128) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `image_path` VARCHAR(512) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_menu_category` (`category_id`),
  CONSTRAINT `fk_menu_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(16) NOT NULL,
  `type` ENUM('DINE_IN', 'DELIVERY') NOT NULL,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `table_id` INT UNSIGNED DEFAULT NULL,
  `customer_name` VARCHAR(128) DEFAULT NULL,
  `customer_phone` VARCHAR(32) DEFAULT NULL,
  `delivery_address` TEXT,
  `status` ENUM('pending', 'preparing', 'ready', 'completed') NOT NULL DEFAULT 'pending',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `gst_rate` DECIMAL(5,2) NOT NULL DEFAULT 18.00,
  `gst_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_orders_code` (`order_code`),
  KEY `fk_orders_user` (`user_id`),
  KEY `fk_orders_table` (`table_id`),
  KEY `idx_orders_status` (`status`),
  KEY `idx_orders_created` (`created_at`),
  CONSTRAINT `fk_orders_table` FOREIGN KEY (`table_id`) REFERENCES `tables` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `menu_item_id` INT UNSIGNED NOT NULL,
  `quantity` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `line_total` DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_oi_order` (`order_id`),
  KEY `fk_oi_menu` (`menu_item_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_menu` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `users` (`email`, `password_hash`, `role`, `full_name`) VALUES
('admin@velvetplate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator'),
('kitchen@velvetplate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kitchen', 'Head Chef'),
('staff@velvetplate.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'staff', 'Floor Staff');
INSERT INTO `tables` (`label`, `capacity`, `status`) VALUES
('T1', 2, 'available'),
('T2', 4, 'available'),
('T3', 4, 'occupied'),
('T4', 6, 'available'),
('T5', 2, 'available');
INSERT INTO `categories` (`name`, `sort_order`) VALUES
('Starters', 1),
('Main Course', 2),
('Breads', 3),
('Desserts', 4),
('Beverages', 5);
INSERT INTO `menu_items` (`category_id`, `name`, `description`, `price`, `image_path`, `is_active`) VALUES
(1, 'Crispy Spring Rolls', 'Garden vegetables, glass noodles, sweet chili dip', 145.00, 'https://images.unsplash.com/photo-1617093727343-374698b1b08d?w=800&q=80&auto=format&fit=crop', 1),
(1, 'Classic Hummus & Pita', 'Chickpea tahini, olive oil, warm flatbread', 165.00, 'https://images.unsplash.com/photo-1577801251599-abcf7bd664aa?w=800&q=80&auto=format&fit=crop', 1),
(1, 'Tomato Basil Soup', 'Slow-simmered tomatoes, fresh basil, cream finish', 110.00, 'https://images.unsplash.com/photo-1547592166-23ac45744acd?w=800&q=80&auto=format&fit=crop', 1),
(1, 'Bruschetta Trio', 'Roasted tomato, olive tapenade, whipped ricotta', 155.00, 'https://images.unsplash.com/photo-1572695157366-5e585ab2b69f?w=800&q=80&auto=format&fit=crop', 1),
(1, 'Garden Salad Bowl', 'Mixed greens, citrus vinaigrette, toasted seeds', 135.00, 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?w=800&q=80&auto=format&fit=crop', 1),
(1, 'Wild Mushroom Crostini', 'Herbed mascarpone, truffle oil', 175.00, 'https://images.unsplash.com/photo-1590167795692-0098d7d8c8a6?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Paneer Butter Masala', 'Soft cottage cheese, velvet tomatoâ€“cashew gravy', 295.00, 'https://images.unsplash.com/photo-1631452256439-1edc32e58487?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Dal Tadka', 'Yellow lentils, garlic cumin tempering', 195.00, 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Hyderabadi Chicken Biryani', 'Aged basmati, saffron, sealed-pot dum style', 345.00, 'https://images.unsplash.com/photo-1563379091339-032b84eade1c?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Butter Chicken', 'Chargrilled tikka, makhani sauce, fenugreek', 325.00, 'https://images.unsplash.com/photo-1603894584373-5ac82b2ae398?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Grilled Atlantic Salmon', 'Lemon butter, charred asparagus', 420.00, 'https://images.unsplash.com/photo-1467003909585-2f8b72700288?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Herb-Crusted Lamb Rack', 'Rosemary jus, fondant potato', 485.00, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Truffle Penne Alfredo', 'Cream, aged parmesan, black truffle oil', 365.00, 'https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Thai Green Curry', 'Coconut, Thai basil, jasmine rice', 315.00, 'https://images.unsplash.com/photo-1455619452474-d7be450dd4e5?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Prime Ribeye Steak', 'Charred edges, red wine reduction', 520.00, 'https://images.unsplash.com/photo-1546833999-87769391eec4?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Malai Kofta', 'Paneerâ€“nut dumplings, mild saffron gravy', 285.00, 'https://images.unsplash.com/photo-1596797038530-2c107229654b?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Seafood Linguine', 'Prawns, mussels, white wine garlic sauce', 395.00, 'https://images.unsplash.com/photo-1563379926898-05f4575a45d8?w=800&q=80&auto=format&fit=crop', 1),
(2, 'Vegetable Thai Basil Stir-fry', 'Jasmine rice, chili, soy glaze', 265.00, 'https://images.unsplash.com/photo-1588166524951-3bf61a9c41db?w=800&q=80&auto=format&fit=crop', 1),
(3, 'Butter Naan', 'Tandoor-fired, brushed with cultured butter', 55.00, 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&q=80&auto=format&fit=crop', 1),
(3, 'Garlic Naan', 'Roasted garlic butter, coriander', 65.00, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&q=80&auto=format&fit=crop', 1),
(3, 'Whole Wheat Tandoori Roti', 'Stone-ground flour, minimal oil', 40.00, 'https://images.unsplash.com/photo-1565557623262-b51c2513a641?w=800&q=80&auto=format&fit=crop', 1),
(3, 'Cheese Kulcha', 'Stuffed with mozzarella & herbs', 85.00, 'https://images.unsplash.com/photo-1606491956689-2eaae868d4ea?w=800&q=80&auto=format&fit=crop', 1),
(4, 'Gulab Jamun', 'Reduced milk dumplings, rose-cardamom syrup', 95.00, 'https://images.unsplash.com/photo-1563804442-071e096cdf13?w=800&q=80&auto=format&fit=crop', 1),
(4, 'Chocolate Lava Cake', 'Valrhona centre, vanilla bean ice cream', 185.00, 'https://images.unsplash.com/photo-1624353365286-3f8d62daad51?w=800&q=80&auto=format&fit=crop', 1),
(4, 'Classic Tiramisu', 'Mascarpone, espresso-soaked ladyfingers', 175.00, 'https://images.unsplash.com/photo-1571877227200-a0d98ea607e9?w=800&q=80&auto=format&fit=crop', 1),
(4, 'Mango Kulfi', 'Alphonso mango, pistachio crumble', 125.00, 'https://images.unsplash.com/photo-1563805042-7684c019e1cb?w=800&q=80&auto=format&fit=crop', 1),
(5, 'Masala Chai', 'Assam tea, ginger, cardamom, steamed milk', 55.00, 'https://images.unsplash.com/photo-1564890369478-c89ca6d9cde9?w=800&q=80&auto=format&fit=crop', 1),
(5, 'Fresh Lime Soda', 'Sweet, salted, or blended mint', 65.00, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=800&q=80&auto=format&fit=crop', 1),
(5, 'Cold Brew Coffee', 'Single-origin, 18-hour steep', 95.00, 'https://images.unsplash.com/photo-1461027288946-07fae16ab8ff?w=800&q=80&auto=format&fit=crop', 1),
(5, 'Sweet Mango Lassi', 'Yogurt, cardamom, saffron strand', 85.00, 'https://images.unsplash.com/photo-1572490122747-3968b75cc699?w=800&q=80&auto=format&fit=crop', 1);
CREATE TABLE IF NOT EXISTS `location_zones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(128) NOT NULL,
  `zone_type` ENUM('pincode', 'radius', 'polygon') NOT NULL DEFAULT 'pincode',
  `pincodes` TEXT DEFAULT NULL, -- Comma separated pincodes if zone_type is pincode
  `center_lat` DECIMAL(10, 8) DEFAULT NULL,
  `center_lng` DECIMAL(11, 8) DEFAULT NULL,
  `radius_km` DECIMAL(5, 2) DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `delivery_fee` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `min_order` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `estimated_time` VARCHAR(32) DEFAULT '30-45 mins',
  `business_type` ENUM('RESTAURANT', 'HOTEL', 'B2B', 'ALL') NOT NULL DEFAULT 'ALL',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS `user_addresses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `address_type` ENUM('HOME', 'WORK', 'HOTEL', 'OTHER') NOT NULL DEFAULT 'HOME',
  `full_name` VARCHAR(128) NOT NULL,
  `phone_number` VARCHAR(32) NOT NULL,
  `flat_number` VARCHAR(64) DEFAULT NULL,
  `street_address` TEXT NOT NULL,
  `landmark` VARCHAR(128) DEFAULT NULL,
  `city` VARCHAR(64) NOT NULL,
  `state` VARCHAR(64) NOT NULL,
  `pincode` VARCHAR(16) NOT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `is_default` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_address_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO `location_zones` (`name`, `zone_type`, `pincodes`, `delivery_fee`, `min_order`, `estimated_time`, `business_type`) VALUES
('Central Dining Zone', 'pincode', '110001, 110002, 400001', 40.00, 200.00, '25-30 mins', 'ALL'),
('Extended Service Area', 'pincode', '110020, 110025, 400050', 80.00, 500.00, '45-60 mins', 'ALL'),
('B2B Logistics Hub', 'pincode', '110092, 400099', 0.00, 2000.00, 'Next Day', 'B2B');
