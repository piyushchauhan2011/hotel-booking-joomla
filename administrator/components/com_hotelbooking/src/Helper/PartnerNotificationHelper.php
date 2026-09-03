<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

class PartnerNotificationHelper
{
    public static function buildMessageSummary(object $booking, object $room, object $destination): string
    {
        return Text::sprintf(
            'COM_HOTELBOOKING_NOTIFY_MESSAGE_SUMMARY',
            $destination->name,
            $room->name,
            $booking->guest_name,
            $booking->checkin_date,
            $booking->checkout_date,
            (int) $booking->guests,
            number_format((float) $booking->total_price, 2),
        );
    }

    public static function buildWhatsAppLink(string $whatsapp, string $message): string
    {
        $digits = preg_replace('/\D+/', '', $whatsapp);

        if ($digits === '') {
            return '';
        }

        return 'https://wa.me/' . $digits . '?text=' . rawurlencode($message);
    }

    public static function sendEmail(object $booking, object $room, object $destination): bool
    {
        if (empty($destination->partner_email)) {
            return false;
        }

        $mailer  = Factory::getMailer();
        $app     = Factory::getApplication();
        $subject = Text::sprintf('COM_HOTELBOOKING_NOTIFY_EMAIL_SUBJECT', $destination->name);
        $body    = self::buildMessageSummary($booking, $room, $destination)
            . "\n\n" . Text::sprintf('COM_HOTELBOOKING_NOTIFY_EMAIL_FOOTER', $app->get('sitename'));

        try {
            $mailer->setSubject($subject);
            $mailer->setBody($body);
            $mailer->addRecipient($destination->partner_email);

            return (bool) $mailer->Send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
