<?php

namespace Athorrent\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGenerator extends BaseUrlGenerator
{
    protected $defaultLocale;

    protected $actionMap = [];

    public function __construct($defaultLocale, RouteCollection $routes, RequestContext $context, LoggerInterface $logger = null)
    {
        parent::__construct($routes, $context, $logger);
        $this->defaultLocale = $defaultLocale;
    }

    public function setActionMap(array $actionMap)
    {
        $this->actionMap = $actionMap;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if ($name[0] !== '_' && isset($this->actionMap[$name])) {
            if (isset($parameters['_locale'])) {
                $locale = $parameters['_locale'];
            } elseif ($this->context->hasParameter('_locale')) {
                $locale = $this->context->getParameter('_locale');
            } else {
                $locale = $this->defaultLocale;
            }

            if (isset($parameters['_prefixId'])) {
                $prefixId = $parameters['_prefixId'];
            } else {
                $currentPrefixId = $this->context->getParameter('_prefixId');

                foreach ($this->actionMap[$name] as $prefixId) {
                    if ($prefixId === $currentPrefixId) {
                        break;
                    }
                }
            }

            try {
                return parent::generate($locale . '|' . $prefixId . '.' . $name, $parameters, $referenceType);
            } catch (RouteNotFoundException $ex) {
                // fallback to default behavior
            }
        }

        // use the default behavior if no localized route exists
        return parent::generate($name, $parameters, $referenceType);
    }
}
