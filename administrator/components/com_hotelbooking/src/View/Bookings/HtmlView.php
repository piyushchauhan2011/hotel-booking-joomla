<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Bookings;

use Joomla\CMS\Helper\ContentHelper;
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
        $canDo = ContentHelper::getActions('com_hotelbooking');

        ToolbarHelper::title(Text::_('COM_HOTELBOOKING_BOOKINGS_TITLE'), 'hotelbooking');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('booking.add');
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'bookings.delete', 'JTOOLBAR_DELETE');
        }
    }
}
