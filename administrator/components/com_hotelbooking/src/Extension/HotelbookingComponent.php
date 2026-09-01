<?php

namespace Learn\Component\Hotelbooking\Administrator\Extension;

use Joomla\CMS\Association\AssociationServiceInterface;
use Joomla\CMS\Association\AssociationServiceTrait;
use Joomla\CMS\Component\Router\RouterServiceInterface;
use Joomla\CMS\Component\Router\RouterServiceTrait;
use Joomla\CMS\Extension\BootableExtensionInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\HTML\HTMLRegistryAwareTrait;
use Psr\Container\ContainerInterface;

\defined('_JEXEC') or die;

class HotelbookingComponent extends MVCComponent implements BootableExtensionInterface, RouterServiceInterface, AssociationServiceInterface
{
    use HTMLRegistryAwareTrait;
    use RouterServiceTrait;
    use AssociationServiceTrait;

    public function boot(ContainerInterface $container): void
    {
        // Pass
    }
}
