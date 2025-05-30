<?php

namespace Athorrent\View;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

readonly class Renderer
{
    public function __construct(private Environment $twig, private RequestStack $requestStack)
    {
    }

    public function getDefaultTemplateName()
    {
        return $this->requestStack->getCurrentRequest()->attributes->get('_action');
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function renderTemplate(string $id, array $parameters): string
    {
        foreach ($parameters as $k => $v) {
            if ($v instanceof FormInterface) {
                $parameters[$k] = $v->createView();
            }
        }

        return $this->twig->render($id . '.html.twig', $parameters);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderFragment(string $name, array $parameters = []): string
    {
        return $this->renderTemplate('fragments/' . $name, $parameters);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function renderPage(string $name, array $parameters = []): string
    {
        return $this->renderTemplate('pages/' . $name, $parameters);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function render(Request $request, string $name, array $parameters = []): string
    {
        if ($request->isXmlHttpRequest()) {
            $html = $this->renderFragment($name, $parameters);
        } else {
            $html = $this->renderPage($name, $parameters);
        }

        return $html;
    }
}
