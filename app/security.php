<?php

use Athorrent\Utils\UserProvider;
use Silex\Application;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\RememberMeServiceProvider;

function initializeSecurity(Application $app) {
    $app->register(new SessionServiceProvider());
    $app->register(new SecurityServiceProvider());
    $app->register(new RememberMeServiceProvider());

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
        array('^/administration', 'ROLE_ADMIN'),
        array('^/sharings/[a-z0-9]{32}/files', 'IS_AUTHENTICATED_ANONYMOUSLY'),
        array('^/.+', 'ROLE_USER')
    );
}

?>
