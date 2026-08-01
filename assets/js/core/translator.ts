
export class Translator {

    constructor(private strings: Translations) {}

    protected replaceParameters(string: string, parameters: Record<string, string>): string {
        return string.replace(/\{([a-z_]+)}/, function (_, varName: string) {
            return parameters[varName] ?? '';
        });
    }

    protected isTranslationKey(string: string) {
        return /^[a-zA-Z]+(\.[a-zA-Z]+)*$/.test(string);
    }

    translate(key: string, parameters: Record<string, string> = {}): string {

        if (this.isTranslationKey(key)) {
            if (this.strings.hasOwnProperty(key)) {
                return this.replaceParameters(this.strings[key]!, parameters);
            }

            console.error(`missing translation for ${key}`);
        }

        return key;
    }
}
