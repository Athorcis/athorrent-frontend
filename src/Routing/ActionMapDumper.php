<?php

namespace Athorrent\Routing;

use Symfony\Component\Routing\RouteCollection;
use function var_export;

class ActionMapDumper
{
    private $routes;

    private $actionMap;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    public function generateActionMap(): array
    {
        if ($this->actionMap === null) {
            $this->actionMap = [];

            foreach ($this->routes as $route) {
                if ($route->hasDefault('_action')) {
                    $action = $route->getDefault('_action');
                    $this->actionMap[$action][] = $route->getDefault('_prefixId');
                }
            }

            foreach ($this->actionMap as &$prefixIds) {
                $prefixIds = array_values(array_unique($prefixIds));
            }
        }

        return $this->actionMap;
    }

    private function generateDeclaredActionMap(): string
    {
        $routes = '';

        foreach ($this->generateActionMap() as $name => $properties) {
            $routes .= sprintf("\n    '%s' => %s,", $name, var_export($properties, true));
        }

        return $routes;
    }

    public function dump()
    {
        $declaredActionMap = $this->generateDeclaredActionMap();

        return <<<EOF
<?php

// This file has been auto-generated by the Symfony Routing Component.

return [{$declaredActionMap}
];

EOF;
    }
}
