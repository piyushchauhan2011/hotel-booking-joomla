<?php

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Learn\Plugin\EditorsXtd\Hotelbooking\Extension\Hotelbooking;

return new class implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(Hotelbooking::class, function (Container $container) {
                $plugin = new Hotelbooking(
                    (array) PluginHelper::getPlugin('editors-xtd', 'hotelbooking'),
                );
                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            }),
        );
    }
};
