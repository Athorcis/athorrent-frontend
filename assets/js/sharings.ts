import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';

class SharingsPage extends AbstractPage {

    init() {
        on(document, 'click', '.sharing-remove', this.onSharingRemove);
    }

    getSharingToken(element: HTMLElement, selector: string = null): string {
        return this.getItemId('sharing', element, selector);
    }

    onSharingRemove = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(target)
        });

        this.getItem('sharing', target).remove();
    }
}

Application.create().run(SharingsPage);
