<?php

namespace Learn\Component\Hotelbooking\Administrator\Controller;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\AdminController;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

class BookingsController extends AdminController
{
    protected $text_prefix = 'COM_HOTELBOOKING_BOOKINGS';

    public function execute($task)
    {
        if (!AccessHelper::isPrivileged(Factory::getApplication()->getIdentity())) {
            throw new \Exception(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }

        return parent::execute($task);
    }

    public function getModel($name = 'Booking', $prefix = 'Administrator', $config = ['ignore_request' => true])
    {
        return parent::getModel($name, $prefix, $config);
    }

    public function exportCsv()
    {
        $this->checkToken('request');

        /** @var \Learn\Component\Hotelbooking\Administrator\Model\BookingsModel $model */
        $model = $this->getModel('Bookings', 'Administrator', ['ignore_request' => false]);
        $model->setState('list.limit', 0);
        $model->setState('list.start', 0);
        $items = $model->getItems();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hotelbooking-bookings-' . Factory::getDate()->format('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'ID', 'Hotel', 'Room', 'Guest Name', 'Guest Email', 'Check-in', 'Check-out', 'Guests',
            'Total Price', 'Commission Rate', 'Commission Amount', 'Commission Paid',
            'Status', 'Partner Status', 'Hotel Notified At', 'Created',
        ]);

        foreach ($items as $item) {
            fputcsv($out, [
                $item->id,
                $item->destination_name ?? '',
                $item->room_name ?? '',
                $item->guest_name,
                $item->guest_email,
                $item->checkin_date,
                $item->checkout_date,
                $item->guests,
                $item->total_price,
                $item->commission_rate,
                $item->commission_amount,
                $item->commission_paid ? 'Yes' : 'No',
                $item->status,
                $item->partner_status,
                $item->hotel_notified_at,
                $item->created,
            ]);
        }

        fclose($out);

        Factory::getApplication()->close();
    }
}
