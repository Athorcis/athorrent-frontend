<?php

namespace Athorrent\Service;

use Athorrent\Utils\AliasResolver;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\UrlGeneratorServiceProvider;

class RoutingServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        if (!isset($app['url_generator'])) {
            $app->register(new UrlGeneratorServiceProvider());
        }
        
        \Athorrent\Controllers\DefaultController::mount($app);
        \Athorrent\Controllers\TorrentController::mount($app);
        \Athorrent\Controllers\FileController::mount($app);
        \Athorrent\Controllers\AdministrationController::mount($app);
        \Athorrent\Controllers\UserController::mount($app);
        \Athorrent\Controllers\CacheController::mount($app);
        \Athorrent\Controllers\SharingController::mount($app);
        \Athorrent\Controllers\SharingFileController::mount($app);
        \Athorrent\Controllers\AccountController::mount($app);
        \Athorrent\Controllers\SchedulerController::mount($app);
    }

    public function boot(Application $app)
    {
        $this->initializeAliasResolver($app);
    }
    
    public function initializeAliasResolver(Application $app)
    {
        if ($app['cache']->exists('routes')) {
            $routes = $app['cache']->fetch('routes');
            $ajaxRoutes = $app['cache']->fetch('ajaxRoutes');
        } else {
            $routes = [];
            $ajaxRoutes = [];

            foreach ($app['routes'] as $alias => $route) {
                $locale = substr($alias, 0, 2);

                if ($route->hasOption('action')) {
                    if (strpos($alias, 'ajax/') !== false) {
                        if ($locale == $app['locale']) {
                            $ajaxRoutes[] = $route;
                        }
                    } else {
                        $action = $route->getOption('action');
                        $actionPrefix = $route->getOption('actionPrefix');

                        if (!isset($routes[$locale . $action])) {
                            $routes[$locale . $action] = [];
                        }

                        if (isset($routes[$locale . $action][$actionPrefix])) {
                            trigger_error('route already defined', E_USER_WARNING);
                        }

                        $routes[$action][$actionPrefix] = $route;
                    }
                }
            }

            $app['cache']->store('routes', $routes);
            $app['cache']->store('ajaxRoutes', $ajaxRoutes);
        }

        $app['alias_resolver'] = new AliasResolver($routes);
        $app['ajax_routes'] = $ajaxRoutes;
    }
}