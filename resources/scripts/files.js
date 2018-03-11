/* eslint-env browser */

import $ from 'jquery';
import athorrent from 'athorrent';
import base64 from 'base64';

Object.assign(athorrent, {
    getFilePath(element) {
        return base64.Base64.decode(this.getItemId('file', element));
    },

    getSharingToken(element, selector) {
        return this.getItemId('sharing', element, selector);
    },

    getFileName(element) {
        return this.getItemAttr('file', element, 'name');
    },

    modalSharingLink(link) {
        athorrent.showModal(athorrent.trans('files.sharingLink'), `<a href="${link}">${link}</a>`);
    },

    updateFileList() {
        athorrent.ajax.listFiles({}, (data) => {
            $('.file-list').html(data);
        });
    },

    onSharingAdd(event) {
        let { target } = event;

        this.ajax.addSharing({
            path: this.getFilePath(target)
        }, (data) => {
            athorrent.modalSharingLink(data);
            athorrent.updateFileList();
        });
    },

    onSharingRemove(event) {
        this.ajax.removeSharing({
            token: athorrent.getSharingToken(event.target, '.sharing-remove')
        }, () => {
            athorrent.updateFileList();
        });
    },

    onSharingLink(event) {
        athorrent.modalSharingLink($(event.target).attr('href'));
        event.preventDefault();
    },

    onFileRemove(event) {
        let { target } = event;

        if (window.confirm(`Êtes-vous sur de vouloir supprimer "${ this.getFileName(target) }" ?`)) {
            this.ajax.removeFile({
                path: this.getFilePath(target)
            }, $.proxy(() => {
                this.getItem('file', target).remove();
            }, this));
        }
    }
});

$(document).on('click', '.add-sharing', athorrent.onSharingAdd.bind(athorrent));
$(document).on('click', '.sharing-remove', athorrent.onSharingRemove.bind(athorrent));
$(document).on('click', '.sharing-link', athorrent.onSharingLink.bind(athorrent));
$(document).on('click', '.file-remove', athorrent.onFileRemove.bind(athorrent));
