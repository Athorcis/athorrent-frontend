/* eslint-env browser, amd */

define(['jquery', 'urldecode', 'bootstrap-sass', 'picturefill'], function ($, urldecode) {
    'use strict';

    var athorrent = window.athorrent || {};

    $.extend(athorrent, {
        trans: function (key) {
            if (athorrent.locale.hasOwnProperty(key)) {
                return athorrent.locale[key];
            }

            return key;
        },

        ajax: function (method, pattern, parameters, success, options) {
            var url, jqXhr;

            if (typeof parameters === 'function') {
                options = success;
                success = parameters;
                parameters = {};
            }

            parameters = $.extend({}, parameters);

            if (method === 'POST') {
                parameters.csrf = athorrent.csrf;
            }

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

            options = $.extend({
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

            jqXhr = $.ajax(url, options);

            if (method === 'POST') {
                jqXhr.done(function (data) {
                    athorrent.csrf = data.csrf;
                });
            }

            return jqXhr;
        },

        ajaxify: function (action, requests) {
            return function (parameters, success, options, actionPrefix) {
                var request, key, method, pattern;

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
                        parameters = $.extend({}, athorrent.routeParameters, athorrent.queryParameters, parameters);
                    } else {
                        parameters = $.extend({}, athorrent.routeParameters, parameters);
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

            $('form[method="post"]').submit(function (event) {
                $(event.target).append('<input type="hidden" name="csrf" value="' + athorrent.csrf + '" />');
            });

            $('[data-ajax-action]').click(function (event) {
                var $btn = $(event.target),
                    action = $btn.data('ajax-action'),
                    spinner = Boolean($btn.data('ajax-spinner'));

                if (spinner) {
                    $btn.append('<span class="fa fa-refresh fa-spin"></span>');
                }

                athorrent.ajax[action]().always(function () {
                    if (spinner) {
                        $btn.children('.fa-spin').remove();
                    }
                });
            });
        },

        getItem: function (type, element, selector) {
            var $item,
                $element = $(element);

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
            var $modal = $(athorrent.templates.modal);

            $modal.find('.modal-title').text(title);
            $modal.find('.modal-body').html(content);

            $modal.appendTo('body');
            $modal.modal('show');
        }
    });

    athorrent.initialize();

    return athorrent;
});