<?php

namespace Athorrent\View;

use Exception;
use Symfony\Component\Form\FormInterface;
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
    public function render(ViewType $type, string $name, array $parameters = []): string
    {
        if (!in_array($type, [ViewType::Page, ViewType::Fragment])) {
            throw new Exception('unsupported view type: ' . $type->value);
        }

        return $this->renderTemplate($type->value . 's/' . $name, $parameters);
    }
}
