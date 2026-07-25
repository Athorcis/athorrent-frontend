import type {
    Filter,
    FilterConfig,
    FilterRegistration,
} from 'typescript-http-client';
import type { Router } from './router';
import { createAbortablePromise } from './utils';

type PendingFilter = {
    filter: Filter<unknown, unknown>;
    name: string;
    config?: FilterConfig;
};

/**
 * Lazy Router: loads `./router` (and thus `query-string` / `typescript-http-client`) on first use.
 * Queues addFilter() until the real client exists so CSRF can register without eager-loading.
 */
export class LazyRouter {

    private router$: Promise<Router> | null = null;

    private router: Router | null = null;

    private pendingFilters: PendingFilter[] = [];

    constructor(private routes: Routes, private routeParameters: Params) {}

    private getRouter(): Promise<Router> {
        if (!this.router$) {
            this.router$ = import('./router').then(({ Router }) => {
                const router = new Router(this.routes, this.routeParameters);

                for (const { filter, name, config } of this.pendingFilters) {
                    router.addHttpFilter(filter, name, config);
                }

                this.pendingFilters = [];
                this.router = router;

                return router;
            });
        }

        return this.router$;
    }

    addFilter(filter: Filter<unknown, unknown>, name: string, config?: FilterConfig): FilterRegistration {
        if (this.router) {
            return this.router.addHttpFilter(filter, name, config);
        }

        const pending: PendingFilter = { filter, name, config };
        this.pendingFilters.push(pending);

        return {
            remove: () => {
                const index = this.pendingFilters.indexOf(pending);

                if (index >= 0) {
                    this.pendingFilters.splice(index, 1);
                }
            },
        };
    }

    getQueryParam(key: string) {
        return this.getRouter().then(router => router.getQueryParam(key));
    }

    generateUrl(name: string, params: Params = {}): Promise<string> {
        return this.getRouter().then(router => router.generateUrl(name, params));
    }

    sendRequest<R>(name: string, parameters: Params = {}): AbortablePromise<R> {
        let aborted = false;
        let inner: AbortablePromise<R> | undefined;

        return createAbortablePromise(
            this.getRouter().then(router => {
                if (aborted) {
                    throw new DOMException('request was aborted', 'AbortError');
                }

                inner = router.sendRequest<R>(name, parameters);
                return inner;
            }),
            () => {
                aborted = true;
                inner?.abort();
            },
        );
    }
}
