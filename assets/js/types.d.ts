
interface Navigator {
    registerProtocolHandler(scheme: string, url: string, title?: string): void;
}

interface JQuery<TElement = HTMLElement> {
    mediaelementplayer(options: Record<string, any>): JQuery<TElement>;
}

interface AppConfig {
    [key: string]: any;
}

interface Window {
    athorrent: AppConfig;
}
