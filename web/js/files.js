/*jslint browser: true, white: true */
/*global require */

require(['jquery', 'athorrent', 'base64'], function (jQuery, athorrent, base64) {
    'use strict';

    jQuery.extend(athorrent, {
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
                jQuery('.file-list').html(data);
            });
        },

        onSharingAdd: function (event) {
            var target = event.target;

            this.ajax.addSharing({
                path: this.getFilePath(target)
            }, jQuery.proxy(function (data) {
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
            athorrent.modalSharingLink(jQuery(event.target).attr('href'));
            event.preventDefault();
        },

        onFileRemove: function (event) {
            var target = event.target;

            if (window.confirm('Êtes-vous sur de vouloir supprimer "' + this.getFileName(target) + '" ?')) {
                this.ajax.removeFile({
                    path: this.getFilePath(target)
                }, jQuery.proxy(function () {
                    this.getItem('file', target).remove();
                }, this));
            }
        },

        onFileDirectLink: function (event) {
            var target = event.target;

            this.ajax.getDirectLink({
                path: this.getFilePath(target)
            }, jQuery.proxy(function (link) {
                athorrent.showModal(athorrent.trans('files.directLink'), '<a href="' + link + '">' + link + '</a>');
            }, this));
        }
    });

    jQuery(document).on('click', '.add-sharing', jQuery.proxy(athorrent.onSharingAdd, athorrent));
    jQuery(document).on('click', '.sharing-remove', jQuery.proxy(athorrent.onSharingRemove, athorrent));
    jQuery(document).on('click', '.sharing-link', jQuery.proxy(athorrent.onSharingLink, athorrent));
    jQuery(document).on('click', '.file-remove', jQuery.proxy(athorrent.onFileRemove, athorrent));
    jQuery(document).on('click', '.file-direct-link', jQuery.proxy(athorrent.onFileDirectLink, athorrent));
});
