import $ from 'jquery';
import parse_str from 'locutus/php/strings/parse_str';
import {SecurityManager} from './security-manager';
import jqXHR = JQuery.jqXHR;
import PlainObject = JQuery.PlainObject;

export interface Params {
    [key: string]: string|string[];
}

export interface HttpRequestOptions {
    method?: string;
    url: string;
    headers?: Params;
    cache?: boolean;
    data?: PlainObject;
}

interface HttpResponse {

}

export class HttpRequest {

    private jqXhr: jqXHR;

    constructor(private options: HttpRequestOptions) {

    }

    getMethod(): string {
        return this.options.method;
    }

    send(): Promise<HttpResponse> {
        this.jqXhr = $.ajax(this.options.url, {
            type: this.options.method,
            ...this.options
        });

        return new Promise((resolve, reject) => {
            this.jqXhr.then(resolve, reject);
        });
    }

    abort() {
        this.jqXhr.abort();
    }
}

export class CreateRequest extends HookType<HttpRequest> {}
export class ReceiveResponse extends HookType<Promise<HttpResponse>> {}

export class HttpClient extends HooksProvider {

    protected createRequest(options: HttpRequestOptions): HttpRequest {

        options = { method: 'GET', headers: {}, ...options };
        options = this.fireHook(CreateRequest, options) as HttpRequestOptions;
        return new HttpRequest(options);
    }

    sendRequest(options: HttpRequestOptions): Promise<HttpResponse> {
        const request = this.createRequest(options);
        return this.fireHook(ReceiveResponse, request.send()) as Promise<HttpResponse>;
    }
}

type Routes = any;

export class Router {

    private queryParams;


    constructor(private http: HttpClient, private routes: Routes, private routeParameters: Params, private action: string, private securityManager: SecurityManager) {
        this.queryParams = Router.parseQueryParameters();
    }

    init() {

        $('[data-ajax-action]').on('click',(event) => {
            let $btn = $(event.target),
                action = $btn.data('ajax-action'),
                spinner = Boolean($btn.data('ajax-spinner'));

            if (spinner) {
                $btn.append('<span class="fa fa-sync-alt fa-spin"></span>');
            }

            this.sendRequest(action).always(() => {
                if (spinner) {
                    $btn.children('.fa-spin').remove();
                }
            });
        });
    }

    ajax(method: string, pattern: string, parameters: Params, success: (data: any) => void, options: Object) {
        if (typeof parameters === 'function') {
            options = success;
            success = parameters;
            parameters = {};
        }

        parameters = Object.assign({}, parameters);

        if (['GET', 'HEAD', 'OPTIONS', 'TRACE'].indexOf(method) < 0) {
            parameters.csrfToken = this.securityManager.getCsrfToken();
        }

        let url = pattern.replace(/{(_?[A-Za-z]+)}/g, (match, p1) => {
            let result;

            if (parameters.hasOwnProperty(p1)) {
                result = parameters[p1];
                delete parameters[p1];
            } else {
                result = match;
            }

            return result;
        });

        for (const key of Object.keys(parameters)) {
            if (key[0] === '_') {
                delete parameters[key];
            }
        }

        // options = Object.assign({
        //     type: method,
        //     data: parameters,
        //     cache: false,
        //     dataType: 'json',
        //     success: (result: any) => {
        //         if (result.status === 'success') {
        //             if (typeof success === 'function') {
        //                 success(result.data);
        //             }
        //         }
        //     }
        // }, options);

        const request = this.http.sendRequest(method, url, {
            data: parameters,
            cache: false
        });
        // let jqXhr = $.ajax(url, options);

        if (['GET', 'HEAD', 'OPTIONS', 'TRACE'].indexOf(method) < 0) {

            jqXhr.done((data) => {
                this.securityManager.setCsrfToken(data.csrfToken);
            });
        }

        return jqXhr;
    }

    sendRequest(action: string , parameters: Params = {}): jqXHR {
        return this.ajaxify(action, this.routes[action])(parameters, $.noop, {}, null).then(response => {
            return response.data;
        });
    }

    ajaxify(action: string, requests: any) {
        return (parameters: Params, success: (data: any) => void, options: Object, actionPrefix: string): jqXHR => {
            let request, method, pattern;

            if (!actionPrefix) {
                actionPrefix = this.routeParameters._prefixId as string;
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

            if (actionPrefix === this.routeParameters._prefixId) {
                if (action === this.action) {
                    parameters = Object.assign({}, this.routeParameters, this.queryParams, parameters);
                } else {
                    parameters = Object.assign({}, this.routeParameters, parameters);
                }
            }

            return this.ajax(method, pattern, parameters, success, options);
        };
    }

    static parseQueryParameters()
    {
        const params = {}
        parse_str(location.search.substr(1), params);

        return params;
    }
}
