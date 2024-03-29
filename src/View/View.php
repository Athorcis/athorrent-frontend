<?php

namespace Athorrent\View;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class View
{
    public function __construct(private array $data = [], private readonly ?string $name = null)
    {
    }

    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }

    public function setJsVar(string $key, mixed $value): void
    {
        $this->data['js_vars'][$key] = $value;
    }

    public function setJsVars(array $vars): void
    {
        foreach ($vars as $key => $value) {
            $this->data['js_vars'][$key] = $value;
        }
    }

    public function addStrings(array $ids): void
    {
        foreach ($ids as $id) {
            $this->data['_strings'][] = $id;
        }
    }

    public function render(Request $request, TranslatorInterface $translator, Renderer $renderer): string
    {
        $name = $this->name ?? $renderer->getDefaultTemplateName();

        $data = $this->data;

        if (isset($data['_strings'])) {
            foreach ($data['_strings'] as $id) {
                $data['js_vars']['strings'][$id] = $translator->trans($id);
            }
        }

        return $renderer->render($request, $name, $data);
    }
}
