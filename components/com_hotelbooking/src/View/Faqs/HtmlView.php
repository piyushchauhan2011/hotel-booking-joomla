<?php

namespace Learn\Component\Hotelbooking\Site\View\Faqs;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $items;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $this->items = $this->getModel()->getItems();

        return parent::display($tpl);
    }
}
