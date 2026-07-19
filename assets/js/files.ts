import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {decodeBase64} from "./core/utils";
import {DropzoneFile} from 'dropzone';
import {DropzoneType, UploadManager} from './core/upload-manager';

class FilesPage extends AbstractPage {

    private uploadManager!: UploadManager;

    init() {
        this.uploadManager = new UploadManager(this.router, this.securityManager, this.ui, this.translator);

        on(document, 'click', new Map([
            ['.add-sharing', this.onSharingAdd],
            ['.sharing-remove', this.onSharingRemove],
            ['.sharing-link', this.onSharingLink],
            ['.file-remove', this.onFileRemove],
            ['.add-file', this.onAddFile],
            ['.add-directory', this.onAddDirectory],
        ]));

        this.initFileDropdowns();
    }

    initFileDropdowns() {
        for (const d of Array.from(document.querySelectorAll('.file-dropdown'))) {
            d.addEventListener('beforetoggle', this.onBeforeFileDropdownToggle as EventListener, { once: true });
        }
    }

    getFilePath(element: HTMLElement) {
        return decodeBase64(this.getItemId('file', element));
    }

    getSharingId(element: HTMLElement, selector: string) {
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
        this.ui.showModal({
            title: 'files.sharingLink',
            content: `<a href="${link}">${link}</a>`,
        });
    }

    async updateFileList() {
        const data = await this.sendRequest<string>('listFiles', { path: this.router.getQueryParam('path')! });

        const content = document.querySelector('.main-header')!.nextElementSibling!;
        const newContent = document.createRange().createContextualFragment(data);

        content.replaceWith(newContent);

        this.initFileDropdowns();
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
            id: this.getSharingId(target, '.sharing-remove')
        });

        this.updateFileList();
    }

    onSharingLink = (event: MouseEvent) => {
        const target = event.target as HTMLAnchorElement;
        this.modalSharingLink(target.getAttribute('href')!);
        event.preventDefault();
    }

    onFileRemove = async (event: MouseEvent) => {
        const target = event.target as HTMLElement;

        if (this.confirm('files.removalConfirmation', { entry: this.getFileName(target) })) {

            await this.sendRequest('removeFile',{
                path: this.getFilePath(target)
            });

            if (document.querySelectorAll('.file').length > 1) {
                this.getItem('file', target).remove();
            }
            else {
                await this.updateFileList();
            }
        }
    }

    onBeforeFileDropdownToggle = (event: ToggleEvent) => {

        if (event.newState === 'open') {
            const dropdownMenu = event.target as HTMLUListElement;

            const playable = this.isFilePlayable(dropdownMenu);

            if (playable === "") {
                const playItem = dropdownMenu.querySelector('.play-item') as HTMLElement|undefined;

                if (playItem) {
                    playItem.setAttribute('hidden', '');
                }
            }
        }
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

    protected triggerFileUpload(type: DropzoneType) {
        const path = this.router.getQueryParam('path') as string | undefined ?? '';

        this.uploadManager.trigger({
            title: 'files.upload',
            route: 'addFile',
            type,

            dropzone: {
                maxFilesize: 1000,
                autoQueue: false,
            },

            init: (dropzone, modal)=> {

                dropzone.on('addedfiles', async (files: DropzoneFile[]) => {

                    // files is typed as an array in dropzone despite being a FileFile at runtime
                    const filenames = Array.from(files).map(file => file.webkitRelativePath || file.name);

                    const result = await this.sendRequest<{ exists: string[] }>('doesFilesExist', {
                        path,
                        filenames,
                    });

                    if (result.exists.length > 0 && !this.confirm('files.overwriteConfirm')) {
                        modal.close();
                        return;
                    }

                    dropzone.enqueueFiles(Array.from(files).filter(file => file.status !== 'error'));
                });

                dropzone.on('sending', (file: DropzoneFile, xhr: XMLHttpRequest, formData: FormData) => {
                    formData.append('path', path);
                    formData.append('overwrite', 'true');
                });
            },

            complete: async (filesUploaded) => {
                if (filesUploaded > 0) {
                    await this.updateFileList();
                }
            }
        });
    }

    onAddFile = () => {
        this.triggerFileUpload('file');
    }

    onAddDirectory = () => {
        this.triggerFileUpload('directory');
    }
}

Application.create().run(FilesPage);
