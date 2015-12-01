<?php

use Silex\Application;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

function initializeTranslation(Application $app) {
    $app['locales'] = array('en', 'fr');

    $app['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) {
        $request = $event->getRequest();

        if ($request->query->has('locale')) {
            $locale = $request->query->get('locale');
        } else if ($request->cookies->has('locale')) {
            $locale = $request->cookies->get('locale');
        } else {
            $locale = $request->getPreferredLanguage();
        }

        $request->setLocale($locale);
    }, Application::EARLY_EVENT);

//    $app->after(function (Request $request, Response $response) {
//        if ($request->query->has('locale')) {
//            $response->headers->setCookie(new Cookie('locale', $request->getLocale(), time() + 3600 * 24 * 364));
//        }
//    });

    $app->register(new Silex\Provider\TranslationServiceProvider(), array (
        'locale_fallbacks' => array('en'),
    ));

    $app['translator'] = $app->share($app->extend('translator', function(Translator $translator, $app) {
        $translator->addLoader('yaml', new YamlFileLoader());

        foreach ($app['locales'] as $locale) {
            $translator->addResource('yaml', LOCALES . '/' . $locale . '.yml', $locale);
        }

        return $translator;
    }));

    $app['translator.cache_dir'] = CACHE . DIRECTORY_SEPARATOR . 'translator';
}

?>
