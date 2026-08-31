<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class DisplayController extends BaseController
{
    protected $default_view = 'destinations';

    private const RESTRICTED_VIEWS = ['bookings', 'booking', 'faqs', 'faq'];

    public function display($cachable = false, $urlparams = [])
    {
        $view = strtolower($this->input->getCmd('view', $this->default_view));

        if (\in_array($view, self::RESTRICTED_VIEWS, true) && !AccessHelper::isPrivileged(Factory::getApplication()->getIdentity())) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::display($cachable, $urlparams);
    }
}
