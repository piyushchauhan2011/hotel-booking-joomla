<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Learn\Component\Hotelbooking\Site\Helper\FaqHelper;

\defined('_JEXEC') or die;

class FaqsModel extends BaseDatabaseModel
{
    public function getItems(): array
    {
        return FaqHelper::getPublished($this->getDatabase(), 'general');
    }
}
