import $ from 'jquery';

export class UiManager {

    constructor(private templates: {[key: string]: string}) {

    }

    showModal(title: string, content: string) {
        let $modal = $(this.templates.modal);

        $modal.find('.modal-title').text(title);
        $modal.find('.modal-body').html(content);

        $modal.appendTo('body');
        $modal.modal('show');
    }
}
