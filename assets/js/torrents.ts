/* eslint-env browser */

import $ from 'jquery';
import Dropzone, {DropzoneFile} from 'dropzone';
import '../css/torrents.scss';
import {Router} from './core/router';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {SecurityManager} from './core/security-manager';
import {Translator} from './core/translator';
import {UiManager} from './core/ui-manager';

const torrentListTimeout = 2000,
    trackerListTimeout = 5000;

class Updater {

    private intervalId = -1;

    private data$: AbortablePromise<unknown>;

    constructor(
        private router: Router,
        private action: string,
        private parameters: Params,
        private success: (data: string) => void,
        private interval: number) {

    }

    start(fireNow = false) {
        if (this.intervalId === -1) {
            if (fireNow) {
                this.intervalCallback();
            }

            this.intervalId = window.setInterval(this.intervalCallback.bind(this), this.interval);
        }
    }

    stop() {
        if (this.intervalId > -1) {
            clearInterval(this.intervalId);
            this.intervalId = -1;

            if (this.data$) {
                this.data$.abort();
                this.data$ = null;
            }
        }
    }

    update() {
        if (this.intervalId > -1) {
            this.stop();
            this.start(true);
        }
    }

    async intervalCallback() {
        if (this.data$) {
            this.data$.abort();
        }

        this.data$ = this.router.sendRequest(this.action, this.parameters)

        this.data$.then(this.internalSuccess.bind(this));
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


class TabsPanel {

    private tabMap: { [key: string]: Tab };

    private panel: HTMLElement;

    private $tabs: HTMLElement;

    constructor(selector: string) {
        this.tabMap = {};
        this.panel = document.querySelector(selector);

        this.panel.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.nav-tabs a')) {
                this.onClick(event);
            }
        })
    }

    addTab(id: string, tab: Tab) {
        this.tabMap[id] = tab;
    }

    getCurrentTab() {
        return this.tabMap[this.panel.querySelector('.nav-tabs li.active a').getAttribute('href').substring(1)];
    }

    onClick(event: MouseEvent) {
        event.preventDefault();
        $(event.target).tab('show');
    }

    isVisible() {
        return this.panel.offsetParent != null;
    }

    show() {
        this.panel.style.display = 'block';
        (document.querySelector('body > .container') as HTMLElement).style.marginBottom = `${ this.panel.clientHeight }px`;
        $(this.panel).find('.nav-tabs li.active a').trigger('show.bs.tab');
    }

    hide() {
        $(this.panel).find('.nav-tabs li.active a').trigger('hide.bs.tab');
        (document.querySelector('body > .container') as HTMLElement).style.marginBottom = '';
        this.panel.style.display = 'none';
    }
}

class Tab {

    protected parent: TabsPanel;

    private $tab: JQuery;

    private container: HTMLElement;

    private updater;

    constructor(router: Router, parent: TabsPanel, id: string, action: string, parameters: Params, interval: number) {
        this.$tab = $(`[href="#${id}"]`);
        this.container = document.querySelector(`#${id}`);
        this.updater = new Updater(router, action, parameters, this.onUpdate.bind(this), interval);

        if (parent) {
            this.parent = parent;
            parent.addTab(id, this);
        }

        this.$tab.on('show.bs.tab', this.onShow.bind(this));
        this.$tab.on('hide.bs.tab', this.onHide.bind(this));
    }

    setParameters(parameters: {[key: string]: string}) {
        this.updater.setParameters(parameters);
    }

    onUpdate(data: string) {
        this.container.innerHTML = data;
    }

    onShow() {
        this.updater.start(true);
    }

    onHide() {
        this.updater.stop();
    }
}

class TorrentPanel extends TabsPanel {

    private hash: string;

    constructor() {
        super('.torrent-panel');
    }

    toggleHash(hash: string) {
        if (this.isVisible()) {
            if (this.hash === hash) {
                this.hide();
            } else {
                this.hash = hash;
                (this.getCurrentTab() as TorrentPanelTab).setHash(hash);
            }
        } else {
            this.setHash(hash);
            this.show();
        }
    }

    setHash(hash: string) {
        if (this.hash !== hash) {
            this.hash = hash;
            (this.getCurrentTab() as TorrentPanelTab).setHash(hash);
        }
    }

    getHash() {
        return this.hash;
    }
}

class TorrentPanelTab extends Tab {

    private hash: string;

    setHash(hash: string) {
        if (this.hash !== hash) {
            this.hash = hash;
            this.setParameters({ hash });
        }
    }

    onShow() {
        this.setHash((this.parent as TorrentPanel).getHash());
        super.onShow();
    }
}

class AddTorrentForm {

    private enabled: boolean;

    private form: HTMLFormElement;

    private submitEl: HTMLSpanElement;

    private mode: AddTorrentMode;

    private modes: AddTorrentMode[];

