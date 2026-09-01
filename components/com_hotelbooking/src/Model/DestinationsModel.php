<?php

namespace Learn\Component\Hotelbooking\Site\Model;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Multilanguage;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\Database\ParameterType;
use Joomla\Database\QueryInterface;
use Learn\Component\Hotelbooking\Site\Helper\FaqHelper;

\defined('_JEXEC') or die;

class DestinationsModel extends ListModel
{
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = ['id', 'name', 'ordering'];
        }

        parent::__construct($config);
    }

    protected function populateState($ordering = 'a.ordering', $direction = 'ASC')
    {
        $app    = Factory::getApplication();
        $search = $app->getInput()->getString('search', '');
        $this->setState('filter.search', $search);

        parent::populateState($ordering, $direction);
    }

    protected function getListQuery(): QueryInterface
    {
        $db    = $this->getDatabase();
        $query = $db->createQuery();

        $query->select('a.*')
            ->from($db->quoteName('#__hotelbooking_destinations', 'a'))
            ->where($db->quoteName('a.published') . ' = 1');

        $search = $this->getState('filter.search');

        if (!empty($search)) {
            $search = '%' . str_replace(' ', '%', trim($search)) . '%';
            $query->where($db->quoteName('a.name') . ' LIKE :search')
                ->bind(':search', $search);
        }

        if (Multilanguage::isEnabled()) {
            $query->whereIn(
                $db->quoteName('a.language'),
                [Factory::getApplication()->getLanguage()->getTag(), '*'],
                ParameterType::STRING
            );
        }

        $query->order($db->quoteName('a.ordering') . ' ASC');

        return $query;
    }

    public function getFaqs(): array
    {
        $language = Multilanguage::isEnabled() ? Factory::getApplication()->getLanguage()->getTag() : null;

        return FaqHelper::getPublished($this->getDatabase(), 'destinations', $language);
    }
}
