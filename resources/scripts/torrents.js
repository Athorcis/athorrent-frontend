/* eslint-env browser */

import $ from 'jquery';
import Dropzone from 'dropzone';
import athorrent from 'athorrent';

let torrentListTimeout = 2000,
    trackerListTimeout = 5000;

athorrent.Updater = function (action, parameters, success, interval) {
    this.action = action;
    this.parameters = parameters;
    this.success = success;
    this.interval = interval;
};

athorrent.Updater.prototype = {
    action: '',

    parameters: null,

    success: null,

    interval: 0,

    intervalId: -1,

    jqXhr: null,

    start(fireNow) {
        if (this.intervalId === -1) {
            if (fireNow) {
                this.intervalCallback();
            }

            this.intervalId = setInterval(this.intervalCallback.bind(this), this.interval);
        }
    },

    stop() {
        if (this.intervalId > -1) {
            clearInterval(this.intervalId);
            this.intervalId = -1;

            if (this.jqXHR) {
                this.jqXHR.abort();
                this.jqXHR = null;
            }
        }
    },

    update() {
        if (this.intervalId > -1) {
            this.stop();
            this.start(true);
        }
    },

    intervalCallback() {
        if (this.jqXHR) {
            this.jqXHR.abort();
        }

        this.jqXhr = athorrent.ajax[this.action](this.parameters, this.internalSuccess.bind(this), { cache: false });
    },

    internalSuccess(data) {
        this.jqXhr = null;
        this.success(data);
    },

    setParameters(parameters) {
        this.parameters = parameters;
        this.update();
    }
};

athorrent.TabsPanel = function (selector) {
    if (arguments.length === 0) {
        return;
    }

    this.tabMap = {};
    this.$panel = $(selector);
    this.$tabs = this.$panel.find('.nav-tabs a');

    this.$tabs.on('click', this.onClick.bind(this));
};

athorrent.TabsPanel.prototype = {
    tabMap: null,

    $panel: null,

    $tabs: null,

    addTab(id, tab) {
        this.tabMap[id] = tab;
    },

    getCurrentTab() {
        return this.tabMap[this.$panel.find('.nav-tabs li.active a').attr('href').substr(1)];
    },

    onClick(event) {
        event.preventDefault();
        $(event.target).tab('show');
    },

    isVisible() {
        return this.$panel.is(':visible');
    },

    show() {
        this.$panel.show();
        $('body > .container').css('margin-bottom', `${ this.$panel.height() }px`);
        this.$panel.find('.nav-tabs li.active a').trigger('show.bs.tab');
    },

    hide() {
        this.$panel.find('.nav-tabs li.active a').trigger('hide.bs.tab');
        $('body > .container').css('margin-bottom', '');
        this.$panel.hide();
    }
};

athorrent.Tab = function (parent, id, action, parameters, interval) {
    if (arguments.length === 0) {
        return;
    }

    this.$tab = $(`[href="#${id}"]`);
    this.$container = $(`#${id}`);
    this.updater = new athorrent.Updater(action, parameters, this.onUpdate.bind(this), interval);

    if (parent) {
        this.parent = parent;
        parent.addTab(id, this);
    }

    this.$tab.on('show.bs.tab', this.onShow.bind(this));
    this.$tab.on('hide.bs.tab', this.onHide.bind(this));
};

athorrent.Tab.prototype = {
    parent: null,

    $tab: null,

    $container: null,

    updater: null,

    setParameters(parameters) {
        this.updater.setParameters(parameters);
    },

    onUpdate(data) {
        this.$container.html(data);
    },

    onShow() {
        this.updater.start(true);
    },

    onHide() {
        this.updater.stop();
    }
};

