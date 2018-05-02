<?php

namespace Athorrent\Routing;

use Doctrine\Common\Annotations\Reader;
use Sensio\Bundle\FrameworkExtraBundle\Routing\AnnotatedRouteControllerLoader;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends AnnotatedRouteControllerLoader
{
    private $locales;

    private $defaultLocale;

    public function __construct(Reader $reader, array $locales, string $defaultLocale)
    {
        parent::__construct($reader);

        $this->locales = $locales;
        $this->defaultLocale = $defaultLocale;
    }

    /**
     * @param RouteCollection $collection
     * @param \Symfony\Component\Routing\Annotation\Route $annot
     * @param array $globals
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     */
    protected function addRouteWithoutLocale(RouteCollection $collection, $annot, $globals, \ReflectionClass $class, \ReflectionMethod $method)
    {
        $annot->setDefaults(array_replace($annot->getDefaults(), [
            '_locale' => $this->defaultLocale
        ]));

        parent::addRoute($collection, $annot, $globals, $class, $method);
    }

    /**
     * @param RouteCollection $collection
     * @param \Symfony\Component\Routing\Annotation\Route $annot
     * @param array $globals
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     */
    protected function addRouteWithLocale(RouteCollection $collection, $annot, $globals, \ReflectionClass $class, \ReflectionMethod $method)
    {
        $annot->setName($annot->getName() . '|i18n');

        $annot->setRequirements(array_replace($annot->getRequirements(), [
            '_locale' => implode('|', $this->locales)
        ]));

        parent::addRoute($collection, $annot, $globals, $class, $method);
    }

    /**
     * @param RouteCollection $collection
     * @param \Symfony\Component\Routing\Annotation\Route $annot
     * @param array $globals
     * @param \ReflectionClass $class
     * @param \ReflectionMethod $method
     */
    protected function addRoute(RouteCollection $collection, $annot, $globals, \ReflectionClass $class, \ReflectionMethod $method)
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

    protected function getDefaultRouteName(\ReflectionClass $class, \ReflectionMethod $method)
    {
        return $method->getName();
    }
}
