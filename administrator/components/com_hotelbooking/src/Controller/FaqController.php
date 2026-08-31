<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class FaqController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_FAQ';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged(Factory::getApplication()->getIdentity())) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }
}
