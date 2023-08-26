import $ from 'jquery';

export class UiManager {

    private modalTemplate: HTMLTemplateElement;

    constructor() {
        this.modalTemplate = document.querySelector('#template-modal');
    }

    showModal(title: string, content: string) {
        const fragment: DocumentFragment = this.modalTemplate.content.cloneNode(true) as DocumentFragment;

        fragment.querySelector('.modal-title').textContent = title;
        fragment.querySelector('.modal-body').innerHTML = content;

        const modal = fragment.children[0];
        document.body.append(fragment);

        $(modal).modal('show');
    }
}
