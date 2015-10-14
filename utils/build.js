/*global require, __dirname */

var WEB = __dirname + '/../web';

var rjs = (function () {
    var requirejs = require('requirejs');

    return function (config, callback) {
        requirejs.optimize(config, function () {
            if (typeof callback === 'function') {
                callback();
            }
        });
    };
}());

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
        baseUrl: WEB + '/js',
        findNestedDependencies: false,
        preserveLicenseComments: false
    };

    mainConfig = require(WEB + '/js/config')({
        build: true
    });

    return function (name, config) {
        if (name instanceof Array) {
            var i, length;

            for (i = 0, length = name.length; i < length; ++i) {
                build(name[i], config);
            }
        } else {
            rjs(extend({
                name: name,
                out: WEB + '/js/dist/' + name + '.min.js'
            }, baseConfig, mainConfig, config), function () {
                console.log('module ' + name + ' builded');
            });
        }
    };
}());

var uglify = (function () {
    var UglifyJS = require('uglify-js'),
        fs = require('fs');

    return function (relativePath, out) {
        var absolutePath = WEB + '/' + relativePath,
            result = UglifyJS.minify([absolutePath]);

        if (out) {
            out = WEB + '/' + out;
        } else {
            out = absolutePath.replace(/\.js$/, '.min.js');
        }

        fs.writeFile(out, result.code, function () {
            console.log('file ' + relativePath + ' uglified');
        });
    };
}());

build('athorrent', {
    include: ['bootstrap', 'analytics']
});

build(['files', 'torrents', 'sharings', 'users'], {
    exclude: ['athorrent']
});

uglify('js/config.js', 'js/dist/config.min.js');
uglify('vendor/requirejs/require.js');
