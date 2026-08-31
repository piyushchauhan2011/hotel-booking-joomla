<?php

namespace Learn\Component\Hotelbooking\Site\View\Home;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $destinations;

    public function display($tpl = null)
    {
        $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
        $wa->useStyle('com_hotelbooking.site')->useScript('com_hotelbooking.search-autocomplete');

        $this->destinations = $this->getModel()->getFeaturedDestinations();

        return parent::display($tpl);
    }
}
