import $ from 'jquery';

export class DataManager {
    getItem(type: string, element: HTMLElement, selector: string = null) {
        let $item;
        const $element = $(element);

        selector = selector || `.${type}`;

        if ($element.filter(selector).length) {
            $item = $element;
        } else {
            $item = $element.closest(selector);

            if ($item.length === 0) {
                $item = null;
            }
        }

        return $item;
    }

    getItemId(type: string, element: HTMLElement, selector: string = null) {
        let id
        const $item = this.getItem(type, element, selector);

        if ($item) {
            id = $item.attr('id').replace(`${type}-`, '');
        } else {
            id = null;
        }

        return id;
    }

    getItemAttr(type: string, element: HTMLElement, name: string, selector: string = null) {
        let attr;
        const $item = this.getItem(type, element, selector);

        if ($item) {
            attr = $item.children(`.${type}-${name}`).text();
        } else {
            attr = null;
        }

        return attr;
    }
}
