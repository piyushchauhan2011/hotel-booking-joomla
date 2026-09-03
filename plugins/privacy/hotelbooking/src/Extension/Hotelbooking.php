<?php

namespace Learn\Plugin\Privacy\Hotelbooking\Extension;

use Joomla\CMS\Event\Privacy\CanRemoveDataEvent;
use Joomla\CMS\Event\Privacy\ExportRequestEvent;
use Joomla\CMS\Event\Privacy\RemoveDataEvent;
use Joomla\Component\Privacy\Administrator\Plugin\PrivacyPlugin;
use Joomla\Component\Privacy\Administrator\Removal\Status;
use Joomla\Event\SubscriberInterface;
use Learn\Plugin\Privacy\Hotelbooking\Helper\PrivacyDataHelper;

\defined('_JEXEC') or die;

final class Hotelbooking extends PrivacyPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onPrivacyExportRequest' => 'onPrivacyExportRequest',
            'onPrivacyCanRemoveData' => 'onPrivacyCanRemoveData',
            'onPrivacyRemoveData'    => 'onPrivacyRemoveData',
        ];
    }

    public function onPrivacyExportRequest(ExportRequestEvent $event): void
    {
        $request = $event->getRequest();
        $email   = (string) ($request->email ?? '');

        if ($email === '') {
            return;
        }

        $domains   = [];
        $db        = $this->getDatabase();
        $bookings  = $this->createDomain('hotelbooking_bookings', 'com_hotelbooking_bookings');
        $partners  = $this->createDomain('hotelbooking_partner_contact', 'com_hotelbooking_destinations');
        $domains[] = $bookings;
        $domains[] = $partners;

        $query = $db->createQuery()
            ->select([
                $db->quoteName('b.guest_name'),
                $db->quoteName('b.guest_email'),
                $db->quoteName('b.checkin_date'),
                $db->quoteName('b.checkout_date'),
                $db->quoteName('b.status'),
                $db->quoteName('r.name', 'room_name'),
                $db->quoteName('d.name', 'destination_name'),
            ])
            ->from($db->quoteName('#__hotelbooking_bookings', 'b'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_rooms', 'r') . ' ON ' . $db->quoteName('r.id') . ' = ' . $db->quoteName('b.room_id'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('b.guest_email') . ' = :email')
            ->bind(':email', $email);

        foreach ($db->setQuery($query)->loadObjectList() as $booking) {
            $bookings->addItem($this->createItemFromArray(PrivacyDataHelper::bookingExportRow($booking)));
        }

        $partnerQuery = $db->createQuery()
            ->select([
                $db->quoteName('name'),
                $db->quoteName('partner_contact_name'),
                $db->quoteName('partner_email'),
                $db->quoteName('partner_whatsapp'),
            ])
            ->from($db->quoteName('#__hotelbooking_destinations'))
            ->where($db->quoteName('partner_email') . ' = :partnerEmail')
            ->bind(':partnerEmail', $email);

        foreach ($db->setQuery($partnerQuery)->loadObjectList() as $destination) {
            $partners->addItem($this->createItemFromArray(PrivacyDataHelper::destinationExportRow($destination)));
        }

        $event->addResult($domains);
    }

    public function onPrivacyCanRemoveData(CanRemoveDataEvent $event): void
    {
        $event->addResult(new Status());
    }

    public function onPrivacyRemoveData(RemoveDataEvent $event): void
    {
        $request = $event->getRequest();
        $email   = (string) ($request->email ?? '');

        if ($email === '') {
            return;
        }

        $db     = $this->getDatabase();
        $guest  = PrivacyDataHelper::anonymisedBookingValues();
        $query  = $db->createQuery()
            ->update($db->quoteName('#__hotelbooking_bookings'))
            ->set($db->quoteName('guest_name') . ' = :guestName')
            ->set($db->quoteName('guest_email') . ' = :guestEmail')
            ->set($db->quoteName('partner_notes') . ' = :partnerNotes')
            ->where($db->quoteName('guest_email') . ' = :email')
            ->bind(':guestName', $guest['guest_name'])
            ->bind(':guestEmail', $guest['guest_email'])
            ->bind(':partnerNotes', $guest['partner_notes'])
            ->bind(':email', $email);
        $db->setQuery($query)->execute();

        $partner = PrivacyDataHelper::anonymisedPartnerValues();
        $redact  = $db->createQuery()
            ->update($db->quoteName('#__hotelbooking_destinations'))
            ->set($db->quoteName('partner_contact_name') . ' = :contactName')
            ->set($db->quoteName('partner_email') . ' = :partnerEmail')
            ->set($db->quoteName('partner_whatsapp') . ' = :whatsapp')
            ->where($db->quoteName('partner_email') . ' = :email')
            ->bind(':contactName', $partner['partner_contact_name'])
            ->bind(':partnerEmail', $partner['partner_email'])
            ->bind(':whatsapp', $partner['partner_whatsapp'])
            ->bind(':email', $email);
        $db->setQuery($redact)->execute();
    }
}
