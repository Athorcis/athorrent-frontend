<?php

namespace Athorrent\View;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

class Renderer
{
    public function __construct(private Environment $twig, private RequestStack $requestStack)
    {
    }

    public function getDefaultTemplateName()
    {
        return $this->requestStack->getCurrentRequest()->attributes->get('_action');
    }

    protected function renderTemplate($id, array $parameters): string
    {
        return $this->twig->render($id . '.html.twig', $parameters);
    }

    public function renderFragment($name, array $parameters = []): string
    {
        return $this->renderTemplate('fragments/' . $name, $parameters);
    }

    public function renderPage($name, array $parameters = []): string
    {
        return $this->renderTemplate('pages/' . $name, $parameters);
    }

    public function render($name, array $parameters = []): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request->isXmlHttpRequest()) {
            $html = $this->renderFragment($name, $parameters);
        } else {
            $html = $this->renderPage($name, $parameters);
        }

        return $html;
    }
}
