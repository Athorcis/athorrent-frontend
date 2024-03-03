import {decode} from 'js-base64';
import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {Router} from './core/router';

class FilesPage extends AbstractPage {

    init() {
        on(document, 'click', new Map([
            ['.add-sharing', this.onSharingAdd],
            ['.sharing-remove', this.onSharingRemove],
            ['.sharing-link', this.onSharingLink],
            ['.file-remove', this.onFileRemove],
        ]));
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
        this.ui.showModal('files.sharingLink', `<a href="${link}">${link}</a>`);
    }

    async updateFileList() {
        const data = await this.sendRequest<string>('listFiles', { path: Router.parseQueryParameters().path });
        document.querySelector('.file-list').innerHTML = data;
    }

    onSharingAdd = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        const data = await this.sendRequest<string>('addSharing',{
            path: this.getFilePath(target)
        })

        this.modalSharingLink(data);
        this.updateFileList();
    }

    onSharingRemove = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(target, '.sharing-remove')
        });

        this.updateFileList();
    }

    onSharingLink = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        this.modalSharingLink(target.getAttribute('href'));
        event.preventDefault();
    }

    onFileRemove = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        if (this.confirm(this.translate('files.removalConfirmation', { entry: this.getFileName(target) }))) {

            await this.sendRequest('removeFile',{
                path: this.getFilePath(target)
            });

            this.getItem('file', target).remove();
        }
    }
}

Application.create().run(FilesPage);
