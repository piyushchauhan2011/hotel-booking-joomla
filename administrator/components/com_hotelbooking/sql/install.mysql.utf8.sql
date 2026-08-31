CREATE TABLE IF NOT EXISTS `#__hotelbooking_destinations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `alias` VARCHAR(255) NOT NULL DEFAULT '',
  `description` MEDIUMTEXT,
  `gallery` MEDIUMTEXT,
  `amenities` VARCHAR(512),
  `offers` MEDIUMTEXT,
  `faqs` MEDIUMTEXT,
  `partner_contact_name` VARCHAR(255) NULL DEFAULT NULL,
  `partner_email` VARCHAR(255) NULL DEFAULT NULL,
  `partner_whatsapp` VARCHAR(32) NULL DEFAULT NULL,
  `payment_instructions` MEDIUMTEXT,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 8.00,
  `manager_user_id` INT(11) UNSIGNED NULL DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_manager_user` (`manager_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__hotelbooking_rooms` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `destination_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `description` MEDIUMTEXT,
  `gallery` MEDIUMTEXT,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `capacity` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `amenities` VARCHAR(512),
  `offers` MEDIUMTEXT,
  `nearby_places` MEDIUMTEXT,
  `faqs` MEDIUMTEXT,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_destination` (`destination_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__hotelbooking_bookings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `guest_name` VARCHAR(255) NOT NULL DEFAULT '',
  `guest_email` VARCHAR(255) NOT NULL DEFAULT '',
  `checkin_date` DATE NOT NULL,
  `checkout_date` DATE NOT NULL,
  `guests` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `partner_status` VARCHAR(30) NOT NULL DEFAULT 'awaiting_hotel_check',
  `partner_notes` MEDIUMTEXT,
  `hotel_notified_at` DATETIME NULL DEFAULT NULL,
  `commission_paid` TINYINT(1) NOT NULL DEFAULT 0,
  `commission_paid_date` DATE NULL DEFAULT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_room` (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `#__hotelbooking_faqs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL DEFAULT '',
  `answer` MEDIUMTEXT,
  `scope` VARCHAR(20) NOT NULL DEFAULT 'general',
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
