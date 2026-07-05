import '../css/torrents.scss';
import {Router} from './core/router';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {UploadManager} from "./core/upload-manager";
import { Response } from 'typescript-http-client';

const torrentListTimeout = 2000;

class Updater {

    private timeoutId = -1;

    private data$: AbortablePromise<string>;

    constructor(
        private router: Router,
        private action: string,
        private parameters: Params,
        private success: (data: string) => void,
        private interval: number) {

    }

    start(fireNow = false) {
        if (this.timeoutId === -1) {
            const run = async (fire = true) => {
                if (fire) {
                    await this.intervalCallback();
                }

                this.timeoutId = window.setTimeout(run, this.interval);
            };

            void run(fireNow);
        }
    }

    stop() {
        if (this.timeoutId > -1) {
            clearTimeout(this.timeoutId);
            this.timeoutId = -1;

            if (this.data$) {
                this.data$.abort();
                this.data$ = null;
            }
        }
    }

    update() {
        if (this.timeoutId > -1) {
            this.stop();
            this.start(true);
        }
    }

    async intervalCallback() {
        if (this.data$) {
            this.data$.abort();
        }

        this.data$ = this.router.sendRequest(this.action, this.parameters)

        try {
            this.internalSuccess(await this.data$);
        }
        catch (error) {
            console.error(error);
        }
    }

    internalSuccess(data: string) {
        this.data$ = null;
        this.success(data);
    }

    setParameters(parameters: {[key: string]: string}) {
        this.parameters = parameters;
        this.update();
    }
}

class TorrentsPage extends AbstractPage {

    private torrentsUpdater: Updater;

    private uploadManager: UploadManager;

    init() {
        this.uploadManager = new UploadManager(this.router, this.securityManager, this.ui, this.translator);

        this.initializeTorrentsList();

        if (navigator.registerProtocolHandler) {
            navigator.registerProtocolHandler('magnet', `${ location.origin }/user/torrents/magnet?magnet=%s`, 'Athorrent');
        }

        this.handleMagnetParam();
    }

    protected handleMagnetParam() {
        const magnet = Router.parseQueryParameters()['magnet'] as string | undefined;

        if (magnet) {
            this.showMagnetModal(magnet);

            const url = new URL(location.href);
            url.searchParams.delete('magnet');
            history.replaceState(null, '', url.toString());
        }
    }

    getTorrentHash(element: HTMLElement) {
        return this.getItemId('torrent', element);
    }

    onUpdateTorrents = (data: string) => {
        document.querySelector('.torrent-list').innerHTML = data;

        if (document.querySelector('.backend-alert')) {
            document.querySelector('.add-button').setAttribute('disabled', 'disabled');
        }
        else {
            document.querySelector('.add-button').removeAttribute('disabled');
        }
    }

    protected async applyActionToTorrent(action: string, element: HTMLElement) {
        await this.sendRequest(action, {
            hash: this.getTorrentHash(element)
        });

        this.torrentsUpdater.update();
    }

    onTorrentPause = async (event: MouseEvent) => {
        return this.applyActionToTorrent('pauseTorrent', event.target as HTMLElement);
    }

    onTorrentResume = async (event: MouseEvent) =>  {
        return this.applyActionToTorrent('resumeTorrent', event.target as HTMLElement);
    }

    onTorrentRemove = async (event: MouseEvent) => {
        return this.applyActionToTorrent('removeTorrent', event.target as HTMLElement);
    }

    initializeTorrentsList() {
        this.torrentsUpdater = new Updater(this.router,'listTorrents', {}, this.onUpdateTorrents, torrentListTimeout);
        this.torrentsUpdater.start();

        on(document, 'click', new Map([
            ['.torrent-pause', this.onTorrentPause],
            ['.torrent-resume', this.onTorrentResume],
            ['.torrent-remove', this.onTorrentRemove],
            ['.add-torrent', this.onTorrentAdd],
            ['.add-magnet', this.onMagnetAdd],
        ]));
    }

    protected onTorrentAdd = (_: MouseEvent) => {
        this.uploadManager.trigger({
            title: 'files.upload',
            route: 'uploadTorrent',

            dropzone: {
                paramName: 'upload-torrent-file',
                acceptedFiles: '.torrent',
                maxFilesize: 1,

                dictInvalidFileType: this.translator.translate('error.notATorrent'),
            },

            complete: async (filesUploaded) => {
                if (filesUploaded > 0) {
                    await this.torrentsUpdater.update();
                }
            }
        });
    }

    protected showMagnetModal(prefill = '') {
        const modal = this.ui.showModal({
            title: 'torrents.magnetModal.title',
            subtitle: 'torrents.magnetModal.subtitle',
            content: '<textarea placeholder="magnet:?xt=urn:btih:...\nmagnet:?xt=urn:btih:..." class="add-magnet-textarea"></textarea>',
            removeWhenClose: true,
            controls: [{
                label: 'common.cancel',
            }, {
                label: 'torrents.add',
                primary: true,
                callback: async () => {
                    const textarea = modal.querySelector('textarea');
                    const magnets = textarea.value.split('\n').map(line => line.trim()).filter(line => line.length > 0);

                    try {
                        await this.sendRequest('addMagnets', { magnets });
                    }
                    catch (response) {
                        const message = (response as Response<ApiErrorResponse>).body.error;

                        this.ui.showModal({
                            title: 'torrents.magnetModal.title',
                            content: message ?? this.translate('error.unknownError'),
                        });
                    }
                }
            }]
        });

        if (prefill) {
            modal.querySelector('textarea').value = prefill;
        }
    }

    protected onMagnetAdd = (_: MouseEvent) => {
        this.showMagnetModal();
    }
}

Application.create().run(TorrentsPage);
