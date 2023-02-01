<?php

namespace Athorrent\View;


use Symfony\Contracts\Translation\TranslatorInterface;

class View
{
    public function __construct(private array $data = [], private ?string $name = null)
    {
    }

    public function has($key): bool
    {
        return isset($this->data[$key]);
    }

    public function set($key, $value): void
    {
        $this->data[$key] = $value;
    }

    public function setJsVar($key, $value): void
    {
        $this->data['js_vars'][$key] = $value;
    }

    public function setJsVars($vars): void
    {
        foreach ($vars as $key => $value) {
            $this->data['js_vars'][$key] = $value;
        }
    }

    public function addString($id): void
    {
        $this->data['_strings'][] = $id;
    }

    public function addTemplate($name): void
    {
        $this->data['_templates'][] = $name;
    }

    public function render(TranslatorInterface $translator, Renderer $renderer): string
    {
        $name = $this->name ?? $renderer->getDefaultTemplateName();

        $data = $this->data;

        if (isset($data['_strings'])) {
            foreach ($data['_strings'] as $id) {
                $data['js_vars']['strings'][$id] = $translator->trans($id);
            }
        }

        if (isset($data['_templates'])) {
            foreach ($data['_templates'] as $fragmentName) {
                $data['js_vars']['templates'][$fragmentName] = $renderer->renderFragment($fragmentName);
            }
        }

        return $renderer->render($name, $data);
    }
}
