<?php

namespace Learn\Component\Hotelbooking\Site\Controller;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Learn\Component\Hotelbooking\Site\Helper\HtmxHelper;
use Learn\Component\Hotelbooking\Site\Model\BookingModel;
use Learn\Component\Hotelbooking\Site\Model\BookingsModel;

\defined('_JEXEC') or die;

class BookingController extends BaseController
{
    public function submit()
    {
        $app    = $this->app;
        $isHtmx = HtmxHelper::isRequest($app);

        if (!Session::checkToken('post')) {
            $message = Text::_('COM_HOTELBOOKING_ERROR_INVALID_TOKEN');

            if ($isHtmx) {
                HtmxHelper::trigger($app, ['hbMessage' => ['type' => 'error', 'text' => $message]]);
                HtmxHelper::sendLayout($app, 'booking_result', ['error' => $message], 403);
            }

            $app->enqueueMessage($message, 'error');
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
            if ($isHtmx) {
                HtmxHelper::trigger($app, ['hbMessage' => ['type' => 'error', 'text' => $e->getMessage()]]);
                HtmxHelper::sendLayout($app, 'booking_result', ['error' => $e->getMessage()], 422);
            }

            $app->enqueueMessage($e->getMessage(), 'error');
            $redirectUrl = 'index.php?option=com_hotelbooking&view=room&id=' . $roomId . ($itemId ? '&Itemid=' . $itemId : '');
            $app->redirect(Route::_($redirectUrl, false));

            return false;
        }

        if ($isHtmx) {
            /** @var BookingsModel $bookingsModel */
            $bookingsModel = $this->getModel('Bookings', 'Site', ['ignore_request' => true]);
            $item          = $bookingsModel->getItem($bookingId);

            HtmxHelper::trigger($app, [
                'hbMessage' => [
                    'type' => 'success',
                    'text' => Text::_('COM_HOTELBOOKING_BOOKING_SUCCESS'),
                ],
            ]);
            HtmxHelper::sendLayout($app, 'booking_result', ['item' => $item]);
        }

        $app->enqueueMessage(Text::_('COM_HOTELBOOKING_BOOKING_SUCCESS'));
        $redirectUrl = 'index.php?option=com_hotelbooking&view=bookings&id=' . $bookingId . ($itemId ? '&Itemid=' . $itemId : '');
        $app->redirect(Route::_($redirectUrl, false));

        return true;
    }
}
