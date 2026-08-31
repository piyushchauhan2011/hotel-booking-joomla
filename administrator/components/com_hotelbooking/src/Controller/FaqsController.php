<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class FaqsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_FAQS';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged(Factory::getApplication()->getIdentity())) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    public function getModel($name = 'Faq', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
