/* eslint-env browser */

import $ from 'jquery';
import Dropzone from 'dropzone';
import '../css/torrents.scss';
import {AbortablePromise, Params, Router} from './core/router';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {SecurityManager} from './core/security-manager';
import ClickEvent = JQuery.ClickEvent;
import {Translator} from './core/translator';
import {UiManager} from './core/ui-manager';
import jqXHR = JQuery.jqXHR;

interface AjaxResponse {
    status: string;
    data: any;
    csrfToken: string;
}

let torrentListTimeout = 2000,
    trackerListTimeout = 5000;

class Updater {

    private intervalId = -1;

    private data$: AbortablePromise<any>;

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

            this.intervalId = setInterval(this.intervalCallback.bind(this), this.interval) as any;
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

    private $panel: JQuery;

    private $tabs: JQuery;

    constructor(selector: string) {
        this.tabMap = {};
        this.$panel = $(selector);
        this.$tabs = this.$panel.find('.nav-tabs a');

        this.$tabs.on('click', this.onClick.bind(this));
    }

    addTab(id: string, tab: Tab) {
        this.tabMap[id] = tab;
    }

    getCurrentTab() {
        return this.tabMap[this.$panel.find('.nav-tabs li.active a').attr('href').substr(1)];
    }

    onClick(event: ClickEvent) {
        event.preventDefault();
        $(event.target).tab('show');
    }

    isVisible() {
        return this.$panel.is(':visible');
    }

    show() {
        this.$panel.show();
        $('body > .container').css('margin-bottom', `${ this.$panel.height() }px`);
        this.$panel.find('.nav-tabs li.active a').trigger('show.bs.tab');
    }

    hide() {
        this.$panel.find('.nav-tabs li.active a').trigger('hide.bs.tab');
        $('body > .container').css('margin-bottom', '');
        this.$panel.hide();
    }
}

class Tab {

    protected parent: TabsPanel;

    private $tab: JQuery;

    private $container: JQuery;

    private updater;

    constructor(router: Router, parent: TabsPanel, id: string, action: string, parameters: Params, interval: number) {
        this.$tab = $(`[href="#${id}"]`);
        this.$container = $(`#${id}`);
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
        this.$container.html(data);
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

    private $form: JQuery;

    private $submit: JQuery;

    private mode: AddTorrentMode;

    private modes: AddTorrentMode[];

    constructor(selector: string, submitSelector: string, private router: Router, private afterSubmit: () => void) {
        this.$form = $(selector);
        this.$submit = $(submitSelector);
        this.modes = [];

        this.$submit.click(this.onSubmitClick.bind(this));
    }

    onSubmitClick(event: ClickEvent) {
        if (!$(event.target).hasClass('disabled')) {
            this.submit();
        }
    }

    isDisabled() {
        return !this.enabled;
    }

    enable() {
        this.enabled = true;
        this.$form.addClass('enabled');
    }

    disable() {
        this.enabled = false;
        this.$form.removeClass('enabled');
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
            this.$submit.removeClass('disabled');
        } else {
            this.$submit.addClass('disabled');
        }
    }

