<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

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

        return parent::save($data);
    }
}
