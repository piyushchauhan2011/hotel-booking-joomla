<?php

namespace Learn\Component\Hotelbooking\Administrator\Extension;

use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\ComponentHelper;
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
use Joomla\CMS\Workflow\WorkflowServiceInterface;
use Joomla\CMS\Workflow\WorkflowServiceTrait;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') or die;

class HotelbookingComponent extends MVCComponent implements
    BootableExtensionInterface,
    RouterServiceInterface,
    AssociationServiceInterface,
    FieldsFormServiceInterface,
    SchemaorgServiceInterface,
    WorkflowServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use AssociationServiceTrait;
    use FieldsServiceTrait;
    use SchemaorgServiceTrait;
    use WorkflowServiceTrait;

    public const CONDITION_CLOSED = 0;
    public const CONDITION_IN_PROGRESS = 1;
    public const CONDITION_COMPLETE = 2;

    public const CONDITION_NAMES = [
        self::CONDITION_IN_PROGRESS => 'COM_HOTELBOOKING_WORKFLOW_CONDITION_IN_PROGRESS',
        self::CONDITION_COMPLETE    => 'COM_HOTELBOOKING_WORKFLOW_CONDITION_COMPLETE',
        self::CONDITION_CLOSED      => 'COM_HOTELBOOKING_WORKFLOW_CONDITION_CLOSED',
    ];

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

    public function getWorkflowContexts(): array
    {
        Factory::getLanguage()->load('com_hotelbooking', JPATH_ADMINISTRATOR);

        return [
            BookingWorkflowHelper::EXTENSION => Text::_('COM_HOTELBOOKING_WORKFLOW_CONTEXT_BOOKING'),
        ];
    }

    public function getCategoryWorkflowContext(?string $section = null): string
    {
        $contexts = $this->getWorkflowContexts();

        return (string) array_key_first($contexts);
    }

    public function getWorkflowTableBySection(?string $section = null): string
    {
        return '#__hotelbooking_bookings';
    }

    public function filterTransitions(array $transitions, int $pk): array
    {
        /** @var array<int, array<string, mixed>> $transitions */
        return BookingWorkflowHelper::filterTransitions($transitions, $pk);
    }

    public function isWorkflowActive($context): bool
    {
        if ($context !== BookingWorkflowHelper::EXTENSION) {
            return false;
        }

        return (bool) ComponentHelper::getParams('com_hotelbooking')->get('workflow_enabled');
    }
}
