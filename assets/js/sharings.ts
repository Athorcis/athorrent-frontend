/* eslint-env browser */

import $ from 'jquery';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import ClickEvent = JQuery.ClickEvent;

class SharingsPage extends AbstractPage {

    init() {
        $(document).on('click', '.sharing-remove', this.onSharingRemove.bind(this));
    }

    getSharingToken(element: HTMLElement, selector: string = null): string {
        return this.getItemId('sharing', element, selector);
    }

    async onSharingRemove(event: ClickEvent) {

        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(event.target)
        });

        this.getItem('sharing', event.target).remove();
    }
}

Application.create().run(SharingsPage);
