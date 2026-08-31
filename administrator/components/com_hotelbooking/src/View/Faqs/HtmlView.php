<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Faqs;

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

        ToolbarHelper::title(Text::_('COM_HOTELBOOKING_FAQS_TITLE'), 'hotelbooking');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('faq.add');
        }

        if ($canDo->get('core.edit.state')) {
            ToolbarHelper::publish('faqs.publish', 'JTOOLBAR_PUBLISH', true);
            ToolbarHelper::unpublish('faqs.unpublish', 'JTOOLBAR_UNPUBLISH', true);
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'faqs.delete', 'JTOOLBAR_DELETE');
        }

        if ($canDo->get('core.admin')) {
            ToolbarHelper::preferences('com_hotelbooking');
        }
    }
}
