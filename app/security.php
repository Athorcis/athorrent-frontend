<?php

use Athorrent\Utils\CSRF\TokenManager as CsrfTokenManager;
use Athorrent\Utils\AuthenticationHandler;
use Athorrent\Utils\UserProvider;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\RememberMeServiceProvider;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;

function initializeSecurity(Application $app) {
    $app->register(new SessionServiceProvider());
    $app->register(new SecurityServiceProvider());
    $app->register(new RememberMeServiceProvider());

    $app['session.storage.options'] = array (
        'name' => 'SESSION',
        'cookie_httponly' => true
    );

    $app['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use($app) {
        $request = $event->getRequest();
        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        $app['csrf.manager'] = new CsrfTokenManager($session);

        if ($request->getMethod() === 'POST') {
            if (!$app['csrf.manager']->isTokenValid($request->get('csrf'))) {
                $app->abort(403);
            }
        }
    }, Application::EARLY_EVENT - 400);

    $app->before(function (Request $request) use ($app) {
        if ($request->getMethod() === 'POST') {
            $app['csrf.token'] = $app['csrf.manager']->refreshToken();
        } else {
            $app['csrf.token'] = $app['csrf.manager']->getToken();
        }
    });

    $app['security.firewalls'] = array (
        'general' => array (
            'anonymous' => true,

            'pattern' => '^/',

            'form' => array (
                'login_path' => '/',
                'check_path' => '/login_check',
                'default_target_path' => '/files/',
                'always_use_default_target_path' => false
            ),

            'logout' => array(
                'logout_path' => '/logout',
                'target_url' => '/',
            ),

            'switch_user' => array (
                'parameter' => '_switch_user',
                'role' => 'ROLE_ALLOWED_TO_SWITCH'
            ),

            'remember_me' => array (
                'key' => REMEMBER_ME_KEY,
                'always_remember_me' => true
            ),

            'users' => $app->share(function (Application $app) {
                return new UserProvider();
            })
        )
    );

    $app['security.role_hierarchy'] = array (
        'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
    );

    $app['security.access_rules'] = array(
        array('^/(ajax/)?administration', 'ROLE_ADMIN'),
        array('^/(ajax/)?sharings/[a-z0-9]{32}/files', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/.+', 'ROLE_USER')
    );

    $app['security.authentication.failure_handler.general'] = $app->share(function ($app) {
        return new AuthenticationHandler();
    });
}

?>