$.extend(athorrent, {
    getTorrentHash(element) {
        return this.getItemId('torrent', element);
    },

    onUpdateTorrents(data) {
        $('.torrent-list').html(data);
    },

    updateTorrentList() {
        this.torrentsUpdater.update();
    },

    onTorrentPause(event) {
        this.ajax.pauseTorrent({
            hash: this.getTorrentHash(event.target)
        }, this.updateTorrentList.bind(this));
    },

    onTorrentResume(event) {
        this.ajax.resumeTorrent({
            hash: this.getTorrentHash(event.target)
        }, this.updateTorrentList.bind(this));
    },

    onTorrentRemove(event) {
        this.ajax.removeTorrent({
            hash: this.getTorrentHash(event.target)
        }, this.updateTorrentList.bind(this));
    },

    initializeTorrentsList() {
        this.torrentsUpdater = new athorrent.Updater('listTorrents', {}, athorrent.onUpdateTorrents, torrentListTimeout);
        this.torrentsUpdater.start();

        $(document).on('click', '.torrent-pause', athorrent.onTorrentPause.bind(athorrent));
        $(document).on('click', '.torrent-resume', athorrent.onTorrentResume.bind(athorrent));
        $(document).on('click', '.torrent-remove', athorrent.onTorrentRemove.bind(athorrent));
    },

    onShowDetails(event) {
        this.torrentPanel.toggleHash(athorrent.getTorrentHash(event.target));
    },

    initializeTorrentPanel() {
        this.torrentPanel = new athorrent.TorrentPanel();
        this.trackersTab = new athorrent.TorrentPanelTab(this.torrentPanel, 'torrent-trackers', 'listTrackers', {}, trackerListTimeout);

        $(document).on('click', '.torrent-detail', this.onShowDetails.bind(this));
    },

    initializeAddTorrentForm() {
        this.addTorrentForm = new athorrent.AddTorrentForm('#add-torrent-form', '#add-torrent-submit');
        this.addTorrentFileMode = new athorrent.AddTorrentFileMode('add-torrent-files', '#add-torrent-file-drop', '#add-torrent-file', '#add-torrent-file-counter', this.addTorrentForm);
        this.addTorrentMagnetMode = new athorrent.AddTorrentMagnetMode('add-torrent-magnets', '#add-torrent-magnet-wrapper', '#add-torrent-magnet', '#add-torrent-magnet-counter', this.addTorrentForm);
    }
});

athorrent.TorrentPanel = function () {
    athorrent.TabsPanel.call(this, '.torrent-panel');
};

athorrent.TorrentPanel.prototype = $.extend(new athorrent.TabsPanel(), {
    hash: '',

    toggleHash(hash) {
        if (this.isVisible()) {
            if (this.hash === hash) {
                this.hide();
            } else {
                this.hash = hash;
                this.getCurrentTab().setHash(hash);
            }
        } else {
            this.setHash(hash);
            this.show();
        }
    },

    setHash(hash) {
        if (this.hash !== hash) {
            this.hash = hash;
            this.getCurrentTab().setHash(hash);
        }
    },

    getHash() {
        return this.hash;
    }
});

athorrent.TorrentPanelTab = function (parent, id, action, parameters, interval) {
    athorrent.Tab.call(this, parent, id, action, parameters, interval);
};

athorrent.TorrentPanelTab.prototype = $.extend(new athorrent.Tab(), {
    hash: '',

    setHash(hash) {
        if (this.hash !== hash) {
            this.hash = hash;
            this.setParameters({ hash });
        }
    },

    onShow() {
        this.setHash(this.parent.getHash());
        athorrent.Tab.prototype.onShow.call(this);
    }
});

athorrent.AddTorrentForm = function (selector, submitSelector) {
    this.$form = $(selector);
    this.$submit = $(submitSelector);
    this.modes = [];

    this.$submit.click(this.onSubmitClick.bind(this));
};

athorrent.AddTorrentForm.prototype = {
    enabled: false,

    $form: null,

    $submit: null,

    mode: null,

    modes: null,

    onSubmitClick(event) {
        if (!$(event.target).hasClass('disabled')) {
            this.submit();
        }
    },

    isDisabled() {
        return !this.enabled;
    },

    enable() {
        this.enabled = true;
        this.$form.addClass('enabled');
    },

    disable() {
        this.enabled = false;
        this.$form.removeClass('enabled');
    },

    setMode(mode) {
        this.mode = mode;
    },

    getMode() {
        return this.mode;
    },

    registerMode(mode) {
        this.modes.push(mode);
    },

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
    },

    submit() {
        let params = {};

        for (let i = 0, { length } = this.modes; i < length; ++i) {
            params[this.modes[i].getInputName()] = this.modes[i].getItems();
        }

        athorrent.ajax.addTorrents(params, () => {
            athorrent.torrentsUpdater.update();
        });

        for (let i = 0, { length } = this.modes; i < length; ++i) {
            this.modes[i].clearItems();
        }

        this.mode.disable();
        this.mode = null;
    }
};

