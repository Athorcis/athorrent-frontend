<?php

use Athorrent\Utils\Cache\Cache;
use Silex\Application;
use Silex\Provider\UrlGeneratorServiceProvider;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require APP . '/constants.php';
require APP . '/routes.php';
require APP . '/security.php';
require APP . '/translation.php';
require APP . '/twig.php';

function initializeApplication() {
    $app = new Application();

    $app['debug'] = DEBUG;

    $app['pdo'] = $app->share(function () {
        return new PDO('mysql:host=127.0.0.1;dbname=' . DB_NAME . ';charset=utf8', DB_USERNAME, DB_PASSWORD, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
    });

    $app['cache'] = Cache::getInstance();

    initializeTwig($app);

    initializeSecurity($app);

    $app->register(new UrlGeneratorServiceProvider());

    $app->before(function (Request $request) use ($app) {
        $user = $app['security']->getToken()->getUser();

        if ($user !== 'anon.') {
            if (!$app['security']->isGranted('ROLE_PREVIOUS_ADMIN')) {
                $user->updateConnectionTimestamp();
            }
        }
    });

    $app->error(function (\Exception $exception, $code) use($app) {
        if ($app['debug']) {
            return;
        }

        if ($exception instanceof NotFoundHttpException) {
            $error = 'error.pageNotFound';
        }

        if ($code === 500) {
            $error = 'error.errorUnknown';
        }

        if (isset($error)) {
            $error = $app['translator']->trans($error);
        } else {
            $error = $exception->getMessage();
        }

        return new Response($app['twig']->render('pages/error.html.twig', array('error' => $error, 'code' => $code)));
    });

    $app['dispatcher']->addListener(KernelEvents::RESPONSE, function () use($app) {
        $app['session']->save();
    }, Application::LATE_EVENT);

    initializeRoutes($app);
    initializeTranslation($app);

    return $app;
}

?>
