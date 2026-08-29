<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

\defined('_JEXEC') or die;

class BookingsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_BOOKINGS';

    public function getModel($name = 'Booking', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
