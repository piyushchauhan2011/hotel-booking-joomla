<?php

namespace Learn\Component\Hotelbooking\Administrator\Field\Modal;

use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class RoomField extends ModalSelectField
{
    protected $type = 'Modal_Room';

    public function setup(\SimpleXMLElement $element, $value, $group = null)
    {
        if ($value && str_contains($value, ':')) {
            [$id]  = explode(':', $value, 2);
            $value = (int) $id;
        }

        $result = parent::setup($element, $value, $group);

        if (!$result) {
            return $result;
        }

        $language = (string) $this->element['language'];

        $linkRooms = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkRooms->setQuery([
            'option'                => 'com_hotelbooking',
            'view'                  => 'rooms',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);
        $linkRoom = clone $linkRooms;
        $linkRoom->setVar('view', 'room');

        if ($language) {
            $linkRooms->setVar('forcedLanguage', $language);
            $linkRoom->setVar('forcedLanguage', $language);

            $this->dataAttributes['data-language'] = $language;
        }

        $urlSelect = $linkRooms;
        $urlEdit   = clone $linkRoom;
        $urlEdit->setVar('task', 'room.edit');
        $urlNew = clone $linkRoom;
        $urlNew->setVar('task', 'room.add');

        $this->urls['select'] = (string) $urlSelect;
        $this->urls['new']    = (string) $urlNew;
        $this->urls['edit']   = (string) $urlEdit;

        $this->modalTitles['select'] = Text::_('COM_HOTELBOOKING_SELECT_A_ROOM');
        $this->modalTitles['new']    = Text::_('COM_HOTELBOOKING_ROOM_NEW');
        $this->modalTitles['edit']   = Text::_('COM_HOTELBOOKING_ROOM_EDIT');

        $this->hint = $this->hint ?: Text::_('COM_HOTELBOOKING_SELECT_A_ROOM');

        return $result;
    }

    protected function getValueTitle()
    {
        $value = (int) $this->value ?: '';
        $title = '';

        if ($value) {
            $db    = $this->getDatabase();
            $query = $db->createQuery()
                ->select($db->quoteName('name'))
                ->from($db->quoteName('#__hotelbooking_rooms'))
                ->where($db->quoteName('id') . ' = :value')
                ->bind(':value', $value, ParameterType::INTEGER);
            $db->setQuery($query);

            $title = $db->loadResult();
        }

        return $title ?: $value;
    }

    protected function getLayoutData()
    {
        $data             = parent::getLayoutData();
        $data['language'] = (string) $this->element['language'];

        return $data;
    }

    protected function getRenderer($layoutId = 'default')
    {
        $layout = parent::getRenderer($layoutId);
        $layout->setComponent('com_hotelbooking');
        $layout->setClient(1);

        return $layout;
    }
}
