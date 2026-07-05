import '../css/search.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import { Response } from 'typescript-http-client';

class SearchPage extends AbstractPage {

    init() {
        on(document, 'click', '.col-add-magnet > a', this.onMagnetAdd);
    }

    protected onMagnetAdd = async (event: MouseEvent) => {
        event.preventDefault();

        const link = (event.target as HTMLElement).closest('a');

        try {
            await this.sendRequest('addMagnets', { magnets: [link.href] });
            location.assign(this.router.generateUrl('listTorrents'));
        }
        catch (response) {
            const message = (response as Response<ApiErrorResponse>).body?.error;

            this.ui.showModal({
                title: 'torrents.magnetModal.title',
                content: message ?? this.translate('error.unknownError'),
            });
        }
    }
}

Application.create().run(SearchPage);
