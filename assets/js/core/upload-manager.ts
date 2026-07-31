import Dropzone, {DropzoneFile, DropzoneOptions} from 'dropzone';
import type {Router} from "./router";
import type {SecurityManager} from "./security-manager";
import type {UiManager} from "./ui-manager";
import type {Translator} from "./translator";

export type DropzoneType = 'file'|'directory';

const PROGRESS_COMPLETED = 100;

const CHUNK_SIZE = 8_388_608; // 8 MiB

export interface UploadOptions {
    title: string;
    route: string;
    init?: (dropzone: Dropzone, modal: HTMLDialogElement) => void;
    complete?: (filesUploaded: number, filesErrored: number) => void|PromiseLike<void>;
    type?: DropzoneType;
    dropzone?: DropzoneOptions;
}

export interface UploadManagerInterface {
    trigger(options: UploadOptions): void;
}

export class UploadManager implements UploadManagerInterface{

    constructor(
        private router: Router,
        private securityManager: SecurityManager,
        private ui: UiManager,
        private translator: Translator,
    ) {}

    protected initialize(options: UploadOptions): [Dropzone, HTMLDialogElement] {
        const {
            title,
            route,
            type = 'file'
        } = options;

        const uploadListEl = document.createElement('div');
        uploadListEl.classList.add('file-upload-list');

        const modal = this.ui.prepareModal({ title, content: uploadListEl, removeWhenClose: true });
        modal.classList.add('hide-close');

        const dropzone = new Dropzone(uploadListEl, {
            ...options.dropzone,
            url: this.router.generateUrl(route),
            paramName: 'file',
            dictFileTooBig: this.translator.translate('error.fileTooBig'),
            dictResponseError: this.translator.translate('error.serverError'),
            previewTemplate: document.querySelector('#template-dropzone-preview')!.innerHTML,
            parallelUploads: 1,
            chunking: true,
            chunkSize: CHUNK_SIZE,
            parallelChunkUploads: false,
            retryChunks: true,
            retryChunksLimit: 2,
            init: function() {
                if (type === 'directory') {
                    // This allows the file picker to select folders instead of files
                    this.hiddenFileInput!.setAttribute("webkitdirectory", 'true');
                }

                this.hiddenFileInput!.addEventListener("cancel", () => {
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

        const csrfCleanups = new WeakMap<DropzoneFile, () => void>();

        dropzone.on('sending', (file: DropzoneFile, _xhr: XMLHttpRequest, formData: FormData) => {
            // Sequential uploads/chunks: clear the previous request cookie before writing a new one.
            csrfCleanups.get(file)?.();
            const { token, cleanup } = this.securityManager.createCsrfToken();
            csrfCleanups.set(file, cleanup);
            formData.append('_token', token);
            formData.append('relativePath', file.webkitRelativePath || file.name);
        });

        const percentFormat = new Intl.NumberFormat(undefined, {
            style: 'percent',
            maximumFractionDigits: 0
        });

        dropzone.on('uploadprogress', (file: DropzoneFile, progress: number) => {

            if (file.status === 'uploading') {
                file.previewElement!.querySelector('.file-upload__status__progress')!.textContent = percentFormat.format(progress / PROGRESS_COMPLETED);

                if (progress === PROGRESS_COMPLETED) {
                    file.previewElement!.querySelector('progress')!.removeAttribute('value');
                }
            }
        });

        dropzone.on('success', (file: DropzoneFile) => {
            file.previewElement!.querySelector('progress')!.value = 100;
            csrfCleanups.get(file)?.();
            csrfCleanups.delete(file);
            ++filesUploaded;
        });

        dropzone.on('error', (file: DropzoneFile) => {
            csrfCleanups.get(file)?.();
            csrfCleanups.delete(file);
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
        const [dropzone] = this.initialize(options);
        dropzone.hiddenFileInput!.click();
    }
}
