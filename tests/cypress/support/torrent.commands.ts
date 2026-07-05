/// <reference types="cypress" />

import Chainable = Cypress.Chainable;

declare namespace Cypress {
    interface Chainable {
        torrentFile(filename: string, shouldExist?: boolean): Chainable<number|undefined>;
        torrentMagnet(uri: string, shouldExist?: boolean): Chainable<number|undefined>;

        torrentStatus(status: string): Chainable<number>;

        torrentClick(selector: string): Chainable<number>;
    }
}

function addTorrent(callback: () => void, shouldExist: boolean): Chainable<number|undefined> {
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

function submitTorrentFile(filename: string) {
    cy.intercept('POST', '/user/torrents/files').as('addTorrents');
    cy.dropdownItem('.add-torrent', '.main-header').click();
    cy.get('input[type="file"]').selectFile(`cypress/fixtures/torrents/${filename}`, { force: true });
}

function submitMagnetUri(uri: string) {
    cy.intercept('POST', '/user/torrents/magnets').as('addTorrents');
    cy.dropdownItem('.add-magnet', '.main-header').click();
    cy.get('dialog textarea').type(uri, { delay: 0 });
    cy.get('dialog button.primary').click();
}

function getTorrentElement(id: number) {
    return cy.get('#torrent-' + id);
}

Cypress.Commands.add('torrentFile', function (filename: string, shouldExist = true): Chainable<number|undefined> {
    return addTorrent(() => {
        submitTorrentFile(filename);
    }, shouldExist);
});

Cypress.Commands.add('torrentMagnet', function (uri: string, shouldExist = true): Chainable<number|undefined> {
    return addTorrent(() => {
        submitMagnetUri(uri)
    }, shouldExist);
});

Cypress.Commands.add('torrentStatus', {
    prevSubject: true,
}, (torrentId: number, status: string) => {

    getTorrentElement(torrentId)
        .find('.torrent-state')
        .should('have.text', status);

    return cy.wrap(torrentId);
});

Cypress.Commands.add('torrentClick', {
    prevSubject: true,
}, (torrentId: number, selector: string) => {

    getTorrentElement(torrentId)
        .find(selector)
        .click();

    return cy.wrap(torrentId);
});
