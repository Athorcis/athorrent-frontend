import $ from 'jquery';
import {BeforeSend, CreateRequest, HttpClient, HttpRequest, HttpRequestOptions, Router} from './router';

export class SecurityManager {

    constructor(private csrfToken: string, private http: HttpClient) {
    }

    init() {
        $('form[method="post"]').submit(event => {
            $(event.target).append(`<input type="hidden" name="csrfToken" value="${this.csrfToken}" />`);
        });

        this.http.addHook(CreateRequest, (options: HttpRequestOptions) => {


            if (['GET', 'HEAD', 'OPTIONS', 'TRACE'].indexOf(options.method) < 0) {
                options.data.csrfToken = this.getCsrfToken();
            }

            return options;
        })
    }

    getCsrfToken(): string {
        return this.csrfToken;
    }

    setCsrfToken(csrfToken: string) {
        this.csrfToken = csrfToken;
    }
}
