-- Workflow stage Hotel confirms writes hotel_confirmed_awaiting_payment (33 chars).
ALTER TABLE `#__hotelbooking_bookings`
    MODIFY COLUMN `partner_status` VARCHAR(64) NOT NULL DEFAULT 'awaiting_hotel_check';
