/*jslint browser: true, plusplus: true, white: true */
/*global define */

define(['jquery', 'urldecode'], function (jQuery, urldecode) {
    'use strict';

    var athorrent = window.athorrent || {};

    jQuery.extend(athorrent, {
        trans: function (key) {
            if (athorrent.locale.hasOwnProperty(key)) {
                return athorrent.locale[key];
            }

            return key;
        },

        ajax: function (method, pattern, parameters, success, options) {
            var url;

            if (typeof parameters === 'function') {
                options = success;
                success = parameters;
                parameters = {};
            }

            parameters = jQuery.extend({}, parameters);

            url = pattern.replace(/\{([a-z]+)\}/g, function (match, p1) {
                var result;

                if (parameters.hasOwnProperty(p1)) {
                    result = parameters[p1];
                    delete parameters[p1];
                } else {
                    result = match;
                }

                return result;
            });

            options = jQuery.extend({
                type: method,
                data: parameters,
                cache: false,
                dataType: 'json',
                success: function (result) {
                    if (result.status === 'success') {
                        if (typeof success === 'function') {
                            success(result.data);
                        }
                    }
                }
            }, options);

            return jQuery.ajax(url, options);
        },

        ajaxify: function (action, requests) {
            return function (parameters, success, options, actionPrefix) {
                var request, key, ownKey, method, pattern;

                if (!actionPrefix) {
                    actionPrefix = athorrent.actionPrefix;
                }

                if (requests.hasOwnProperty(actionPrefix)) {
                    request = requests[actionPrefix];
                } else {
                    for (key in requests) {
                        if (requests.hasOwnProperty(key)) {
                            request = requests[key];
                            actionPrefix = key;
                            break;
                        }
                    }
                }

                if (request) {
                    method = request[0];
                    pattern = request[1];
                }

                if (actionPrefix === athorrent.actionPrefix) {
                    if (action === athorrent.action) {
                        parameters = jQuery.extend({}, athorrent.routeParameters, athorrent.queryParameters, parameters);
                    } else {
                        parameters = jQuery.extend({}, athorrent.routeParameters, parameters);
                    }
                }

                return athorrent.ajax(method, pattern, parameters, success, options);
            };
        },

        buildAjax: function () {
            var alias,
                ajax = this.ajax,
                routes = this.routes;

            for (alias in routes) {
                if (routes.hasOwnProperty(alias)) {
                    ajax[alias] = this.ajaxify(alias, routes[alias]);
                }
            }
        },

        initializeParameters: function () {
            var i, length, rawParameter, key, value,
                rawParameters = location.search.substr(1).split('&'),
                parameters = {};

            if (location.search.length > 1) {
                for (i = 0, length = rawParameters.length; i < length; ++i) {
                    rawParameter = rawParameters[i].split('=');

                    key = rawParameter[0];
                    value = urldecode(rawParameter[1]);

                    parameters[key] = value;
                }
            }

            this.queryParameters = parameters;
            this.routeParameters = window.athorrent.routeParameters || {};

        },

        initialize: function () {
            this.buildAjax();
            this.initializeParameters();
        },

        getItem: function (type, element, selector) {
            var $item,
                $element = jQuery(element);

            selector = selector || '.' + type;

            if ($element.filter(selector).length) {
                $item = $element;
            } else {
                $item = $element.closest(selector);

                if ($item.length === 0) {
                    $item = null;
                }
            }

            return $item;
        },

        getItemId: function (type, element, selector) {
            var id,
                $item = this.getItem(type, element, selector);

            if ($item) {
                id = $item.attr('id').replace(type + '-', '');
            } else {
                id = null;
            }

            return id;
        },

        getItemAttr: function (type, element, name, selector) {
            var attr,
                $item = this.getItem(type, element, selector);

            if ($item) {
                attr = $item.children('.' + type + '-' + name).text();
            } else {
                attr = null;
            }

            return attr;
        },

        showModal: function (title, content) {
            var $modal = jQuery(athorrent.templates.modal);

            $modal.find('.modal-title').text(title);
            $modal.find('.modal-body').html(content);

            $modal.appendTo('body');
            $modal.modal('show');
        }
    });

    athorrent.initialize();

    return athorrent;
});
