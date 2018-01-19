<?php

namespace Athorrent\Application;

use Athorrent\Controller\AccountController;
use Athorrent\Controller\AdministrationController;
use Athorrent\Controller\CacheController;
use Athorrent\Controller\DefaultController;
use Athorrent\Controller\FileController;
use Athorrent\Controller\SearchController;
use Athorrent\Controller\SharingController;
use Athorrent\Controller\SharingFileController;
use Athorrent\Controller\TorrentController;
use Athorrent\Controller\UserController;
use Athorrent\Database\Entity\User;
use Athorrent\Filesystem\UserFilesystem;
use Athorrent\Routing\ControllerMounterTrait;
use Athorrent\Routing\RoutingServiceProvider;
use Athorrent\Security\Csrf\CsrfServiceProvider;
use Athorrent\Security\SecurityServiceProvider;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\TwigServiceProvider;
use Athorrent\View\View;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;
use Silex\Provider\LocaleServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Silex\Provider\SessionServiceProvider;
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

        $this['torrent_manager'] = $this->protect(function (User $user) {
            static $instances = [];

            $userId = $user->getId();

            if (!isset($instances[$userId])) {
                $instances[$userId] = new TorrentManager($user);
            }

            return $instances[$userId];
        });

//        $this['fs'] = function () {
//            return new Filesystem();
//        };

        $this['user.fs'] = function (Application $app) {
            return new UserFilesystem($app, $app['user']);
        };

        $this->view(function (View $result, Request $request) {
            if (!$request->attributes->get('_ajax')) {
                $flashBag = $this['session']->getFlashBag();

                if ($flashBag->has('notifications')) {
                    $result->set('notifications', $flashBag->get('notifications'));
                }

                $result->addTemplate('modal');

                $vars = [
                    'debug' => $this['debug'],
                    'staticHost' => STATIC_HOST
                ];

                $result->setJsVars($vars);
            }
        });

        $this->after([$this, 'addHeaders']);

        $this->error([$this, 'handleError']);

        $this->register(new TwigServiceProvider(), [
            'twig.path' => TEMPLATES_DIR,
            'twig.options' => ['cache' => CACHE_DIR . DIRECTORY_SEPARATOR . 'twig']
        ]);

        $this->register(new SecurityServiceProvider());

        $this->register(new SessionServiceProvider());
        $this->register(new RememberMeServiceProvider());
        $this->register(new CsrfServiceProvider());

        $this->register(new RoutingServiceProvider());
        $this->register(new LocaleServiceProvider());

        $this['dispatcher']->addListener(KernelEvents::RESPONSE, function () {
            $this['session']->save();
        }, self::LATE_EVENT);
    }

    public function addHeaders(Request $request, Response $response)
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($response->headers->has('Content-Disposition')) {
            return;
        }

        if (strpos($request->get('_route'), ':ajax') === false) {
            $response->headers->set('Content-Security-Policy', "script-src 'unsafe-inline' " . $request->getScheme() . '://' . STATIC_HOST);
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
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

        if ($exception instanceof NotifiableException) {
            return $this->notify('error', $exception->getMessage());
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

        $request = $this['request_stack']->getCurrentRequest();

        if ($request->attributes->get('_ajax')) {
            return $this->json([
                'status' => 'error',
                'error' => $error
            ]);
        }

        return new Response($this['twig']->render('pages/error.html.twig', ['error' => $error, 'code' => $code]));
    }

    public function mountControllers()
    {
        $this->mount('/', new DefaultController(), '');

        $this->mount('/search', new SearchController(), 'search');

        $this->mount('/user/files', new FileController(), 'files');
        $this->mount('/user/torrents', new TorrentController(), 'torrents');
        $this->mount('/user/account', new AccountController(), 'account');

        $this->mount('/user/sharings', new SharingController(), 'sharings');
        $this->mount('/sharings/{token}/files', new SharingFileController(), 'sharings');

        $this->mount('/administration', new AdministrationController(), 'administration');
        $this->mount('/administration/users', new UserController(), 'users');
        $this->mount('/administration/cache', new CacheController(), 'cache');
    }

    public function redirect($url, $status = 302)
    {
        try {
            $url = $this['url_generator']->generate($url);
        } catch (\Exception $exception) {

        }

        return parent::redirect($url, $status);
    }

    public function notify($type, $message, $url = null)
    {
        $flashBag = $this['session']->getFlashBag();
        $flashBag->add('notifications', ['type' => $type, 'message' => $message]);

        if (!$url) {
            $request = $this['request_stack']->getCurrentRequest();
            $url = $request->headers->get('Referer');
        }

        return $this->redirect($url);
    }
}
