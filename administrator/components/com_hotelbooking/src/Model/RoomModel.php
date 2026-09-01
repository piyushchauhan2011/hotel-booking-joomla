<?php

namespace Learn\Component\Hotelbooking\Administrator\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;

\defined('_JEXEC') or die;

class RoomModel extends AdminModel
{
    public $typeAlias = 'com_hotelbooking.room';

    protected $associationsContext = 'com_hotelbooking.item.room';

    public function getTable($name = 'Room', $prefix = 'Administrator', $options = []): Table
    {
        return parent::getTable($name, $prefix, $options);
    }

    public function getItem($pk = null)
    {
        $item = parent::getItem($pk);

        if ($item && Associations::isEnabled() && !empty($item->id)) {
            $item->associations = [];

            $associations = Associations::getAssociations(
                'com_hotelbooking',
                '#__hotelbooking_rooms',
                'com_hotelbooking.item.room',
                $item->id,
                'id',
                'alias',
                ''
            );

            foreach ($associations as $tag => $association) {
                $item->associations[$tag] = $association->id;
            }
        }

        return $item;
    }

    protected function preprocessForm(Form $form, $data, $group = 'content')
    {
        if (Associations::isEnabled()) {
            $languages = LanguageHelper::getContentLanguages(false, false, null, 'ordering', 'asc');

            if (\count($languages) > 1) {
                $addform = new \SimpleXMLElement('<form />');
                $fields  = $addform->addChild('fields');
                $fields->addAttribute('name', 'associations');
                $fieldset = $fields->addChild('fieldset');
                $fieldset->addAttribute('name', 'item_associations');

                foreach ($languages as $language) {
                    $field = $fieldset->addChild('field');
                    $field->addAttribute('name', $language->lang_code);
                    $field->addAttribute('type', 'modal_room');
                    $field->addAttribute('language', $language->lang_code);
                    $field->addAttribute('label', $language->title);
                    $field->addAttribute('translate_label', 'false');
                    $field->addAttribute('select', 'true');
                    $field->addAttribute('new', 'true');
                    $field->addAttribute('edit', 'true');
                    $field->addAttribute('clear', 'true');
                }

                $form->load($addform, false);
            }
        }

        parent::preprocessForm($form, $data, $group);
    }

    public function getForm($data = [], $loadData = true)
    {
        $form = $this->loadForm('com_hotelbooking.room', 'room', ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    protected function loadFormData()
    {
        $data = Factory::getApplication()->getUserState('com_hotelbooking.edit.room.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
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

            if (!$table->load($id) || !AccessHelper::canEditRoom($user, $this->getDestinationManagerId((int) $table->destination_id))) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $newDestinationId = (int) ($data['destination_id'] ?? $table->destination_id);

            if ($newDestinationId !== (int) $table->destination_id) {
                $this->setError(Text::_('JERROR_ALERTNOAUTHOR'));

                return false;
            }

            $data['published'] = 0;
        }

        return parent::save($data);
    }

    private function getDestinationManagerId(int $destinationId): int
    {
        if ($destinationId <= 0) {
            return 0;
        }

        $destinationTable = new DestinationTable($this->getDatabase());

        if (!$destinationTable->load($destinationId)) {
            return 0;
        }

        return (int) $destinationTable->manager_user_id;
    }
}
