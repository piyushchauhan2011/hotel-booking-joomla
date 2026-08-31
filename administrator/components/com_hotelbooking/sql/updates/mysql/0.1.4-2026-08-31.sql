ALTER TABLE `#__hotelbooking_destinations`
    ADD COLUMN `partner_contact_name` VARCHAR(255) NULL DEFAULT NULL AFTER `faqs`,
    ADD COLUMN `partner_email` VARCHAR(255) NULL DEFAULT NULL AFTER `partner_contact_name`,
    ADD COLUMN `partner_whatsapp` VARCHAR(32) NULL DEFAULT NULL AFTER `partner_email`,
    ADD COLUMN `payment_instructions` MEDIUMTEXT NULL AFTER `partner_whatsapp`,
    ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 8.00 AFTER `payment_instructions`;

ALTER TABLE `#__hotelbooking_bookings`
    ADD COLUMN `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `guests`,
    ADD COLUMN `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `total_price`,
    ADD COLUMN `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `commission_rate`,
    ADD COLUMN `partner_status` VARCHAR(30) NOT NULL DEFAULT 'awaiting_hotel_check' AFTER `status`,
    ADD COLUMN `partner_notes` MEDIUMTEXT NULL AFTER `partner_status`,
    ADD COLUMN `hotel_notified_at` DATETIME NULL DEFAULT NULL AFTER `partner_notes`,
    ADD COLUMN `commission_paid` TINYINT(1) NOT NULL DEFAULT 0 AFTER `hotel_notified_at`,
    ADD COLUMN `commission_paid_date` DATE NULL DEFAULT NULL AFTER `commission_paid`;
