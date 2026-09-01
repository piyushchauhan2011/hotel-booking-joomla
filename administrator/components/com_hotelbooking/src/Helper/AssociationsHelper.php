<?php

namespace Learn\Component\Hotelbooking\Administrator\Helper;

use Joomla\CMS\Association\AssociationExtensionHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Table\Table;
use Learn\Component\Hotelbooking\Administrator\Table\DestinationTable;
use Learn\Component\Hotelbooking\Administrator\Table\FaqTable;
use Learn\Component\Hotelbooking\Administrator\Table\RoomTable;
use Learn\Component\Hotelbooking\Site\Helper\AssociationHelper;

\defined('_JEXEC') or die;

class AssociationsHelper extends AssociationExtensionHelper
{
    protected $extension = 'com_hotelbooking';

    protected $itemTypes = ['destination', 'room', 'faq'];

    protected $associationsSupport = true;

    public function getAssociationsForItem($id = 0, $view = null)
    {
        return AssociationHelper::getAssociations($id, $view);
    }

    public function getAssociations($typeName, $id)
    {
        $type       = $this->getType($typeName);
        $context    = $this->extension . '.item.' . $typeName;
        $aliasField = $typeName === 'faq' ? '' : 'alias';

        return Associations::getAssociations(
            $this->extension,
            $type['tables']['a'],
            $context,
            $id,
            'id',
            $aliasField,
            ''
        );
    }

    public function getItem($typeName, $id)
    {
        if (empty($id)) {
            return null;
        }

        $table = null;

        switch ($typeName) {
            case 'destination':
                $table = new DestinationTable(Factory::getDbo());
                break;

            case 'room':
                $table = new RoomTable(Factory::getDbo());
                break;

            case 'faq':
                $table = new FaqTable(Factory::getDbo());
                break;
        }

        if ($table === null) {
            return null;
        }

        $table->load($id);

        return $table;
    }

    public function getType($typeName = '')
    {
        $fields  = $this->getFieldsTemplate();
        $tables  = [];
        $support = $this->getSupportTemplate();
        $title   = '';

        // None of our tables have catid/access/created_by/checked_out(_time) columns like #__content does.
        $fields['catid']            = '';
        $fields['access']           = '';
        $fields['created_user_id']  = '';
        $fields['checked_out']      = '';
        $fields['checked_out_time'] = '';
        $fields['state']            = 'a.published';

        $support['acl'] = false;

        if (\in_array($typeName, $this->itemTypes)) {
            switch ($typeName) {
                case 'destination':
                    $fields['title'] = 'a.name';

                    $support['state'] = true;

                    $tables = ['a' => '#__hotelbooking_destinations'];
                    $title  = 'destination';
                    break;

                case 'room':
                    $fields['title'] = 'a.name';

                    $support['state'] = true;

                    $tables = ['a' => '#__hotelbooking_rooms'];
                    $title  = 'room';
                    break;

                case 'faq':
                    $fields['title'] = 'a.question';
                    $fields['alias'] = 'a.question';

                    $support['state'] = true;

                    $tables = ['a' => '#__hotelbooking_faqs'];
                    $title  = 'faq';
                    break;
            }
        }

        return [
            'fields'  => $fields,
            'support' => $support,
            'tables'  => $tables,
            'joins'   => [],
            'title'   => $title,
        ];
    }
}
