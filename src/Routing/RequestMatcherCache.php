<?php

namespace Athorrent\Routing;

use Symfony\Component\Routing\Matcher\Dumper\PhpMatcherDumper;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class RequestMatcherCache
{
    private $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
        $this->fetchRequestMatcher();
    }

    protected function getRequestMatcherPath()
    {
        return CACHE_DIR . '/DumpedUrlMatcher.php';
    }

    public function fetchRequestMatcher()
    {
        if (DEBUG) {
            return;
        }

        $requestMatcherPath = $this->getRequestMatcherPath();

        if (file_exists($requestMatcherPath)) {
            require $requestMatcherPath;

            $this->app['request_matcher'] = function () {
                return new \DumpedUrlMatcher($this->requestContext);
            };
        }
    }

    public function storeRequestMatcher(RouteCollection $routes)
    {
        if (DEBUG) {
            return;
        }

        $requestMatcherPath = $this->getRequestMatcherPath();
        file_put_contents($requestMatcherPath, (new PhpMatcherDumper($routes))->dump(['class' => 'DumpedUrlMatcher']));
    }
}
