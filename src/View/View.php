<?php

namespace Athorrent\View;

use Silex\Application;
use Symfony\Component\Translation\Translator;

class View
{
    private $name;

    private $data;

    public function __construct(array $data = [], $name = null)
    {
        $this->name = $name;
        $this->data = $data;
    }

    public function has($key)
    {
        return isset($this->data[$key]);
    }

    public function set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function setJsVar($key, $value)
    {
        $this->data['js_vars'][$key] = $value;
    }

    public function setJsVars($vars)
    {
        foreach ($vars as $key => $value) {
            $this->data['js_vars'][$key] = $value;
        }
    }

    public function addString($id)
    {
        $this->data['_strings'][] = $id;
    }

    public function addTemplate($name)
    {
        $this->data['_templates'][] = $name;
    }

    public function render(Application $app)
    {
        $name = $this->name;

        if ($name === null) {
            $name = $app['request_stack']->getCurrentRequest()->attributes->get('_action');
        }

        $data = $this->data;

        if (isset($data['_strings'])) {
            foreach ($data['_strings'] as $id) {
                $data['js_vars']['strings'][$id] = $app['translator']->trans($id);
            }
        }

        if (isset($data['_templates'])) {
            foreach ($data['_templates'] as $fragmentName) {
                $data['js_vars']['templates'][$fragmentName] = $app['renderer']->renderFragment($fragmentName);
            }
        }

        return $app['renderer']->render($name, $data);
    }
}
