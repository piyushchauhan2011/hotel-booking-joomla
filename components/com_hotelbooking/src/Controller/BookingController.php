<?php

namespace Learn\Component\Hotelbooking\Site\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Learn\Component\Hotelbooking\Site\Model\BookingModel;

\defined('_JEXEC') or die;

class BookingController extends BaseController
{
    public function submit()
    {
        $app = $this->app;

        if (!Session::checkToken('post')) {
            $app->enqueueMessage(Text::_('COM_HOTELBOOKING_ERROR_INVALID_TOKEN'), 'error');
            $app->redirect(Route::_('index.php?option=com_hotelbooking&view=home', false));

            return false;
        }

        $data   = $app->getInput()->post->getArray();
        $roomId = (int) ($data['room_id'] ?? 0);
        $itemId = (int) ($data['Itemid'] ?? 0);

        /** @var BookingModel $model */
        $model = $this->getModel('Booking', 'Site', ['ignore_request' => true]);

        try {
            $bookingId = $model->submitBooking($data);
        } catch (\RuntimeException $e) {
            $app->enqueueMessage($e->getMessage(), 'error');
            $redirectUrl = 'index.php?option=com_hotelbooking&view=room&id=' . $roomId . ($itemId ? '&Itemid=' . $itemId : '');
            $app->redirect(Route::_($redirectUrl, false));

            return false;
        }

        $app->enqueueMessage(Text::_('COM_HOTELBOOKING_BOOKING_SUCCESS'));
        $redirectUrl = 'index.php?option=com_hotelbooking&view=bookings&id=' . $bookingId . ($itemId ? '&Itemid=' . $itemId : '');
        $app->redirect(Route::_($redirectUrl, false));

        return true;
    }
}
