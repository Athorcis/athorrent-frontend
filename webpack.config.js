const path = require('path');
const dotenv = require('dotenv');
const Encore = require('@symfony/webpack-encore');
const StyleLintPlugin = require('stylelint-webpack-plugin');

dotenv.config({ path : __dirname + '/.env.local' });
dotenv.config({ path : __dirname + '/.env' });

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || process.env.APP_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath(process.env.ASSETS_ORIGIN + '/build/')
    // only needed for CDN's or sub-directory deploy
    .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Add 1 entry for each "page" of your app
     * (including one that's included on every page - e.g. "app")
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('athorrent', './assets/js/athorrent.js')
    .addEntry('files', './assets/js/files.js')
    .addEntry('media', './assets/js/media.js')
    .addEntry('search', './assets/js/search.js')
    .addEntry('sharings', './assets/js/sharings.js')
    .addEntry('torrents', './assets/js/torrents.js')
    .addEntry('users', './assets/js/users.js')

    .addStyleEntry('administration', './assets/css/administration.scss')
    .addStyleEntry('cache', './assets/css/cache.scss')
    .addStyleEntry('home', './assets/css/home.scss')
    .addStyleEntry('main', './assets/css/main.scss')

    .copyFiles({
        from: './assets/images',
        pattern: /\.(ico|png)$/
    })

    .addAliases({
        fonts: path.resolve(__dirname, 'assets/fonts')
    })

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // enables Sass/SCSS support
    .enableSassLoader()

    .enablePostCssLoader()

    .addPlugin(new StyleLintPlugin({ context: './assets/css' }))

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
