<?php

namespace Athorrent\Service;

use Jenyak\I18nRouting\Provider\I18nRoutingServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\Translation\Translator;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['locale'] = $app['default_locale'] = 'fr';
        $app['locales'] = ['fr', 'en'];

        $app->register(new I18nRoutingServiceProvider());
        $app['i18n_routing.locales'] = $app['locales'];

        $app->register(new \Silex\Provider\TranslationServiceProvider(), [
            'locale_fallbacks' => [$app['default_locale']],
        ]);

        $app['translator'] = $app->share($app->extend('translator', function (Translator $translator) use ($app) {
            return $this->extendTranslator($translator, $app);
        }));

        $app['translator.cache_dir'] = CACHE . DIRECTORY_SEPARATOR . 'translator';
    }
    
    public function boot(Application $app)
    {
    }
    
    public function extendTranslator(Translator $translator, Application $app)
    {
        $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

        foreach ($app['locales'] as $locale) {
            $translator->addResource('yaml', LOCALES . '/' . $locale . '.yml', $locale);
        }

        return $translator;
    }
}
