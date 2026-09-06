<?php

namespace Learn\Plugin\Privacy\Hotelbooking\Helper;

\defined('_JEXEC') or die;

class PrivacyDataHelper
{
    /**
     * @return array<string, string>
     */
    public static function bookingExportRow(object $booking): array
    {
        return [
            'guest_name'    => (string) ($booking->guest_name ?? ''),
            'guest_email'   => (string) ($booking->guest_email ?? ''),
            'checkin_date'  => (string) ($booking->checkin_date ?? ''),
            'checkout_date' => (string) ($booking->checkout_date ?? ''),
            'room'          => (string) ($booking->room_name ?? ''),
            'hotel'         => (string) ($booking->destination_name ?? ''),
            'status'        => (string) ($booking->status ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function destinationExportRow(object $destination): array
    {
        return [
            'name'                 => (string) ($destination->name ?? ''),
            'partner_contact_name' => (string) ($destination->partner_contact_name ?? ''),
            'partner_email'        => (string) ($destination->partner_email ?? ''),
            'partner_whatsapp'     => (string) ($destination->partner_whatsapp ?? ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function anonymisedBookingValues(): array
    {
        return [
            'guest_name'    => 'Removed Guest',
            'guest_email'   => 'removed@email.invalid',
            'partner_notes' => '',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function anonymisedPartnerValues(): array
    {
        return [
            'partner_contact_name' => '',
            'partner_email'        => '',
            'partner_whatsapp'     => '',
        ];
    }
}
