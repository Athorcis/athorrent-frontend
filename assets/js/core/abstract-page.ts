import {Translator} from './translator';
import {Params, Router} from './router';
import {DataManager} from './data-manager';
import {UiManager} from './ui-manager';

export abstract class AbstractPage extends DataManager {

    protected router: Router;

    protected ui: UiManager;

    protected translator: Translator;

    injectServices(router: Router, translator: Translator, ui: UiManager) {
        this.router = router;
        this.translator = translator;
        this.ui = ui;
    }

    sendRequest(action: string, parameters: Params = {}) {
        return this.router.sendRequest(action, parameters);
    }

    translate(key: string): string {
        return this.translator.translate(key);
    }

    abstract init(): void;
}
