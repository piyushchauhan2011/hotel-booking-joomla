<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Snippets;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $pagination;
    protected $state;
    protected $filterForm;
    protected $activeFilters;
    protected $editor;

    public function display($tpl = null)
    {
        $this->items         = $this->get('Items');
        $this->pagination    = $this->get('Pagination');
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');
        $this->editor        = Factory::getApplication()->getInput()->getCmd('editor', '');

        $type = (string) $this->state->get('filter.type', 'destination');
        $this->pagination->setAdditionalUrlParam('editor', $this->editor);
        $this->pagination->setAdditionalUrlParam(Session::getFormToken(), '1');
        $this->pagination->setAdditionalUrlParam('filter_type', $type);

        if ($this->filterForm) {
            $this->filterForm->setValue('destination_id', 'filter', $this->state->get('filter.destination_id'));
            $this->filterForm->setValue('entity', 'filter', $this->state->get('filter.entity'));
            $this->filterForm->setValue('limit', 'list', $this->state->get('list.limit'));
            $this->filterForm->addControlField('editor', $this->editor);
        }

        return parent::display($tpl);
    }

    public function getTabUrl(string $type): string
    {
        return Route::_(
            'index.php?option=com_hotelbooking&view=snippets&layout=modal&tmpl=component&editor='
            . urlencode($this->editor)
            . '&' . Session::getFormToken() . '=1&filter_type=' . $type . '&limitstart=0',
        );
    }

    public function hasActiveFilters(): bool
    {
        return trim((string) $this->state->get('filter.search', '')) !== ''
            || (string) $this->state->get('filter.destination_id', '') !== ''
            || (string) $this->state->get('filter.entity', '') !== '';
    }

    public function getEmptyMessage(): string
    {
        if ($this->hasActiveFilters()) {
            return Text::_('JGLOBAL_NO_MATCHING_RESULTS');
        }

        return Text::_('COM_HOTELBOOKING_SNIPPETS_EMPTY');
    }
}
