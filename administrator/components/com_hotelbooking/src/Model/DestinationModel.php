<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class DestinationModel extends AdminModel
{
    public $typeAlias = 'com_hotelbooking.destination';

    public function getTable($name = 'Destination', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.destination', 'destination', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.destination.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    public function save($data)
    {
        if (empty($data['alias'])) {
            $data['alias'] = $data['name'] ?? '';
        }

        $data['alias'] = ApplicationHelper::stringURLSafe($data['alias']);

        $user = $this->getCurrentUser();

        if (!AccessHelper::isPrivileged($user)) {
            $id = (int) ($data['id'] ?? 0);

            if ($id <= 0) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $table = $this->getTable();

            if (!$table->load($id) || !AccessHelper::canEditDestination($user, (int) $table->manager_user_id)) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            unset($data['manager_user_id']);
            $data['published'] = 0;
        }

        return parent::save($data);
    }
}
