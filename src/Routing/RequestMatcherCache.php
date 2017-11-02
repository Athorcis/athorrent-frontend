<?php

namespace Athorrent\Routing;

use Silex\Application;
use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RouteCollection;

class RequestMatcherCache
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->fetchRequestMatcher();
    }

    protected function getRequestMatcherPath()
    {
        return CACHE_DIR . '/DumpedUrlMatcher.php';
    }

    public function fetchRequestMatcher()
    {
        if ($this->app['debug']) {
            return;
        }

        $requestMatcherPath = $this->getRequestMatcherPath();

        if (file_exists($requestMatcherPath)) {
            require $requestMatcherPath;

            $this->app['request_matcher'] = function (Application $app) {
                return new \DumpedUrlMatcher($app['request_context']);
            };
        }
    }

    public function storeRequestMatcher(RouteCollection $routes)
    {
        if ($this->app['debug']) {
            return;
        }

        $requestMatcherPath = $this->getRequestMatcherPath();
        file_put_contents($requestMatcherPath, (new PhpMatcherDumper($routes))->dump(['class' => 'DumpedUrlMatcher']));
    }
}
