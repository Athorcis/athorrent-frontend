<?php

namespace Athorrent\View;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig_Environment;

class Renderer
{
    private $twig;

    private $requestStack;

    public function __construct(Twig_Environment $twig, RequestStack $requestStack)
    {
        $this->twig = $twig;
        $this->requestStack = $requestStack;
    }

    public function getDefaultTemplateName()
    {
        return $this->requestStack->getCurrentRequest()->attributes->get('_action');
    }

    protected function renderTemplate($id, array $parameters)
    {
        return $this->twig->render($id . '.html.twig', $parameters);
    }

    public function renderFragment($name, array $parameters = [])
    {
        return $this->renderTemplate('fragments/' . $name, $parameters);
    }

    public function renderPage($name, array $parameters = [])
    {
        return $this->renderTemplate('pages/' . $name, $parameters);
    }

    public function render($name, array $parameters = [])
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
