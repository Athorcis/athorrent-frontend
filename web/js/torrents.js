/*jslint browser: true, nomen: true, plusplus: true, white: true */
/*global require */

require(['jquery', 'athorrent', 'dropzone'], function (jQuery, athorrent, Dropzone) {
    'use strict';

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

        start: function (fireNow) {
            if (this.intervalId === -1) {
                if (fireNow) {
                    this.intervalCallback();
                }

                this.intervalId = setInterval(jQuery.proxy(this.intervalCallback, this), this.interval);
            }
        },

        stop: function () {
            if (this.intervalId > -1) {
                clearInterval(this.intervalId);
                this.intervalId = -1;

                if (this.jqXHR) {
                    this.jqXHR.abort();
                    this.jqXHR = null;
                }
            }
        },

        update: function () {
            if (this.intervalId > -1) {
                this.stop();
                this.start(true);
            }
        },

        intervalCallback: function () {
            if (this.jqXHR) {
                this.jqXHR.abort();
            }

            this.jqXhr = athorrent.ajax[this.action](this.parameters, jQuery.proxy(this.internalSuccess, this), { cache: false });
        },

        internalSuccess: function (data) {
            this.jqXhr = null;
            this.success(data);
        },

        setParameters: function (parameters) {
            this.parameters = parameters;
            this.update();
        }
    };

    athorrent.TabsPanel = function (selector) {
        this.tabMap = {};
        this.$panel = jQuery(selector);
        this.$tabs = this.$panel.find('.nav-tabs a');

        this.$tabs.on('click', jQuery.proxy(this.onClick, this));
    };

    athorrent.TabsPanel.prototype = {
        tabMap: null,

        $panel: null,

        $tabs: null,

        addTab: function (id, tab) {
            this.tabMap[id] = tab;
        },

        getCurrentTab: function () {
            return this.tabMap[this.$panel.find('.nav-tabs li.active a').attr('href').substr(1)];
        },

        onClick: function (event) {
            event.preventDefault();
            jQuery(event.target).tab('show');
        },

        isVisible: function () {
            return this.$panel.is(':visible');
        },

        show: function () {
            this.$panel.show();
            jQuery('body > .container').css('margin-bottom', this.$panel.height() + 'px');
            this.$panel.find('.nav-tabs li.active a').trigger('show.bs.tab');
        },

        hide: function () {
            this.$panel.find('.nav-tabs li.active a').trigger('hide.bs.tab');
            jQuery('body > .container').css('margin-bottom', '');
            this.$panel.hide();
        }
    };

    athorrent.Tab = function (parent, id, action, parameters, interval) {
        this.$tab = jQuery('[href=#' + id + ']');
        this.$container = jQuery('#' + id);
        this.updater = new athorrent.Updater(action, parameters, jQuery.proxy(this.onUpdate, this), interval);

        if (parent) {
            this.parent = parent;
            parent.addTab(id, this);
        }

        this.$tab.on('show.bs.tab', jQuery.proxy(this.onShow, this));
        this.$tab.on('hide.bs.tab', jQuery.proxy(this.onHide, this));
    };

    athorrent.Tab.prototype = {
        parent: null,

        $tab: null,

        $container: null,

        updater: null,

        setParameters: function (parameters) {
            this.updater.setParameters(parameters);
        },

        onUpdate: function (data) {
            this.$container.html(data);
        },

        onShow: function () {
            this.updater.start(true);
        },

        onHide: function () {
            this.updater.stop();
        }
    };

    jQuery.extend(athorrent, {
        getTorrentHash: function (element) {
            return this.getItemId('torrent', element);
        },

        onUpdateTorrents: function (data) {
            jQuery('.torrent-list').html(data);
        },

        onTorrentPause: function (event) {
            this.ajax.pauseTorrent({
                hash: this.getTorrentHash(event.target)
            }, jQuery.proxy(function () {
                this.torrentsUpdater.update();
            }, this));
        },

        onTorrentResume: function (event) {
            this.ajax.resumeTorrent({
                hash: this.getTorrentHash(event.target)
            }, jQuery.proxy(function () {
                this.torrentsUpdater.update();
            }, this));
        },

        onTorrentRemove: function (event) {
            this.ajax.removeTorrent({
                hash: this.getTorrentHash(event.target)
            }, jQuery.proxy(function () {
                this.torrentsUpdater.update();
            }, this));
        },

        initializeTorrentsList: function () {
            this.torrentsUpdater = new athorrent.Updater('listTorrents', {}, athorrent.onUpdateTorrents, 2000);
            this.torrentsUpdater.start();

            jQuery(document).on('click', '.torrent-pause', jQuery.proxy(athorrent.onTorrentPause, athorrent));
            jQuery(document).on('click', '.torrent-resume', jQuery.proxy(athorrent.onTorrentResume, athorrent));
            jQuery(document).on('click', '.torrent-remove', jQuery.proxy(athorrent.onTorrentRemove, athorrent));
        },

        onShowDetails: function (event) {
            this.torrentPanel.toggleHash(athorrent.getTorrentHash(event.target));
        },

        initializeTorrentPanel: function () {
            this.torrentPanel = new athorrent.TorrentPanel();
            this.trackersTab = new athorrent.TorrentPanelTab(this.torrentPanel, 'torrent-trackers', 'listTrackers', {}, 5000);

            jQuery(document).on('click', '.torrent-detail', jQuery.proxy(this.onShowDetails, this));
        },

        initializeAddTorrentForm: function () {
            this.addTorrentForm = new athorrent.AddTorrentForm('#add-torrent-form', '#add-torrent-submit');
            this.addTorrentFileMode = new athorrent.AddTorrentFileMode('add-torrent-files', '#add-torrent-file-drop', '#add-torrent-file', '#add-torrent-file-counter', this.addTorrentForm);
            this.addTorrentMagnetMode = new athorrent.AddTorrentMagnetMode('add-torrent-magnets', '#add-torrent-magnet-wrapper', '#add-torrent-magnet', '#add-torrent-magnet-counter', this.addTorrentForm);
        }
    });

    athorrent.TorrentPanel = function () {
        athorrent.TabsPanel.call(this, '.torrent-panel');
    };

    athorrent.TorrentPanel.prototype = jQuery.extend(new athorrent.TabsPanel(), {
        hash: '',

        toggleHash: function (hash) {
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

        setHash: function (hash) {
            if (this.hash !== hash) {
                this.hash = hash;
                this.getCurrentTab().setHash(hash);
            }
        },

        getHash: function () {
            return this.hash;
        }
    });

    athorrent.TorrentPanelTab = function (parent, id, action, parameters, interval) {
        athorrent.Tab.call(this, parent, id, action, parameters, interval);
    };

    athorrent.TorrentPanelTab.prototype = jQuery.extend(new athorrent.Tab(), {
        hash: '',

        setHash: function (hash) {
            if (this.hash !== hash) {
                this.hash = hash;
                this.setParameters({ hash: hash });
            }
        },

        onShow: function () {
            this.setHash(this.parent.getHash());
            athorrent.Tab.prototype.onShow.call(this);
        }
    });

    athorrent.AddTorrentForm = function (selector, submitSelector) {
        this.$form = jQuery(selector);
        this.$submit = jQuery(submitSelector);
        this.modes = [];

        this.$submit.click(jQuery.proxy(this.onSubmitClick, this));
    };

    athorrent.AddTorrentForm.prototype = {
        enabled: false,

        $form: null,

        $submit: null,

        mode: null,

        modes: null,

        onSubmitClick: function (event) {
            if (!jQuery(event.target).hasClass('disabled')) {
                this.submit();
            }
        },

        isDisabled: function () {
            return !this.enabled;
        },

        enable: function () {
            this.enabled = true;
            this.$form.addClass('enabled');
        },

        disable: function () {
            this.enabled = false;
            this.$form.removeClass('enabled');
        },

        setMode: function (mode) {
            this.mode = mode;
        },

        getMode: function () {
            return this.mode;
        },

        registerMode: function (mode) {
            this.modes.push(mode);
        },

        updateFileCounter: function () {
            var i, length,
                count = 0;

            for (i = 0, length = this.modes.length; i < length; ++i) {
                count += this.modes[i].getCounter();
            }

            if (count > 0) {
                this.$submit.removeClass('disabled');
            } else {
                this.$submit.addClass('disabled');
            }
        },

        submit: function () {
            var i, length,
                params = {};

            for (i = 0, length = this.modes.length; i < length; ++i) {
                params[this.modes[i].getInputName()] = this.modes[i].getItems();
            }

            athorrent.ajax.addTorrents(params, function () {
                athorrent.torrentsUpdater.update();
            });

            for (i = 0, length = this.modes.length; i < length; ++i) {
                this.modes[i].clearItems();
            }

            this.mode.disable();
            this.mode = null;
        }
    };

    athorrent.AddTorrentMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
        this.inputName = inputName;
        this.$element = jQuery(elementSelector);
        this.$btn = jQuery(btnSelector);
        this.$counter = jQuery(counterSelector);

        if (form) {
            this.form = form;
            form.registerMode(this);
        }

        this.$btn.click(jQuery.proxy(this.toggle, this));
        jQuery(this).on('enabled', jQuery.proxy(this.onEnabled, this));
    };

    athorrent.AddTorrentMode.prototype = {
        enabled: false,

        $element: null,

        $btn: null,

        counter: 0,

        $counter: null,

        form: null,

        onEnabled: function () {},

        enable: function () {
            this.enabled = true;
            this.$element.show();
            this.$btn.addClass('active');

            if (this.form.isDisabled()) {
                this.form.enable();
            } else {
                this.form.getMode().disable(true);
            }

            this.form.setMode(this);

            jQuery(this).trigger('enabled');
        },

        disable: function (recursive) {
            this.enabled = false;
            this.$element.hide();
            this.$btn.removeClass('active');

            if (!recursive) {
                this.form.disable();
                this.form.setMode(null);
            }
        },

        toggle: function () {
            if (this.enabled) {
                this.disable();
            } else {
                this.enable();
            }
        },

        setCounter: function (number) {
            this.counter = number;
            this.$counter.text('(' + number + ')');
            this.form.updateFileCounter();
        },

        getCounter: function () {
            return this.counter;
        },

        getInputName: function () {
            return this.inputName;
        }
    };

    athorrent.AddTorrentFileMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
        athorrent.AddTorrentMode.call(this, inputName, elementSelector, btnSelector, counterSelector, form);

        this.dropzone = new Dropzone(elementSelector, {
            url: athorrent.routes.uploadTorrent.torrents_[1],
            paramName: 'upload-torrent-file',
            dictDefaultMessage: athorrent.trans('torrents.dropzone'),
            previewTemplate: athorrent.templates.dropzonePreview,
    //        acceptedFiles: '.torrent',
            parallelUploads: 1,
            maxFilesize: 1
        });

        this.dropzone.on('removedfile', jQuery.proxy(this.onRemovedFile, this));
        this.dropzone.on('success', jQuery.proxy(this.onSuccess, this));
        this.dropzone.on('error', jQuery.proxy(this.onError, this));

        this.dropzone.on('sending', jQuery.proxy(this.onSending, this));
    };

    athorrent.AddTorrentFileMode.prototype = jQuery.extend(new athorrent.AddTorrentMode(), {
        dropzone: null,

        onEnabled: function () {
            this.$element.click();
        },

        onRemovedFile: function () {
            this.setCounter(this.dropzone.getAcceptedFiles().length);
        },

        onSuccess: function (file, result) {
            athorrent.csrf = result.csrf;
            this.setCounter(this.dropzone.getAcceptedFiles().length);
        },

        onError: function (file, result) {
            if (typeof result === 'object' && result.hasOwnProperty('csrf')) {
                athorrent.csrf = result.csrf;
            }
        },

        onSending: function (file, xhr, formData) {
            formData.append('csrf', athorrent.csrf);
        },

        getItems: function () {
            var i, length,
                items = [],
                files = this.dropzone.getAcceptedFiles();

            for (i = 0, length = files.length; i < length; ++i) {
                items.push(files[i].name);
            }

            return items;
        },

        clearItems: function () {
            this.dropzone.removeAllFiles(true);
            this.setCounter(0);
        }
    });

    athorrent.AddTorrentMagnetMode = function (inputName, elementSelector, btnSelector, counterSelector, form) {
        athorrent.AddTorrentMode.call(this, inputName, elementSelector, btnSelector, counterSelector, form);
        jQuery('#add-torrent-magnet-input').on('input', jQuery.proxy(this.onInput, this));
    };

    athorrent.AddTorrentMagnetMode.prototype = jQuery.extend(new athorrent.AddTorrentMode(), {
        onEnabled: function () {
            this.$element.children('textarea').focus();
        },

        onInput: function () {
            this.setCounter(this.getItems().length);
        },

        getItems: function () {
            var i, length,
                magnets = [],
                rmagnet = /^magnet:\?[\x20-\x7E]*/, // jslint ignore:line
                lines = jQuery('#add-torrent-magnet-input').val().split(/(?:\r\n)|\r|\n/);

            for (i = 0, length = lines.length; i < length; ++i) {
                if (rmagnet.test(lines[i])) {
                    magnets.push(lines[i]);
                }
            }

            return magnets;
        },

        clearItems: function () {
            jQuery('#add-torrent-magnet-input').val('');
            this.setCounter(0);
        }
    });

    navigator.registerProtocolHandler('magnet', location.origin + '/user/torrents/magnet?magnet=%s', 'Athorrent');

    athorrent.initializeTorrentsList();
    athorrent.initializeTorrentPanel();
    athorrent.initializeAddTorrentForm();
});

require(['dropzone'], function (Dropzone) {
    'use strict';
    Dropzone.autoDiscover = false;
});
