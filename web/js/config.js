/*jslint plusplus: true, white: true */
/*global module */

(function (global, factory) {
    if (typeof module === 'object' && typeof module.exports === 'object') {
        module.exports = factory;
    } else {
        global.require = factory(global.athorrent);
    }
}(this, function (config) {
    'use strict';

    var suffix, require, vendors, scripts, name, i, scriptPrefix, vendorPrefix,
        build = config.build,
        debug = config.debug && !build;

    scriptPrefix = debug ? 'js/' : 'js/dist/';
    suffix = debug || build ? '' : '.min';

    vendorPrefix = 'vendor/';

    if (build) {
        vendorPrefix = '../' + vendorPrefix;
    }

    require = {
        paths: {}
    };

    if (!build) {
        require.baseUrl = '//' + config.staticHost;
    }

    if (debug) {
        require.deps = ['bootstrap', 'analytics', 'picturefill'];
        require.urlArgs = '_=' + (new Date()).getTime();
    }

    if (debug || build) {
        require.shim = {
            bootstrap: ['jquery'],

            base64_decode: {
                exports: 'base64_decode'
            },

            urldecode: {
                exports: 'urldecode'
            }
        };

        vendors = {
            bootstrap: 'bootstrap/dist/js/bootstrap',

            dropzone: 'dropzone/dist/dropzone-amd-module',

            jquery: 'jquery/dist/jquery',

            picturefill: 'picturefill/dist/picturefill',

            base64_decode: 'phpjs/requirejs/php/url/base64_decode',

            urldecode: 'phpjs/requirejs/php/url/urldecode'
        };

        for (name in vendors) {
            if (vendors.hasOwnProperty(name)) {
                require.paths[name] = vendorPrefix + vendors[name] + suffix;
            }
        }
    }

    if (!build) {
        if (!debug) {
            require.bundles = {
                athorrent: ['jquery', 'bootstrap', 'analytics', 'urldecode', 'picturefill', 'athorrent']
            };
        }

        scripts = ['athorrent', 'cache', 'files', 'sharings', 'torrents', 'users'];

        if (debug) {
            scripts.push('analytics');
        }
        
        for (i = scripts.length - 1; i >= 0; --i) {
            name = scripts[i];
            require.paths[name] = scriptPrefix + name + suffix;
        }
    }
    
    return require;
}));
