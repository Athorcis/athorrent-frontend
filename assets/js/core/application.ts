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
        this.securityManager = new SecurityManager(config.csrfToken, this.router.getHttpClient());
        this.ui = new UiManager(config.templates);
        this.translator = new Translator(config.strings);
    }

    initialize() {
        this.securityManager.init();
        this.router.init();
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
        return new Application(window.athorrent || {});
    }
}
