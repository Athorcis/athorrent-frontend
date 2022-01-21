
interface Translations {
    [key: string]: string;
}

export class Translator {

    constructor(private strings: Translations) {}

    translate(key: string): string {
        if (this.strings.hasOwnProperty(key)) {
            return this.strings[key];
        }

        return key;
    }
}
