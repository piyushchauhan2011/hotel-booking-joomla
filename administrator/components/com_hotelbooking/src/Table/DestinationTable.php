<?php

namespace Learn\Component\Hotelbooking\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

class DestinationTable extends Table
{
    protected $_jsonEncode = ['gallery', 'offers'];

    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__hotelbooking_destinations', 'id', $db, $dispatcher);
    }

    public function check()
    {
        if (trim($this->name) === '') {
            $this->setError('Name is required.');

            return false;
        }

        if (trim($this->alias) === '') {
            $this->alias = $this->name;
        }

        $this->alias = \Joomla\CMS\Application\ApplicationHelper::stringURLSafe($this->alias);

        return true;
    }
}
