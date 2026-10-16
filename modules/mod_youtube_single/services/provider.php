<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_youtube_single
 *
 * @copyright   Copyright (C) 2025 BKWSU. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Extension\Service\Provider\HelperFactory;
use Joomla\CMS\Extension\Service\Provider\Module;
use Joomla\CMS\Extension\Service\Provider\ModuleDispatcherFactory;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The YouTube single video module service provider.
 *
 * @since  1.2.12
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the service provider with a DI container.
     *
     * @param   Container  $container  The DI container.
     *
     * @return  void
     *
     * @since   1.2.12
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new ModuleDispatcherFactory('\\BKWSU\\Module\\YoutubeSingle'));
        $container->registerServiceProvider(new HelperFactory('\\BKWSU\\Module\\YoutubeSingle\\Site\\Helper'));

        $container->registerServiceProvider(new Module());
    }
};
