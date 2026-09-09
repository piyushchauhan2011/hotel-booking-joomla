<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Workflow\Workflow;
use Joomla\Component\Workflow\Administrator\Table\StageTable;
use Joomla\Component\Workflow\Administrator\Table\TransitionTable;
use Joomla\Component\Workflow\Administrator\Table\WorkflowTable;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Registry\Registry;

\defined('_JEXEC') or die;

class BookingWorkflowSeeder
{
    public static function seed(DatabaseInterface $db): void
    {
        $workflowId = self::ensureWorkflow($db);
        $stageIds   = self::ensureStages($db, $workflowId);
        self::ensureTransitions($db, $workflowId, $stageIds);
        self::associateExistingBookings($db, $stageIds);
        self::enableWorkflowParam($db);
    }

    private static function ensureWorkflow(DatabaseInterface $db): int
    {
        $extension = BookingWorkflowHelper::EXTENSION;
        $query     = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__workflows'))
            ->where($db->quoteName('extension') . ' = :extension')
            ->bind(':extension', $extension)
            ->order($db->quoteName('id') . ' ASC')
            ->setLimit(1);
        $db->setQuery($query);
        $id = (int) $db->loadResult();

        if ($id > 0) {
            return $id;
        }

        $table              = new WorkflowTable($db);
        $table->title       = 'COM_HOTELBOOKING_WORKFLOW_TITLE';
        $table->description = 'COM_HOTELBOOKING_WORKFLOW_DESCRIPTION';
        $table->published   = 1;
        $table->default     = 1;
        $table->access      = (int) Factory::getApplication()->get('access', 1);
        $table->extension   = $extension;

        if (!$table->store()) {
            throw new \RuntimeException((string) $table->getError());
        }

        return (int) $table->id;
    }

    /**
     * @return array<string, int>
     */
    private static function ensureStages(DatabaseInterface $db, int $workflowId): array
    {
        $positions = [
            BookingWorkflowHelper::STAGE_RECEIVED         => ['x' => 80, 'y' => 80],
            BookingWorkflowHelper::STAGE_HOTEL_NOTIFIED   => ['x' => 320, 'y' => 80],
            BookingWorkflowHelper::STAGE_AWAITING_PAYMENT => ['x' => 560, 'y' => 80],
            BookingWorkflowHelper::STAGE_CONFIRMED        => ['x' => 800, 'y' => 80],
            BookingWorkflowHelper::STAGE_DECLINED         => ['x' => 320, 'y' => 260],
            BookingWorkflowHelper::STAGE_CANCELLED        => ['x' => 560, 'y' => 260],
        ];

        $ids    = [];
        $order  = 1;

        foreach (BookingWorkflowHelper::STAGE_TITLES as $alias => $title) {
            $ids[$alias] = self::ensureStage(
                $db,
                $workflowId,
                $title,
                $alias === BookingWorkflowHelper::STAGE_RECEIVED,
                $order,
                $positions[$alias],
            );
            $order++;
        }

        return $ids;
    }

    /**
     * @param  array{x:int, y:int}  $position
     */
    private static function ensureStage(
        DatabaseInterface $db,
        int $workflowId,
        string $title,
        bool $default,
        int $ordering,
        array $position,
    ): int {
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__workflow_stages'))
            ->where($db->quoteName('workflow_id') . ' = :workflowId')
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':workflowId', $workflowId, ParameterType::INTEGER)
            ->bind(':title', $title)
            ->setLimit(1);
        $db->setQuery($query);
        $id = (int) $db->loadResult();

        if ($id > 0) {
            return $id;
        }

        $table              = new StageTable($db);
        $table->title       = $title;
        $table->description = '';
        $table->published   = 1;
        $table->default     = $default ? 1 : 0;
        $table->ordering    = $ordering;
        $table->workflow_id = $workflowId;
        $table->position    = json_encode($position);

        if (!$table->store()) {
            throw new \RuntimeException((string) $table->getError());
        }

