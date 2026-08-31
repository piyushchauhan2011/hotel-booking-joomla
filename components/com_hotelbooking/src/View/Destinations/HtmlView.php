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

    public function display($tpl = null)
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->registerAndUseStyle('com_hotelbooking.site', 'com_hotelbooking/hotelbooking.css', ['relative' => true, 'version' => 'auto']);
        $wa->registerAndUseScript('com_hotelbooking.search-autocomplete', 'com_hotelbooking/search-autocomplete.js', ['relative' => true, 'version' => 'auto'], ['defer' => true]);

        $this->items      = $this->get('Items');
        $this->state      = $this->get('State');
        $this->pagination = $this->get('Pagination');

        return parent::display($tpl);
    }
}
