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
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_hotelbooking.site', 'com_hotelbooking/hotelbooking.css', ['relative' => true, 'version' => 'auto']);

        $this->destinations = $this->getModel()->getFeaturedDestinations();

        return parent::display($tpl);
    }
}
