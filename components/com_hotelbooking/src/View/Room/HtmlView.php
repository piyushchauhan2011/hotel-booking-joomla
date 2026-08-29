<?php

namespace Learn\Component\Hotelbooking\Site\View\Room;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_hotelbooking.site', 'com_hotelbooking/hotelbooking.css', ['relative' => true, 'version' => 'auto']);

        $this->item = $this->getModel()->getItem();

        return parent::display($tpl);
    }
}
