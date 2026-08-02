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

    /** Bumped on each new fetch / invalidate so stale responses are ignored. */
    private requestId = 0;

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
            this.abortRequest();
        }
    }

    update() {
        this.stop();
        this.start(true);
    }

    private abortRequest() {
        this.requestId++;

        if (this.data$) {
            this.data$.abort();
            this.data$ = null;
        }
    }

    async intervalCallback() {
        if (document.hidden) {
            return;
        }

        if (this.data$) {
            this.data$.abort();
        }

        const requestId = ++this.requestId;
        this.data$ = this.router.sendRequest(this.action, this.parameters);

        try {
            const data = await this.data$;

            if (requestId !== this.requestId) {
                return;
            }

            this.data$ = null;
            this.success(data);
        }
        catch (error) {
            if (requestId !== this.requestId) {
                return;
            }

            this.data$ = null;
            console.error(error);
        }
    }
}

class TorrentsPage extends AbstractPage {

    private torrentsUpdater!: Updater;

    private uploadManager: UploadManagerInterface|null = null;

    /** hash → control class currently showing a loading state */
    private busyTorrents = new Map<string, string>();

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

    getTorrentName(element: HTMLElement) {
        return this.getItemAttr('torrent', element, 'name');
    }

    onUpdateTorrents = (data: string) => {
        document.querySelector('.torrent-list')!.innerHTML = data;
        this.applyTorrentBusyStates();

        if (document.querySelector('.backend-alert')) {
            document.querySelector('.add-button')!.setAttribute('disabled', 'disabled');
        }
        else {
            document.querySelector('.add-button')!.removeAttribute('disabled');
        }
    }

    private applyTorrentBusyStates() {
        for (const [hash, loadingClass] of this.busyTorrents) {
            const torrent = document.getElementById(`torrent-${hash}`);

            if (!torrent) {
                this.busyTorrents.delete(hash);
                continue;
            }

            // Pause/resume swaps the control class once qBittorrent reflects the new state.
            if (
                (loadingClass === 'torrent-pause' || loadingClass === 'torrent-resume')
                && !torrent.querySelector(`.${loadingClass}`)
            ) {
                this.busyTorrents.delete(hash);
                continue;
            }

            for (const button of torrent.querySelectorAll<HTMLButtonElement>('.torrent-controls button')) {
                const loading = button.classList.contains(loadingClass);
                button.disabled = true;
                button.classList.toggle('loading', loading);
                button.ariaBusy = loading ? 'true' : 'false';
            }
        }
    }

    protected async applyActionToTorrent(action: string, element: HTMLElement) {
        const button = element.closest('button') as HTMLButtonElement;
        const hash = this.getTorrentHash(element);
        const loadingClass = ['torrent-pause', 'torrent-resume', 'torrent-remove']
            .find(className => button.classList.contains(className));
        const waitForStateChange = loadingClass === 'torrent-pause' || loadingClass === 'torrent-resume';

        if (loadingClass) {
            this.busyTorrents.set(hash, loadingClass);
            this.applyTorrentBusyStates();
        }

        try {
            await this.sendRequest(action, { hash });

            // Pause/resume can take a moment in qBittorrent; keep the loader and let the
            // normal list poll clear it once the torrent state actually changes.
            if (!waitForStateChange) {
                this.busyTorrents.delete(hash);
                this.torrentsUpdater.update();
            }
        }
        catch (error) {
            this.busyTorrents.delete(hash);

            const torrent = document.getElementById(`torrent-${hash}`);

            for (const control of torrent?.querySelectorAll<HTMLButtonElement>('.torrent-controls button') ?? []) {
                control.disabled = false;
                control.classList.remove('loading');
                control.ariaBusy = 'false';
            }

            throw error;
        }
    }

    onTorrentPause = noParallelRun(async (event: MouseEvent) => {
        return this.applyActionToTorrent('pauseTorrent', event.target as HTMLElement);
    })

    onTorrentResume = noParallelRun(async (event: MouseEvent) =>  {
        return this.applyActionToTorrent('resumeTorrent', event.target as HTMLElement);
    })

    onTorrentRemove = noParallelRun(async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        await this.confirm(
            'torrents.removalConfirmation',
            { torrent: this.getTorrentName(target) },
            async () => {
                await this.applyActionToTorrent('removeTorrent', target);
            },
        );
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
        const textareaEl = document.createElement('textarea');
        textareaEl.classList.add('add-magnet-textarea');
        textareaEl.setAttribute('placeholder', 'magnet:?xt=urn:btih:...\nmagnet:?xt=urn:btih:...');

        this.ui.showModal({
            title: 'torrents.magnetModal.title',
            subtitle: 'torrents.magnetModal.subtitle',
            content: textareaEl,
            removeWhenClose: true,
            controls: [{
                label: 'common.cancel',
            }, {
                label: 'torrents.add',
                primary: true,
                callback: noParallelRun(async () => {
                    const magnets = textareaEl.value.split('\n').map(line => line.trim()).filter(line => line.length > 0);

                    try {
                        await this.sendRequest('addMagnets', { magnets });
                        this.torrentsUpdater.update();
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
            textareaEl.value = prefill;
        }
    }

    protected onMagnetAdd = (_: MouseEvent) => {
        this.showMagnetModal();
    }
}

Application.create().run(TorrentsPage);
