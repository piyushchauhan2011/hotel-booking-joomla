<?php

namespace Learn\Component\Hotelbooking\Administrator\Extension;

use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Factory;
use Joomla\CMS\Fields\FieldsFormServiceInterface;
use Joomla\CMS\Fields\FieldsServiceTrait;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Schemaorg\SchemaorgServiceInterface;
use Joomla\CMS\Schemaorg\SchemaorgServiceTrait;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') or die;

class HotelbookingComponent extends MVCComponent implements
    BootableExtensionInterface,
    RouterServiceInterface,
    AssociationServiceInterface,
    FieldsFormServiceInterface,
    SchemaorgServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use AssociationServiceTrait;
    use FieldsServiceTrait;
    use SchemaorgServiceTrait;

    public function boot(ContainerInterface $container): void
    {
        // Pass
    }

    public function validateSection($section, $item = null): ?string
    {
        return \in_array($section, ['destination', 'room'], true) ? $section : null;
    }

    public function getContexts(): array
    {
        Factory::getLanguage()->load('com_hotelbooking', JPATH_ADMINISTRATOR);

        return [
            'com_hotelbooking.destination' => Text::_('COM_HOTELBOOKING_FIELDS_CONTEXT_DESTINATION'),
            'com_hotelbooking.room'        => Text::_('COM_HOTELBOOKING_FIELDS_CONTEXT_ROOM'),
        ];
    }

    public function getSchemaorgContexts(): array
    {
        Factory::getLanguage()->load('com_hotelbooking', JPATH_ADMINISTRATOR);

        return [
            'com_hotelbooking.destination' => Text::_('COM_HOTELBOOKING_FIELDS_CONTEXT_DESTINATION'),
            'com_hotelbooking.room'        => Text::_('COM_HOTELBOOKING_FIELDS_CONTEXT_ROOM'),
        ];
    }
}
