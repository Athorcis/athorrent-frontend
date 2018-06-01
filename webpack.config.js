/* eslint-env node */

const { resolve } = require('path');
const yargs = require('yargs');
const webpack = require('webpack');
const CleanPlugin = require('clean-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const ManifestPlugin = require('webpack-manifest-plugin');
const SuppressChunksPlugin = require('suppress-chunks-webpack-plugin').default;
const StyleLintPlugin = require('stylelint-webpack-plugin');
const RuntimePublicPathPlugin = require('webpack-runtime-public-path-plugin');

function buildWebpackConfig(config) {
    let dev = yargs.argv.mode === 'development';

    let { entries } = config;

    let nonScriptEntries = {};

    for (let key in entries) {
        if (entries.hasOwnProperty(key) && !key.match(/^scripts\//)) {
            nonScriptEntries[key] = entries[key];
        }
    }

    let plugins = config.plugins || [];

    plugins.push(new MiniCssExtractPlugin({
        filename: dev ? '[name].css' : '[name].[contenthash].css',
        publicPath: '../'
    }));

    plugins.push(new SuppressChunksPlugin(Object.keys(nonScriptEntries), {
        filter: /\.js(\.map)?$/
    }));

    plugins.push(new ManifestPlugin({
        fileName: 'manifest.json',
        publicPath: '/'
    }));

    plugins.push(new CleanPlugin(['web'], { exclude: ['index.php', 'robots.txt']}));

    plugins.push(new StyleLintPlugin({ context: 'resources/stylesheets' }));

    return {
        entry: config.entries,

        output: {
            path: resolve(__dirname, 'web'),
            publicPath: '/',

            filename: dev ? '[name].js' : '[name].[chunkhash].js'
        },

        module: {
            rules: [{
                test: /\.js$/,
                include: resolve(__dirname, 'resources/scripts'),
                loader: 'babel-loader'
            }, {
                test: /\.css$/,
                use: [{
                    loader: MiniCssExtractPlugin.loader,
                    options: { publicPath: '../' }
                }, 'css-loader']
            }, {
                test: /\.scss$/,
                use: [
                    {
                        loader: MiniCssExtractPlugin.loader,
                        options: { publicPath: '../' }
                    },
                    'css-loader',
                    {
                        loader: 'postcss-loader',
                        options: { sourceMap: true }
                    },
                    'resolve-url-loader',
                    {
                        loader: 'sass-loader',
                        options: { sourceMap: true }
                    }
                ]
            }, {
                test: /\.ico$/,
                loader: 'file-loader',
                options: { name: dev ? '[name].[ext]' : '[name].[hash:8].[ext]' }
            }, {
                test: /\.(png|jpe?g|gif|svg)$/,
                loader: 'file-loader',
                options: { name: dev ? 'images/[name].[ext]' : 'images/[name].[hash:8].[ext]' }
            }, {
                test: /\.(woff2?|[ot]tf|eot)(\?v=[0-9]\.[0-9]\.[0-9])?$/,
                loader: 'file-loader',
                options: { name: dev ? 'fonts/[name].[ext]' : 'fonts/[name].[hash:8].[ext]' }
            }]
        },

        resolve: {
            modules: ['node_modules', 'resources/scripts'],

            alias: config.aliases
        },

        plugins,

        devtool: dev ? 'source-map' : false
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
