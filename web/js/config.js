/*jslint plusplus: true, white: true */
/*global athorrent */

var require = (function (athorrent) {
    'use strict';

    var suffix, require, vendors, scripts, name, i, length,
        debug = athorrent.debug;

    suffix = debug ? '' : '.min';

    require = {
        baseUrl: '//' + athorrent.staticHost,

        deps: ['bootstrap', 'analytics'],

        paths: {},

        shim: {
            bootstrap: ['jquery'],

            base64_decode: {
                exports: 'base64_decode'
            },

            urldecode: {
                exports: 'urldecode'
            }
        }
    };

    vendors = {
        bootstrap: 'bootstrap/dist/js/bootstrap',

        dropzone: 'dropzone/dist/dropzone-amd-module',

        jquery: 'jquery/dist/jquery',

        base64_decode: 'phpjs/functions/url/base64_decode',

        urldecode: 'phpjs/functions/url/urldecode'
    };

    if (!athorrent.debug) {
        vendors.dropzone = 'dropzone/dist/min/dropzone-amd-module';
    }

    for (name in vendors) {
        if (vendors.hasOwnProperty(name)) {
            require.paths[name] = 'vendor/' + vendors[name] + suffix;
        }
    }

    scripts = ['analytics', 'athorrent', 'files', 'sharings', 'torrents', 'users'];

    for (i = scripts.length - 1; i >= 0; --i) {
        name = scripts[i];
        require.paths[name] = 'js/' + name + suffix;
    }

    return require;
}(athorrent));
