import '../css/torrents.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import type {UploadManagerInterface} from "./core/upload-manager";
import type {Router} from './core/router';
import {getQueryParam, noParallelRun, setAsyncInterval} from "./core/utils";

const torrentListTimeout = 2000;

class Updater {

    private data$: AbortablePromise<string>|null = null;

    private intervalStop: (() => void)|null = null;

    constructor(
        private router: Router,
        private action: string,
        private parameters: Params,
        private success: (data: string) => void,
        private interval: number) {

    }

    start(fireNow = false) {
        if (this.intervalStop) {
            return;
        }

        this.intervalStop = setAsyncInterval(
            () => this.intervalCallback(),
            this.interval,
            fireNow,
        );
    }

    stop() {
        if (this.intervalStop) {
            this.intervalStop();
            this.intervalStop = null;

            if (this.data$) {
                this.data$.abort();
                this.data$ = null;
            }
        }
    }

    update() {
        this.stop();
        this.start(true);
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
}

class TorrentsPage extends AbstractPage {

    private torrentsUpdater!: Updater;

    private uploadManager: UploadManagerInterface|null = null;

    init() {
        if (navigator.registerProtocolHandler) {
            navigator.registerProtocolHandler('magnet', `${ location.origin }/user/torrents/magnet?magnet=%s`, 'Athorrent');
        }

        this.handleMagnetParam();

        void this.initializeTorrentsList();
    }

    protected handleMagnetParam() {
        const magnet = getQueryParam('magnet') as string | undefined;

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
        document.querySelector('.torrent-list')!.innerHTML = data;

        if (document.querySelector('.backend-alert')) {
            document.querySelector('.add-button')!.setAttribute('disabled', 'disabled');
        }
        else {
            document.querySelector('.add-button')!.removeAttribute('disabled');
        }
    }

    protected async applyActionToTorrent(action: string, element: HTMLElement) {
        await this.sendRequest(action, {
            hash: this.getTorrentHash(element)
        });

        this.torrentsUpdater.update();
    }

    onTorrentPause = noParallelRun(async (event: MouseEvent) => {
        return this.applyActionToTorrent('pauseTorrent', event.target as HTMLElement);
    })

    onTorrentResume = noParallelRun(async (event: MouseEvent) =>  {
        return this.applyActionToTorrent('resumeTorrent', event.target as HTMLElement);
    })

    onTorrentRemove = noParallelRun(async (event: MouseEvent) => {
        return this.applyActionToTorrent('removeTorrent', event.target as HTMLElement);
    })

    async initializeTorrentsList() {
        this.torrentsUpdater = new Updater(await this.getRouter(),'listTorrents', {}, this.onUpdateTorrents, torrentListTimeout);
        this.torrentsUpdater.start();

        on(document, 'click', new Map([
            ['.torrent-pause', this.onTorrentPause],
            ['.torrent-resume', this.onTorrentResume],
            ['.torrent-remove', this.onTorrentRemove],
            ['.add-torrent', this.onTorrentAdd],
            ['.add-magnet', this.onMagnetAdd],
        ]));
    }

    protected onTorrentAdd = async (_: MouseEvent) => {

        if (this.uploadManager === null) {
            const {UploadManager} = await import("./core/upload-manager");
            this.uploadManager = new UploadManager(await this.getRouter(), this.securityManager, this.ui, this.translator);
        }

        this.uploadManager.trigger({
            title: 'torrents.addTorrent',
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
                callback: noParallelRun(async () => {
                    const textarea = modal.querySelector('textarea')!;
                    const magnets = textarea.value.split('\n').map(line => line.trim()).filter(line => line.length > 0);

                    try {
                        await this.sendRequest('addMagnets', { magnets });
                    }
                    catch (error) {
                        this.ui.showModal({
                            title: 'torrents.magnetModal.title',
                            content: (error as Error).message || this.translate('error.unknownError'),
                            id: 'dialog-error'
                        });
                    }
                })
            }]
        });

        if (prefill) {
            modal.querySelector('textarea')!.value = prefill;
        }
    }

    protected onMagnetAdd = (_: MouseEvent) => {
        this.showMagnetModal();
    }
}

Application.create().run(TorrentsPage);
