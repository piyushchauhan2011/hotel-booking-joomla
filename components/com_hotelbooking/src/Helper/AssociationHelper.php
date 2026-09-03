<?php

namespace Learn\Component\Hotelbooking\Site\Helper;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Associations;
use Joomla\CMS\Router\Route;

\defined('_JEXEC') or die;

abstract class AssociationHelper
{
    public static function getAssociations($id = 0, $view = null)
    {
        $jinput = Factory::getApplication()->getInput();
        $view ??= $jinput->get('view');
        $id     = empty($id) ? $jinput->getInt('id') : $id;

        if (empty($id) || empty($view)) {
            return [];
        }

        $return = [];

        switch ($view) {
            case 'destination':
                $associations = Associations::getAssociations(
                    'com_hotelbooking',
                    '#__hotelbooking_destinations',
                    'com_hotelbooking.item.destination',
                    (int) $id,
                    'id',
                    'alias',
                    '',
                );

                foreach ($associations as $tag => $item) {
                    $return[$tag] = Route::_('index.php?option=com_hotelbooking&view=destination&id=' . (int) $item->id . '&lang=' . $tag);
                }

                break;

            case 'room':
                $associations = Associations::getAssociations(
                    'com_hotelbooking',
                    '#__hotelbooking_rooms',
                    'com_hotelbooking.item.room',
                    (int) $id,
                    'id',
                    'alias',
                    '',
                );

                foreach ($associations as $tag => $item) {
                    $return[$tag] = Route::_('index.php?option=com_hotelbooking&view=room&id=' . (int) $item->id . '&lang=' . $tag);
                }

                break;

            case 'faq':
            case 'faqs':
                $associations = Associations::getAssociations(
                    'com_hotelbooking',
                    '#__hotelbooking_faqs',
                    'com_hotelbooking.item.faq',
                    (int) $id,
                    'id',
                    '',
                    '',
                );

                foreach ($associations as $tag => $item) {
                    $return[$tag] = Route::_('index.php?option=com_hotelbooking&view=faqs&lang=' . $tag);
                }

                break;
        }

        return $return;
    }
}
