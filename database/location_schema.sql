-- Location and Serviceability Schema for Velvet Plate

-- Table for storing delivery/service zones
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

-- Table for user saved addresses
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

-- Sample zones
INSERT INTO `location_zones` (`name`, `zone_type`, `pincodes`, `delivery_fee`, `min_order`, `estimated_time`, `business_type`) VALUES
('Central Dining Zone', 'pincode', '110001, 110002, 400001', 40.00, 200.00, '25-30 mins', 'ALL'),
('Extended Service Area', 'pincode', '110020, 110025, 400050', 80.00, 500.00, '45-60 mins', 'ALL'),
('B2B Logistics Hub', 'pincode', '110092, 400099', 0.00, 2000.00, 'Next Day', 'B2B');
