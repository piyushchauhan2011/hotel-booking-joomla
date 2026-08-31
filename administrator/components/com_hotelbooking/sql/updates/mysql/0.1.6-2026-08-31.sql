ALTER TABLE `#__hotelbooking_destinations`
    ADD COLUMN `manager_user_id` INT(11) UNSIGNED NULL DEFAULT NULL AFTER `commission_rate`,
    ADD KEY `idx_manager_user` (`manager_user_id`);
