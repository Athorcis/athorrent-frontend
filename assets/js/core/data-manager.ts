
export class DataManager {
    getItem(type: string, element: HTMLElement, selector: string = null): HTMLElement {
        return element.closest(selector || `.${type}`);
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