    submit() {
        let params: Params = {};

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

    protected $element: JQuery;

    private $btn: JQuery;

    private counter: number = 0;

    private $counter: JQuery;

    private form: AddTorrentForm;

    abstract onEnabled(): void;

    constructor(private inputName: string, elementSelector: string, btnSelector: string, counterSelector: string, form: AddTorrentForm) {

        this.$element = $(elementSelector);
        this.$btn = $(btnSelector);
        this.$counter = $(counterSelector);

        if (form) {
            this.form = form;
            form.registerMode(this);
        }

        this.$btn.click(this.toggle.bind(this));
        $(this).on('enabled', this.onEnabled.bind(this));
    }

    enable() {
        this.enabled = true;
        this.$element.show();
        this.$btn.addClass('active');

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
        this.$element.hide();
        this.$btn.removeClass('active');

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
        this.$counter.text(`(${number})`);
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
            // @ts-ignore
            url: router.generateUrl('uploadTorrent'),
            paramName: 'upload-torrent-file',
            dictDefaultMessage: translator.translate('torrents.dropzone'),
            // @ts-ignore
            previewTemplate: ui.templates.dropzonePreview,
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
        this.$element.click();
    }

    onRemovedFile() {
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    }

    onSuccess(file: any, result: AjaxResponse) {
        this.securityManager.setCsrfToken(result.csrfToken);
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    }

    onError(file: any, result: AjaxResponse) {
        if (typeof result === 'object' && result.hasOwnProperty('csrfToken')) {
            this.securityManager.setCsrfToken(result.csrfToken);
        }
    }

    onSending(file: any, xhr: any, formData: any) {
        formData.append('csrfToken', this.securityManager.getCsrfToken());
    }

    getItems() {
        let items = [],
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

    constructor(inputName: string, elementSelector: string, btnSelector: string, counterSelector: string, form: AddTorrentForm) {
        super(inputName, elementSelector, btnSelector, counterSelector, form);
        $('#add-torrent-magnet-input').on('input', this.onInput.bind(this));
    }

    onEnabled() {
        this.$element.children('textarea').focus();
    }

    onInput() {
        this.setCounter(this.getItems().length);
    }

    getItems() {
        let magnets = [],
            rmagnet = /^magnet:\?[\x20-\x7E]*/,
            lines = ($('#add-torrent-magnet-input').val() as string).split(/(?:\r\n)|\r|\n/);

        for (let i = 0, { length } = lines; i < length; ++i) {
            if (rmagnet.test(lines[i])) {
                magnets.push(lines[i]);
            }
        }

        return magnets;
    }

    clearItems() {
        $('#add-torrent-magnet-input').val('');
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
        $('.torrent-list').html(data);
    }

    updateTorrentList() {
        this.torrentsUpdater.update();
    }

    async onTorrentPause(event: ClickEvent) {
        await this.sendRequest('pauseTorrent', {
            hash: this.getTorrentHash(event.target)
        });

        this.updateTorrentList();
    }

    async onTorrentResume(event: ClickEvent) {
        await this.sendRequest('resumeTorrent', {
            hash: this.getTorrentHash(event.target)
        });

        this.updateTorrentList();
    }

    async onTorrentRemove(event: ClickEvent) {
        await this.sendRequest('removeTorrent', {
            hash: this.getTorrentHash(event.target)
        });

        this.updateTorrentList();
    }

    initializeTorrentsList() {
        this.torrentsUpdater = new Updater(this.router,'listTorrents', {}, this.onUpdateTorrents, torrentListTimeout);
        this.torrentsUpdater.start();

        $(document).on('click', '.torrent-pause', this.onTorrentPause.bind(this));
        $(document).on('click', '.torrent-resume', this.onTorrentResume.bind(this));
        $(document).on('click', '.torrent-remove', this.onTorrentRemove.bind(this));
    }

    onShowDetails(event: ClickEvent) {
        this.torrentPanel.toggleHash(this.getTorrentHash(event.target));
    }

    initializeTorrentPanel() {
        this.torrentPanel = new TorrentPanel();
        this.trackersTab = new TorrentPanelTab(this.router, this.torrentPanel, 'torrent-trackers', 'listTrackers', {}, trackerListTimeout);

        $(document).on('click', '.torrent-detail', this.onShowDetails.bind(this));
    }

    initializeAddTorrentForm() {
        const addTorrentForm = new AddTorrentForm('#add-torrent-form', '#add-torrent-submit', this.router, () => {
            this.torrentsUpdater.update();
        });
        // @ts-ignore
        this.addTorrentFileMode = new AddTorrentFileMode(this.router, this.translator, this.ui, this.securityManager, 'add-torrent-files', '#add-torrent-file-drop', '#add-torrent-file', '#add-torrent-file-counter', addTorrentForm);
        this.addTorrentMagnetMode = new AddTorrentMagnetMode('add-torrent-magnets', '#add-torrent-magnet-wrapper', '#add-torrent-magnet', '#add-torrent-magnet-counter', addTorrentForm);
    }
}

Application.create().run(TorrentsPage);
