<?php

namespace Athorrent\View;

use Pimple\Container;
use Silex\Application;
use Silex\Provider\TranslationServiceProvider as BaseTranslationServiceProvider;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

class TranslationServiceProvider extends BaseTranslationServiceProvider
{
    public function register(Container $app)
    {
        parent::register($app);

        $app['translator'] = $app->extend('translator', function (Translator $translator, Application $app) {
            return $this->extendTranslator($translator, $app);
        });
    }

    public function extendTranslator(Translator $translator, Application $app)
    {
        $translator->addLoader('yaml', new YamlFileLoader());

        foreach ($app['locales'] as $locale) {
            $translator->addResource('yaml', LOCALES_DIR . '/' . $locale . '.yml', $locale);
        }

        return $translator;
    }
}
