<?php

namespace Learn\Component\Hotelbooking\Site\View\Room;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Learn\Component\Hotelbooking\Site\Helper\SubformHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useStyle('com_hotelbooking.site')
            ->useScript('com_hotelbooking.gallery-lightbox');

        $this->item = $this->getModel()->getItem();

        if ($this->item) {
            $this->item->gallery       = SubformHelper::decodeRows($this->item->gallery, 'gallery_item');
            $this->item->offers        = SubformHelper::decodeRows($this->item->offers, 'offer_item');
            $this->item->amenities     = $this->item->amenities ? explode(',', $this->item->amenities) : [];
            $this->item->nearby_places = SubformHelper::decodeRows($this->item->nearby_places, 'nearby_place_item');

            $description = trim(strip_tags((string) $this->item->description));
            $description = $description !== ''
                ? $description
                : sprintf('Book the %s in %s.', $this->item->name, $this->item->destination_name);

            if (\function_exists('mb_strimwidth')) {
                $description = mb_strimwidth($description, 0, 160, '...');
            }

            $this->getDocument()->setDescription($description);
        }

        return parent::display($tpl);
    }
}
