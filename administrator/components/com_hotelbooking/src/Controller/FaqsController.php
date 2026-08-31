<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\MVC\Controller\AdminController;

\defined('_JEXEC') or die;

class FaqsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_FAQS';

    public function getModel($name = 'Faq', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
