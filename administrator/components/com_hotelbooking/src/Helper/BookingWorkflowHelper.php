<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

class BookingWorkflowHelper
{
    public const EXTENSION = 'com_hotelbooking.booking';

    public const STAGE_RECEIVED = 'received';
    public const STAGE_HOTEL_NOTIFIED = 'hotel_notified';
    public const STAGE_AWAITING_PAYMENT = 'awaiting_payment';
    public const STAGE_CONFIRMED = 'confirmed';
    public const STAGE_DECLINED = 'declined';
    public const STAGE_CANCELLED = 'cancelled';

    public const ACTION_NOTIFY_HOTEL = 'notify_hotel';

    /**
     * @var array<string, string>
     */
    public const STAGE_TITLES = [
        self::STAGE_RECEIVED         => 'COM_HOTELBOOKING_WORKFLOW_STAGE_RECEIVED',
        self::STAGE_HOTEL_NOTIFIED   => 'COM_HOTELBOOKING_WORKFLOW_STAGE_HOTEL_NOTIFIED',
        self::STAGE_AWAITING_PAYMENT => 'COM_HOTELBOOKING_WORKFLOW_STAGE_AWAITING_PAYMENT',
        self::STAGE_CONFIRMED        => 'COM_HOTELBOOKING_WORKFLOW_STAGE_CONFIRMED',
        self::STAGE_DECLINED         => 'COM_HOTELBOOKING_WORKFLOW_STAGE_DECLINED',
        self::STAGE_CANCELLED        => 'COM_HOTELBOOKING_WORKFLOW_STAGE_CANCELLED',
    ];

    /**
     * @var array<string, array{guest_status:string, partner_status:string}>
     */
    public const STAGE_STATUSES = [
        self::STAGE_RECEIVED         => ['guest_status' => 'pending', 'partner_status' => 'awaiting_hotel_check'],
        self::STAGE_HOTEL_NOTIFIED   => ['guest_status' => 'pending', 'partner_status' => 'awaiting_hotel_check'],
        self::STAGE_AWAITING_PAYMENT => ['guest_status' => 'pending', 'partner_status' => 'hotel_confirmed_awaiting_payment'],
        self::STAGE_CONFIRMED        => ['guest_status' => 'confirmed', 'partner_status' => 'confirmed_paid'],
        self::STAGE_DECLINED         => ['guest_status' => 'cancelled', 'partner_status' => 'declined_by_hotel'],
        self::STAGE_CANCELLED        => ['guest_status' => 'cancelled', 'partner_status' => 'awaiting_hotel_check'],
    ];

    public static function isEnabled(): bool
    {
        return (bool) ComponentHelper::getParams('com_hotelbooking')->get('workflow_enabled');
    }

    /**
     * @param  array<int, array<string, mixed>>  $transitions
     *
     * @return list<array<string, mixed>>
     */
    public static function filterTransitions(array $transitions, int $pk, int $workflowId = 0): array
    {
        return array_values(array_filter(
            $transitions,
            static function ($var) use ($pk, $workflowId) {
                $from = (int) ($var['from_stage_id'] ?? 0);
                $wid  = (int) ($var['workflow_id'] ?? 0);

                return \in_array($from, [-1, $pk], true) && ($workflowId === 0 || $wid === $workflowId);
            },
        ));
    }

    public static function stageAliasForBooking(object $booking): string
    {
        $status        = (string) ($booking->status ?? '');
        $partnerStatus = (string) ($booking->partner_status ?? '');

        if ($partnerStatus === 'declined_by_hotel' || ($status === 'cancelled' && $partnerStatus === 'declined_by_hotel')) {
            return self::STAGE_DECLINED;
        }

        if ($status === 'cancelled') {
            return self::STAGE_CANCELLED;
        }

        if ($partnerStatus === 'confirmed_paid' || $status === 'confirmed') {
            return self::STAGE_CONFIRMED;
        }

        if ($partnerStatus === 'hotel_confirmed_awaiting_payment') {
            return self::STAGE_AWAITING_PAYMENT;
        }

        if (!empty($booking->hotel_notified_at)) {
            return self::STAGE_HOTEL_NOTIFIED;
        }

        return self::STAGE_RECEIVED;
    }

    /**
     * @return array{guest_status:string, partner_status:string}
     */
    public static function statusesForStage(string $alias): array
    {
        return self::STAGE_STATUSES[$alias] ?? self::STAGE_STATUSES[self::STAGE_RECEIVED];
    }

