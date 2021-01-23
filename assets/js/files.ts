/* eslint-env browser */

import $ from 'jquery';
import {decode} from 'js-base64';
import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import ClickEvent = JQuery.ClickEvent;

class FilesPage extends AbstractPage {

    init() {
        $(document).on('click', '.add-sharing', this.onSharingAdd.bind(this));
        $(document).on('click', '.sharing-remove', this.onSharingRemove.bind(this));
        $(document).on('click', '.sharing-link', this.onSharingLink.bind(this));
        $(document).on('click', '.file-remove', this.onFileRemove.bind(this));
    }

    getFilePath(element: HTMLElement) {
        return decode(this.getItemId('file', element));
    }

    getSharingToken(element: HTMLElement, selector: string) {
        return this.getItemId('sharing', element, selector);
    }

    getFileName(element: HTMLElement) {
        return this.getItemAttr('file', element, 'name');
    }

    modalSharingLink(link: string) {
        this.ui.showModal(this.translate('files.sharingLink'), `<a href="${link}">${link}</a>`);
    }

    updateFileList() {
        this.sendRequest('listFiles').then(data => {
            $('.file-list').html(data);
        });
    }

    onSharingAdd(event: ClickEvent) {
        let { target } = event;

        this.sendRequest('addSharing',{
            path: this.getFilePath(target)
        }).then(data => {
            this.modalSharingLink(data);
            this.updateFileList();
        });
    }

    async onSharingRemove(event: ClickEvent) {
        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(event.target, '.sharing-remove')
        });

        this.updateFileList();
    }

    onSharingLink(event: ClickEvent) {
        this.modalSharingLink($(event.target).attr('href'));
        event.preventDefault();
    }

    async onFileRemove(event: ClickEvent) {
        let { target } = event;

        if (window.confirm(`Êtes-vous sur de vouloir supprimer "${ this.getFileName(target) }" ?`)) {

            await this.sendRequest('removeFile',{
                path: this.getFilePath(target)
            });

            this.getItem('file', target).remove();
        }
    }
}

Application.create().run(FilesPage);
