<?php

namespace Athorrent\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class WebApplication extends BaseApplication
{
    public function __construct()
    {
        parent::__construct();

        $this->register(new \Athorrent\Service\TwigServiceProvider());
        $this->register(new \Athorrent\Service\TranslationServiceProvider());
        $this->register(new \Athorrent\Service\SecurityServiceProvider());
        $this->register(new \Athorrent\Service\RoutingServiceProvider());

        $this->before([$this, 'updateConnectionTimestamp']);
        $this->after([$this, 'addHeaders']);
        
        $this->error([$this, 'handleError']);

        $this['dispatcher']->addListener(KernelEvents::RESPONSE, function () {
            $this['session']->save();
        }, self::LATE_EVENT);
    }

    public function updateConnectionTimestamp()
    {
        $user = $this['security']->getToken()->getUser();

        if ($user === 'anon.') {
            return;
        }

        if (!$this['security']->isGranted('ROLE_PREVIOUS_ADMIN')) {
            $user->updateConnectionTimestamp();
        }
    }

    public function addHeaders(Request $request, Response $response)
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($response->headers->has('Content-Disposition')) {
            return;
        }
        
        if (strpos($request->get('_route'), ':ajax') === false) {
            $response->headers->set('Content-Security-Policy', "script-src 'unsafe-inline' https://" . STATIC_HOST);
            $response->headers->set('Referrer-Policy', 'strict-origin');
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubdomains');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        }
    }

    public function handleError(\Exception $exception, $code)
    {
        if ($this['debug']) {
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $error = 'error.pageNotFound';
        }

        if ($code === 500) {
            $error = 'error.errorUnknown';
        }

        if (isset($error)) {
            $error = $this['translator']->trans($error);
        } else {
            $error = $exception->getMessage();
        }

        return new Response($this['twig']->render('pages/error.html.twig', ['error' => $error, 'code' => $code]));
    }
}
