
interface Params {
    [name: string]: string|string[];
}

interface Route {
    name: string;
    method: string;
    pattern: string;
    prefixId: string;
}

interface RouteGroup {
    [namePrefix: string]: Route;
}

interface Routes {
    [name: string]: RouteGroup;
}


interface Abortable { abort(): void; }
type AbortablePromise<T> = Promise<T> & Abortable;

interface ApiResponse<T> {
    status: string;
    data?: T;
    message?: string;
    csrfToken?: string;
}
