ALTER TABLE `#__hotelbooking_destinations`
    ADD COLUMN `faqs` MEDIUMTEXT NULL AFTER `offers`;

ALTER TABLE `#__hotelbooking_rooms`
    ADD COLUMN `faqs` MEDIUMTEXT NULL AFTER `nearby_places`;

CREATE TABLE IF NOT EXISTS `#__hotelbooking_faqs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `question` VARCHAR(255) NOT NULL DEFAULT '',
  `answer` MEDIUMTEXT,
  `scope` VARCHAR(20) NOT NULL DEFAULT 'general',
  `published` TINYINT NOT NULL DEFAULT 1,
  `ordering` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
