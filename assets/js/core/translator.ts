
export class Translator {

    constructor(private strings: Translations) {}

    protected replaceParameters(string: string, parameters: Record<string, string>): string {
        return string.replace(/\{([a-z_]+)}/, function (_, varName: string) {
            return parameters[varName];
        });
    }

    translate(key: string, parameters: Record<string, string> = {}): string {
        if (this.strings.hasOwnProperty(key)) {
            return this.replaceParameters(this.strings[key], parameters);
        }

        return key;
    }
}
