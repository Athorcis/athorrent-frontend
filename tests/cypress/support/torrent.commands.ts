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

    cy.intercept('POST', '/user/torrents').as('addTorrents');

    callback();

    const res = cy.wait('@addTorrents').then(interception => {

        if (interception.response.statusCode === 200) {
            return interception.response.body.data.torrentIds[0] ?? null;
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
    cy.get('#add-torrent-file').click();
    cy.get('input[type="file"]').selectFile(`cypress/fixtures/torrents/${filename}`, { force: true });
    cy.get('#add-torrent-file-counter').should('have.text', '(1)');
    cy.get('#add-torrent-submit').click();
}

function submitMagnetUri(uri: string) {
    cy.get('#add-torrent-magnet').click();
    cy.get('#add-torrent-magnet-input').type(uri, { delay: 0 });
    cy.get('#add-torrent-magnet-counter').should('have.text', '(1)');
    cy.get('#add-torrent-submit').click();
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
