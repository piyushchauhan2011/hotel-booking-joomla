<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Association\AssociationExtensionInterface;
use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Learn\Component\Hotelbooking\Administrator\Extension\HotelbookingComponent;
use Learn\Component\Hotelbooking\Administrator\Helper\AssociationsHelper;

return new class implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(AssociationExtensionInterface::class, new AssociationsHelper());

        $container->registerServiceProvider(new MVCFactory('\\Learn\\Component\\Hotelbooking'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Learn\\Component\\Hotelbooking'));
        $container->registerServiceProvider(new RouterFactory('\\Learn\\Component\\Hotelbooking'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new HotelbookingComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));
                $component->setAssociationExtension($container->get(AssociationExtensionInterface::class));

                return $component;
            },
        );
    }
};
