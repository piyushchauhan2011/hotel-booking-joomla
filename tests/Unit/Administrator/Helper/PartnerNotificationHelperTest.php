<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use PHPUnit\Framework\TestCase;

final class PartnerNotificationHelperTest extends TestCase
{
    public function testTemplateDataMapsBookingFields(): void
    {
        $booking = (object) [
            'guest_name'    => 'Maya Chen',
            'checkin_date'  => '2026-09-10',
            'checkout_date' => '2026-09-12',
            'guests'        => 2,
            'total_price'   => 199.5,
        ];
        $room        = (object) ['name' => 'Deluxe King'];
        $destination = (object) ['name' => 'Tokyo House'];

        $this->assertSame(
            [
                'sitename'    => 'Demo Site',
                'destination' => 'Tokyo House',
                'room'        => 'Deluxe King',
                'guest'       => 'Maya Chen',
                'checkin'     => '2026-09-10',
                'checkout'    => '2026-09-12',
                'guests'      => '2',
                'total'       => '199.50',
            ],
            PartnerNotificationHelper::templateData($booking, $room, $destination, 'Demo Site')
        );
    }

    public function testBuildWhatsAppLinkStripsNonDigits(): void
    {
        $this->assertSame(
            'https://wa.me/66812345678?text=Hello%20there',
            PartnerNotificationHelper::buildWhatsAppLink('+66 81-234-5678', 'Hello there')
        );
    }

    public function testBuildWhatsAppLinkReturnsEmptyWhenNoDigits(): void
    {
        $this->assertSame('', PartnerNotificationHelper::buildWhatsAppLink('abc', 'Hello'));
    }

    public function testBuildMessageSummaryReturnsAString(): void
    {
        $booking = (object) [
            'guest_name'    => 'Maya Chen',
            'checkin_date'  => '2026-09-10',
            'checkout_date' => '2026-09-12',
            'guests'        => 2,
            'total_price'   => 199.5,
        ];

        $summary = PartnerNotificationHelper::buildMessageSummary(
            $booking,
            (object) ['name' => 'Deluxe King'],
            (object) ['name' => 'Tokyo House'],
        );

        $this->assertNotSame('', $summary);
    }

    public function testSendEmailReturnsFalseWithoutPartnerEmail(): void
    {
        $this->assertFalse(
            PartnerNotificationHelper::sendEmail(
                (object) [],
                (object) ['name' => 'Deluxe King'],
                (object) ['name' => 'Tokyo House', 'partner_email' => ''],
            )
        );
    }

    public function testSendEmailReturnsFalseWhenMailerCannotBoot(): void
    {
        $this->assertFalse(
            PartnerNotificationHelper::sendEmail(
                (object) [
                    'guest_name'    => 'Maya Chen',
                    'checkin_date'  => '2026-09-10',
                    'checkout_date' => '2026-09-12',
                    'guests'        => 2,
                    'total_price'   => 10,
                ],
                (object) ['name' => 'Deluxe King'],
                (object) ['name' => 'Tokyo House', 'partner_email' => 'hotel@example.com'],
            )
        );
    }
}
