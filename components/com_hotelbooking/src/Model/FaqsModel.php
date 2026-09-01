<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Learn\Component\Hotelbooking\Site\Helper\FaqHelper;

\defined('_JEXEC') or die;

class FaqsModel extends BaseDatabaseModel
{
    public function getItems(): array
    {
        $language = Multilanguage::isEnabled() ? Factory::getApplication()->getLanguage()->getTag() : null;

        return FaqHelper::getPublished($this->getDatabase(), 'general', $language);
    }
}