    constructor(selector: string, submitSelector: string, private router: Router, private afterSubmit: () => void) {
        this.form = document.querySelector(selector);
        this.submitEl = document.querySelector(submitSelector);
        this.modes = [];

        this.submitEl.addEventListener('click', this.onSubmitClick.bind(this));
    }

    onSubmitClick(event: MouseEvent) {
        const target = event.target as HTMLElement;

        if (!target.classList.contains('disabled')) {
            this.submit();
        }
    }

    isDisabled() {
        return !this.enabled;
    }

    enable() {
        this.enabled = true;
        this.form.classList.add('enabled');
    }

    disable() {
        this.enabled = false;
        this.form.classList.remove('enabled');
    }

    setMode(mode: AddTorrentMode) {
        this.mode = mode;
    }

    getMode() {
        return this.mode;
    }

    registerMode(mode: AddTorrentMode) {
        this.modes.push(mode);
    }

    updateFileCounter() {
        let count = 0;

        for (let i = 0, { length } = this.modes; i < length; ++i) {
            count += this.modes[i].getCounter();
        }

        if (count > 0) {
            this.submitEl.classList.remove('disabled');
        } else {
            this.submitEl.classList.add('disabled');
        }
    }

    submit() {
        const params: Params = {};

        for (let i = 0, { length } = this.modes; i < length; ++i) {
            params[this.modes[i].getInputName()] = this.modes[i].getItems();
        }

        this.router.sendRequest('addTorrents', params).then(this.afterSubmit);

        for (let i = 0, { length } = this.modes; i < length; ++i) {
            this.modes[i].clearItems();
        }

        this.mode.disable();
        this.mode = null;
    }
}

abstract class AddTorrentMode {

    private enabled: boolean = false;

    protected element: HTMLElement;

    private btn: HTMLElement;

    private counter: number = 0;

    private counterEL: HTMLElement;

    private form: AddTorrentForm;

    abstract onEnabled(): void;

    protected constructor(private inputName: string, elementSelector: string, btnSelector: string, counterSelector: string, form: AddTorrentForm) {

        this.element = document.querySelector(elementSelector);
        this.btn = document.querySelector(btnSelector);
        this.counterEL = document.querySelector(counterSelector);

        if (form) {
            this.form = form;
            form.registerMode(this);
        }

        this.btn.addEventListener('click', this.toggle.bind(this));
        $(this).on('enabled', this.onEnabled.bind(this));
    }

    enable() {
        this.enabled = true;
        this.element.style.display = 'block';
        this.btn.classList.add('active');

        if (this.form.isDisabled()) {
            this.form.enable();
        } else {
            this.form.getMode().disable(true);
        }

        this.form.setMode(this);

        $(this).trigger('enabled');
    }

    disable(recursive = false) {
        this.enabled = false;
        this.element.style.display = 'none';
        this.btn.classList.remove('active');

        if (!recursive) {
            this.form.disable();
            this.form.setMode(null);
        }
    }

    toggle() {
        if (this.enabled) {
            this.disable();
        } else {
            this.enable();
        }
    }

    setCounter(number: number) {
        this.counter = number;
        this.counterEL.textContent = `(${number})`;
        this.form.updateFileCounter();
    }

    getCounter() {
        return this.counter;
    }

    getInputName(): string {
        return this.inputName;
    }

    abstract getItems(): string[];
    abstract clearItems(): void;
}

class AddTorrentFileMode extends AddTorrentMode {

    private dropzone;

    constructor(
        router: Router,
        translator: Translator,
        ui: UiManager,
        private securityManager: SecurityManager,
        inputName: string,
        elementSelector: string,
        btnSelector: string,
        counterSelector: string,
        form: AddTorrentForm
    ) {
        super(inputName, elementSelector, btnSelector, counterSelector, form);


        this.dropzone = new Dropzone(elementSelector, {
            url: router.generateUrl('uploadTorrent'),
            paramName: 'upload-torrent-file',
            dictDefaultMessage: translator.translate('torrents.dropzone'),
            dictInvalidFileType: translator.translate('error.notATorrent'),
            dictFileTooBig: translator.translate('error.fileTooBig'),
            dictResponseError: translator.translate('error.serverError'),
            previewTemplate: document.querySelector('#template-dropzone-preview').innerHTML,
            acceptedFiles: '.torrent',
            parallelUploads: 1,
            maxFilesize: 1
        });

        this.dropzone.on('removedfile', this.onRemovedFile.bind(this));
        this.dropzone.on('success', this.onSuccess.bind(this));
        this.dropzone.on('error', this.onError.bind(this));

        this.dropzone.on('sending', this.onSending.bind(this));
    }

    onEnabled() {
        this.element.dispatchEvent(new MouseEvent('click'));
    }

    onRemovedFile() {
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    }

    onSuccess(file: DropzoneFile[], result: ApiResponse<unknown>) {
        this.securityManager.setCsrfToken(result.csrfToken);
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    }

