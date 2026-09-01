ALTER TABLE `#__hotelbooking_destinations`
    ADD COLUMN `language` CHAR(7) NOT NULL DEFAULT '*' AFTER `alias`,
    ADD KEY `idx_language` (`language`);

ALTER TABLE `#__hotelbooking_rooms`
    ADD COLUMN `alias` VARCHAR(255) NOT NULL DEFAULT '' AFTER `name`,
    ADD COLUMN `language` CHAR(7) NOT NULL DEFAULT '*' AFTER `alias`,
    ADD KEY `idx_language` (`language`);

ALTER TABLE `#__hotelbooking_faqs`
    ADD COLUMN `language` CHAR(7) NOT NULL DEFAULT '*' AFTER `scope`,
    ADD KEY `idx_language` (`language`);