        return (int) $table->id;
    }

    /**
     * @param  array<string, int>  $stageIds
     */
    private static function ensureTransitions(DatabaseInterface $db, int $workflowId, array $stageIds): void
    {
        $received  = $stageIds[BookingWorkflowHelper::STAGE_RECEIVED];
        $notified  = $stageIds[BookingWorkflowHelper::STAGE_HOTEL_NOTIFIED];
        $awaiting  = $stageIds[BookingWorkflowHelper::STAGE_AWAITING_PAYMENT];
        $confirmed = $stageIds[BookingWorkflowHelper::STAGE_CONFIRMED];
        $declined  = $stageIds[BookingWorkflowHelper::STAGE_DECLINED];
        $cancelled = $stageIds[BookingWorkflowHelper::STAGE_CANCELLED];

        $notifiedStatuses  = BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_HOTEL_NOTIFIED);
        $awaitingStatuses  = BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_AWAITING_PAYMENT);
        $confirmedStatuses = BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_CONFIRMED);
        $declinedStatuses  = BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_DECLINED);
        $cancelledStatuses = BookingWorkflowHelper::statusesForStage(BookingWorkflowHelper::STAGE_CANCELLED);

        $transitions = [
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_NOTIFY_HOTEL',
                'from_stage_id' => $received,
                'to_stage_id'   => $notified,
                'options'       => [
                    'booking_action' => BookingWorkflowHelper::ACTION_NOTIFY_HOTEL,
                    'guest_status'   => $notifiedStatuses['guest_status'],
                    'partner_status' => $notifiedStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_HOTEL_CONFIRMS',
                'from_stage_id' => $notified,
                'to_stage_id'   => $awaiting,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $awaitingStatuses['guest_status'],
                    'partner_status' => $awaitingStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_MARK_PAID',
                'from_stage_id' => $awaiting,
                'to_stage_id'   => $confirmed,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $confirmedStatuses['guest_status'],
                    'partner_status' => $confirmedStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_DECLINE_RECEIVED',
                'from_stage_id' => $received,
                'to_stage_id'   => $declined,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $declinedStatuses['guest_status'],
                    'partner_status' => $declinedStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_DECLINE_NOTIFIED',
                'from_stage_id' => $notified,
                'to_stage_id'   => $declined,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $declinedStatuses['guest_status'],
                    'partner_status' => $declinedStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_CANCEL_RECEIVED',
                'from_stage_id' => $received,
                'to_stage_id'   => $cancelled,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $cancelledStatuses['guest_status'],
                    'partner_status' => $cancelledStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_CANCEL_NOTIFIED',
                'from_stage_id' => $notified,
                'to_stage_id'   => $cancelled,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $cancelledStatuses['guest_status'],
                    'partner_status' => $cancelledStatuses['partner_status'],
                ],
            ],
            [
                'title'         => 'COM_HOTELBOOKING_WORKFLOW_TRANSITION_CANCEL_AWAITING',
                'from_stage_id' => $awaiting,
                'to_stage_id'   => $cancelled,
                'options'       => [
                    'booking_action' => '',
                    'guest_status'   => $cancelledStatuses['guest_status'],
                    'partner_status' => $cancelledStatuses['partner_status'],
                ],
            ],
        ];

        foreach ($transitions as $i => $item) {
            self::ensureTransition($db, $workflowId, $item, $i + 1);
        }
    }

    /**
     * @param  array{title:string, from_stage_id:int, to_stage_id:int, options:array<string, string>}  $item
     */
    private static function ensureTransition(DatabaseInterface $db, int $workflowId, array $item, int $ordering): void
    {
        $from  = (int) $item['from_stage_id'];
        $to    = (int) $item['to_stage_id'];
        $title = $item['title'];
        $query = $db->createQuery()
            ->select($db->quoteName('id'))
            ->from($db->quoteName('#__workflow_transitions'))
            ->where($db->quoteName('workflow_id') . ' = :workflowId')
            ->where($db->quoteName('from_stage_id') . ' = :fromStage')
            ->where($db->quoteName('to_stage_id') . ' = :toStage')
            ->where($db->quoteName('title') . ' = :title')
            ->bind(':workflowId', $workflowId, ParameterType::INTEGER)
            ->bind(':fromStage', $from, ParameterType::INTEGER)
            ->bind(':toStage', $to, ParameterType::INTEGER)
            ->bind(':title', $title)
            ->setLimit(1);
        $db->setQuery($query);

        if ((int) $db->loadResult() > 0) {
            return;
        }

        $table                = new TransitionTable($db);
        $table->title         = $title;
        $table->description   = '';
        $table->from_stage_id = $from;
        $table->to_stage_id   = $to;
        $table->workflow_id   = $workflowId;
        $table->published     = 1;
        $table->ordering      = $ordering;
        $table->options       = json_encode($item['options']);

        if (!$table->store()) {
            throw new \RuntimeException((string) $table->getError());
        }
    }

    /**
     * @param  array<string, int>  $stageIds
     */
    private static function associateExistingBookings(DatabaseInterface $db, array $stageIds): void
    {
        $extension = BookingWorkflowHelper::EXTENSION;
        $query     = $db->createQuery()
            ->select($db->quoteName(['id', 'status', 'partner_status', 'hotel_notified_at']))
            ->from($db->quoteName('#__hotelbooking_bookings', 'b'))
            ->where(
                'NOT EXISTS (SELECT 1 FROM ' . $db->quoteName('#__workflow_associations', 'wa')
                . ' WHERE ' . $db->quoteName('wa.item_id') . ' = ' . $db->quoteName('b.id')
                . ' AND ' . $db->quoteName('wa.extension') . ' = :extension)',
            )
            ->bind(':extension', $extension);
        $db->setQuery($query);
        $rows = $db->loadObjectList() ?: [];

        if ($rows === []) {
            return;
        }

        $workflow = new Workflow(
            $extension,
            Factory::getApplication(),
            $db instanceof DatabaseDriver ? $db : Factory::getDbo(),
        );

        foreach ($rows as $row) {
            $alias   = BookingWorkflowHelper::stageAliasForBooking($row);
            $stageId = $stageIds[$alias] ?? ($stageIds[BookingWorkflowHelper::STAGE_RECEIVED] ?? 0);

            if ($stageId < 1) {
                continue;
            }

            $workflow->createAssociation((int) $row->id, $stageId);
        }
    }

    private static function enableWorkflowParam(DatabaseInterface $db): void
    {
        $element = 'com_hotelbooking';
        $type    = 'component';
        $query   = $db->createQuery()
            ->select([$db->quoteName('extension_id'), $db->quoteName('params')])
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = :type')
            ->where($db->quoteName('element') . ' = :element')
            ->bind(':type', $type)
            ->bind(':element', $element)
            ->setLimit(1);
        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row) {
            return;
        }

        $params = new Registry($row->params);

        if ((int) $params->get('workflow_enabled') === 1) {
            return;
        }

        $params->set('workflow_enabled', 1);
        $encoded = $params->toString();
        $id      = (int) $row->extension_id;
        $query   = $db->createQuery()
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = :params')
            ->where($db->quoteName('extension_id') . ' = :id')
            ->bind(':params', $encoded)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query)->execute();
    }
}
