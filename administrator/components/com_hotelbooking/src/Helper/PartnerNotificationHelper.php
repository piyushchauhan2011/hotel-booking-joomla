<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Mail\MailTemplate;

\defined('_JEXEC') or die;

class PartnerNotificationHelper
{
    public const PARTNER_NOTIFY_TEMPLATE = 'com_hotelbooking.partner_notify';

    /**
     * @return list<string>
     */
    public static function mailTemplateTags(): array
    {
        return ['sitename', 'destination', 'room', 'guest', 'checkin', 'checkout', 'guests', 'total'];
    }

    public static function ensureRegisteredMailTemplate(): bool
    {
        $existing = MailTemplate::getTemplate(self::PARTNER_NOTIFY_TEMPLATE, '');

        if ($existing === null) {
            return MailTemplate::createTemplate(
                self::PARTNER_NOTIFY_TEMPLATE,
                'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_SUBJECT',
                'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_BODY',
                self::mailTemplateTags(),
                'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_HTMLBODY',
            );
        }

        if (trim((string) $existing->htmlbody) !== '') {
            return true;
        }

        return MailTemplate::updateTemplate(
            self::PARTNER_NOTIFY_TEMPLATE,
            'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_SUBJECT',
            'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_BODY',
            self::mailTemplateTags(),
            'COM_HOTELBOOKING_MAIL_PARTNER_NOTIFY_HTMLBODY',
        );
    }

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

    /**
     * @return array<string, string>
     */
    public static function templateData(object $booking, object $room, object $destination, string $sitename): array
    {
        return [
            'sitename'    => $sitename,
            'destination' => (string) $destination->name,
            'room'        => (string) $room->name,
            'guest'       => (string) $booking->guest_name,
            'checkin'     => (string) $booking->checkin_date,
            'checkout'    => (string) $booking->checkout_date,
            'guests'      => (string) (int) $booking->guests,
            'total'       => number_format((float) $booking->total_price, 2),
        ];
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

        try {
            $app  = Factory::getApplication();
            $mail = new MailTemplate(self::PARTNER_NOTIFY_TEMPLATE, $app->getLanguage()->getTag());
            $mail->addTemplateData(self::templateData($booking, $room, $destination, (string) $app->get('sitename')));
            $mail->addRecipient($destination->partner_email);

            return (bool) $mail->send();
        } catch (\Exception $e) {
            return false;
        }
    }
}
