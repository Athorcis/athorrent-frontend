/// <reference types="cypress" />

import Chainable = Cypress.Chainable;

export const TEST_DOWNLOAD_LIMIT = 51_200;

export interface TorrentAddOptions {
    downloadLimit?: number;
}

declare namespace Cypress {
    interface Chainable {
        torrentFile(filename: string, shouldExist?: boolean, options?: TorrentAddOptions): Chainable<string|undefined>;
        torrentMagnet(uri: string, shouldExist?: boolean, options?: TorrentAddOptions): Chainable<string|undefined>;

        torrentStatus(status: string): Chainable<string>;

        /** Wait until download progress exceeds min (default 0), i.e. content has started writing. */
        torrentProgress(min?: number): Chainable<string>;

        torrentClick(selector: string): Chainable<string>;
    }
}

function interceptAddTorrent(path: string, downloadLimit?: number) {
    if (downloadLimit === undefined) {
        cy.intercept('POST', path).as('addTorrents');
        return;
    }

    cy.intercept('POST', path, (req) => {
        const separator = req.url.includes('?') ? '&' : '?';
        req.url = `${req.url}${separator}downloadLimit=${downloadLimit}`;
    }).as('addTorrents');
}

function addTorrent(callback: () => void, shouldExist: boolean): Chainable<string|undefined> {
    cy.url().should('contain', '/user/torrents');

    callback();

    const res = cy.wait('@addTorrents').then(interception => {

        if (interception.response.statusCode === 200) {
            const {data} = interception.response.body;
            return data.torrentIds?.[0] ?? data.hash ?? null;
        }

        return null;
    }).as('addTorrent_torrentId');

    if (shouldExist) {
        res.then(torrentId => {
            cy.get('#torrent-' + torrentId).should('exist');
        });
    }

    return cy.get('@addTorrent_torrentId');
}

function submitTorrentFile(filename: string, options?: TorrentAddOptions) {
    interceptAddTorrent('/user/torrents/files*', options?.downloadLimit);
    cy.dropdownItem('.add-torrent', '.main-header').click();
    cy.get('input[type="file"]').selectFile(`cypress/fixtures/torrents/${filename}`, { force: true });
}

function submitMagnetUri(uri: string, options?: TorrentAddOptions) {
    interceptAddTorrent('/user/torrents/magnets*', options?.downloadLimit);
    cy.dropdownItem('.add-magnet', '.main-header').click();
    cy.get('dialog textarea').type(uri, { delay: 0 });
    cy.get('dialog button.primary').click();
}

function getTorrentElement(id: string) {
    return cy.get('#torrent-' + id);
}

Cypress.Commands.add('torrentFile', function (filename: string, shouldExist = true, options?: TorrentAddOptions): Chainable<string|undefined> {
    return addTorrent(() => {
        submitTorrentFile(filename, options);
    }, shouldExist);
});

Cypress.Commands.add('torrentMagnet', function (uri: string, shouldExist = true, options?: TorrentAddOptions): Chainable<string|undefined> {
    return addTorrent(() => {
        submitMagnetUri(uri, options)
    }, shouldExist);
});

Cypress.Commands.add('torrentStatus', {
    prevSubject: true,
}, (torrentId: string, status: string) => {

    getTorrentElement(torrentId)
        .find('.torrent-state')
        .should('have.text', status);

    return cy.wrap(torrentId);
});

Cypress.Commands.add('torrentProgress', {
    prevSubject: true,
}, (torrentId: string, min = 0) => {
    cy.get(`#torrent-${torrentId} progress`, { timeout: 30_000 })
        .should($el => {
            expect(Number($el.attr('value') ?? 0)).to.be.greaterThan(min);
        });

    return cy.wrap(torrentId);
});

Cypress.Commands.add('torrentClick', {
    prevSubject: true,
}, (torrentId: string, selector: string) => {

    getTorrentElement(torrentId)
        .find(selector)
        .click();

    return cy.wrap(torrentId);
});
