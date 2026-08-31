<?php

namespace Learn\Component\Hotelbooking\Site\View\Bookings;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $this->item = $this->getModel()->getItem();

        return parent::display($tpl);
    }
}
