import {HttpClient, Request, Response, FilterChain} from "typescript-http-client";

export class SecurityManager {

    constructor(private csrfToken: string, private http: HttpClient) {
    }

    init() {
        document.addEventListener('submit', event => {
            const form = event.target as HTMLFormElement;

            form.appendChild(
                document.createRange().createContextualFragment(`<input type="hidden" name="csrfToken" value="${this.csrfToken}" />`)
            );
        });

        this.http.addFilter({
            doFilter: <T>(request: Request, filterChain: FilterChain<ApiResponse<T>>): Promise<Response<ApiResponse<T>>> => {
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
