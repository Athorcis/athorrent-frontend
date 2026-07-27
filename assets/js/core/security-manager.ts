import type {Request, Response, FilterChain} from "typescript-http-client";
import type {Router} from "./router";

const CSRF_TOKEN_LENGTH = 18;
const CSRF_COOKIE_NAME = 'csrf-token';

export interface CsrfProtection {
    token: string;
    cleanup: () => void;
}

export class SecurityManager {

    init() {
        document.addEventListener('submit', event => {
            const form = event.target as HTMLFormElement;
            this.addCsrfTokenToForm(form);
        });
    }

    setRouter(router: Router) {
        router.getHttpClient().addFilter({
            doFilter: <T>(request: Request, filterChain: FilterChain<ApiResponse<T>>): Promise<Response<ApiResponse<T>>> => {
                const { token, cleanup } = this.createCsrfToken();

                request.addHeader('X-Csrf-Token', token);

                const response$ = filterChain.doFilter(request);

                response$
                    .catch(() => {})
                    .finally(cleanup);

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

    /**
     * Creates a CSRF cookie/token pair scoped to a single request.
     * Call `cleanup()` when the request finishes so concurrent requests do not share state.
     */
    createCsrfToken(cookieName: string = CSRF_COOKIE_NAME): CsrfProtection {
        const token = this.generateToken();

        this.writeCsrfCookie(cookieName, token);

        return {
            token,
            cleanup: () => this.clearCsrfCookie(cookieName, token),
        };
    }

    initializeCsrfToken(form: HTMLFormElement): string|null {
        const csrfField = this.getCsrfField(form);

        if (!csrfField) {
            return null;
        }

        let csrfCookie = csrfField.getAttribute('data-csrf-protection-cookie-value');
        let csrfToken = csrfField.value;

        if (!csrfCookie && csrfToken && this.nameCheck.test(csrfToken)) {
            csrfCookie = csrfToken;
            csrfToken = this.generateToken();
        }

        if (csrfCookie && csrfToken && this.tokenCheck.test(csrfToken)) {
            this.writeCsrfCookie(csrfCookie, csrfToken);
        }

        return csrfToken;
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

    private generateToken(): string {
        return btoa(String.fromCharCode.apply(null, Array.from(crypto.getRandomValues(new Uint8Array(CSRF_TOKEN_LENGTH)))));
    }

    private writeCsrfCookie(cookieName: string, token: string): void {
        if (!this.nameCheck.test(cookieName) || !this.tokenCheck.test(token)) {
            return;
        }

        const cookie = cookieName + '_' + token + '=' + cookieName + '; path=/; samesite=strict';
        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }

    private clearCsrfCookie(cookieName: string, token: string): void {
        if (!this.nameCheck.test(cookieName) || !this.tokenCheck.test(token)) {
            return;
        }

        const cookie = cookieName + '_' + token + '=0; path=/; samesite=strict; max-age=0';
        document.cookie = window.location.protocol === 'https:' ? '__Host-' + cookie + '; secure' : cookie;
    }
}
