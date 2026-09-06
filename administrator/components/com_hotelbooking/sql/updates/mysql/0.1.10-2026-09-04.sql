ALTER TABLE `#__hotelbooking_destinations`
  ADD COLUMN `asset_id` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `id`,
  ADD COLUMN `created_by` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `manager_user_id`,
  ADD KEY `idx_asset_id` (`asset_id`),
  ADD KEY `idx_created_by` (`created_by`);

INSERT IGNORE INTO `#__mail_templates` (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('com_hotelbooking.partner_notify', 'com_hotelbooking', '', 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_SUBJECT', 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_BODY', '', '', '{"tags":["sitename","destination","room","guest","checkin","checkout","guests","total"]}');
