import {Translator} from './translator';
import {LazyRouter} from './lazy-router';
import {SecurityManager} from './security-manager';
import {AbstractPage} from './abstract-page';
import {UiManager} from './ui-manager';

export class Application {

    private router: LazyRouter;

    private ui: UiManager;

    private securityManager: SecurityManager;

    private translator: Translator;

    constructor(config: Partial<AppConfig>) {
        this.router = new LazyRouter(config.routes!, config.routeParameters!);
        this.securityManager = new SecurityManager(this.router);
        this.translator = new Translator(config.strings!);
        this.ui = new UiManager(this.translator);
    }

    initialize() {
        this.securityManager.init();
    }

    run(pageType: { new(): AbstractPage }|null = null) {

        this.initialize();

        if (pageType) {
            const page = new pageType();
            page.injectServices(this.router, this.translator, this.ui, this.securityManager);
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
