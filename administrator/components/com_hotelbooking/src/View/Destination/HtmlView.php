<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Destination;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $form;

    public function display($tpl = null)
    {
        Factory::getApplication()->getInput()->set('hidemainmenu', true);

        $this->item = $this->get('Item');
        $this->form = $this->get('Form');

        $forcedLanguage = Factory::getApplication()->getInput()->get('forcedLanguage', '', 'cmd');

        if ($this->getLayout() === 'modal' && $forcedLanguage) {
            $this->form->setValue('language', null, $forcedLanguage);
            $this->form->setFieldAttribute('language', 'readonly', 'true');
        }

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_HOTELBOOKING_DESTINATION_NEW' : 'COM_HOTELBOOKING_DESTINATION_EDIT'), 'hotelbooking');
        ToolbarHelper::apply('destination.apply');
        ToolbarHelper::save('destination.save');
        ToolbarHelper::cancel('destination.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');

        if (
            !$isNew
            && Associations::isEnabled()
            && ComponentHelper::isEnabled('com_associations')
        ) {
            ToolbarHelper::custom('destination.editAssociations', 'contract', 'contract', 'JTOOLBAR_ASSOCIATIONS', false);
        }
    }
}
