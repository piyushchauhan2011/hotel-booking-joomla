ALTER TABLE `#__hotelbooking_destinations`
    ADD COLUMN `gallery` MEDIUMTEXT NULL AFTER `description`,
    ADD COLUMN `offers` MEDIUMTEXT NULL AFTER `gallery`;

ALTER TABLE `#__hotelbooking_rooms`
    ADD COLUMN `gallery` MEDIUMTEXT NULL AFTER `description`,
    ADD COLUMN `amenities` VARCHAR(512) NULL AFTER `capacity`,
    ADD COLUMN `offers` MEDIUMTEXT NULL AFTER `amenities`,
    ADD COLUMN `nearby_places` MEDIUMTEXT NULL AFTER `offers`;
