<?php

namespace BKWSU\Component\Youtubevideos\Administrator\Service\Provider;

use Joomla\CMS\Component\Router\RouterFactoryInterface;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\CategoryFactory;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Extension\Service\Provider\RouterFactory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

use BKWSU\Component\Youtubevideos\Administrator\Extension\YoutubevideosComponent;

return new class implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new CategoryFactory('\\BKWSU\\Component\\Youtubevideos'));
        $container->registerServiceProvider(new MVCFactory('\\BKWSU\\Component\\Youtubevideos'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\BKWSU\\Component\\Youtubevideos'));
        $container->registerServiceProvider(new RouterFactory('\\BKWSU\\Component\\Youtubevideos'));

        $container->set(
            ComponentInterface::class,
            function (Container $container) {
                $component = new YoutubevideosComponent($container->get(ComponentDispatcherFactoryInterface::class));

                $component->setRegistry($container->get(Registry::class));
                $component->setMVCFactory($container->get(MVCFactoryInterface::class));
                $component->setRouterFactory($container->get(RouterFactoryInterface::class));

                return $component;
            }
        );
    }
};
