<?php

namespace Athorrent\Application;

use Athorrent\Routing\ControllerMounterTrait;
use Silex\Application\UrlGeneratorTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class WebApplication extends BaseApplication
{
    use ControllerMounterTrait;
    use UrlGeneratorTrait;

    public function __construct()
    {
        parent::__construct();

        $this->before([$this, 'updateConnectionTimestamp']);
        $this->after([$this, 'addHeaders']);
        
        $this->error([$this, 'handleError']);

        $this->register(new \Athorrent\Service\TwigServiceProvider());
        $this->register(new \Athorrent\Service\TranslationServiceProvider());
        $this->register(new \Athorrent\Service\SecurityServiceProvider());
        $this->register(new \Athorrent\Routing\RoutingServiceProvider());
        $this->register(new \Athorrent\Routing\RoutingServiceProvider());
        $this->register(new \Silex\Provider\LocaleServiceProvider());

        $this['dispatcher']->addListener(KernelEvents::RESPONSE, function () {
            $this['session']->save();
        }, self::LATE_EVENT);
    }

    public function updateConnectionTimestamp()
    {
        $user = $this['user'];

        if ($user === null) {
            return;
        }

        if (!$this['security.authorization_checker']->isGranted('ROLE_PREVIOUS_ADMIN')) {
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
            $response->headers->set('Content-Security-Policy', "script-src 'unsafe-inline' " . $request->getScheme() . '://' . STATIC_HOST);
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

    public function mountControllers()
    {
        $this->mount('/', new \Athorrent\Controllers\DefaultController(), '');

        $this->mount('/search', new \Athorrent\Controllers\SearchController(), 'search');

        $this->mount('/user/files', new \Athorrent\Controllers\FileController(), 'files');
        $this->mount('/user/torrents', new \Athorrent\Controllers\TorrentController(), 'torrents');
        $this->mount('/user/account', new \Athorrent\Controllers\AccountController(), 'account');

        $this->mount('/user/sharings', new \Athorrent\Controllers\SharingController(), 'sharings');
        $this->mount('/sharings/{token}/files', new \Athorrent\Controllers\SharingFileController(), 'sharings');

        $this->mount('/administration', new \Athorrent\Controllers\AdministrationController(), 'administration');
        $this->mount('/administration/users', new \Athorrent\Controllers\UserController(), 'users');
        $this->mount('/administration/cache', new \Athorrent\Controllers\CacheController(), 'cache');

//        $this->mount('/user/scheduler', new \Athorrent\Controller\SchedulerController(), 'scheduler');
    }
}
