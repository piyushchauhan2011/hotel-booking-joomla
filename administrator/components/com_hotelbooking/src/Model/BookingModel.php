<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\MVC\Model\WorkflowBehaviorTrait;
use Joomla\CMS\MVC\Model\WorkflowModelInterface;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;
use Learn\Component\Hotelbooking\Administrator\Table\RoomTable;

\defined('_JEXEC') or die;

class BookingModel extends AdminModel implements WorkflowModelInterface
{
    use WorkflowBehaviorTrait;

    public $typeAlias = 'com_hotelbooking.booking';

    public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?FormFactoryInterface $formFactory = null)
    {
        parent::__construct($config, $factory, $formFactory);

        $this->setUpWorkflow(BookingWorkflowHelper::EXTENSION);
    }

    public function getTable($name = 'Booking', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.booking', 'booking', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.booking.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        $this->workflowPreprocessForm($form, $data);

        if ($this->workflowEnabled) {
            $form->setFieldAttribute('status', 'readonly', 'true');
            $form->setFieldAttribute('partner_status', 'readonly', 'true');
        }

        parent::preprocessForm($form, $data, $group);
    }

    protected function getStageForNewItem(Form $form, $data)
    {
        $stageId = BookingWorkflowHelper::getDefaultStageId($this->getDatabase());

        return $stageId > 0 ? $stageId : false;
    }

    public function workflowCleanupBatchMove($oldId, $newId)
    {
        $stageId = BookingWorkflowHelper::getDefaultStageId($this->getDatabase());

        if ($stageId < 1) {
            return null;
        }

        $this->workflow->createAssociation((int) $newId, $stageId);

        return null;
    }

    public function save($data)
    {
        $user = $this->getCurrentUser();

        if (!AccessHelper::isPrivileged($user)) {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $table = $this->getTable();

            if (!$table->load($id)) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $auth = $this->getDestinationAuth((int) $table->room_id);

            if (!AccessHelper::canEditDestination($user, $auth['id'], $auth['created_by'])) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $newRoomId = (int) ($data['room_id'] ?? $table->room_id);

            if ($newRoomId !== (int) $table->room_id) {
                $newAuth = $this->getDestinationAuth($newRoomId);

                if ($newAuth['id'] !== $auth['id'] || !AccessHelper::canEditDestination($user, $newAuth['id'], $newAuth['created_by'])) {
                    $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                    return false;
                }
            }

            unset($data['commission_rate'], $data['commission_paid'], $data['commission_paid_date']);
        }

        if ($this->workflowEnabled) {
            unset($data['status'], $data['partner_status']);
        }

        $this->workflowBeforeSave();

        if (!parent::save($data)) {
            return false;
        }

        $this->workflowAfterSave($data);

        return true;
    }

    public function delete(&$pks)
    {
        $return = parent::delete($pks);

        if ($return) {
            $this->workflow->deleteAssociation($pks);
        }

        return $return;
    }

    public function executeTransition(array $pks, int $transitionId)
    {
        $user    = $this->getCurrentUser();
        $allowed = [];

        foreach ($pks as $pk) {
            $pk = (int) $pk;

            if ($pk < 1) {
                continue;
            }

            $auth = $this->getDestinationAuthForBooking($pk);

            if (AccessHelper::canEditDestination($user, $auth['id'], $auth['created_by'])) {
                $allowed[] = $pk;
            }
        }

        if ($allowed === []) {
            $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

            return false;
        }

        $result = $this->workflow->executeTransition($allowed, $transitionId);

        if (!$result) {
            Factory::getApplication()->enqueueMessage(Text::_('COM_HOTELBOOKING_ERROR_UPDATE_STAGE'), 'warning');

            return false;
        }

        return true;
    }

    /**
     * @return array{id:int,created_by:int}
     */
    private function getDestinationAuthForBooking(int $bookingId): array
    {
        $table = $this->getTable();

        if (!$table->load($bookingId)) {
            return ['id' => 0, 'created_by' => 0];
        }

        return $this->getDestinationAuth((int) $table->room_id);
    }

    /**
     * @return array{id:int,created_by:int}
     */
    private function getDestinationAuth(int $roomId): array
    {
        if ($roomId <= 0) {
            return ['id' => 0, 'created_by' => 0];
        }

        $roomTable = new RoomTable($this->getDatabase());

        if (!$roomTable->load($roomId)) {
            return ['id' => 0, 'created_by' => 0];
        }

        $destinationTable = new DestinationTable($this->getDatabase());

        if (!$destinationTable->load((int) $roomTable->destination_id)) {
            return ['id' => 0, 'created_by' => 0];
        }

        return [
            'id'         => (int) $destinationTable->id,
            'created_by' => (int) $destinationTable->created_by,
        ];
    }
}
