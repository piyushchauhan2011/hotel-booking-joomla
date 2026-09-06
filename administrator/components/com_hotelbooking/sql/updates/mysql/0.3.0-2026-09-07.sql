INSERT IGNORE INTO `#__mail_templates` (`template_id`, `extension`, `language`, `subject`, `body`, `htmlbody`, `attachments`, `params`) VALUES
('com_hotelbooking.partner_notify', 'com_hotelbooking', '', 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_SUBJECT', 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_BODY', 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_HTMLBODY', '', '{"tags":["sitename","destination","room","guest","checkin","checkout","guests","total"]}');

UPDATE `#__mail_templates`
SET `htmlbody` = 'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_HTMLBODY'
WHERE `template_id` = 'com_hotelbooking.partner_notify'
AND (`htmlbody` IS NULL OR `htmlbody` = '');
