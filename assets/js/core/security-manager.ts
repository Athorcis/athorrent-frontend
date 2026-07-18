import {HttpClient, Request, Response, FilterChain} from "typescript-http-client";

const CSRF_TOKEN_LENGTH = 18;

export class SecurityManager {

    private csrfCookie: string|null = null;

    private csrfToken: string|null = null;

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

    initializeCsrfToken(): string;
    initializeCsrfToken(form: HTMLFormElement): string|null;
    initializeCsrfToken(form?: HTMLFormElement): string|null {
        if (form) {
            const csrfField = this.getCsrfField(form);

            if (csrfField) {
                this.csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
                this.csrfToken = csrfField.value;
            }
        }
        else {
            this.csrfToken = 'csrf-token';
        }

        if (!this.csrfCookie && this.csrfToken && this.nameCheck.test(this.csrfToken)) {
            this.csrfCookie = this.csrfToken;
            this.csrfToken = btoa(String.fromCharCode.apply(null, Array.from(crypto.getRandomValues(new Uint8Array(CSRF_TOKEN_LENGTH)))));
        }

        if (this.csrfCookie && this.csrfToken && this.tokenCheck.test(this.csrfToken)) {
            const cookie = this.csrfCookie + '_' + this.csrfToken + '=' + this.csrfCookie + '; path=/; samesite=strict';
            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
        }

        return this.csrfToken;
    }

    protected getCsrfField(form: HTMLFormElement): HTMLInputElement|null {
        return form.querySelector('input[data-controller="csrf-protection"], input[name="_csrf_token"]');
    }

    addCsrfTokenToForm(form: HTMLFormElement) {

        if (form.method === 'get') {
            return;
        }

        const csrfField = this.getCsrfField(form);

        if (csrfField) {
            const csrfToken = this.initializeCsrfToken(form);

            if (csrfToken) {
                csrfField.value = csrfToken;
            }
        }
    }

    removeCsrfCookie () {
        if (this.csrfToken && this.tokenCheck.test(this.csrfToken) && this.csrfCookie && this.nameCheck.test(this.csrfCookie)) {
            const cookie = this.csrfCookie + '_' + this.csrfToken + '=0; path=/; samesite=strict; max-age=0';

            document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;

            this.csrfCookie = null;
            this.csrfToken = null;
        }
    }
}
