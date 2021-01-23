
abstract class HookType<V = unknown> {}
type HookValue<T extends HookType> = T extends HookType<infer V> ? V : never;

type Hook<T extends HookType> = (value: HookValue<T>) => HookValue<T>;

type HookList<T extends HookType> = Hook<T>[];

class HooksMap extends Map<HookType, HookList<HookType>> {

    get<T extends HookType>(key: T) {
        return super.get(key) as HookList<T>;
    }

    set<T extends HookType>(key: T, value: HookList<T>) {
        return super.set(key, value);
    }
}

class HooksProvider {

    private map: HooksMap;

    addHook<T extends HookType>(type: T, hook: Hook<T>) {

        const hooks = this.map.get(type) || [];

        hooks.push(hook);
        this.map.set(type, hooks);
    }

    fireHook<T extends HookType>(type: T, value: HookValue<T>): HookValue<T> {

        const hooks = this.map.get(type) || [];

        return hooks.reduce((value, hook) => hook(value), value);
    }
}
