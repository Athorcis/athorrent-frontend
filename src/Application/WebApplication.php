<?php

namespace Athorrent\Application;

use Athorrent\Database\Entity\User;
use Athorrent\Routing\ControllerMounterTrait;
use Athorrent\Utils\TorrentManager;
use Athorrent\View\View;
use Silex\Application;
use Silex\Application\UrlGeneratorTrait;
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

        $this['locale'] = $this['default_locale'] = 'fr';
        $this['locales'] = ['fr', 'en'];

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
            return new \Athorrent\Filesystem\UserFilesystem($app, $app['user']);
        };
        $this->before([$this, 'updateConnectionTimestamp']);

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

        $this->register(new \Athorrent\View\TwigServiceProvider(), [
            'twig.path' => TEMPLATES_DIR,
            'twig.options' => ['cache' => CACHE_DIR . DIRECTORY_SEPARATOR . 'twig']
        ]);

        $this->register(new \Athorrent\View\TranslationServiceProvider(), [
            'locale_fallbacks' => [$this['default_locale']],
            'translator.cache_dir' => CACHE_DIR . '/translator'
        ]);

        $this->register(new \Athorrent\Security\SecurityServiceProvider());

        $this->register(new SessionServiceProvider());
        $this->register(new RememberMeServiceProvider());

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
            $user->setConnectionDateTime(new \DateTime());

            $this['orm.em']->persist($user);
            $this['orm.em']->flush();
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
        $this->mount('/', new \Athorrent\Controller\DefaultController(), '');

        $this->mount('/search', new \Athorrent\Controller\SearchController(), 'search');

        $this->mount('/user/files', new \Athorrent\Controller\FileController(), 'files');
        $this->mount('/user/torrents', new \Athorrent\Controller\TorrentController(), 'torrents');
        $this->mount('/user/account', new \Athorrent\Controller\AccountController(), 'account');

        $this->mount('/user/sharings', new \Athorrent\Controller\SharingController(), 'sharings');
        $this->mount('/sharings/{token}/files', new \Athorrent\Controller\SharingFileController(), 'sharings');

        $this->mount('/administration', new \Athorrent\Controller\AdministrationController(), 'administration');
        $this->mount('/administration/users', new \Athorrent\Controller\UserController(), 'users');
        $this->mount('/administration/cache', new \Athorrent\Controller\CacheController(), 'cache');

//        $this->mount('/user/scheduler', new \Athorrent\Controller\SchedulerController(), 'scheduler');
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
