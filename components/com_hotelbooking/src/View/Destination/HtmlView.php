<?php

namespace Learn\Component\Hotelbooking\Site\View\Destination;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Learn\Component\Hotelbooking\Site\Helper\SchemaHelper;
use Learn\Component\Hotelbooking\Site\Helper\SubformHelper;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;
    protected $rooms;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()
            ->useStyle('com_hotelbooking.site')
            ->useScript('com_hotelbooking.gallery-lightbox');

        $model      = $this->getModel();
        $this->item = $model->getItem();
        $this->rooms = $this->item ? $model->getRooms((int) $this->item->id) : [];

        if ($this->item) {
            $this->item->gallery    = SubformHelper::decodeRows($this->item->gallery, 'gallery_item');
            $this->item->offers     = SubformHelper::decodeRows($this->item->offers, 'offer_item');
            $this->item->faqs       = SubformHelper::decodeRows($this->item->faqs, 'faq_item');
            $this->item->amenities  = $this->item->amenities ? explode(',', $this->item->amenities) : [];

            $description = trim(strip_tags((string) $this->item->description));
            $description = $description !== ''
                ? $description
                : sprintf('Browse rooms and book your stay in %s.', $this->item->name);

            if (\function_exists('mb_strimwidth')) {
                $description = mb_strimwidth($description, 0, 160, '...');
            }

            $this->getDocument()->setDescription($description);

            $this->getDocument()->addCustomTag(
                '<script type="application/ld+json">'
                . json_encode(SchemaHelper::forDestination($this->item), JSON_UNESCAPED_SLASHES)
                . '</script>',
            );
        }

        return parent::display($tpl);
    }
}
