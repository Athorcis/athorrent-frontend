<?php

namespace Athorrent\Service;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Symfony\Component\Translation\Translator;

class TranslationServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['locale'] = $app['default_locale'] = 'fr';
        $app['locales'] = ['fr', 'en'];

        $app->register(new \Silex\Provider\TranslationServiceProvider(), [
            'locale_fallbacks' => [$app['default_locale']],
        ]);

        $app->extend('translator', function (Translator $translator) use ($app) {
            return $this->extendTranslator($translator, $app);
        });

        $app['translator.cache_dir'] = CACHE_DIR . DIRECTORY_SEPARATOR . 'translator';
    }

    public function extendTranslator(Translator $translator, Application $app)
    {
        $translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());

        foreach ($app['locales'] as $locale) {
            $translator->addResource('yaml', LOCALES_DIR . DIRECTORY_SEPARATOR . $locale . '.yml', $locale);
        }

        return $translator;
    }
}
