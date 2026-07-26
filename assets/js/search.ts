import '../css/search.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {noParallelRun} from './core/utils';

class SearchPage extends AbstractPage {

    init() {
        on(document, 'click', '.col-add-magnet > a', this.onMagnetAdd);
    }

    protected onMagnetAdd = noParallelRun(async (event: MouseEvent) => {
        event.preventDefault();

        const link = (event.target as HTMLElement).closest('a');

        try {
            await this.sendRequest('addMagnets', { magnets: [link!.href] });
            location.assign((await this.getRouter()).generateUrl('listTorrents'));
        }
        catch (error) {
            this.ui.showModal({
                title: 'torrents.magnetModal.title',
                content: (error as Error).message || this.translate('error.unknownError'),
            });
        }
    })
}

Application.create().run(SearchPage);
