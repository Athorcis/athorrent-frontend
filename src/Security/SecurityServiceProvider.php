<?php

namespace Athorrent\Security;

use Athorrent\Security\Csrf\TokenManager;
use Athorrent\View\View;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider as BaseSecurityServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityServiceProvider extends BaseSecurityServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        $app['session.storage.options'] = [
            'name' => 'SESSION',
            'cookie_httponly' => true,
            'cookie_secure' => !$app['debug']
        ];

        $app['security.firewalls'] = [
            'general' => [
                'anonymous' => true,

                'pattern' => '^/',

                'form' => [
                    'login_path' => '/',
                    'check_path' => '/_login_check',
                    'default_target_path' => '/user/files/',
                    'always_use_default_target_path' => false
                ],

                'logout' => [
                    'logout_path' => '/_logout',
                    'target_url' => '/',
                ],

                'switch_user' => [
                    'parameter' => '_switch_user',
                    'role' => 'ROLE_ALLOWED_TO_SWITCH'
                ],

                'remember_me' => [
                    'key' => REMEMBER_ME_KEY,
                    'always_remember_me' => true,
                    'secure' => !$app['debug']
                ],

                'users' => function (Application $app) {
                    return $app['user_manager'];
                }
            ]
        ];

        $app['security.role_hierarchy'] = [
            'ROLE_ADMIN' => ['ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'],
        ];

        $nonDefaultLocales = $app['locales'];
        $defaultLocaleKey = array_search($app['default_locale'], $nonDefaultLocales);

        if ($defaultLocaleKey !== false) {
            unset($nonDefaultLocales[$defaultLocaleKey]);
        }

        if (count($nonDefaultLocales)) {
            $localesPrefix = '((' . implode('|', $nonDefaultLocales) . ')/)?';
        } else {
            $localesPrefix = '';
        }

        $app['security.access_rules'] = [
            ['^/' . $localesPrefix . '(ajax/)?administration', 'ROLE_ADMIN'],
            ['^/' . $localesPrefix . '(ajax/)?search', 'ROLE_USER'],
            ['^/' . $localesPrefix . '(ajax/)?user', 'ROLE_USER']
        ];

        $app['security.default_encoder'] = $app['security.encoder.digest'];

        $app['security.authentication.failure_handler.general'] = function () {
            return new AuthenticationHandler();
        };

        $app['user_manager'] = function (Container $app) {
            return new UserManager($app['orm.em'], $app['orm.repo.user'], $app['security.default_encoder']);
        };
    }

    public function boot(Application $app)
    {
        if (!$app['cache']->has('routes')) {
            parent::boot($app);
        }
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        parent::subscribe($app, $dispatcher);

        $dispatcher->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($app) {
            $this->handleRequest($event, $app);
        });

        $dispatcher->addListener(KernelEvents::VIEW, function (GetResponseForControllerResultEvent $event) use ($app) {
            $result = $event->getControllerResult();

            if ($result === null) {
                return;
            }

            if ($result instanceof View) {
                $result->setJsVar('csrfToken', $app['csrf.token']);
            } elseif ($event->getRequest()->getMethod() === 'POST') {
                $result['csrfToken'] = $app['csrf.token'];
            }

            $event->setControllerResult($result);
        });
    }

    public function handleRequest(GetResponseEvent $event, Application $app)
    {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $app['csrf.manager'] = new TokenManager($session);

        if ($request->getMethod() === 'POST') {
            if (!$app['csrf.manager']->isTokenValid($request->get('csrfToken'))) {
                $app->abort(403);
            }

            $app['csrf.token'] = $app['csrf.manager']->refreshToken();
        } else {
            $app['csrf.token'] = $app['csrf.manager']->getToken();
        }
    }
}
