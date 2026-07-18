
export class DataManager {
    getItem(type: string, element: HTMLElement, selector: string|null = null): HTMLElement {
        selector = selector || `.${type}`;

        const item = element.closest<HTMLElement>(selector);

        if (item) {
            return item;
        }

        throw new Error(`failed to get item : root not found ${selector}`);
    }

    getItemId(type: string, element: HTMLElement, selector: string|null = null): string {
        const item = this.getItem(type, element, selector);
        const elementId = item.getAttribute('id');

        if (elementId) {
            return elementId.replace(`${type}-`, '');
        }

        throw new Error('failed to get item id : missing attribute id');
    }

    getItemAttr(type: string, element: HTMLElement, name: string, selector: string|null = null): string {
        const item = this.getItem(type, element, selector);
        const attr = item.querySelector(`.${type}-${name}`);

        if (attr) {
            return attr.textContent;
        }

        throw new Error(`failed to get item attribute : missing attribute ${name}`);
    }

    getItemData(type: string, element: HTMLElement, name: string, selector: string|null = null): string|null {
        const item = this.getItem(type, element, selector);
        return item.dataset[name] ?? null;
    }
}
