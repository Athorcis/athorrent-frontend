
/**
 * Decode base64 using the right charset (because atob does not)
 */
export function decodeBase64(base64: string, charset: string = 'utf-8'): string {
    const text = atob(base64);
    const length = text.length;
    const bytes = new Uint8Array(length);

    for (let i = 0; i < length; i++) {
        bytes[i] = text.charCodeAt(i);
    }

    const decoder = new TextDecoder(charset);
    return decoder.decode(bytes);
}
