
declare module '*.svg' {
    const content: string;
    export default content;
}

interface Navigator {
    registerProtocolHandler(scheme: string, url: string, title?: string): void;
}

interface AppConfig {
    routes: Routes;
    routeParameters: Params;
    action: string;
    csrfToken: string;
    strings: Translations;
}

interface Window {
    athorrent: AppConfig;
}
