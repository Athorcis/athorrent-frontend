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
    content: string;
    removeWhenClose?: boolean;
    controls?: ModalControl[];
}

export class UiManager {

    private modalTemplate: HTMLTemplateElement;

    constructor(private translator: Translator) {
        this.modalTemplate = document.querySelector('#template-modal');

        this.initMobileNav();

        on(document, 'click', '.dropdown-menu', function (event) {
            const target = event.target as HTMLElement;

            if (target.matches('.dropdown-menu')) {
                return;
            }

            const menu = target.closest<HTMLUListElement>('.dropdown-menu');
            menu.hidePopover();
        });

        on(document, 'click', '.alert-dismissible > .close', function (event) {
            const target = event.target as HTMLElement;
            target.closest('.alert').remove();
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

        fragment.querySelector('.modal-title').textContent = this.translator.translate(title);
        fragment.querySelector('.modal-body').innerHTML = content;

        if (subtitle) {
            const subtitleEL = document.createElement('p');
            subtitleEL.classList.add('modal-subtitle');
            subtitleEL.textContent = this.translator.translate(subtitle);

            fragment.querySelector('header').append(subtitleEL);
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
                        await callback();
                    }

                    modal.close();
                });

                controlsEL.appendChild(controlEl);
            }

            fragment.firstElementChild.appendChild(controlsEL);
        }

        const modal = fragment.firstElementChild as HTMLDialogElement;

        modal.addEventListener('click', (e) => {
            const target = e.target as HTMLElement;

            if (target.closest('.close')) {
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

    showModal(options: ModalOptions) {
        const modal = this.prepareModal(options);
        modal.showModal();

        return modal;
    }
}
