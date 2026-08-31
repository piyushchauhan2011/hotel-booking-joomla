<?php

namespace Learn\Component\Hotelbooking\Administrator\Table;

use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\DispatcherInterface;

\defined('_JEXEC') or die;

class FaqTable extends Table
{
    public function __construct(DatabaseInterface $db, ?DispatcherInterface $dispatcher = null)
    {
        parent::__construct('#__hotelbooking_faqs', 'id', $db, $dispatcher);
    }

    public function check()
    {
        if (trim($this->question) === '') {
            $this->setError('Question is required.');

            return false;
        }

        return true;
    }
}
