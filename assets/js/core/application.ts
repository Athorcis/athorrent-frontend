import {Translator} from './translator';
import {Router} from './router';
import $ from 'jquery';
import {SecurityManager} from './security-manager';
import {AbstractPage} from './abstract-page';
import 'bootstrap-sass';
import 'picturefill';
import {UiManager} from './ui-manager';

export class Application {

    private router: Router;

    private ui: UiManager;

    private securityManager: SecurityManager;

    private translator: Translator;

    constructor(private config: {[key: string]: any}) {
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
            page.injectServices(this.router, this.translator, this.ui);
            page.init();
        }
    }

    static create() {
        return new Application((window as any).athorrent || {});
    }
}
