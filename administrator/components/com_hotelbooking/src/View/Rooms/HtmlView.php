<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Rooms;

use Joomla\CMS\Factory;
use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $filterForm;
    protected $activeFilters;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state          = $this->get('State');
        $this->filterForm     = $this->get('FilterForm');
        $this->activeFilters  = $this->get('ActiveFilters');

        if (!Multilanguage::isEnabled()) {
            unset($this->activeFilters['language']);
            $this->filterForm->removeField('language', 'filter');
        }

        if ($this->getLayout() !== 'modal') {
            $this->addToolbar();
        } else {
            $forcedLanguage = Factory::getApplication()->getInput()->get('forcedLanguage', '', 'CMD');

            if ($forcedLanguage) {
                $languageXml = new \SimpleXMLElement('<field name="language" type="hidden" default="' . $forcedLanguage . '" />');
                $this->filterForm->setField($languageXml, 'filter', true);
                unset($this->activeFilters['language']);
            }

            $this->filterForm->addControlField('forcedLanguage', $forcedLanguage);
        }

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo = ContentHelper::getActions('com_hotelbooking');

        ToolbarHelper::title(Text::_('COM_HOTELBOOKING_ROOMS_TITLE'), 'hotelbooking');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('room.add');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('rooms.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('rooms.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'rooms.delete', 'JTOOLBAR_DELETE');
        }
    }
}
