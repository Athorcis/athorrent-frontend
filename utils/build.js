/* eslint-env node */
/* eslint no-console: "off", no-sync: "off" */

'use strict';

var execSync = require('child_process').execSync,
    requirejs = require('requirejs'),
    uglifyJs = require('uglify-js'),
    path = require('path'),
    fs = require('fs');

var WEB = path.join(__dirname, '../web');

var rjs = {
    optimize: function (config, callback) {
        requirejs.optimize(config, callback);
    },

    convert: function (input, output) {
        var cmd = '"' + process.execPath + '" node_modules/requirejs/bin/r.js -convert "' + input + '" "' + output + '"';

        execSync(cmd);
    }
};

var extend = (function () {
    var slice = Array.prototype.slice;

    return function (target) {
        var i, length, object, key,
            objects = slice.call(arguments, 1);

        for (i = 0, length = objects.length; i < length; ++i) {
            object = objects[i];

            for (key in object) {
                if (object.hasOwnProperty(key)) {
                    target[key] = object[key];
                }
            }
        }

        return target;
    };
}());

var build = (function () {
    var baseConfig, mainConfig;

    baseConfig = {
        baseUrl: path.join(WEB, 'js'),
        findNestedDependencies: false,
        preserveLicenseComments: false
    };

    // eslint-disable-next-line global-require
    mainConfig = require(path.join(WEB, 'js/config'))({ build: true });

    return function (name, config) {
        if (name instanceof Array) {
            var i, length;

            for (i = 0, length = name.length; i < length; ++i) {
                build(name[i], config);
            }
        } else {
            rjs.optimize(extend({
                name: name,
                out: path.join(WEB, 'js/dist/' + name + '.min.js')
            }, baseConfig, mainConfig, config), function () {
                console.log('module ' + name + ' builded');
            });
        }
    };
}());

var uglify = (function () {
    return function (relativePath, out) {
        var absolutePath = path.join(WEB, relativePath),
            result = uglifyJs.minify([absolutePath]);

        if (out) {
            out = path.join(WEB, out);
        } else {
            out = absolutePath.replace(/\.js$/, '.min.js');
        }

        fs.writeFile(out, result.code, function () {
            console.log('file ' + relativePath + ' uglified');
        });
    };
}());

rjs.convert('web/vendor/phpjs/src', 'web/vendor/phpjs/requirejs');

build('athorrent', {
    include: ['bootstrap']
});

build(['files', 'search', 'torrents', 'sharings', 'users'], {
    exclude: ['athorrent']
});

uglify('js/config.js', 'js/dist/config.min.js');
uglify('vendor/requirejs/require.js');
