<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

\defined('_JEXEC') or die;

class RoomsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_ROOMS';

    public function getModel($name = 'Room', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
