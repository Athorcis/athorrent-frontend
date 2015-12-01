<?php

use Athorrent\Utils\AliasResolver;
use Silex\Application;
use Symfony\Component\HttpKernel\KernelEvents;

function initializeRoutes(Application $app) {
    Athorrent\Controllers\DefaultController::mount($app);
    Athorrent\Controllers\TorrentController::mount($app);
    Athorrent\Controllers\FileController::mount($app);
    Athorrent\Controllers\AdministrationController::mount($app);
    Athorrent\Controllers\UserController::mount($app);
    Athorrent\Controllers\CacheController::mount($app);
    Athorrent\Controllers\SharingController::mount($app);
    Athorrent\Controllers\SharingFileController::mount($app);

    $app['dispatcher']->addListener(KernelEvents::REQUEST, function () use($app) {
        if ($app['cache']->exists('routes')) {
            $routes = $app['cache']->fetch('routes');
            $ajaxRoutes = $app['cache']->fetch('ajaxRoutes');
        } else {
            $routes = array();
            $ajaxRoutes = array();

            foreach ($app['routes'] as $alias => $route) {
                if ($alias[0] === ':') {
                    if (substr($alias, 1, 5) === 'ajax/') {
                        $ajaxRoutes[] = $route;
                    } else {
                        $action = $route->getOption('action');
                        $actionPrefix = $route->getOption('actionPrefix');

                        if (!isset($routes[$action])) {
                            $routes[$action] = array();
                        }

                        if (isset($routes[$action][$actionPrefix])) {
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
    }, Application::EARLY_EVENT);
}

?>
