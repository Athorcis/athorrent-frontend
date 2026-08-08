import {Translator} from './translator';
import type {Router} from './router';
import {SecurityManager} from './security-manager';
import {AbstractPage} from './abstract-page';
import {UiManager} from './ui-manager';

export type RouterProvider = () => Promise<Router>;

export class Application {

    private router: Router|undefined;

    private readonly ui: UiManager;

    private readonly securityManager: SecurityManager;

    private readonly translator: Translator;

    readonly routerProvider: RouterProvider;

    constructor(config: Partial<AppConfig>) {

        this.routerProvider = async () => {
            if (this.router == null) {
                this.router = await this.initRouter(config.routes!, config.routeParameters!);
            }

            return this.router;
        };

        this.securityManager = new SecurityManager();
        this.translator = new Translator(config.strings!);
        this.ui = new UiManager(this.translator);
    }

    protected async initRouter(routes: Routes, routeParams: Params): Promise<Router> {
        const {Router} = await import('./router');

        const router = new Router(routes, routeParams);
        this.securityManager.setRouter(router);

        return router;
    }

    initialize() {
        this.securityManager.init();
    }

    run(pageType: { new(): AbstractPage }|null = null) {

        this.initialize();

        if (pageType) {
            const page = new pageType();
            page.injectServices(this.routerProvider, this.translator, this.ui, this.securityManager);
            page.init();
        }
    }

    static create() {
        let data: AppConfig|undefined;
        const json = document.body.dataset['athorrent'];

        if (json !== undefined) {
            try {
                data = JSON.parse(json);
            } catch (e) {
                console.error(e);
            }
        }

        return new Application(data || {});
    }
}
