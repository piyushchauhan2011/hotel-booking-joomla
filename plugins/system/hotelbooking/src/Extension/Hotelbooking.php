<?php

namespace Learn\Plugin\System\Hotelbooking\Extension;

use Joomla\CMS\Event\Menu\PreprocessMenuItemsEvent;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\SubscriberInterface;
use Learn\Component\Hotelbooking\Administrator\Helper\AccessHelper;

\defined('_JEXEC') or die;

/**
 * Hides the site-wide FAQs admin menu from hotel managers. CssMenu only checks
 * core.manage on the component, which those users must have for Destinations.
 */
final class Hotelbooking extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onPreprocessMenuItems' => 'onPreprocessMenuItems'];
    }

    public function onPreprocessMenuItems(PreprocessMenuItemsEvent $event): void
    {
        $app = $this->getApplication();

        if (!$app->isClient('administrator')) {
            return;
        }

        $user = $app->getIdentity();

        if ($user && AccessHelper::isPrivileged($user)) {
            return;
        }

        foreach ($event->getItems() as $item) {
            if (!AccessHelper::isAdministratorFaqsLink((string) $item->link)) {
                continue;
            }

            $item->getParams()->set('menu_show', 0);
        }
    }
}
