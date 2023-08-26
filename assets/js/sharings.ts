import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';

class SharingsPage extends AbstractPage {

    init() {
        document.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.sharing-remove')) {
                this.onSharingRemove(event);
            }
        });
    }

    getSharingToken(element: HTMLElement, selector: string = null): string {
        return this.getItemId('sharing', element, selector);
    }

    async onSharingRemove(event: MouseEvent) {
        const target = event.target as HTMLElement;

        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(target)
        });

        this.getItem('sharing', target).remove();
    }
}

Application.create().run(SharingsPage);
