<?php

namespace Athorrent\Routing;

use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\CompiledUrlGenerator as BaseUrlGenerator;
use Symfony\Component\Routing\RequestContext;

class CompiledUrlGenerator extends BaseUrlGenerator
{
    private $actionMap;

    private $compiledRoutes;

    private $defaultLocale;

    public function __construct($actionMap, array $compiledRoutes, RequestContext $context, LoggerInterface $logger = null, string $defaultLocale = null)
    {
        $this->actionMap = $actionMap;
        $this->compiledRoutes = $compiledRoutes;
        $this->context = $context;
        $this->logger = $logger;
        $this->defaultLocale = $defaultLocale;
    }

    protected function getPrefixId($name, array $parameters)
    {
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

        return $prefixId;
    }

    public function generate($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH): string
    {
        $locale = $parameters['_locale']
            ?? $this->context->getParameter('_locale')
                ?: $this->defaultLocale;

        if (null !== $locale) {

            $tmpLocale = $locale;

            do {
                if (($this->compiledRoutes[$name.'.'.$tmpLocale][1]['_canonical_route'] ?? null) === $name) {
                    $name .= '.'.$tmpLocale;
                    break;
                }
            } while (false !== $tmpLocale = strstr($tmpLocale, '_', true));
        }

        if (!isset($this->compiledRoutes[$name])) {

            $prefixId = $this->getPrefixId($name, $parameters);

            if ($locale === $this->defaultLocale) {
                $name = $prefixId . $name;
                unset($parameters['_locale']);
            } else {
                $name = $prefixId . $name . '|i18n';
                $parameters['_locale'] = $locale;
            }
        }

        if (!isset($this->compiledRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        [$variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes] = $this->compiledRoutes[$name];

        if (isset($defaults['_canonical_route'], $defaults['_locale'])) {
            if (!\in_array('_locale', $variables, true)) {
                unset($parameters['_locale']);
            } elseif (!isset($parameters['_locale'])) {
                $parameters['_locale'] = $defaults['_locale'];
            }
        }

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
