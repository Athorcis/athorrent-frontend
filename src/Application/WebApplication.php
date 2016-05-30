<?php

namespace Athorrent\Application;

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
