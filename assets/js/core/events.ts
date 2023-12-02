
type ValueOrArray<T> = T|T[];
type Listener<E> = (event: E) => void|PromiseLike<void>;

export function on<E extends Event>(
    target: EventTarget,
    type: string,
    selector: string,
    listeners: ValueOrArray<Listener<E>>
): void;
export function on<E extends Event>(
    target: EventTarget,
    type: string,
    listenersPerSelector: Map<string, ValueOrArray<Listener<E>>>
): void;
export function on<E extends Event>(
    target: EventTarget,
    type: string,
    selector: string|Map<string, ValueOrArray<Listener<E>>>,
    listeners?: ValueOrArray<Listener<E>>
): void {
    const listenersPerSelector = typeof selector === 'string' ? new Map([[selector, listeners]]) : selector;

    target.addEventListener(type, async function (event: E) {
        const target = event.target as HTMLElement;

        for (let [selector, listeners] of listenersPerSelector) {
            if (typeof listeners === 'function') {
                listeners = [listeners];
            }

            if (target.closest(selector)) {
                for (const listener of listeners) {
                    try {
                        await listener(event);
                    }
                    catch (err) {
                        console.error(err);
                    }
                }
            }
        }
    });
}
