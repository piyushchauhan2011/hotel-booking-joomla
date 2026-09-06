<?php

namespace Learn\Component\Hotelbooking\Administrator\Table;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Table\Asset;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

class DestinationTable extends Table
{
    protected $_jsonEncode = ['gallery', 'offers', 'faqs'];

    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__hotelbooking_destinations', 'id', $db, $dispatcher);
    }

    public function bind($src, $ignore = [])
    {
        if (isset($src['amenities']) && \is_array($src['amenities'])) {
            $src['amenities'] = implode(',', $src['amenities']);
        }

        if (isset($src['manager_user_id']) && (string) $src['manager_user_id'] === '') {
            $src['manager_user_id'] = null;
        }

        return parent::bind($src, $ignore);
    }

    public function check()
    {
        if (trim((string) $this->name) === '') {
            $this->setError('Name is required.');

            return false;
        }

        if (trim((string) $this->alias) === '') {
            $this->alias = $this->name;
        }

        $this->alias = ApplicationHelper::stringURLSafe($this->alias);

        return true;
    }

    protected function _getAssetName()
    {
        $k = $this->_tbl_key;

        return 'com_hotelbooking.destination.' . (int) $this->$k;
    }

    protected function _getAssetTitle()
    {
        return $this->name;
    }

    protected function _getAssetParentId(?Table $table = null, $id = null)
    {
        $asset = new Asset($this->getDatabase(), $this->getDispatcher());

        if ($asset->loadByName('com_hotelbooking')) {
            return (int) $asset->id;
        }

        return parent::_getAssetParentId($table, $id);
    }
}
