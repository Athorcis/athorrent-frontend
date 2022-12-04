/* eslint-env node */
const path = require('path');
const Encore = require('@symfony/webpack-encore');
const StyleLintPlugin = require('stylelint-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    .setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('athorrent', './assets/js/athorrent.ts')
    .addEntry('files', './assets/js/files.ts')
    .addEntry('media', './assets/js/media.ts')
    .addEntry('search', './assets/js/search.ts')
    .addEntry('sharings', './assets/js/sharings.ts')
    .addEntry('torrents', './assets/js/torrents.ts')
    .addEntry('users', './assets/js/users.ts')

    .addStyleEntry('administration', './assets/css/administration.scss')
    .addStyleEntry('cache', './assets/css/cache.scss')
    .addStyleEntry('home', './assets/css/home.scss')
    .addStyleEntry('main', './assets/css/main.scss')

    .copyFiles({
        from: './assets/images',
        pattern: /\.(ico|png)$/
    })

    .addAliases({
        fonts: path.resolve(__dirname, 'assets/fonts'),
        jquery: 'jquery/dist/jquery.slim'
    })

    // enables the Symfony UX Stimulus bridge (used in assets/bootstrap.js)
    //.enableStimulusBridge('./assets/controllers.json')

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

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    .enablePostCssLoader()

    .addPlugin(new StyleLintPlugin({ context: './assets/css' }))

    // uncomment if you use TypeScript
    .enableTypeScriptLoader()

    .enableEslintPlugin({
        extensions: ['ts']
    })

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    .enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    .autoProvidejQuery()
;

module.exports = Encore.getWebpackConfig();
