<?php

use Jenyak\I18nRouting\Provider\I18nRoutingServiceProvider;
use Silex\Application;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

function initializeTranslation(Application $app) {
    $app['locale'] = $app['default_locale'] = 'fr';
    $app['locales'] = array('fr', 'en');

    $app->register(new I18nRoutingServiceProvider());
    $app['i18n_routing.locales'] = $app['locales'];

    $app->register(new Silex\Provider\TranslationServiceProvider(), array (
        'locale_fallbacks' => array($app['default_locale']),
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
