<?php

namespace Learn\Plugin\Workflow\Hotelbooking\Extension;

use Joomla\CMS\Event\Model;
use Joomla\CMS\Event\Workflow\WorkflowTransitionEvent;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Workflow\WorkflowPluginTrait;
use Joomla\CMS\Workflow\WorkflowServiceInterface;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\SubscriberInterface;
use Learn\Component\Hotelbooking\Administrator\Helper\BookingWorkflowHelper;
use Learn\Component\Hotelbooking\Administrator\Helper\PartnerNotificationHelper;

\defined('_JEXEC') or die;

final class Hotelbooking extends CMSPlugin implements SubscriberInterface
{
    use WorkflowPluginTrait;
    use DatabaseAwareTrait;

    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm'       => 'onContentPrepareForm',
            'onWorkflowBeforeTransition' => 'onWorkflowBeforeTransition',
            'onWorkflowAfterTransition'  => 'onWorkflowAfterTransition',
        ];
    }

    public function onContentPrepareForm(Model\PrepareFormEvent $event): void
    {
        $form = $event->getForm();

        if ($form->getName() === 'com_workflow.transition') {
            $this->enhanceWorkflowTransitionForm($form, $event->getData());
        }
    }

    public function onWorkflowBeforeTransition(WorkflowTransitionEvent $event): void
    {
        $context    = (string) $event->getArgument('extension');
        $transition = $event->getArgument('transition');
        $pks        = (array) $event->getArgument('pks');

        if (!$this->isSupported($context)) {
            return;
        }

        $parsed = BookingWorkflowHelper::statusesFromOptions($transition->options);

        if ($parsed['action'] !== BookingWorkflowHelper::ACTION_NOTIFY_HOTEL) {
            return;
        }

        $db = $this->getDatabase();

        foreach ($pks as $pk) {
            $payload = BookingWorkflowHelper::loadNotifyContext($db, (int) $pk);
            $sent    = $payload !== null
                && PartnerNotificationHelper::sendEmail($payload['booking'], $payload['room'], $payload['destination']);

            if (BookingWorkflowHelper::shouldStopNotify($parsed['action'], $sent)) {
                $this->getApplication()->enqueueMessage(Text::_('COM_HOTELBOOKING_NOTIFY_EMAIL_FAILED'), 'error');
                $event->setStopTransition();

                return;
            }
        }
    }

    public function onWorkflowAfterTransition(WorkflowTransitionEvent $event): void
    {
        $context    = (string) $event->getArgument('extension');
        $transition = $event->getArgument('transition');
        $pks        = (array) $event->getArgument('pks');

        if (!$this->isSupported($context)) {
            return;
        }

        BookingWorkflowHelper::applyTransitionEffects($this->getDatabase(), $pks, $transition->options);
    }

    protected function isSupported($context)
    {
        if ($context !== BookingWorkflowHelper::EXTENSION) {
            return false;
        }

        $component = $this->getApplication()->bootComponent('com_hotelbooking');

        return $component instanceof WorkflowServiceInterface
            && $component->isWorkflowActive($context);
    }
}
