<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Joomla\CMS\Router\Route;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class FaqsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_FAQS';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged($this->app->getIdentity())) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_hotelbooking&view=destinations', false));
            $this->redirect();
        }

        return parent::execute($task);
    }

    public function getModel($name = 'Faq', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }
}
