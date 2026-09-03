<?php

namespace Learn\Module\Hotelrooms\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Helper\HelperFactoryAwareInterface;
use Joomla\CMS\Helper\HelperFactoryAwareTrait;
use Joomla\CMS\Language\Text;

\defined('_JEXEC') or die;

class Dispatcher extends AbstractModuleDispatcher implements HelperFactoryAwareInterface
{
    use HelperFactoryAwareTrait;

    protected function getLayoutData(): array
    {
        $this->getApplication()->getLanguage()->load('com_hotelbooking', JPATH_SITE);
        $this->getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $data = parent::getLayoutData();
        $data['rooms'] = $this->getHelperFactory()
            ->getHelper('HotelroomsHelper')
            ->getRooms($data['params'], $this->getApplication());
        $data['emptyMessage'] = Text::_('MOD_HOTELROOMS_NO_ROOMS');

        return $data;
    }
}
