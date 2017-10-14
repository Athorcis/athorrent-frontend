<?php

namespace Athorrent\Controllers;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractController extends \Athorrent\Routing\AbstractController implements ControllerProviderInterface
{
    protected $action;

    protected function getUser()
    {
        global $app;
        return $app['user'];
    }

    protected function getUserId()
    {
        $user = $this->getUser();

        if ($user === 'anon.') {
            return null;
        }

        return $user->getUserId();
    }

    protected function renderFragment($parameters = array(), $view = null)
    {
        global $app;

        if (!$view) {
            $view = $app['request_stack']->getCurrentRequest()->attributes->get('_action');
        }

        return $app['twig']->render('fragments/' . $view . '.html.twig', $parameters);
    }

    protected function renderPage($parameters = array(), $view = null)
    {
        global $app;

        if (!$view) {
            $view = $app['request_stack']->getCurrentRequest()->attributes->get('_action');
        }

        return $app['twig']->render('pages/' . $view . '.html.twig', $parameters);
    }

    protected function render($parameters = array(), $view = null)
    {
        global $app;

        $parameters = array_merge($this->getTwigParameters(), $parameters);

        if ($app['request_stack']->getCurrentRequest()->isXmlHttpRequest()) {
            $response = $this->success($this->renderFragment($parameters, $view));
        } else {
            $response = $this->renderPage($parameters, $view);
        }

        return $response;
    }

    protected function sendFile($path, $status = 200, $headers = array())
    {
        global $app;
        return $app->sendFile($path, $status, $headers);
    }

    protected function json($data, $code)
    {
        global $app;

        if ($app['request_stack']->getCurrentRequest()->getMethod() === 'POST') {
            $data['csrf'] = $app['csrf.token'];
        }

        return $app->json($data, $code);
    }

    protected function success($data = array(), $code = 200)
    {
        global $app;

        if ($app['request_stack']->getCurrentRequest()->isXmlHttpRequest()) {
            $response = $this->json(array('status' => 'success', 'data' => $data), $code);
        } else {
            $response = new Response($data, $code);
        }

        return $response;
    }

    protected function abort($code, $error = null)
    {
        global $app;

        if ($error === null) {
            switch ($code) {
            case 400:
                $error = 'error.badRequest';
                break;

            case 404:
                $error = 'error.pageNotFound';
                break;

            default:
                $error = 'error.errorUnknown';
                break;
            }
        }

        $error = $app['translator']->trans($error);

        if ($app['request_stack']->getCurrentRequest()->isXmlHttpRequest()) {
            return $this->json(array('status' => 'error', 'error' => $error), $code);
        }

        $app->abort($code, $error);
    }

    public function getRouteParameters($action)
    {
        return array();
    }

    protected function getJsVariables()
    {
        global $app;

        $jsVariables = array();

        $jsVariables['debug'] = DEBUG;
        $jsVariables['staticHost'] = STATIC_HOST;

        $jsVariables['csrf'] = $app['csrf.token'];

        $jsVariables['action'] = $this->action;
        $jsVariables['actionPrefix'] = $app['request_stack']->getCurrentRequest()->attributes->get('_prefixId');
        $jsVariables['routeParameters'] = $this->getRouteParameters(null);

        if (!count($jsVariables['routeParameters'])) {
            unset($jsVariables['routeParameters']);
        }

        $jsVariables['routes'] = $app['ajax_route_descriptors'];
        $jsVariables['templates']['modal'] = $this->renderFragment(array(), 'modal');

        return $jsVariables;
    }

    protected function addNotification($type, $message)
    {
        global $app;
        $app['request_stack']->getCurrentRequest()->getSession()->getFlashBag()->add($type, $message);
    }

    protected function getTwigParameters()
    {
        global $app;
        $request = $app['request_stack']->getCurrentRequest();

        $parameters = array ();

        if ($request->getSession()->getFlashBag()->has('error')) {
            $errors = $request->getSession()->getFlashBag()->get('error');
            $parameters['error'] = $errors[0];
        }

        if (!$request->isXmlHttpRequest()) {
            $parameters['js_variables'] = $this->getJsVariables();
        }

        return $parameters;
    }

    protected function redirect($url, $status = 302)
    {
        global $app;

        try {
            $url = $app['url_generator']->generate($url);
        } catch (\Exception $exception) {

        }

        return $app->redirect($url, $status);
    }

    protected function url($action, $parameters = array())
    {
        global $app;

        return $app->url($action, $parameters);
    }
}
