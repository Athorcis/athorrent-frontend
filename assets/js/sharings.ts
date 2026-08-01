import '../css/sharings.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {noParallelRun, withButtonLoading} from './core/utils';

class SharingsPage extends AbstractPage {

    init() {
        on(document, 'click', '.sharing-remove', this.onSharingRemove);
    }

    getSharingId(element: HTMLElement, selector: string|null = null): string {
        return this.getItemId('sharing', element, selector);
    }

    onSharingRemove = noParallelRun(async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        await withButtonLoading(target, async () => {
            await this.sendRequest('removeSharing', {
                id: this.getSharingId(target)
            });

            this.getItem('sharing', target).remove();
        });
    })
}

Application.create().run(SharingsPage);
