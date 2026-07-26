import {Translator} from './translator';
import {DataManager} from './data-manager';
import {UiManager} from './ui-manager';
import {SecurityManager} from "./security-manager";
import {RouterProvider} from "./application";

export abstract class AbstractPage extends DataManager {

    protected ui!: UiManager;

    protected translator!: Translator;

    protected securityManager!: SecurityManager;

    injectServices(routerProvider: RouterProvider, translator: Translator, ui: UiManager, securityManager: SecurityManager) {
        this.getRouter = routerProvider;
        this.securityManager = securityManager;
        this.translator = translator;
        this.ui = ui;
    }

    protected getRouter!: RouterProvider;

    async sendRequest<R>(action: string, parameters: Params = {}): Promise<R> {
        const router = await this.getRouter();
        return router.sendRequest<R>(action, parameters);
    }

    translate(key: string, parameters: Record<string, string> = {}): string {
        return this.translator.translate(key, parameters);
    }

    confirm(key: string, parameters: Record<string, string> = {}): boolean {
        return window.confirm(this.translate(key, parameters));
    }

    abstract init(): void;
}
