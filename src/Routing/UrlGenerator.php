<?php

namespace Athorrent\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class UrlGenerator extends BaseUrlGenerator
{
    protected $defaultLocale;

    protected $actionMap = [];

    public function __construct($defaultLocale, ActionMap $actionMap, RouteCollection $routes, RequestContext $context, LoggerInterface $logger = null)
    {
        parent::__construct($routes, $context, $logger);
        $this->defaultLocale = $defaultLocale;
        $this->actionMap = $actionMap;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        if (null === $this->routes->get($name)) {
            $locale = $parameters['_locale']
                ?? $this->context->getParameter('_locale')
                ?: $this->defaultLocale;

            if (isset($parameters['_prefixId'])) {
                $prefixId = $parameters['_prefixId'];
            } else {
                $currentPrefixId = $this->context->getParameter('_prefixId');

                foreach ($this->actionMap[$name] as $prefixId) {
                    if ($currentPrefixId === $prefixId) {
                        break;
                    }
                }
            }

            if ($locale === $this->defaultLocale) {
                $name = $prefixId . $name;
                unset($parameters['_locale']);
            } else {
                $name = $prefixId . $name . '|i18n';
                $parameters['_locale'] = $locale;
            }
        }

        return parent::generate($name, $parameters, $referenceType);
    }
}
