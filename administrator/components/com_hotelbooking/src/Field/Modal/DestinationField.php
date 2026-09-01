<?php

namespace Learn\Component\Hotelbooking\Administrator\Field\Modal;

use Joomla\CMS\Form\Field\ModalSelectField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\ParameterType;

\defined('_JEXEC') or die;

class DestinationField extends ModalSelectField
{
    protected $type = 'Modal_Destination';

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

        $linkDestinations = (new Uri())->setPath(Uri::base(true) . '/index.php');
        $linkDestinations->setQuery([
            'option'                => 'com_hotelbooking',
            'view'                  => 'destinations',
            'layout'                => 'modal',
            'tmpl'                  => 'component',
            Session::getFormToken() => 1,
        ]);
        $linkDestination = clone $linkDestinations;
        $linkDestination->setVar('view', 'destination');

        if ($language) {
            $linkDestinations->setVar('forcedLanguage', $language);
            $linkDestination->setVar('forcedLanguage', $language);

            $this->dataAttributes['data-language'] = $language;
        }

        $urlSelect = $linkDestinations;
        $urlEdit   = clone $linkDestination;
        $urlEdit->setVar('task', 'destination.edit');
        $urlNew = clone $linkDestination;
        $urlNew->setVar('task', 'destination.add');

        $this->urls['select'] = (string) $urlSelect;
        $this->urls['new']    = (string) $urlNew;
        $this->urls['edit']   = (string) $urlEdit;

        $this->modalTitles['select'] = Text::_('COM_HOTELBOOKING_SELECT_A_DESTINATION');
        $this->modalTitles['new']    = Text::_('COM_HOTELBOOKING_DESTINATION_NEW');
        $this->modalTitles['edit']   = Text::_('COM_HOTELBOOKING_DESTINATION_EDIT');

        $this->hint = $this->hint ?: Text::_('COM_HOTELBOOKING_SELECT_A_DESTINATION');

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
                ->from($db->quoteName('#__hotelbooking_destinations'))
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
