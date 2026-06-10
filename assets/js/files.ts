import '../css/files.scss';
import {AbstractPage} from './core/abstract-page';
import {Application} from './core/application';
import {on} from './core/events';
import {Router} from './core/router';
import {decodeBase64} from "./core/utils";
import Dropzone, {DropzoneFile} from 'dropzone';
import $ from 'jquery';

type DropzoneType = 'file'|'directory';

class FilesPage extends AbstractPage {

    init() {
        on(document, 'click', new Map([
            ['.add-sharing', this.onSharingAdd],
            ['.sharing-remove', this.onSharingRemove],
            ['.sharing-link', this.onSharingLink],
            ['.file-remove', this.onFileRemove],
            ['.dropdown-toggle', this.onDropDownButtonClicked],
            ['.add-file', this.onAddFile],
            ['.add-directory', this.onAddDirectory],
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
        const data = await this.sendRequest<string>('listFiles', { path: Router.parseQueryParameters()['path'] });
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

        if (dropdown.classList.contains('open') || dropdown.dataset['mimeTypeChecked']) {
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

        dropdown.dataset['mimeTypeChecked'] = "true";
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

    protected dropzoneMap = new Map<DropzoneType, [Dropzone, HTMLDivElement]>();

    protected createDropzone(type: DropzoneType): [Dropzone, HTMLDivElement] {
        const path = Router.parseQueryParameters()['path'] as string ?? '';

        const modal = this.ui.prepareModal(this.translator.translate('files.upload'), `<div class="upload-area"></div>`);
        modal.classList.add('hide-close');

        const dropzone = new Dropzone(modal.querySelector<HTMLDivElement>('.upload-area'), {
            url: this.router.generateUrl('addFile'),
            paramName: 'file',
            dictFileTooBig: this.translate('error.fileTooBig'),
            dictResponseError: this.translate('error.serverError'),
            previewTemplate: document.querySelector('#template-dropzone-preview').innerHTML,
            parallelUploads: 1,
            maxFilesize: 1000,
            autoQueue: false,
            init: function() {
                if (type === 'directory') {
                    enableDirectoryUpload(this);
                }
            }
        });

        function enableDirectoryUpload(dropzone: Dropzone) {
            // This allows the file picker to select folders instead of files
            dropzone.hiddenFileInput.setAttribute("webkitdirectory", 'true');
        }

        dropzone.on('addedfiles', async (files: DropzoneFile[]) => {

            if (type === 'directory') {
                // input gets recreated after each change
                setTimeout(() => enableDirectoryUpload(dropzone));
            }

            $(modal).modal('show');

            // files is typed as an array in dropzone despite being a FileFile at runtime
            const filenames = Array.from(files).map(file => file.webkitRelativePath || file.name);

            const result = await this.sendRequest<{ exists: string[] }>('doesFilesExist', {
                path,
                filenames,
            });

            if (result.exists.length > 0 && !confirm(this.translator.translate('files.overwriteConfirm'))) {
                $(modal).modal('hide');
                return;
            }

            dropzone.enqueueFiles(files);
        });

        dropzone.on('sending', (file: DropzoneFile, xhr: XMLHttpRequest, formData: FormData) => {
            formData.append('_token', this.securityManager.initializeCsrfToken());
            formData.append('path', path);
            formData.append('relativePath', file.webkitRelativePath || file.name);
            formData.append('overwrite', 'true');
        });

        dropzone.on('uploadprogress', (file: DropzoneFile, progress: number) => {

            if (file.status === 'uploading' && progress === 100) {
                const progressBar = file.previewElement.querySelector('.progress-bar');

                progressBar.classList.add('progress-bar-striped', 'active');
            }
        });

        dropzone.on('success', (file: DropzoneFile) => {
            const progressBar = file.previewElement.querySelector('.progress-bar');

            progressBar.classList.add('progress-bar-success');
            progressBar.classList.remove('progress-bar-info', 'progress-bar-striped', 'active');
            this.securityManager.removeCsrfCookie();
        });

        dropzone.on('error', () => {
            this.securityManager.removeCsrfCookie();
        });

        dropzone.on('queuecomplete', () => {
            $(modal).modal('hide');
        });

        $(modal).on('hidden.bs.modal', function () {
            dropzone.removeAllFiles();
        });

        return [dropzone, modal];
    }

    protected toggleDropzone(type: DropzoneType) {
        let data: [Dropzone, HTMLDivElement];

        if (this.dropzoneMap.has(type)) {
            data = this.dropzoneMap.get(type);
        }
        else {
            data = this.createDropzone(type);
            this.dropzoneMap.set(type, data);
        }

        const [, modal] = data;

        modal.querySelector('.upload-area').dispatchEvent(new MouseEvent('click'));
    }

    onAddFile = () => {
        this.toggleDropzone('file');
    }

    onAddDirectory = () => {
        this.toggleDropzone('directory');
    }
}

Application.create().run(FilesPage);