    onError(file: DropzoneFile[], result: ApiResponse<unknown>) {
        if (typeof result === 'object' && result.hasOwnProperty('csrfToken')) {
            this.securityManager.setCsrfToken(result.csrfToken);
        }
    }

    onSending(file: DropzoneFile[], xhr: XMLHttpRequest, formData: FormData) {
        formData.append('csrfToken', this.securityManager.getCsrfToken());
    }

    getItems() {
        const items = [],
            files = this.dropzone.getAcceptedFiles();

        for (let i = 0, { length } = files; i < length; ++i) {
            items.push(files[i].name);
        }

        return items;
    }

    clearItems() {
        this.dropzone.removeAllFiles(true);
        this.setCounter(0);
    }
}

class AddTorrentMagnetMode extends AddTorrentMode {

    private textarea: HTMLTextAreaElement;

    constructor(inputName: string, elementSelector: string, btnSelector: string, counterSelector: string, form: AddTorrentForm) {
        super(inputName, elementSelector, btnSelector, counterSelector, form);

        this.textarea = document.querySelector('#add-torrent-magnet-input');
        this.textarea.addEventListener('input', this.onInput.bind(this));
    }

    onEnabled() {
        this.element.querySelector('textarea').focus();
    }

    onInput() {
        this.setCounter(this.getItems().length);
    }

    getItems() {
        const magnets = [],
            rmagnet = /^magnet:\?[\x20-\x7E]*/,
            lines = this.textarea.value.split(/\r\n|\r|\n/);

        for (let i = 0, { length } = lines; i < length; ++i) {
            if (rmagnet.test(lines[i])) {
                magnets.push(lines[i]);
            }
        }

        return magnets;
    }

    clearItems() {
        this.textarea.value = '';
        this.setCounter(0);
    }
}

class TorrentsPage extends AbstractPage {

    private torrentsUpdater: Updater;

    private torrentPanel: TorrentPanel;

    private trackersTab: TorrentPanelTab;

    private addTorrentFileMode: AddTorrentFileMode;

    private addTorrentMagnetMode: AddTorrentMagnetMode;

    init() {

        this.initializeTorrentsList();
        this.initializeTorrentPanel();
        this.initializeAddTorrentForm();

        if (navigator.registerProtocolHandler) {
            navigator.registerProtocolHandler('magnet', `${ location.origin }/user/torrents/magnet?magnet=%s`, 'Athorrent');
        }
    }

    getTorrentHash(element: HTMLElement) {
        return this.getItemId('torrent', element);
    }

    onUpdateTorrents(data: string) {
        document.querySelector('.torrent-list').innerHTML = data;
    }

    protected async applyActionToTorrent(action: string, element: HTMLElement) {
        await this.sendRequest(action, {
            hash: this.getTorrentHash(element)
        });

        this.torrentsUpdater.update();
    }

    onTorrentPause(event: MouseEvent) {
        return this.applyActionToTorrent('pauseTorrent', event.target as HTMLElement);
    }

    onTorrentResume(event: MouseEvent) {
        return this.applyActionToTorrent('resumeTorrent', event.target as HTMLElement);
    }

    onTorrentRemove(event: MouseEvent) {
        return this.applyActionToTorrent('removeTorrent', event.target as HTMLElement);
    }

    initializeTorrentsList() {
        this.torrentsUpdater = new Updater(this.router,'listTorrents', {}, this.onUpdateTorrents, torrentListTimeout);
        this.torrentsUpdater.start();

        document.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.torrent-pause')) {
                this.onTorrentPause(event);
            }
            else if (target.closest('.torrent-resume')) {
                this.onTorrentResume(event);
            }
            else if (target.closest('.torrent-remove')) {
                this.onTorrentRemove(event);
            }
        });
    }

    onShowDetails(event: MouseEvent) {
        this.torrentPanel.toggleHash(this.getTorrentHash(event.target as HTMLElement));
    }

    initializeTorrentPanel() {
        this.torrentPanel = new TorrentPanel();
        this.trackersTab = new TorrentPanelTab(this.router, this.torrentPanel, 'torrent-trackers', 'listTrackers', {}, trackerListTimeout);

        document.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.torrent-detail')) {
                this.onShowDetails(event);
            }
        });
    }

    initializeAddTorrentForm() {
        const addTorrentForm = new AddTorrentForm('#add-torrent-form', '#add-torrent-submit', this.router, () => {
            this.torrentsUpdater.update();
        });

        this.addTorrentFileMode = new AddTorrentFileMode(this.router, this.translator, this.ui, this.securityManager, 'add-torrent-files', '#add-torrent-file-drop', '#add-torrent-file', '#add-torrent-file-counter', addTorrentForm);
        this.addTorrentMagnetMode = new AddTorrentMagnetMode('add-torrent-magnets', '#add-torrent-magnet-wrapper', '#add-torrent-magnet', '#add-torrent-magnet-counter', addTorrentForm);
    }
}

Application.create().run(TorrentsPage);
