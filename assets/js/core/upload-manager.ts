import Dropzone, {DropzoneFile, DropzoneOptions} from 'dropzone';
import {Router} from "./router";
import {SecurityManager} from "./security-manager";
import {UiManager} from "./ui-manager";
import {Translator} from "./translator";

export type DropzoneType = 'file'|'directory';

const PROGRESS_COMPLETED = 100;

export interface UploadOptions {
    title: string;
    route: string;
    init?: (dropzone: Dropzone, modal: HTMLDialogElement) => void;
    complete?: (filesUploaded: number, filesErrored: number) => void|PromiseLike<void>;
    type?: DropzoneType;
    dropzone?: DropzoneOptions;
}

export class UploadManager {

    constructor(
        private router: Router,
        private securityManager: SecurityManager,
        private ui: UiManager,
        private translator: Translator,
    ) {}

    initialize(options: UploadOptions): [Dropzone, HTMLDialogElement] {
        const {
            title,
            route,
            type = 'file'
        } = options;

        const modal = this.ui.prepareModal({ title, content: `<div class="file-upload-list"></div>`, removeWhenClose: true });
        modal.classList.add('hide-close');

        const dropzone = new Dropzone(modal.querySelector<HTMLDivElement>('.file-upload-list'), {
            ...options.dropzone,
            url: this.router.generateUrl(route),
            paramName: 'file',
            dictFileTooBig: this.translator.translate('error.fileTooBig'),
            dictResponseError: this.translator.translate('error.serverError'),
            previewTemplate: document.querySelector('#template-dropzone-preview').innerHTML,
            parallelUploads: 1,
            init: function() {
                if (type === 'directory') {
                    // This allows the file picker to select folders instead of files
                    this.hiddenFileInput.setAttribute("webkitdirectory", 'true');
                }

                this.hiddenFileInput.addEventListener("cancel", () => {
                    this.destroy();
                    modal.remove();
                });
            },
        });

        let filesUploaded: number = 0;
        let filesErrored: number = 0;

        dropzone.on('addedfiles', () => {
            modal.showModal();
        });

        dropzone.on('sending', (file: DropzoneFile, xhr: XMLHttpRequest, formData: FormData) => {
            formData.append('_token', this.securityManager.initializeCsrfToken());
            formData.append('relativePath', file.webkitRelativePath || file.name);
        });

        const percentFormat = new Intl.NumberFormat(undefined, {
            style: 'percent',
            maximumFractionDigits: 0
        });

        dropzone.on('uploadprogress', (file: DropzoneFile, progress: number) => {

            if (file.status === 'uploading') {
                file.previewElement.querySelector('.file-upload__status__progress').textContent = percentFormat.format(progress / PROGRESS_COMPLETED);

                if (progress === PROGRESS_COMPLETED) {
                    file.previewElement.querySelector('progress').removeAttribute('value');
                }
            }
        });

        dropzone.on('success', (file: DropzoneFile) => {
            file.previewElement.querySelector('progress').value = 100;
            this.securityManager.removeCsrfCookie();
            ++filesUploaded;
        });

        dropzone.on('error', () => {
            this.securityManager.removeCsrfCookie();
            ++filesErrored;
        });

        dropzone.on('queuecomplete', async () => {

            if (options.complete) {
                await options.complete(filesUploaded, filesErrored);
            }

            if (filesErrored === 0) {
                modal.close();
            }
            else {
                modal.classList.remove('hide-close');
            }
        });

        modal.addEventListener('close', () => {
            dropzone.destroy();
        }, { once: true });

        if (options.init) {
            options.init(dropzone, modal);
        }

        return [dropzone, modal];
    }

    trigger(options: UploadOptions): void {
        const [, modal] = this.initialize(options);
        modal.querySelector('.file-upload-list').dispatchEvent(new MouseEvent('click'));
    }
}
