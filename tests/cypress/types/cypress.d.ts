/// <reference types="cypress" />

interface TorrentAddOptions {
    downloadLimit?: number;
}

declare namespace Cypress {
    interface Chainable {
        elementExists(selector: string): Chainable<null>;
        login(username?: string, password?: string): Chainable<null>;
        logout(): Chainable<null>;
        dropdownItem(selector: string, parentSelector: string): Chainable<null>;
        dropdownItem(selector: string, parentSelector: string, skipOpen: boolean): Chainable<null>;
        confirmModal(): Chainable<JQuery<HTMLElement>>;

        torrentFile(filename: string, shouldExist?: boolean, options?: TorrentAddOptions): Chainable<string|undefined>;
        torrentMagnet(uri: string, shouldExist?: boolean, options?: TorrentAddOptions): Chainable<string|undefined>;
        torrentStatus(status: string): Chainable<string>;
        torrentProgress(min?: number): Chainable<string>;
        torrentClick(selector: string): Chainable<string>;
    }
}
