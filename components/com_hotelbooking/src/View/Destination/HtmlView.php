<?php

namespace Learn\Component\Hotelbooking\Site\View\Destination;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $rooms;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->registerAndUseStyle('com_hotelbooking.site', 'com_hotelbooking/hotelbooking.css', ['relative' => true, 'version' => 'auto']);

        $model      = $this->getModel();
        $this->item = $model->getItem();
        $this->rooms = $this->item ? $model->getRooms((int) $this->item->id) : [];

        if ($this->item) {
            $description = trim(strip_tags((string) $this->item->description));
            $description = $description !== ''
                ? $description
                : sprintf('Browse rooms and book your stay in %s.', $this->item->name);

            if (\function_exists('mb_strimwidth')) {
                $description = mb_strimwidth($description, 0, 160, '...');
            }

            $this->getDocument()->setDescription($description);
        }

        return parent::display($tpl);
    }
}
