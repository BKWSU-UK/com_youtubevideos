<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtubevideos
 *
 * @copyright   Copyright (C) 2024 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later;
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The YouTube Videos grid module service provider.
 *
 * @since  1.0.6
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.0.6
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\BKWSU\\Module\\Youtubevideos'));
        $container->registerServiceProvider(new HelperFactory('\\BKWSU\\Module\\Youtubevideos\\Site\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
