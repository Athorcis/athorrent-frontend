import {Translator} from './translator';
import {Router} from './router';
import {SecurityManager} from './security-manager';
import {AbstractPage} from './abstract-page';
import 'jquery';
import 'bootstrap-sass';
import {UiManager} from './ui-manager';

export class Application {

    private router: Router;

    private ui: UiManager;

    private securityManager: SecurityManager;

    private translator: Translator;

    constructor(private config: Partial<AppConfig>) {
        this.router = new Router(config.routes, config.routeParameters, config.action);
        this.securityManager = new SecurityManager(this.router.getHttpClient());
        this.translator = new Translator(config.strings);
        this.ui = new UiManager(this.translator);
    }

    initialize() {
        this.securityManager.init();
    }

    run(pageType: { new(): AbstractPage } = null) {

        this.initialize();

        if (pageType) {
            const page = new pageType();
            page.injectServices(this.router, this.translator, this.ui, this.securityManager);
            page.init();
        }
    }

    static create() {
        let data: AppConfig;
        const json = document.body.dataset.athorrent;

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
