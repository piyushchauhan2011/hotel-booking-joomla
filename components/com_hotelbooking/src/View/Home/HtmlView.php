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
        $wa->registerAndUseStyle('com_hotelbooking.site', 'com_hotelbooking/hotelbooking.css', ['relative' => true, 'version' => 'auto']);
        $wa->registerAndUseScript('com_hotelbooking.search-autocomplete', 'com_hotelbooking/search-autocomplete.js', ['relative' => true, 'version' => 'auto'], ['defer' => true]);

        $this->destinations = $this->getModel()->getFeaturedDestinations();

        return parent::display($tpl);
    }
}