    public static function shouldStopNotify(string $action, bool $sent): bool
    {
        return $action === self::ACTION_NOTIFY_HOTEL && !$sent;
    }

    /**
     * @param  array<string, mixed>|Registry  $options
     *
     * @return array{action:string, guest_status:string, partner_status:string}
     */
    public static function statusesFromOptions($options): array
    {
        if (!$options instanceof Registry) {
            $options = new Registry($options);
        }

        return [
            'action'         => (string) $options->get('booking_action', ''),
            'guest_status'   => (string) $options->get('guest_status', ''),
            'partner_status' => (string) $options->get('partner_status', ''),
        ];
    }

    public static function getDefaultStageId(DatabaseInterface $db): int
    {
        $extension = self::EXTENSION;
        $query     = $db->createQuery()
            ->select($db->quoteName('s.id'))
            ->from($db->quoteName('#__workflow_stages', 's'))
            ->join('INNER', $db->quoteName('#__workflows', 'w'), $db->quoteName('w.id') . ' = ' . $db->quoteName('s.workflow_id'))
            ->where($db->quoteName('w.extension') . ' = :extension')
            ->where($db->quoteName('w.published') . ' = 1')
            ->where($db->quoteName('s.published') . ' = 1')
            ->where($db->quoteName('s.default') . ' = 1')
            ->bind(':extension', $extension)
            ->order($db->quoteName('w.default') . ' DESC')
            ->setLimit(1);
        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    public static function associateNewItem(int $id, DatabaseInterface $db): bool
    {
        if ($id < 1) {
            return false;
        }

        $stageId = self::getDefaultStageId($db);

        if ($stageId < 1) {
            return false;
        }

        $workflow = new Workflow(
            self::EXTENSION,
            Factory::getApplication(),
            $db instanceof DatabaseDriver ? $db : Factory::getDbo(),
        );

        if ($workflow->getAssociation($id)) {
            return true;
        }

        return $workflow->createAssociation($id, $stageId);
    }

    /**
     * @return array{booking:object, room:object, destination:object}|null
     */
    public static function loadNotifyContext(DatabaseInterface $db, int $bookingId): ?array
    {
        if ($bookingId < 1) {
            return null;
        }

        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_bookings'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $bookingId, ParameterType::INTEGER);
        $db->setQuery($query);
        $booking = $db->loadObject();

        if (!$booking) {
            return null;
        }

        $roomId = (int) $booking->room_id;
        $query  = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_rooms'))
            ->where($db->quoteName('id') . ' = :roomId')
            ->bind(':roomId', $roomId, ParameterType::INTEGER);
        $db->setQuery($query);
        $room = $db->loadObject();

        if (!$room) {
            return null;
        }

        $destinationId = (int) $room->destination_id;
        $query         = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('id') . ' = :destinationId')
            ->bind(':destinationId', $destinationId, ParameterType::INTEGER);
        $db->setQuery($query);
        $destination = $db->loadObject();

        if (!$destination) {
            return null;
        }

        return [
            'booking'     => $booking,
            'room'        => $room,
            'destination' => $destination,
        ];
    }

    /**
     * @param  list<int>  $pks
     * @param  array<string, mixed>|Registry  $options
     */
    public static function applyTransitionEffects(DatabaseInterface $db, array $pks, $options): void
    {
        $parsed = self::statusesFromOptions($options);
        $pks    = array_values(array_filter(array_map('intval', $pks)));

        if ($pks === []) {
            return;
        }

        $sets   = [];
        $query  = $db->createQuery();
        $now    = Factory::getDate()->toSql();

        if ($parsed['guest_status'] !== '') {
            $sets[] = $db->quoteName('status') . ' = :guestStatus';
            $query->bind(':guestStatus', $parsed['guest_status']);
        }

        if ($parsed['partner_status'] !== '') {
            $sets[] = $db->quoteName('partner_status') . ' = :partnerStatus';
            $query->bind(':partnerStatus', $parsed['partner_status']);
        }

        if ($parsed['action'] === self::ACTION_NOTIFY_HOTEL) {
            $sets[] = $db->quoteName('hotel_notified_at') . ' = :notifiedAt';
            $query->bind(':notifiedAt', $now);
        }

        if ($sets === []) {
            return;
        }

        $query->update($db->quoteName('#__hotelbooking_bookings'))
            ->set($sets)
            ->whereIn($db->quoteName('id'), $pks);
        $db->setQuery($query)->execute();
    }
}
