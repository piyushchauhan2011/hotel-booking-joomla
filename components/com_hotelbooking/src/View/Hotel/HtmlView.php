<?php

namespace Learn\Component\Hotelbooking\Site\View\Hotel;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $destinationId = 0;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $this->destinationId = Factory::getApplication()->getInput()->getInt('id', 0);

        return parent::display($tpl);
    }
}
