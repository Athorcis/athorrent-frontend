<?php

namespace Athorrent\Security;

use Pimple\Container;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider as BaseSecurityServiceProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            ['^/' . $localesPrefix . '(ajax/)?(search|user)', 'ROLE_USER']
        ];

        $app['security.default_encoder'] = $app['security.encoder.digest'];

        $app['security.login.listener'] = function () use ($app) {
            return new LoginListener($app['orm.em']);
        };

        $app['security.authentication.failure_handler.general'] = function () {
            return new AuthenticationFailureHandler();
        };

        $app['user_manager'] = function (Application $app) {
            return new UserManager($app['orm.em'], $app['orm.repo.user'], $app['security.default_encoder']);
        };
    }

    public function subscribe(Container $app, EventDispatcherInterface $dispatcher)
    {
        parent::subscribe($app, $dispatcher);
        $dispatcher->addSubscriber($app['security.login.listener']);
    }

    public function boot(Application $app)
    {
        if (!$app['cache']->has('routes')) {
            parent::boot($app);
        }
    }
}
