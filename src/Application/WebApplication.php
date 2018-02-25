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
use Athorrent\Notification\NotificationListener;
use Athorrent\Routing\ControllerMounterTrait;
use Athorrent\Routing\RoutingServiceProvider;
use Athorrent\Security\AuthenticationFailureHandler;
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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;

use Symfony\Component\HttpKernel\KernelEvents;

class WebApplication extends BaseApplication implements EventSubscriberInterface
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

        $this['dispatcher']->addSubscriber($this);

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


        $notificationListener = new NotificationListener($this['url_generator']);
        $this['dispatcher']->addSubscriber($notificationListener);

        $app['security.authentication.failure_handler.general'] = function () use ($notificationListener) {
            return new AuthenticationFailureHandler($notificationListener);
        };
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => 'onKernelView',
            KernelEvents::RESPONSE => [
                ['addHeaders', 0],
                ['saveSession', -512]
            ]
        ];
    }

    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if ($result instanceof View) {
            $request = $event->getRequest();

            if (!$request->attributes->get('_ajax')) {
                $result->addTemplate('modal');

                $vars = [
                    'debug' => DEBUG,
                    'staticHost' => STATIC_HOST
                ];

                $result->setJsVars($vars);
            }
        }
    }

    public function saveSession(FilterResponseEvent $event)
    {
        $session = $event->getRequest()->getSession();

        if ($session->isStarted()) {
            $session->save();
        }
    }

    public function addHeaders(FilterResponseEvent $event)
    {
        $response = $event->getResponse();
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if ($response->headers->has('Content-Disposition')) {
            return;
        }

        $request = $event->getRequest();

        if (strpos($request->get('_route'), ':ajax') === false) {
            $response->headers->set('Content-Security-Policy', "script-src 'unsafe-inline' " . $request->getScheme() . '://' . STATIC_HOST);
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->headers->set('Strict-Transport-Security', 'max-age=63072000; includeSubdomains');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        }
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
}
