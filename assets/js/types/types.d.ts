
declare module '*.scss';

interface Navigator {
    registerProtocolHandler(scheme: string, url: string, title?: string): void;
}

interface AppConfig {
    routes: Routes;
    routeParameters: Params;
    action: string;
    strings: Translations;
}
