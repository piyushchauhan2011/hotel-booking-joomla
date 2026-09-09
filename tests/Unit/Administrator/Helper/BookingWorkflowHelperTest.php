<?php

declare(strict_types=1);

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\Registry\Registry;
use PHPUnit\Framework\TestCase;

final class BookingWorkflowHelperTest extends TestCase
{
    public function testFilterTransitionsKeepsMatchingFromStage(): void
    {
        $transitions = [
            ['from_stage_id' => 1, 'workflow_id' => 9, 'value' => 1],
            ['from_stage_id' => -1, 'workflow_id' => 9, 'value' => 2],
            ['from_stage_id' => 4, 'workflow_id' => 9, 'value' => 3],
            ['from_stage_id' => 1, 'workflow_id' => 8, 'value' => 4],
        ];

        $filtered = BookingWorkflowHelper::filterTransitions($transitions, 1, 9);

        $this->assertSame([1, 2], array_column($filtered, 'value'));
    }

    public function testFilterTransitionsWithoutWorkflowIdKeepsAnyMatchingStage(): void
    {
        $transitions = [
            ['from_stage_id' => 2, 'workflow_id' => 1, 'value' => 10],
            ['from_stage_id' => 3, 'workflow_id' => 1, 'value' => 11],
        ];

        $filtered = BookingWorkflowHelper::filterTransitions($transitions, 2);

        $this->assertSame([10], array_column($filtered, 'value'));
    }

    public function testStageAliasForBookingMapsPartnerAndGuestStatus(): void
    {
        $this->assertSame(
            BookingWorkflowHelper::STAGE_RECEIVED,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'         => 'pending',
                'partner_status' => 'awaiting_hotel_check',
            ]),
        );
        $this->assertSame(
            BookingWorkflowHelper::STAGE_HOTEL_NOTIFIED,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'            => 'pending',
                'partner_status'    => 'awaiting_hotel_check',
                'hotel_notified_at' => '2026-09-09 10:00:00',
            ]),
        );
        $this->assertSame(
            BookingWorkflowHelper::STAGE_AWAITING_PAYMENT,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'         => 'pending',
                'partner_status' => 'hotel_confirmed_awaiting_payment',
            ]),
        );
        $this->assertSame(
            BookingWorkflowHelper::STAGE_CONFIRMED,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'         => 'confirmed',
                'partner_status' => 'confirmed_paid',
            ]),
        );
        $this->assertSame(
            BookingWorkflowHelper::STAGE_DECLINED,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'         => 'cancelled',
                'partner_status' => 'declined_by_hotel',
            ]),
        );
        $this->assertSame(
            BookingWorkflowHelper::STAGE_CANCELLED,
            BookingWorkflowHelper::stageAliasForBooking((object) [
                'status'         => 'cancelled',
                'partner_status' => 'awaiting_hotel_check',
            ]),
        );
    }

    public function testMappedStatusValuesFitBookingColumns(): void
    {
        foreach (BookingWorkflowHelper::STAGE_STATUSES as $map) {
            $this->assertLessThanOrEqual(20, \strlen($map['guest_status']));
            $this->assertLessThanOrEqual(64, \strlen($map['partner_status']));
        }
    }

    public function testStatusesForStageReturnsGuestAndPartnerValues(): void
    {
        $this->assertSame(
            ['guest_status' => 'pending', 'partner_status' => 'awaiting_hotel_check'],
            BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_RECEIVED),
        );
        $this->assertSame(
            ['guest_status' => 'confirmed', 'partner_status' => 'confirmed_paid'],
            BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_CONFIRMED),
        );
        $this->assertSame(
            BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_RECEIVED),
            BookingWorkflowHelper::statusesForStage('unknown'),
        );
    }

    public function testNotifyTransitionIdPicksNotifyHotelAction(): void
    {
        $transitions = [
            ['id' => 8, 'options' => ['booking_action' => '', 'guest_status' => 'pending']],
            ['id' => 9, 'options' => ['booking_action' => BookingWorkflowHelper::ACTION_NOTIFY_HOTEL]],
            ['id' => 10, 'options' => ['booking_action' => BookingWorkflowHelper::ACTION_NOTIFY_HOTEL]],
        ];

        $this->assertSame(9, BookingWorkflowHelper::notifyTransitionId($transitions));
        $this->assertSame(0, BookingWorkflowHelper::notifyTransitionId([
            ['id' => 1, 'options' => ['booking_action' => '']],
        ]));
    }

    public function testShouldStopNotifyOnlyWhenNotifyFails(): void
    {
        $this->assertTrue(
            BookingWorkflowHelper::shouldStopNotify(BookingWorkflowHelper::ACTION_NOTIFY_HOTEL, false),
        );
        $this->assertFalse(
            BookingWorkflowHelper::shouldStopNotify(BookingWorkflowHelper::ACTION_NOTIFY_HOTEL, true),
        );
        $this->assertFalse(BookingWorkflowHelper::shouldStopNotify('', false));
    }

    public function testStatusesFromOptionsReadsRegistryAndArray(): void
    {
        $fromArray = BookingWorkflowHelper::statusesFromOptions([
            'booking_action' => BookingWorkflowHelper::ACTION_NOTIFY_HOTEL,
            'guest_status'   => 'pending',
            'partner_status' => 'awaiting_hotel_check',
        ]);

        $this->assertSame(BookingWorkflowHelper::ACTION_NOTIFY_HOTEL, $fromArray['action']);
        $this->assertSame('pending', $fromArray['guest_status']);
        $this->assertSame('awaiting_hotel_check', $fromArray['partner_status']);

        $fromRegistry = BookingWorkflowHelper::statusesFromOptions(new Registry([
            'booking_action' => '',
            'guest_status'   => 'confirmed',
            'partner_status' => 'confirmed_paid',
        ]));

        $this->assertSame('', $fromRegistry['action']);
        $this->assertSame('confirmed', $fromRegistry['guest_status']);
        $this->assertSame('confirmed_paid', $fromRegistry['partner_status']);
    }

    public function testApplyTransitionEffectsSkipsEmptyPks(): void
    {
        $db = $this->createMock(\Joomla\Database\DatabaseInterface::class);
        $db->expects($this->never())->method('createQuery');

        BookingWorkflowHelper::applyTransitionEffects($db, [], [
            'guest_status' => 'pending',
        ]);
    }

    public function testAssociateNewItemRejectsInvalidId(): void
    {
        $db = $this->createMock(\Joomla\Database\DatabaseInterface::class);

        $this->assertFalse(BookingWorkflowHelper::associateNewItem(0, $db));
    }

    public function testLoadNotifyContextRejectsInvalidId(): void
    {
        $db = $this->createMock(\Joomla\Database\DatabaseInterface::class);

        $this->assertNull(BookingWorkflowHelper::loadNotifyContext($db, 0));
    }
}
