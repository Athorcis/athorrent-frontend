
const resolve = require('path').resolve;
const webpack = require('webpack');
const CleanWebpackPlugin = require('clean-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const SuppressChunksPlugin = require('suppress-chunks-webpack-plugin').default;
const StyleLintPlugin = require('stylelint-webpack-plugin');

function buildWebpackConfig(config) {
    let extractSass = new ExtractTextPlugin('[name].[contenthash].css');

    let production = process.env.production || false;
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

    plugins.push(new webpack.HashedModuleIdsPlugin());

    plugins.push(new CleanWebpackPlugin(['web'], { exclude: ['index.php', 'robots.txt']}));

    plugins.push(new webpack.optimize.CommonsChunkPlugin({ name: 'scripts/runtime' }));

    if (production) {
        plugins.push(new webpack.optimize.UglifyJsPlugin({ sourceMap: true }));
    }

    plugins.push(new StyleLintPlugin({ context: 'assets/styles' }));

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
                exclude: /node_modules/,
                loader: 'eslint-loader'
            },{
                test: /\.scss$/,
                use: extractSass.extract([{
                    loader: 'css-loader',
                    options: { minimize: production, sourceMap: !production }
                }, 'resolve-url-loader', {
                    loader: 'postcss-loader',
                    options: { sourceMap: !production }
                }, {
                    loader: 'sass-loader',
                    options: { sourceMap: true }
                }])
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
            modules: ['node_modules', 'assets/scripts'],

            alias: config.aliases
        },

        plugins: plugins,

        devtool: production ? false : 'source-map'
    };
}

module.exports = buildWebpackConfig({
    entries: {
        'favicon.ico': './assets/images/favicon.ico',
        'images/logo-narrow': './assets/images/logo-narrow.png',
        'images/logo-wide': './assets/images/logo-wide.png',

        'scripts/athorrent': [
            'athorrent',
            'jquery',
            'bootstrap-sass',
            'urldecode',
            'picturefill'
        ],
        'scripts/files': 'files',
        'scripts/html5shiv': 'html5shiv',
        'scripts/search': 'search',
        'scripts/sharings': 'sharings',
        'scripts/torrents': 'torrents',
        'scripts/users': 'users',

        'styles/administration': './assets/styles/administration.scss',
        'styles/cache': './assets/styles/cache.scss',
        'styles/files': './assets/styles/files.scss',
        'styles/home': './assets/styles/home.scss',
        'styles/main': './assets/styles/main.scss',
        'styles/media': './assets/styles/media.scss',
        'styles/search': './assets/styles/search.scss',
        'styles/torrents': './assets/styles/torrents.scss',
        'styles/users': './assets/styles/users.scss',
        'styles/videos': './assets/styles/videos.scss'
    },

    aliases: {
        base64: 'js-base64/base64',
        urldecode: 'locutus/php/url/urldecode'
    },

    plugins: [
        new webpack.ProvidePlugin({
          $: 'jquery',
          jQuery: 'jquery'
        })
    ]
});
