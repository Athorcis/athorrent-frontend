<?php

namespace Athorrent\Service;

use Athorrent\Utils\AuthenticationHandler;
use Athorrent\Utils\Csrf\TokenManager;
use Athorrent\Utils\UserProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class SecurityServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app->register(new \Silex\Provider\SessionServiceProvider());
        $app->register(new \Silex\Provider\SecurityServiceProvider());
        $app->register(new \Silex\Provider\RememberMeServiceProvider());

        $app['session.storage.options'] = [
            'name' => 'SESSION',
            'cookie_httponly' => true,
            'cookie_secure' => true
        ];

        $app['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use ($app) {
            $this->handleRequest($event, $app);
        }, Application::EARLY_EVENT - 400);

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
                    'secure' => true
                ],

                'users' => $app->share(function () {
                    return new UserProvider();
                })
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

        $app['security.authentication.failure_handler.general'] = $app->share(function () {
            return new AuthenticationHandler();
        });
    }

    public function boot(Application $app)
    {
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
            if (!$app['csrf.manager']->isTokenValid($request->get('csrf'))) {
                $app->abort(403);
            }

            $app['csrf.token'] = $app['csrf.manager']->refreshToken();
        } else {
            $app['csrf.token'] = $app['csrf.manager']->getToken();
        }
    }
}
