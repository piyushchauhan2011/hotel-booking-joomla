<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class DisplayController extends BaseController
{
    protected $default_view = 'destinations';

    public function display($cachable = false, $urlparams = [])
    {
        $view = strtolower((string) $this->input->getCmd('view', $this->default_view));

        if (\in_array($view, ['faqs', 'faq'], true) && !AccessHelper::isPrivileged($this->app->getIdentity())) {
            $this->setMessage(Text::_('JERROR_ALERTNOAUTHOR'), 'warning');
            $this->setRedirect(Route::_('index.php?option=com_hotelbooking&view=destinations', false));
            $this->redirect();

            return $this;
        }

        return parent::display($cachable, $urlparams);
    }
}
