<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Bookings;

use Joomla\CMS\Helper\ContentHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Toolbar\Button\DropdownButton;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $filterForm;
    protected $activeFilters;

    /**
     * @var list<array<string, mixed>>
     */
    protected $transitions = [];

    protected $workflowEnabled = false;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->workflowEnabled = BookingWorkflowHelper::isEnabled();

        if ($this->workflowEnabled) {
            PluginHelper::importPlugin('workflow');
            $this->transitions = $this->get('Transitions') ?: [];
        } elseif ($this->filterForm) {
            $this->filterForm->removeField('stage', 'filter');
        }

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function addToolbar()
    {
        $canDo   = ContentHelper::getActions('com_hotelbooking');
        $toolbar = Toolbar::getInstance('toolbar');

        ToolbarHelper::title(Text::_('COM_HOTELBOOKING_BOOKINGS_TITLE'), 'hotelbooking');

        if ($canDo->get('core.create')) {
            ToolbarHelper::addNew('booking.add');
        }

        if ($this->workflowEnabled && $this->transitions !== [] && $canDo->get('core.execute.transition')) {
            $dropdown = $toolbar->dropdownButton('status-group');

            if ($dropdown instanceof DropdownButton) {
                $dropdown->toggleSplit(false);
                $dropdown->text('JTOOLBAR_CHANGE_STATUS');
                $dropdown->icon('icon-ellipsis-h');
                $dropdown->buttonClass('btn btn-action');
                $dropdown->listCheck(true);

                $childBar = $dropdown->getChildToolbar();
                $childBar->separatorButton('transition-headline')
                    ->text('COM_HOTELBOOKING_RUN_TRANSITIONS')
                    ->buttonClass('text-center py-2 h3');

                $cmd      = "Joomla.submitbutton('bookings.runTransition');";
                $messages = "{error: [Joomla.Text._('JLIB_HTML_PLEASE_MAKE_A_SELECTION_FROM_THE_LIST')]}";
                $alert    = 'Joomla.renderMessages(' . $messages . ')';
                $cmd      = 'if (document.adminForm.boxchecked.value == 0) { ' . $alert . ' } else { ' . $cmd . ' }';

                foreach ($this->transitions as $transition) {
                    $transitionValue = (int) ($transition['value'] ?? 0);

                    $childBar->standardButton('transition', $transition['text'])
                        ->buttonClass('transition-' . $transitionValue)
                        ->icon('icon-project-diagram')
                        ->onclick('document.adminForm.transition_id.value=' . $transitionValue . ';' . $cmd);
                }

                $childBar->separatorButton('transition-separator');
            }
        }

        if ($canDo->get('core.delete')) {
            ToolbarHelper::deleteList('', 'bookings.delete', 'JTOOLBAR_DELETE');
        }
    }
}
