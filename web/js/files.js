/* eslint-env browser, amd */

require(['jquery', 'athorrent', 'base64'], function ($, athorrent, base64) {
    'use strict';

    $.extend(athorrent, {
        getFilePath: function (element) {
            return base64.decode(this.getItemId('file', element));
        },

        getSharingToken: function (element, selector) {
            return this.getItemId('sharing', element, selector);
        },

        getFileName: function (element) {
            return this.getItemAttr('file', element, 'name');
        },

        modalSharingLink: function (link) {
            athorrent.showModal(athorrent.trans('files.sharingLink'), '<a href="' + link + '">' + link + '</a>');
        },

        updateFileList: function () {
            athorrent.ajax.listFiles({}, function (data) {
                $('.file-list').html(data);
            });
        },

        onSharingAdd: function (event) {
            var target = event.target;

            this.ajax.addSharing({
                path: this.getFilePath(target)
            }, $.proxy(function (data) {
                athorrent.modalSharingLink(data);
                athorrent.updateFileList();
            }));
        },

        onSharingRemove: function (event) {
            this.ajax.removeSharing({
                token: athorrent.getSharingToken(event.target, '.sharing-remove')
            }, function () {
                athorrent.updateFileList();
            });
        },

        onSharingLink: function (event) {
            athorrent.modalSharingLink($(event.target).attr('href'));
            event.preventDefault();
        },

        onFileRemove: function (event) {
            var target = event.target;

            if (window.confirm('Êtes-vous sur de vouloir supprimer "' + this.getFileName(target) + '" ?')) {
                this.ajax.removeFile({
                    path: this.getFilePath(target)
                }, $.proxy(function () {
                    this.getItem('file', target).remove();
                }, this));
            }
        },

        onFileDirectLink: function (event) {
            var target = event.target;

            this.ajax.getDirectLink({
                path: this.getFilePath(target)
            }, $.proxy(function (link) {
                athorrent.showModal(athorrent.trans('files.directLink'), '<a href="' + link + '">' + link + '</a>');
            }, this));
        }
    });

    $(document).on('click', '.add-sharing', $.proxy(athorrent.onSharingAdd, athorrent));
    $(document).on('click', '.sharing-remove', $.proxy(athorrent.onSharingRemove, athorrent));
    $(document).on('click', '.sharing-link', $.proxy(athorrent.onSharingLink, athorrent));
    $(document).on('click', '.file-remove', $.proxy(athorrent.onFileRemove, athorrent));
    $(document).on('click', '.file-direct-link', $.proxy(athorrent.onFileDirectLink, athorrent));
});
