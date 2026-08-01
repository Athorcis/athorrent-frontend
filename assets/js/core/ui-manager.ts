import {Translator} from './translator';
import {on} from "./events";

interface ModalControl {
    label: string;
    primary?: boolean;
    callback?: () => (void|PromiseLike<void>);
}

interface ModalOptions {
    title: string;
    subtitle?: string;
    content: string|HTMLElement;
    removeWhenClose?: boolean;
    controls?: ModalControl[];
    id?: string;
}

export class UiManager {

    private modalTemplate: HTMLTemplateElement;

    constructor(private translator: Translator) {
        const modalTemplate = document.querySelector<HTMLTemplateElement>('#template-modal');

        if (modalTemplate) {
            this.modalTemplate = modalTemplate;
        }
        else {
            throw new Error('failed to find #template-modal');
        }

        this.initMobileNav();

        on(document, 'click', '.dropdown-menu', function (event) {
            const target = event.target as HTMLElement;

            if (target.matches('.dropdown-menu')) {
                return;
            }

            const menu = target.closest<HTMLUListElement>('.dropdown-menu')!;
            menu.hidePopover();
        });

        on(document, 'click', '.alert-dismissible > .close', function (event) {
            const target = event.target as HTMLElement;
            target.closest('.alert')!.remove();
        });
    }

    private initMobileNav() {
        const mobileQuery = window.matchMedia('(width < 768px)');

        const burger = document.querySelector<HTMLButtonElement>('.menu-burger-button');

        const closeSidebar = () => {
            document.body.classList.remove('sidebar-open');
            burger?.setAttribute('aria-expanded', 'false');
        };

        const openSidebar = () => {
            document.body.classList.add('sidebar-open');
            burger?.setAttribute('aria-expanded', 'true');
        };

        const toggleSidebar = () => {
            if (document.body.classList.contains('sidebar-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        };

        on(document, 'click', '.menu-burger-button', (event) => {
            event.preventDefault();
            toggleSidebar();
        });

        on(document, 'click', '.sidebar-backdrop', () => {
            closeSidebar();
        });

        on(document, 'click', 'nav.sidebar a', () => {
            if (mobileQuery.matches) {
                closeSidebar();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeSidebar();
            }
        });

        mobileQuery.addEventListener('change', (event) => {
            if (!event.matches) {
                closeSidebar();
            }
        });
    }

    prepareModal(options: ModalOptions) {
        const {
            title,
            subtitle,
            content,
            removeWhenClose = false,
            controls,
        } = options;

        const fragment: DocumentFragment = this.modalTemplate.content.cloneNode(true) as DocumentFragment;

        fragment.querySelector('.modal-title')!.textContent = this.translator.translate(title);

        const modalBodyEl = fragment.querySelector('.modal-body')!;

        if (typeof content === 'string') {
            modalBodyEl.textContent = content;
        }
        else {
            modalBodyEl.appendChild(content);
        }

        if (subtitle) {
            const subtitleEL = document.createElement('p');
            subtitleEL.classList.add('modal-subtitle');
            subtitleEL.textContent = this.translator.translate(subtitle);

            fragment.querySelector('header')!.append(subtitleEL);
        }

        const modal = fragment.firstElementChild as HTMLDialogElement;

        if (options.id) {
            modal.id = options.id;
        }

        if (controls) {
            const controlsEL = document.createElement('div');
            controlsEL.className = 'modal-controls';

            for (const {label, primary = false, callback} of controls) {
                const controlEl = document.createElement('button');
                controlEl.textContent = this.translator.translate(label);

                if (primary) {
                    controlEl.classList.add('primary');
                }

                controlEl.addEventListener('click', async () => {
                    if (callback) {
                        if (modal.ariaBusy === 'true') {
                            return;
                        }

                        const result = callback();

                        if (result != null && typeof (result as PromiseLike<void>).then === 'function') {
                            this.setModalBusy(modal, true, controlEl);

                            try {
                                await result;
                            }
                            catch {
                                this.setModalBusy(modal, false, controlEl);
                                return;
                            }
                        }
                    }

                    modal.close();
                });

                controlsEL.appendChild(controlEl);
            }

            modal.appendChild(controlsEL);
        }

        modal.addEventListener('cancel', (event) => {
            if (modal.ariaBusy === 'true') {
                event.preventDefault();
            }
        });

        modal.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;

            if (target.closest('.close') && modal.ariaBusy !== 'true') {
                modal.close();
            }
        });

        if (removeWhenClose) {
            modal.addEventListener('close', function () {
                document.body.removeChild(modal);
            }, { once: true });
        }

        document.body.append(modal);

        return modal;
    }

    private setModalBusy(modal: HTMLDialogElement, busy: boolean, loadingButton?: HTMLButtonElement) {
        modal.ariaBusy = busy ? 'true' : 'false';

        for (const button of modal.querySelectorAll('button')) {
            button.disabled = busy;
        }

        for (const field of modal.querySelectorAll<HTMLInputElement | HTMLTextAreaElement>('input, textarea')) {
            field.disabled = busy;
        }

        loadingButton?.classList.toggle('loading', busy);
    }

    showModal(options: ModalOptions) {
        const modal = this.prepareModal(options);
        modal.showModal();

        return modal;
    }

    /**
     * Shows a confirm dialog. If `onConfirm` is provided it runs when the user confirms
     * (with a loading state on the confirm button for async work).
     * Resolves `true` when confirmed, `false` when cancelled/dismissed.
     * Rejects if `onConfirm` throws.
     */
    confirm(
        key: string,
        parameters: Record<string, string> = {},
        onConfirm?: () => void | PromiseLike<void>,
    ): Promise<boolean> {
        return new Promise((resolve, reject) => {
            let settled = false;

            const settle = (value: boolean) => {
                if (!settled) {
                    settled = true;
                    resolve(value);
                }
            };

            const modal = this.prepareModal({
                title: 'common.confirm',
                content: this.translator.translate(key, parameters),
                removeWhenClose: true,
                id: 'dialog-confirm',
                controls: [
                    { label: 'common.cancel' },
                    {
                        label: 'common.confirm',
                        primary: true,
                        callback: async () => {
                            try {
                                if (onConfirm) {
                                    await onConfirm();
                                }

                                modal.returnValue = 'confirm';
                            }
                            catch (error) {
                                if (!settled) {
                                    settled = true;
                                    reject(error);
                                }

                                throw error;
                            }
                        },
                    },
                ],
            });

            modal.addEventListener('close', () => {
                settle(modal.returnValue === 'confirm');
            }, { once: true });

            modal.showModal();
        });
    }
}
