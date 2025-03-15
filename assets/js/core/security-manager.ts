import {HttpClient, Request, Response, FilterChain} from "typescript-http-client";

export class SecurityManager {

    private csrfCookie: string|undefined;

    private csrfToken: string|undefined;

    constructor(private http: HttpClient) {}

    init() {
        document.addEventListener('submit', event => {
            const form = event.target as HTMLFormElement;
            this.addCsrfTokenToForm(form);
        });

        this.http.addFilter({
            doFilter: <T>(request: Request, filterChain: FilterChain<ApiResponse<T>>): Promise<Response<ApiResponse<T>>> => {
                request.addHeader('X-Csrf-Token', this.initializeCsrfToken());

                const response$ = filterChain.doFilter(request);

                response$
                    .catch(() => {})
                    .finally(() => this.removeCsrfCookie());

                return response$;
            }

        }, "addCsrfToken", {
            enabled(request: Request) {
                return !['GET', 'HEAD', 'OPTIONS', 'TRACE'].includes(request.method);
            }
        });
    }

    private nameCheck = /^[-_a-zA-Z0-9]{4,22}$/;
    private tokenCheck = /^[-_/+a-zA-Z0-9]{24,}$/;

    initializeCsrfToken(form?: HTMLFormElement): string {
        if (form) {
            const csrfField = form.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]') as HTMLInputElement;

            if (csrfField) {
                this.csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
                this.csrfToken = csrfField.value;
            }
        }
        else {
            this.csrfToken = 'csrf-token';
        }

        if (!this.csrfCookie && this.nameCheck.test(this.csrfToken)) {
            this.csrfCookie = this.csrfToken;
            this.csrfToken = btoa(String.fromCharCode.apply(null, crypto.getRandomValues(new Uint8Array(18))));
        }

        if (this.csrfCookie && this.tokenCheck.test(this.csrfToken)) {
            const cookie = this.csrfCookie + '_' + this.csrfToken + '=' + this.csrfCookie + '; path=/; samesite=strict';
            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
        }

        return this.csrfToken;
    }

    addCsrfTokenToForm(form: HTMLFormElement) {
        const csrfToken = this.initializeCsrfToken(form);
        const csrfField = form.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]') as HTMLInputElement;

        csrfField.value = csrfToken;
    }

    removeCsrfCookie () {
        if (this.tokenCheck.test(this.csrfToken) && this.nameCheck.test(this.csrfCookie)) {
            const cookie = this.csrfCookie + '_' + this.csrfToken + '=0; path=/; samesite=strict; max-age=0';

            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;

            this.csrfCookie = undefined;
            this.csrfToken = undefined;
        }
    }
}
