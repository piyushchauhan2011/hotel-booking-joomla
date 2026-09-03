<?php

namespace Learn\Plugin\Schemaorg\Lodging\Extension;

use Joomla\CMS\Event\Plugin\System\Schemaorg\BeforeCompileHeadEvent;
use Joomla\CMS\Event\Plugin\System\Schemaorg\PrepareFormEvent;
use Joomla\CMS\Event\Plugin\System\Schemaorg\PrepareSaveEvent;
use Joomla\CMS\Form\Field\ListField;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Schemaorg\SchemaorgPluginTrait;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseAwareInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\Priority;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Learn\Component\Hotelbooking\Site\Helper\SchemaHelper;

\defined('_JEXEC') or die;

final class Lodging extends CMSPlugin implements SubscriberInterface, DatabaseAwareInterface
{
    use SchemaorgPluginTrait;
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    protected $pluginName = 'LodgingBusiness';

    public static function getSubscribedEvents(): array
    {
        return [
            'onSchemaPrepareForm'       => 'onSchemaPrepareForm',
            'onSchemaPrepareSave'       => 'onSchemaPrepareSave',
            'onSchemaBeforeCompileHead' => ['onSchemaBeforeCompileHead', Priority::BELOW_NORMAL],
        ];
    }

    public function onSchemaPrepareForm(PrepareFormEvent $event): void
    {
        $form    = $event->getForm();
        $context = $form->getName();

        if (!$this->isSupported($context)) {
            return;
        }

        $schemaType = $form->getField('schemaType', 'schema');

        if ($schemaType instanceof ListField) {
            $schemaType->addOption('LodgingBusiness', ['value' => 'LodgingBusiness']);
            $schemaType->addOption('Product', ['value' => 'Product']);
        }

        $formFile = JPATH_PLUGINS . '/' . $this->_type . '/' . $this->_name . '/forms/schemaorg.xml';

        if (is_file($formFile)) {
            $form->loadFile($formFile);
        }
    }

    public function onSchemaPrepareSave(PrepareSaveEvent $event): void
    {
        $entry   = $event->getData();
        $context = $event->getContext();
        $item    = $event->getItem();

        if (empty($entry->schemaType) || !\in_array($entry->schemaType, ['LodgingBusiness', 'Product'], true)) {
            return;
        }

        $graph = $this->graphFromItem($context, $item, $entry->schemaType);

        if ($graph === []) {
            return;
        }

        $schema = new Registry($entry->schema ?? []);

        foreach ($graph as $key => $value) {
            $current = $schema->get($key);

            if ($current === null || $current === '' || $current === []) {
                $schema->set($key, $value);
            }
        }

        $entry->schema = $schema->toString();
    }

    public function onSchemaBeforeCompileHead(BeforeCompileHeadEvent $event): void
    {
        $context = $event->getContext();
        $parts   = explode('.', $context);

        if (\count($parts) < 3 || $parts[0] !== 'com_hotelbooking') {
            return;
        }

        $view   = $parts[1];
        $itemId = (int) $parts[2];
        $type   = $view === 'destination' ? 'LodgingBusiness' : ($view === 'room' ? 'Product' : '');

        if ($type === '' || $itemId < 1) {
            return;
        }

        $schema = $event->getSchema();
        $graph  = $schema->get('@graph', []);

        foreach ((array) $graph as $entry) {
            if (($entry['@type'] ?? '') === $type) {
                return;
            }
        }

        $item = $this->loadItem($view, $itemId);

        if (!$item) {
            return;
        }

        $node = $this->graphFromItem('com_hotelbooking.' . $view, $item, $type);

        if ($node === []) {
            return;
        }

        $graph[] = $node;
        $schema->set('@graph', $graph);
    }

    /**
     * @return array<string, mixed>
     */
    private function graphFromItem(string $context, object $item, string $schemaType): array
    {
        $url = Uri::current();

        if ($context === 'com_hotelbooking.destination' && $schemaType === 'LodgingBusiness') {
            return SchemaHelper::graphNode(SchemaHelper::forDestination($item, $url));
        }

        if ($context === 'com_hotelbooking.room' && $schemaType === 'Product') {
            return SchemaHelper::graphNode(SchemaHelper::forRoom($item, $url));
        }

        return [];
    }

    private function loadItem(string $view, int $itemId): ?object
    {
        $db    = $this->getDatabase();
        $table = $view === 'room' ? '#__hotelbooking_rooms' : '#__hotelbooking_destinations';
        $query = $db->createQuery()
            ->select('*')
            ->from($db->quoteName($table))
            ->where($db->quoteName('id') . ' = :id')
            ->bind(':id', $itemId, ParameterType::INTEGER);

        $item = $db->setQuery($query)->loadObject();

        return $item ?: null;
    }
}
