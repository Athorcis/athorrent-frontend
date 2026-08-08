import {
    HttpClient,
    Request,
    newHttpClient
} from 'typescript-http-client';
import {createAbortablePromise, stringifyParams, toQueryString} from './utils';

type RequestOptions = ConstructorParameters<typeof Request>[1];

export class Router {

    private readonly http: HttpClient;

    constructor(private routes: Routes, private routeParameters: Params) {
        this.http = newHttpClient();
    }

    getHttpClient(): HttpClient {
        return this.http;
    }

    sendRequest<R>(name: string, parameters: Params = {}): AbortablePromise<R> {
        const route = this.getRoute(name);
        const request = this.createRequestFromRoute(route, { ...parameters });

        return createAbortablePromise(
            this.http.executeForResponse<ApiResponse<R>>(request).then(response => {

                const {body}: {body: ApiResponse<R>|null} = response;

                if (body) {
                    if (body.status === 'success') {
                        return body.data;
                    }

                    if (body.code === 'LOGIN_REQUIRED') {
                        location.reload();
                    }

                    throw new Error(body.error ?? body.code ?? '');
                }

                throw new Error('response without a body')
            }),
            () => request.abort(),
        );
    }

    generateUrl(name: string, params: Params = {}): string {
        const route = this.getRoute(name);
        return this.prepareUrl(route, params) + toQueryString(params);
    }

    protected prepareUrl(route: Route, params: Params): string {

        params = {
            _locale: this.routeParameters['_locale'] as string,
            ...params
        };

        return route.pattern.replace(/{(_?[A-Za-z]+)}/g, (match, name) => {
            let result;

            if (params.hasOwnProperty(name)) {
                result = params[name] as string;
                delete params[name];
            } else {
                result = match;
            }

            return result;
        });
    }

    protected createRequest(method: string, url: string, params: Params): Request {
        const options: RequestOptions = {
            method,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (method === 'GET') {
            url += toQueryString(params);
        }
        else {
            options.body = stringifyParams(params);
            options.contentType = 'application/x-www-form-urlencoded';
        }

        return new Request(url, options);
    }

    protected createRequestFromRoute(route: Route, params: Params): Request {
        const url = this.prepareUrl(route, params);

        for (const key of Object.keys(params)) {
            if (key[0] === '_') {
                delete params[key];
            }
        }

        return this.createRequest(route.method, url, params);
    }

    protected getRoute(name: string): Route {

        let prefixId = this.routeParameters['_prefixId'] as string;

        if (!this.routes.hasOwnProperty(name)) {
            throw new Error(`cannot find route with name: ${name}`);
        }

        const routeGroup = this.routes[name]!;

        if (!routeGroup.hasOwnProperty(prefixId)) {
            prefixId = Object.keys(routeGroup)[0]!;
        }

        return routeGroup[prefixId]!;
    }
}
