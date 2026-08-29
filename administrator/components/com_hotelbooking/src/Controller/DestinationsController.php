<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

\defined('_JEXEC') or die;

class DestinationsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_DESTINATIONS';

    public function getModel($name = 'Destination', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