athorrent.AddTorrentMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
    if (arguments.length === 0) {
        return;
    }

    this.inputName = inputName;
    this.$element = $(elementSelector);
    this.$btn = $(btnSelector);
    this.$counter = $(counterSelector);

    if (form) {
        this.form = form;
        form.registerMode(this);
    }

    this.$btn.click(this.toggle.bind(this));
    $(this).on('enabled', this.onEnabled.bind(this));
};

athorrent.AddTorrentMode.prototype = {
    enabled: false,

    $element: null,

    $btn: null,

    counter: 0,

    $counter: null,

    form: null,

    onEnabled: $.noop,

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
    },

    disable(recursive) {
        this.enabled = false;
        this.$element.hide();
        this.$btn.removeClass('active');

        if (!recursive) {
            this.form.disable();
            this.form.setMode(null);
        }
    },

    toggle() {
        if (this.enabled) {
            this.disable();
        } else {
            this.enable();
        }
    },

    setCounter(number) {
        this.counter = number;
        this.$counter.text(`(${number})`);
        this.form.updateFileCounter();
    },

    getCounter() {
        return this.counter;
    },

    getInputName() {
        return this.inputName;
    }
};

athorrent.AddTorrentFileMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
    athorrent.AddTorrentMode.call(this, inputName, elementSelector, btnSelector, counterSelector, form);

    this.dropzone = new Dropzone(elementSelector, {
        url: athorrent.routes.uploadTorrent.torrents[1],
        paramName: 'upload-torrent-file',
        dictDefaultMessage: athorrent.trans('torrents.dropzone'),
        previewTemplate: athorrent.templates.dropzonePreview,
        acceptedFiles: '.torrent',
        parallelUploads: 1,
        maxFilesize: 1
    });

    this.dropzone.on('removedfile', this.onRemovedFile.bind(this));
    this.dropzone.on('success', this.onSuccess.bind(this));
    this.dropzone.on('error', this.onError.bind(this));

    this.dropzone.on('sending', this.onSending.bind(this));
};

athorrent.AddTorrentFileMode.prototype = $.extend(new athorrent.AddTorrentMode(), {
    dropzone: null,

    onEnabled() {
        this.$element.click();
    },

    onRemovedFile() {
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    },

    onSuccess(file, result) {
        athorrent.csrfToken = result.csrfToken;
        this.setCounter(this.dropzone.getAcceptedFiles().length);
    },

    onError(file, result) {
        if (typeof result === 'object' && result.hasOwnProperty('csrfToken')) {
            athorrent.csrfToken = result.csrfToken;
        }
    },

    onSending(file, xhr, formData) {
        formData.append('csrfToken', athorrent.csrfToken);
    },

    getItems() {
        let items = [],
            files = this.dropzone.getAcceptedFiles();

        for (let i = 0, { length } = files; i < length; ++i) {
            items.push(files[i].name);
        }

        return items;
    },

    clearItems() {
        this.dropzone.removeAllFiles(true);
        this.setCounter(0);
    }
});

athorrent.AddTorrentMagnetMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
    athorrent.AddTorrentMode.call(this, inputName, elementSelector, btnSelector, counterSelector, form);
    $('#add-torrent-magnet-input').on('input', this.onInput.bind(this));
};

athorrent.AddTorrentMagnetMode.prototype = $.extend(new athorrent.AddTorrentMode(), {
    onEnabled() {
        this.$element.children('textarea').focus();
    },

    onInput() {
        this.setCounter(this.getItems().length);
    },

    getItems() {
        let magnets = [],
            rmagnet = /^magnet:\?[\x20-\x7E]*/,
            lines = $('#add-torrent-magnet-input').val().split(/(?:\r\n)|\r|\n/);

        for (let i = 0, { length } = lines; i < length; ++i) {
            if (rmagnet.test(lines[i])) {
                magnets.push(lines[i]);
            }
        }

        return magnets;
    },

    clearItems() {
        $('#add-torrent-magnet-input').val('');
        this.setCounter(0);
    }
});

navigator.registerProtocolHandler('magnet', `${ location.origin }/user/torrents/magnet?magnet=%s`, 'Athorrent');

athorrent.initializeTorrentsList();
athorrent.initializeTorrentPanel();
athorrent.initializeAddTorrentForm();
