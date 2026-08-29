<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Destinations;

use Joomla\CMS\Factory;
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

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo = \Joomla\CMS\Helper\ContentHelper::getActions('com_hotelbooking');

        ToolbarHelper::title(Text::_('COM_HOTELBOOKING_DESTINATIONS_TITLE'), 'hotelbooking');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('destination.add');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('destinations.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('destinations.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'destinations.delete', 'JTOOLBAR_DELETE');
        }

        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_hotelbooking');
        }
    }
}
