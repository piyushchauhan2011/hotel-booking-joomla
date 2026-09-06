<?php

declare(strict_types=1);

namespace Learn\Plugin\Privacy\Hotelbooking\Helper;

use PHPUnit\Framework\TestCase;

final class PrivacyDataHelperTest extends TestCase
{
    public function testBookingExportRowMapsGuestStay(): void
    {
        $booking = (object) [
            'guest_name'        => 'Maya Chen',
            'guest_email'       => 'maya@example.com',
            'checkin_date'      => '2026-09-10',
            'checkout_date'     => '2026-09-12',
            'room_name'         => 'Deluxe King',
            'destination_name'  => 'Tokyo House',
            'status'            => 'pending',
        ];

        $this->assertSame(
            [
                'guest_name'    => 'Maya Chen',
                'guest_email'   => 'maya@example.com',
                'checkin_date'  => '2026-09-10',
                'checkout_date' => '2026-09-12',
                'room'          => 'Deluxe King',
                'hotel'         => 'Tokyo House',
                'status'        => 'pending',
            ],
            PrivacyDataHelper::bookingExportRow($booking)
        );
    }

    public function testDestinationExportRowMapsPartnerContact(): void
    {
        $destination = (object) [
            'name'                 => 'Tokyo House',
            'partner_contact_name' => 'Aiko',
            'partner_email'        => 'aiko@example.com',
            'partner_whatsapp'     => '66812345678',
        ];

        $this->assertSame(
            [
                'name'                 => 'Tokyo House',
                'partner_contact_name' => 'Aiko',
                'partner_email'        => 'aiko@example.com',
                'partner_whatsapp'     => '66812345678',
            ],
            PrivacyDataHelper::destinationExportRow($destination)
        );
    }

    public function testAnonymisedBookingValuesUseInvalidEmail(): void
    {
        $values = PrivacyDataHelper::anonymisedBookingValues();

        $this->assertSame('Removed Guest', $values['guest_name']);
        $this->assertSame('removed@email.invalid', $values['guest_email']);
        $this->assertSame('', $values['partner_notes']);
    }

    public function testAnonymisedPartnerValuesClearContactFields(): void
    {
        $this->assertSame(
            [
                'partner_contact_name' => '',
                'partner_email'        => '',
                'partner_whatsapp'     => '',
            ],
            PrivacyDataHelper::anonymisedPartnerValues()
        );
    }
}
