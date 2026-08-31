<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Snippets;

use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $destinations;
    protected $rooms;
    protected $offers;

    public function display($tpl = null)
    {
        $this->destinations = $this->get('Destinations');
        $this->rooms        = $this->get('Rooms');
        $this->offers       = $this->get('Offers');

        return parent::display($tpl);
    }
}
