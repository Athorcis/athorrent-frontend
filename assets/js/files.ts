import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {Router} from './core/router';
import {decodeBase64} from "./core/utils";

class FilesPage extends AbstractPage {

    init() {
        on(document, 'click', new Map([
            ['.add-sharing', this.onSharingAdd],
            ['.sharing-remove', this.onSharingRemove],
            ['.sharing-link', this.onSharingLink],
            ['.file-remove', this.onFileRemove],
            ['.dropdown-toggle', this.onDropDownButtonClicked]
        ]));
    }

    getFilePath(element: HTMLElement) {
        return decodeBase64(this.getItemId('file', element));
    }

    getSharingToken(element: HTMLElement, selector: string) {
        return this.getItemId('sharing', element, selector);
    }

    getFileName(element: HTMLElement) {
        return this.getItemAttr('file', element, 'name');
    }

    getFileMimeType(element: HTMLElement) {
        return this.getItemData('file', element, 'mime');
    }

    isFilePlayable(element: HTMLElement): CanPlayTypeResult {
        const mimeType = this.getFileMimeType(element);

        if (!mimeType) {
            return "";
        }

        return this.detectMediaTypeSupport(mimeType);
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

    onDropDownButtonClicked = (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        const dropdown = target.closest('.dropdown') as HTMLDivElement;

        if (dropdown.classList.contains('open') || dropdown.dataset.mimeTypeChecked) {
            return;
        }

        const playable = this.isFilePlayable(target);

        if (playable === "") {
            const button = target.closest('.dropdown-toggle');
            const playItem = button.nextElementSibling.querySelector('.play-item') as HTMLElement|undefined;

            if (playItem) {
                playItem.style.display = 'none';
            }
        }

        dropdown.dataset.mimeTypeChecked = "true";
    }

    detectMediaTypeSupport(mimeType: string): CanPlayTypeResult {
        const match = mimeType.match(/^(audio|video)\//);

        if (match) {
            const type = match[1] as 'audio' | 'video';
            const mediaEl = document.createElement(type);

            return mediaEl.canPlayType(mimeType);
        }

        return "";
    }
}

Application.create().run(FilesPage);
