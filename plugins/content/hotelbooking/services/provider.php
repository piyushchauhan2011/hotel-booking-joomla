<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Learn\Plugin\Content\Hotelbooking\Extension\Hotelbooking;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Hotelbooking::class, function (Container $container) {
                $plugin = new Hotelbooking(
                    (array) PluginHelper::getPlugin('content', 'hotelbooking'),
                );
                $plugin->setApplication(Factory::getApplication());
                $plugin->setDatabase($container->get(DatabaseInterface::class));

                return $plugin;
            }),
        );
    }
};
