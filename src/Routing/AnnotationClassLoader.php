<?php

namespace Athorrent\Routing;

use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Bundle\FrameworkBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouteCollection;

class AnnotationClassLoader extends AnnotatedRouteControllerLoader
{
    /**
     * @param string[] $locales
     */
    public function __construct(Reader $reader, private array $locales, private string $defaultLocale)
    {
        parent::__construct($reader);
    }

    protected function addRouteWithoutLocale(RouteCollection $collection, Route $annot, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        $annot->setDefaults(array_replace($annot->getDefaults(), [
            '_locale' => $this->defaultLocale
        ]));

        parent::addRoute($collection, $annot, $globals, $class, $method);
    }

    protected function addRouteWithLocale(RouteCollection $collection, Route $annot, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        $annot->setName($annot->getName() . '|i18n');

        $globals['path'] = '/{_locale}' . $globals['path'];
        $globals['requirements']['_locale'] = implode('|', $this->locales);

        parent::addRoute($collection, $annot, $globals, $class, $method);
    }

    /**
     * @param Route $annot
     */
    protected function addRoute(RouteCollection $collection, $annot, array $globals, ReflectionClass $class, ReflectionMethod $method): void
    {
        if ($annot->getName() === null) {
            $annot->setName($this->getDefaultRouteName($class, $method));
        }

        $annot->setDefaults(array_replace($annot->getDefaults(), [
            '_action' => $method->getName(),
            '_prefixId' => $globals['name'] ?? ''
        ]));

        $this->addRouteWithLocale($collection, clone $annot, $globals, $class, $method);
        $this->addRouteWithoutLocale($collection, $annot, $globals, $class, $method);
    }

    protected function getDefaultRouteName(ReflectionClass $class, ReflectionMethod $method): string
    {
        return $method->getName();
    }
}
