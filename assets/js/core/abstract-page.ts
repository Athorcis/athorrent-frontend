import {Translator} from './translator';
import {LazyRouter} from './lazy-router';
import {DataManager} from './data-manager';
import {UiManager} from './ui-manager';
import {SecurityManager} from "./security-manager";

export abstract class AbstractPage extends DataManager {

    protected router!: LazyRouter;

    protected ui!: UiManager;

    protected translator!: Translator;

    protected securityManager!: SecurityManager;

    injectServices(router: LazyRouter, translator: Translator, ui: UiManager, securityManager: SecurityManager) {
        this.router = router;
        this.securityManager = securityManager;
        this.translator = translator;
        this.ui = ui;
    }

    sendRequest<R>(action: string, parameters: Params = {}) {
        return this.router.sendRequest<R>(action, parameters);
    }

    translate(key: string, parameters: Record<string, string> = {}): string {
        return this.translator.translate(key, parameters);
    }

    confirm(key: string, parameters: Record<string, string> = {}): boolean {
        return window.confirm(this.translate(key, parameters));
    }

    abstract init(): void;
}
