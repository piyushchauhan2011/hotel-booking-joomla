<?php

namespace Learn\Module\Hoteldetails\Site\Dispatcher;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Learn\Component\Hotelbooking\Site\Helper\DestinationContextHelper;

\defined('_JEXEC') or die;

class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $this->getApplication()->bootComponent('com_hotelbooking');
        $this->getApplication()->getLanguage()->load('com_hotelbooking', JPATH_SITE);
        $this->getApplication()->getDocument()->getWebAssetManager()->useStyle('com_hotelbooking.site');

        $data = parent::getLayoutData();
        $data['destination'] = DestinationContextHelper::getDestination(
            $data['params'],
            $this->getApplication()
        );

        return $data;
    }
}
