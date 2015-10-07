<?php

use Silex\Application;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

function initializeTranslation(Application $app) {
    $app['dispatcher']->addListener(KernelEvents::REQUEST, function (GetResponseEvent $event) use($app) {
        $request = $event->getRequest();
        $locale = $request->getPreferredLanguage();

        $request->setLocale($locale);
    }, Application::EARLY_EVENT);

    $app->register(new Silex\Provider\TranslationServiceProvider(), array (
        'locale_fallbacks' => array('fr'),
    ));

    $app['translator'] = $app->share($app->extend('translator', function(Translator $translator, $app) {
        $translator->addLoader('yaml', new YamlFileLoader());

        $translator->addResource('yaml', LOCALES . '/fr.yml', 'fr');
        $translator->addResource('yaml', LOCALES . '/en.yml', 'en');

        return $translator;
    }));

    $app['translator.cache_dir'] = CACHE . DIRECTORY_SEPARATOR . 'translator';
}

?>
