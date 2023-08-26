import {decode} from 'js-base64';
import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';

class FilesPage extends AbstractPage {

    init() {

        document.addEventListener('click', event => {
            const target = event.target as HTMLElement;

            if (target.closest('.add-sharing')) {
                this.onSharingAdd(event);
            }
            else if (target.closest('.sharing-remove')) {
                this.onSharingRemove(event);
            }
            else if (target.closest('.sharing-link')) {
                this.onSharingLink(event);
            }
            else if (target.closest('.file-remove')) {
                this.onFileRemove(event);
            }
        });
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
        this.sendRequest<string>('listFiles').then(data => {
            document.querySelector('.file-list').innerHTML = data;
        });
    }

    onSharingAdd(event: MouseEvent) {
        const target = event.target as HTMLElement;

        this.sendRequest<string>('addSharing',{
            path: this.getFilePath(target)
        }).then(data => {
            this.modalSharingLink(data);
            this.updateFileList();
        });
    }

    async onSharingRemove(event: MouseEvent) {
        const target = event.target as HTMLElement;

        await this.sendRequest('removeSharing', {
            token: this.getSharingToken(target, '.sharing-remove')
        });

        this.updateFileList();
    }

    onSharingLink(event: MouseEvent) {
        const target = event.target as HTMLElement;
        this.modalSharingLink(target.getAttribute('href'));
        event.preventDefault();
    }

    async onFileRemove(event: MouseEvent) {
        const target = event.target as HTMLElement;

        if (window.confirm(`Êtes-vous sur de vouloir supprimer "${ this.getFileName(target) }" ?`)) {

            await this.sendRequest('removeFile',{
                path: this.getFilePath(target)
            });

            this.getItem('file', target).remove();
        }
    }
}

Application.create().run(FilesPage);
