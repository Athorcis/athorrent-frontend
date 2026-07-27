
/**
 * Decode base64 using the right charset (because atob does not)
 */
export function decodeBase64(base64: string, charset: string = 'utf-8'): string {
    const text = atob(base64);
    const length = text.length;
    const bytes = new Uint8Array(length);

    for (let i = 0; i < length; i++) {
        bytes[i] = text.charCodeAt(i);
    }

    const decoder = new TextDecoder(charset);
    return decoder.decode(bytes);
}

/**
 * `setInterval`-like helper that awaits the callback and never overlaps executions.
 * Returns a cancel function that stops further scheduling immediately.
 */
export function setAsyncInterval(
    callback: () => Promise<void>,
    intervalMs: number,
    fireNow = false,
): () => void {
    let cancelled = false;
    let timeoutId: number | undefined;

    const tick = async () => {
        if (cancelled) {
            return;
        }

        await callback();

        if (cancelled) {
            return;
        }

        timeoutId = window.setTimeout(() => {
            void tick();
        }, intervalMs);
    };

    if (fireNow) {
        void tick();
    } else {
        timeoutId = window.setTimeout(() => {
            void tick();
        }, intervalMs);
    }

    return () => {
        cancelled = true;
        if (timeoutId !== undefined) {
            clearTimeout(timeoutId);
        }
    };
}

export function stringifyParams(params: Params): string {
    const builder = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value == null) {
            continue;
        }

        if (Array.isArray(value)) {
            for (const v of value) {
                builder.append(key + '[]', v);
            }
        }
        else {
            builder.append(key, value);
        }
    }

    return builder.toString();
}

export function toQueryString(params: Params): string {
    if (Object.keys(params).length > 0) {
        return '?' + stringifyParams(params);
    }

    return '';
}

export function getQueryParam(key: string) {
    const params = new URLSearchParams(location.search);
    return params.get(key);
}

export function createAbortablePromise<T>(promise: Promise<T>, abort: () => void): AbortablePromise<T> {
    const abortable = promise as AbortablePromise<T>;
    abortable.abort = abort;
    return abortable;
}

/**
 * Wraps an async callback so a second call is ignored while the first is still running.
 */
export function noParallelRun<A extends unknown[]>(
    fn: (...args: A) => void | PromiseLike<void>,
): (...args: A) => Promise<void> {
    let running = false;

    return async (...args: A) => {
        if (running) {
            return;
        }

        running = true;

        try {
            await fn(...args);
        } finally {
            running = false;
        }
    };
}
