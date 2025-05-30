<?php

namespace Athorrent\Routing;

use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\RouteCollection;

class AttributeClassLoader extends AttributeRouteControllerLoader
{
    /**
     * @param string[] $locales
     */
    public function __construct(private readonly array $locales, private readonly string $defaultLocale)
    {
        parent::__construct($_ENV['APP_ENV']);
    }

    protected function addRouteWithoutLocale(RouteCollection $collection, Route $attr, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        $attr->setDefaults(array_replace($attr->getDefaults(), [
            '_locale' => $this->defaultLocale
        ]));

        parent::addRoute($collection, $attr, $globals, $class, $method);
    }

    protected function addRouteWithLocale(RouteCollection $collection, Route $attr, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        $attr->setName($attr->getName() . '|i18n');

        $globals['path'] = '/{_locale}' . $globals['path'];
        $globals['requirements']['_locale'] = implode('|', $this->locales);

        parent::addRoute($collection, $attr, $globals, $class, $method);
    }

    /**
     * @param Route $attr
     */
    protected function addRoute(RouteCollection $collection, object $attr, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        if ($attr->getName() === null) {
            $attr->setName($this->getDefaultRouteName($class, $method));
        }

        $attr->setDefaults(array_replace($attr->getDefaults(), [
            '_action' => $method->getName(),
            '_prefixId' => $globals['name'] ?? ''
        ]));

        $this->addRouteWithLocale($collection, clone $attr, $globals, $class, $method);
        $this->addRouteWithoutLocale($collection, $attr, $globals, $class, $method);
    }

    protected function getDefaultRouteName(ReflectionClass $class, ReflectionMethod $method): string
    {
        return $method->getName();
    }
}
