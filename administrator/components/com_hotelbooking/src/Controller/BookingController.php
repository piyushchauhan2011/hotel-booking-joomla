<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\Database\ParameterType;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;
use Learn\Component\Hotelbooking\Administrator\Table\BookingTable;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;
use Learn\Component\Hotelbooking\Administrator\Table\RoomTable;

\defined('_JEXEC') or die;

class BookingController extends FormController
{
    protected $text_prefix = 'COM_HOTELBOOKING_BOOKING';

    protected function allowEdit($data = [], $key = 'id')
    {
        $user = $this->app->getIdentity();

        if (AccessHelper::isPrivileged($user)) {
            return parent::allowEdit($data, $key);
        }

        $id = (int) ($data[$key] ?? 0);

        if ($id <= 0) {
            return false;
        }

        $auth = $this->destinationAuthForBooking($id);

        return AccessHelper::canEditDestination($user, $auth['id'], $auth['created_by']);
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

        $user = $this->app->getIdentity();

        if (!AccessHelper::canEditDestination($user, (int) $destination->id, (int) $destination->created_by)) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
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

    /**
     * @return array{id:int,created_by:int}
     */
    private function destinationAuthForBooking(int $bookingId): array
    {
        $db = Factory::getDbo();
        $bookingTable = new BookingTable($db);

        if (!$bookingTable->load($bookingId)) {
            return ['id' => 0, 'created_by' => 0];
        }

        $roomTable = new RoomTable($db);

        if (!$roomTable->load((int) $bookingTable->room_id)) {
            return ['id' => 0, 'created_by' => 0];
        }

        $destinationTable = new DestinationTable($db);

        if (!$destinationTable->load((int) $roomTable->destination_id)) {
            return ['id' => 0, 'created_by' => 0];
        }

        return [
            'id'         => (int) $destinationTable->id,
            'created_by' => (int) $destinationTable->created_by,
        ];
    }
}
