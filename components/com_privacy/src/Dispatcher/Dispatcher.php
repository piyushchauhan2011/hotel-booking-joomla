<?php

/**
 * @package     Joomla.Site
 * @subpackage  com_privacy
 *
 * @copyright   (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\Component\Privacy\Site\Dispatcher;

use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\Router\Route;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * ComponentDispatcher class for com_privacy
 *
 * @since  5.2.3
 */
class Dispatcher extends ComponentDispatcher
{
    /**
     * Method to check component access permission
     *
     * @since   5.2.3
     *
     * @return  void
     */
    protected function checkAccess()
    {
        parent::checkAccess();

        $view = $this->input->get('view');
        $task = $this->input->get('task', 'display');

        // Ignore any-non-"display" tasks
        if (str_contains($task, '.')) {
            $task = explode('.', $task)[1];
        }

        if ($task !== 'display') {
            return;
        }

        // Creating a request still needs a login. Confirmation emails prove
        // control of the address, and booking guests are not CMS users, so
        // the confirm view must work with only the emailed token.
        if ($view === 'request' && $this->app->getIdentity()->guest) {
            $this->app->redirect(
                Route::_('index.php?option=com_users&view=login&return=' . base64_encode('index.php?option=com_privacy&view=' . $view), false)
            );
        }

        if ($view === 'confirm' && $this->app->getIdentity()->guest && $this->input->getAlnum('confirm_token') === '') {
            $this->app->redirect(
                Route::_('index.php?option=com_users&view=login&return=' . base64_encode('index.php?option=com_privacy&view=' . $view), false)
            );
        }
    }
}
