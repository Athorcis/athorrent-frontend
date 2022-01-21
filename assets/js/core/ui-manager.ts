import $ from 'jquery';

export class UiManager {

    constructor(readonly templates: {[key: string]: string}) {

    }

    showModal(title: string, content: string) {
        const $modal = $(this.templates.modal);

        $modal.find('.modal-title').text(title);
        $modal.find('.modal-body').html(content);

        $modal.appendTo('body');
        $modal.modal('show');
    }
}
