<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;

\defined('_JEXEC') or die;

class BookingController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_BOOKING';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged(Factory::getApplication()->getIdentity())) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    public function notifyHotel()
    {
        $this->checkToken();

        $id = $this->input->getInt('id');
        $db = Factory::getDbo();

        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_bookings'))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($query);
        $booking = $db->loadObject();

        if (!$booking) {
            $this->setRedirect(Route::_('index.php?option=com_hotelbooking&view=bookings', false));

            return;
        }

        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName('#__hotelbooking_rooms'))
            ->where($db->quoteName('id') . ' = :roomId')
            ->bind(':roomId', $booking->room_id, ParameterType::INTEGER);
        $db->setQuery($query);
        $room = $db->loadObject();

        $destination = null;

        if ($room) {
            $query = $db->createQuery()
                ->select('*')
                ->from($db->quoteName('#__hotelbooking_destinations'))
                ->where($db->quoteName('id') . ' = :destinationId')
                ->bind(':destinationId', $room->destination_id, ParameterType::INTEGER);
            $db->setQuery($query);
            $destination = $db->loadObject();
        }

        $redirect = Route::_('index.php?option=com_hotelbooking&task=booking.edit&id=' . (int) $id, false);

        if (!$room || !$destination) {
            $this->setMessage(Text::_('COM_HOTELBOOKING_NOTIFY_ERROR_NO_HOTEL'), 'error');
            $this->setRedirect($redirect);

            return;
        }

        $sent = PartnerNotificationHelper::sendEmail($booking, $room, $destination);

        $notifiedAt = Factory::getDate()->toSql();

        $updateQuery = $db->createQuery()
            ->update($db->quoteName('#__hotelbooking_bookings'))
            ->set($db->quoteName('hotel_notified_at') . ' = :notifiedAt')
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':notifiedAt', $notifiedAt, ParameterType::STRING)
            ->bind(':id', $id, ParameterType::INTEGER);
        $db->setQuery($updateQuery)->execute();

        $this->setMessage(Text::_($sent ? 'COM_HOTELBOOKING_NOTIFY_SUCCESS' : 'COM_HOTELBOOKING_NOTIFY_EMAIL_FAILED'), $sent ? 'message' : 'warning');
        $this->setRedirect($redirect);
    }
}
