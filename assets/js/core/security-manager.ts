import $ from 'jquery';
import {httpclient} from "typescript-http-client";
import HttpClient = httpclient.HttpClient;
import Request = httpclient.Request;
import Response = httpclient.Response;
import FilterChain = httpclient.FilterChain;

export class SecurityManager {

    constructor(private csrfToken: string, private http: HttpClient) {
    }

    init() {
        $('form[method="post"]').submit(event => {
            $(event.target).append(`<input type="hidden" name="csrfToken" value="${this.csrfToken}" />`);
        });

        this.http.addFilter({
            doFilter: (request: Request, filterChain: FilterChain<any>): Promise<Response<any>> => {
                request.addHeader('X-Csrf-Token', this.csrfToken);

                const response$ = filterChain.doFilter(request);

                response$.then(response => {
                    this.csrfToken = response.body.csrfToken;
                });

                return response$;
            }

        }, "addCsrfToken", {
            enabled(request: Request) {
                return !['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(request.method);
            }
        });
    }

    getCsrfToken(): string {
        return this.csrfToken;
    }

    setCsrfToken(csrfToken: string) {
        this.csrfToken = csrfToken;
    }
}
