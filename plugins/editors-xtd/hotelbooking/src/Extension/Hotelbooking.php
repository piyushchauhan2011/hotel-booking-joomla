<?php

namespace Learn\Plugin\EditorsXtd\Hotelbooking\Extension;

use Joomla\CMS\Editor\Button\Button;
use Joomla\CMS\Event\Editor\EditorButtonsSetupEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Session\Session;
use Joomla\Event\SubscriberInterface;

\defined('_JEXEC') or die;

/**
 * Adds a "Hotel Booking" editor toolbar button that opens a modal (com_hotelbooking's
 * Snippets picker, backend-only) for inserting a {hotelbooking ...} tag — resolved into
 * a room, destination, or offer/coupon card by plg_content_hotelbooking at display time.
 */
final class Hotelbooking extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onEditorButtonsSetup' => 'onEditorButtonsSetup'];
    }

    public function onEditorButtonsSetup(EditorButtonsSetupEvent $event): void
    {
        $subject  = $event->getButtonsRegistry();
        $disabled = $event->getDisabledButtons();

        if (\in_array($this->_name, $disabled)) {
            return;
        }

        $button = $this->onDisplay($event->getEditorId());

        if ($button) {
            $subject->add($button);
        }
    }

    /**
     * The Snippets picker is a backend-only admin view, so skip the button entirely
     * on front-end editors (e.g. front-end article submission) rather than link to it.
     */
    public function onDisplay($name)
    {
        if (!$this->getApplication()->isClient('administrator')) {
            return;
        }

        $user = $this->getApplication()->getIdentity();

        $canInsert = $user->authorise('core.create', 'com_content')
            || $user->authorise('core.edit', 'com_content')
            || $user->authorise('core.edit.own', 'com_content');

        if (!$canInsert) {
            return;
        }

        $this->loadLanguage();

        $link = 'index.php?option=com_hotelbooking&view=snippets&layout=modal&tmpl=component&'
            . Session::getFormToken() . '=1&editor=' . $name;

        return new Button(
            $this->_name,
            [
                'action'  => 'modal',
                'link'    => $link,
                'text'    => Text::_('PLG_EDITORS-XTD_HOTELBOOKING_BUTTON'),
                'icon'    => 'hotel',
                'iconSVG' => '<svg viewBox="0 0 512 512" width="24" height="24"><path d="M64 32a32 32 0 0 0-32 32v384a32 32 0 0 0 64 0v-64h320v64a32 32 '
                    . '0 0 0 64 0V224a96 96 0 0 0-96-96H96V64a32 32 0 0 0-32-32zm32 160h128v64H96v-64zm192 0h96a32 32 0 0 1 32 32v32H288v-64zM96 320h320v'
                    . '32H96v-32z"></path></svg>',
                'name' => $this->_type . '_' . $this->_name,
            ]
        );
    }
}
