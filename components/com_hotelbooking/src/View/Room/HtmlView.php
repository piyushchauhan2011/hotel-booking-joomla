<?php

namespace Learn\Component\Hotelbooking\Site\View\Room;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

\defined('_JEXEC') or die;

class HtmlView extends BaseHtmlView
{
    protected $item;

    public function display($tpl = null)
    {
        Factory::getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $this->item = $this->getModel()->getItem();

        if ($this->item) {
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
