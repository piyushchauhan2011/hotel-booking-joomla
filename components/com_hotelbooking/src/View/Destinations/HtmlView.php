<?php

namespace Learn\Component\Hotelbooking\Site\View\Destinations;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $items;
    protected $state;
    protected $pagination;
    protected $faqs;

    public function display($tpl = null)
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->useStyle('com_hotelbooking.site')->useScript('com_hotelbooking.search-autocomplete');

        $this->items      = $this->get('Items');
        $this->state      = $this->get('State');
        $this->pagination = $this->get('Pagination');
        $this->faqs       = $this->getModel()->getFaqs();

        return parent::display($tpl);
    }
}
