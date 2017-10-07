
const resolve = require('path').resolve;
const webpack = require('webpack');
const ChunkHashPlugin = require('webpack-chunk-hash');
const CleanPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const SuppressChunksPlugin = require('suppress-chunks-webpack-plugin').default;
const StyleLintPlugin = require('stylelint-webpack-plugin');
const RuntimePublicPathPlugin = require('webpack-runtime-public-path-plugin');

function buildWebpackConfig(config) {
    let extractSass = new ExtractTextPlugin('[name].[contenthash].css');

    let production = process.env.NODE_ENV === 'production';
    let nonScriptEntries = {};

    for (var key in config.entries) {
        if (!key.match(/^scripts\//)) {
            nonScriptEntries[key] = config.entries[key];
        }
    }

    let plugins = config.plugins || [];

    plugins.push(extractSass);

    plugins.push(new SuppressChunksPlugin(Object.keys(nonScriptEntries), { filter: /\.js(\.map)?$/ }));

    plugins.push(new ManifestPlugin({
        fileName: 'manifest.json',
        publicPath: '/'
    }));

    plugins.push(new ChunkHashPlugin());

    if (production) {
        plugins.push(new webpack.HashedModuleIdsPlugin());
    } else {
        plugins.push(new webpack.NamedModulesPlugin());
    }

    plugins.push(new CleanPlugin(['web'], { exclude: ['index.php', 'robots.txt']}));

    plugins.push(new webpack.optimize.CommonsChunkPlugin({ name: 'scripts/runtime' }));

    plugins.push(new StyleLintPlugin({ context: 'resources/stylesheets' }));

    return {
        entry: config.entries,

        output: {
            path: resolve(__dirname, 'web'),
            publicPath: '/',

            filename: '[name].[chunkhash].js',
            chunkFilename: 'scripts/[name].[chunkhash].js'
        },

        module: {
            rules: [{
                test: /\.js$/,
                include: resolve(__dirname, 'resources/scripts'),
                loader: 'eslint-loader'
            }, {
                test: /\.css$/,
                use: extractSass.extract([
                    'css-loader'
                ])
            }, {
                test: /\.scss$/,
                use: extractSass.extract([
                    'css-loader',
                    'resolve-url-loader', {
                        loader: 'postcss-loader',
                        options: { sourceMap: true }
                    }, {
                        loader: 'sass-loader',
                        options: { sourceMap: true }
                    }
                ])
            }, {
                test: /\.ico$/,
                loader: 'file-loader',
                options: { name: '[name].[hash:8].[ext]' }
            }, {
                test: /\.(png|jpe?g|gif|svg)$/,
                loader: 'file-loader',
                options: { name: 'images/[name].[hash:8].[ext]' }
            }, {
                test: /\.(woff2?|[ot]tf|eot)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                loader: 'file-loader',
                options: { name: 'fonts/[name].[hash:8].[ext]' }
            }]
        },

        resolve: {
            modules: ['node_modules', 'resources/scripts'],

            alias: config.aliases
        },

        plugins: plugins,

        devtool: production ? false : 'source-map'
    };
}

module.exports = buildWebpackConfig({
    entries: {
        'favicon.ico': './resources/images/favicon.ico',
        'images/logo-narrow': './resources/images/logo-narrow.png',
        'images/logo-wide': './resources/images/logo-wide.png',

        'scripts/athorrent': 'athorrent',
        'scripts/files': 'files',
        'scripts/html5shiv': 'html5shiv',
        'scripts/media': 'media',
        'scripts/search': 'search',
        'scripts/sharings': 'sharings',
        'scripts/torrents': 'torrents',
        'scripts/users': 'users',

        'stylesheets/administration': './resources/stylesheets/administration.scss',
        'stylesheets/cache': './resources/stylesheets/cache.scss',
        'stylesheets/files': './resources/stylesheets/files.scss',
        'stylesheets/home': './resources/stylesheets/home.scss',
        'stylesheets/main': './resources/stylesheets/main.scss',
        'stylesheets/media': './resources/stylesheets/media.scss',
        'stylesheets/media-element-player': 'mediaelement/build/mediaelementplayer.css',
        'stylesheets/search': './resources/stylesheets/search.scss',
        'stylesheets/torrents': './resources/stylesheets/torrents.scss',
        'stylesheets/users': './resources/stylesheets/users.scss'
    },

    aliases: {
        base64: 'js-base64/base64',
        urldecode: 'locutus/php/url/urldecode'
    },

    plugins: [
        new RuntimePublicPathPlugin({
            runtimePublicPath: '"//" + athorrent.staticHost + "/"'
        }),

        new webpack.ProvidePlugin({
            $: 'jquery',
            jQuery: 'jquery'
        })
    ]
});
