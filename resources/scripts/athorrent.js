/* eslint-env browser */

import $ from 'jquery';
import bs from 'bootstrap-sass';
import pf from 'picturefill';
import urldecode from 'urldecode';

let athorrent = window.athorrent || {};

Object.assign(athorrent, {
    trans(key) {
        if (athorrent.strings.hasOwnProperty(key)) {
            return athorrent.strings[key];
        }

        return key;
    },

    ajax(method, pattern, parameters, success, options) {
        if (typeof parameters === 'function') {
            options = success;
            success = parameters;
            parameters = {};
        }

        parameters = Object.assign({}, parameters);

        if (method === 'POST') {
            parameters.csrfToken = athorrent.csrfToken;
        }

        let url = pattern.replace(/{(_?[a-z]+)}/g, (match, p1) => {
            let result;

            if (parameters.hasOwnProperty(p1)) {
                result = parameters[p1];
                delete parameters[p1];
            } else {
                result = match;
            }

            return result;
        });

        for (let key in parameters) {
            if (key[0] === '_') {
                delete parameters[key];
            }
        }

        options = Object.assign({
            type: method,
            data: parameters,
            cache: false,
            dataType: 'json',
            success: (result) => {
                if (result.status === 'success') {
                    if (typeof success === 'function') {
                        success(result.data);
                    }
                }
            }
        }, options);

        let jqXhr = $.ajax(url, options);

        if (method === 'POST') {
            jqXhr.done((data) => {
                athorrent.csrfToken = data.csrfToken;
            });
        }

        return jqXhr;
    },

    ajaxify(action, requests) {
        return function (parameters, success, options, actionPrefix) {
            let request, method, pattern;

            if (!actionPrefix) {
                actionPrefix = athorrent.routeParameters._prefixId;
            }

            if (requests.hasOwnProperty(actionPrefix)) {
                request = requests[actionPrefix];
            } else {
                for (let key in requests) {
                    if (requests.hasOwnProperty(key)) {
                        request = requests[key];
                        actionPrefix = key;
                        break;
                    }
                }
            }

            if (request) {
                [method, pattern] = request;
            }

            if (actionPrefix === athorrent.routeParameters._prefixId) {
                if (action === athorrent.action) {
                    parameters = Object.assign({}, athorrent.routeParameters, athorrent.queryParameters, parameters);
                } else {
                    parameters = Object.assign({}, athorrent.routeParameters, parameters);
                }
            }

            return athorrent.ajax(method, pattern, parameters, success, options);
        };
    },

    buildAjax() {
        let { ajax, routes } = this;

        for (let alias in routes) {
            if (routes.hasOwnProperty(alias)) {
                ajax[alias] = this.ajaxify(alias, routes[alias]);
            }
        }
    },

    initializeParameters() {
        let rawParameters = location.search.substr(1).split('&'),
            parameters = {};

        if (location.search.length > 1) {
            for (let i = 0, { length } = rawParameters; i < length; ++i) {
                let rawParameter = rawParameters[i].split('=');

                let [key] = rawParameter;
                let value = urldecode(rawParameter[1]);

                parameters[key] = value;
            }
        }

        this.queryParameters = parameters;
        this.routeParameters = window.athorrent.routeParameters || {};
    },

    initialize() {
        this.buildAjax();
        this.initializeParameters();

        $('form[method="post"]').submit((event) => {
            $(event.target).append(`<input type="hidden" name="csrfToken" value="${athorrent.csrfToken}" />`);
        });

        $('[data-ajax-action]').click((event) => {
            let $btn = $(event.target),
                action = $btn.data('ajax-action'),
                spinner = Boolean($btn.data('ajax-spinner'));

            if (spinner) {
                $btn.append('<span class="fa fa-refresh fa-spin"></span>');
            }

            athorrent.ajax[action]().always(() => {
                if (spinner) {
                    $btn.children('.fa-spin').remove();
                }
            });
        });
    },

    getItem(type, element, selector) {
        let $item,
            $element = $(element);

        selector = selector || `.${type}`;

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

    getItemId(type, element, selector) {
        let id,
            $item = this.getItem(type, element, selector);

        if ($item) {
            id = $item.attr('id').replace(`${type}-`, '');
        } else {
            id = null;
        }

        return id;
    },

    getItemAttr(type, element, name, selector) {
        let attr,
            $item = this.getItem(type, element, selector);

        if ($item) {
            attr = $item.children(`.${type}-${name}`).text();
        } else {
            attr = null;
        }

        return attr;
    },

    showModal(title, content) {
        let $modal = $(athorrent.templates.modal);

        $modal.find('.modal-title').text(title);
        $modal.find('.modal-body').html(content);

        $modal.appendTo('body');
        $modal.modal('show');
    }
});

athorrent.initialize();

export { athorrent as default };
