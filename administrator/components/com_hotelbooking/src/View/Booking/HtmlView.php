<?php

namespace Learn\Component\Hotelbooking\Administrator\View\Booking;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\ParameterType;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $form;
    protected $whatsappLink = '';

    public function display($tpl = null)
    {
        $this->item = $this->get('Item');
        $this->form = $this->get('Form');
        $this->whatsappLink = $this->buildWhatsAppLink();

        $this->addToolbar();

        return parent::display($tpl);
    }

    protected function buildWhatsAppLink(): string
    {
        $roomId = (int) ($this->item->room_id ?? 0);

        if ($roomId <= 0) {
            return '';
        }

        $db    = Factory::getDbo();
        $query = $db->createQuery()
            ->select([$db->quoteName('r.name', 'room_name'), $db->quoteName('d.name', 'destination_name'), $db->quoteName('d.partner_whatsapp')])
            ->from($db->quoteName('#__hotelbooking_rooms', 'r'))
            ->join('LEFT', $db->quoteName('#__hotelbooking_destinations', 'd') . ' ON ' . $db->quoteName('d.id') . ' = ' . $db->quoteName('r.destination_id'))
            ->where($db->quoteName('r.id') . ' = :roomId')
            ->bind(':roomId', $roomId, ParameterType::INTEGER);
        $db->setQuery($query);
        $row = $db->loadObject();

        if (!$row || empty($row->partner_whatsapp)) {
            return '';
        }

        $room        = (object) ['name' => $row->room_name];
        $destination = (object) ['name' => $row->destination_name];
        $message     = PartnerNotificationHelper::buildMessageSummary($this->item, $room, $destination);

        return PartnerNotificationHelper::buildWhatsAppLink($row->partner_whatsapp, $message);
    }

    protected function addToolbar()
    {
        $isNew = empty($this->item->id);

        ToolbarHelper::title(Text::_($isNew ? 'COM_HOTELBOOKING_BOOKING_NEW' : 'COM_HOTELBOOKING_BOOKING_EDIT'), 'hotelbooking');
        ToolbarHelper::apply('booking.apply');
        ToolbarHelper::save('booking.save');

        if (!$isNew) {
            ToolbarHelper::custom('booking.notifyHotel', 'envelope', '', 'COM_HOTELBOOKING_NOTIFY_HOTEL', false);
        }

        ToolbarHelper::cancel('booking.cancel', $isNew ? 'JTOOLBAR_CANCEL' : 'JTOOLBAR_CLOSE');
    }
}
