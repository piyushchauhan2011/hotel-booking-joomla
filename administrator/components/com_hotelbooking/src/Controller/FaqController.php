<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class FaqController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_FAQ';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged($this->app->getIdentity())) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_hotelbooking&view=destinations', false));
            $this->redirect();
        }

        return parent::execute($task);
    }
}
