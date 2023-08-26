
export class DataManager {
    getItem(type: string, element: HTMLElement, selector: string = null): HTMLElement {

        selector = selector || `.${type}`;

        if (element.matches(selector)) {
            return element;
        }

        return element.closest(selector);
    }

    getItemId(type: string, element: HTMLElement, selector: string = null) {
        const item = this.getItem(type, element, selector);

        if (item) {
            return item.getAttribute('id').replace(`${type}-`, '');
        }

        return null;
    }

    getItemAttr(type: string, element: HTMLElement, name: string, selector: string = null) {
        const item = this.getItem(type, element, selector);

        if (item) {
            return item.querySelector(`.${type}-${name}`).textContent;
        }

        return null;
    }
}
