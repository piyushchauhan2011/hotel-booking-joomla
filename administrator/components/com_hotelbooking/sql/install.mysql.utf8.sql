CREATE TABLE IF NOT EXISTS `#__hotelbooking_destinations` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `alias` VARCHAR(255) NOT NULL DEFAULT '',
  `description` MEDIUMTEXT,
  `gallery` MEDIUMTEXT,
  `amenities` VARCHAR(512),
  `offers` MEDIUMTEXT,
  `faqs` MEDIUMTEXT,
  `image` VARCHAR(255) NOT NULL DEFAULT '',
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
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
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
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
