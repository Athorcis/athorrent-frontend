import $ from 'jquery';
import {Translator} from './translator';

export class UiManager {

    private modalTemplate: HTMLTemplateElement;

    constructor(private translator: Translator) {
        this.modalTemplate = document.querySelector('#template-modal');
    }

    prepareModal(title: string, content: string, removeWhenClose: boolean = true) {
        const fragment: DocumentFragment = this.modalTemplate.content.cloneNode(true) as DocumentFragment;

        fragment.querySelector('.modal-title').textContent = this.translator.translate(title);
        fragment.querySelector('.modal-body').innerHTML = content;

        const modal = fragment.firstElementChild;

        if (removeWhenClose) {
            $(modal).on('hidden.bs.modal', function () {
                document.body.removeChild(modal);
            });
        }

        document.body.append(modal);

        return modal as HTMLDivElement;
    }

    showModal(title: string, content: string, removeWhenClose: boolean = true) {
        const modal = this.prepareModal(title, content,removeWhenClose);
        $(modal).modal('show');

        return modal;
    }
}
