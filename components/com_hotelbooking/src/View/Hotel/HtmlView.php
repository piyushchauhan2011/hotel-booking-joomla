<?php

namespace Learn\Component\Hotelbooking\Site\View\Hotel;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        return parent::display($tpl);
    }
}
