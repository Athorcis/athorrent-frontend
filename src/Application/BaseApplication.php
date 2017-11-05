<?php

namespace Athorrent\Application;

use Athorrent\Cache\CacheCleaner;
use Doctrine\DBAL\Types\Type;
use phpFastCache\Helper\Psr16Adapter;

use Silex\Application;

class BaseApplication extends Application
{
    public function __construct()
    {
        parent::__construct(['debug' => DEBUG]);

        $this->initializeCache();
        $this->initializeDoctrine();
        $this->initializeTranslations();
    }

    protected function initializeCache()
    {
        $this['cache'] = function () {
            return new Psr16Adapter(CACHE_DRIVER, ['ignoreSymfonyNotice' => true]);
        };

        $this['cache.cleaner'] = function (Application $app) {
            return new CacheCleaner($app['cache'], CACHE_DIR);
        };
    }

    protected function initializeDoctrine()
    {
        $this->register(new \Athorrent\Database\DoctrineServiceProvider(), [
            'db.options' => [
                'host' => '127.0.0.1',
                'user' => DB_USERNAME,
                'password' => DB_PASSWORD,
                'dbname' => DB_NAME,
                'charset' => 'utf8'
            ]
        ]);

        $this['orm.repo.user'] = function (Application $app) {
            Type::addType('UserRole', 'Athorrent\Database\Type\UserRole');
            return $app['orm.em']->getRepository('Athorrent\\Database\\Entity\\User');
        };

        $this['orm.repo.sharing'] = function (Application $app) {
            return $app['orm.em']->getRepository('Athorrent\\Database\\Entity\\Sharing');
        };
    }

    protected function initializeTranslations()
    {
        $this['locale'] = $this['default_locale'] = 'fr';
        $this['locales'] = ['fr', 'en'];

        $this->register(new \Athorrent\View\TranslationServiceProvider(), [
            'locale_fallbacks' => [$this['default_locale']],
            'translator.cache_dir' => CACHE_DIR . '/translator'
        ]);
    }
}
