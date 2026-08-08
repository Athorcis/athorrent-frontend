import {getFileSelector} from "../support/commands";
import {HTTP_OK, pathWithLocale, resetTestData} from "../support/utils";
import {TEST_DOWNLOAD_LIMIT} from "../support/torrent.commands";

describe('torrents', () => {
    beforeEach(() => {
        resetTestData();
        cy.login();
        cy.visit(pathWithLocale('/user/torrents/'));
    });

    it('should add torrent file', function() {
        cy.torrentFile('sintel.torrent', true, { downloadLimit: TEST_DOWNLOAD_LIMIT })

            .torrentStatus('downloading');
    });

    it('should pause and resume torrents', function () {
        cy.torrentFile('sintel.torrent', true, { downloadLimit: TEST_DOWNLOAD_LIMIT })

            .torrentClick('.torrent-pause')
            .torrentStatus('paused')

            .torrentClick('.torrent-resume')
            .torrentStatus('downloading')
    });

    it('should allow to remove torrents', function () {
        cy.torrentFile('sintel.torrent')

            .torrentClick('.torrent-remove');

        cy.confirmModal();
    });

    it('should not allow to remove file bound to torrents', function () {
        cy.torrentFile('sintel.torrent', true, { downloadLimit: TEST_DOWNLOAD_LIMIT })
            .torrentStatus('downloading')
            .torrentProgress();

        cy.visit(pathWithLocale('/user/files/'));

        const selector = getFileSelector('Sintel');

        cy.get(selector).should('exist');
        cy.get(`${selector} .file-remove`).should('be.disabled');
    });

    it('should fail for invalid torrent file', function() {
        cy.torrentFile('invalid.torrent', false);
        cy.get('@addTorrents').its('response.body.code').should('eq', 'INVALID_TORRENT_FILE');
        cy.get('dialog:open .file-upload__error').should('be.visible');
    });


    it('should add magnet uri', function() {
        cy.torrentMagnet('magnet:?xt=urn:btih:a88fda5954e89178c372716a6a78b8180ed4dad3&dn=The+WIRED+CD+-+Rip.+Sample.+Mash.+Share&tr=udp%3A%2F%2Fexplodie.org%3A6969&tr=udp%3A%2F%2Ftracker.coppersurfer.tk%3A6969&tr=udp%3A%2F%2Ftracker.empire-js.us%3A1337&tr=udp%3A%2F%2Ftracker.leechers-paradise.org%3A6969&tr=udp%3A%2F%2Ftracker.opentrackr.org%3A1337&tr=wss%3A%2F%2Ftracker.btorrent.xyz&tr=wss%3A%2F%2Ftracker.fastcast.nz&tr=wss%3A%2F%2Ftracker.openwebtorrent.com&ws=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2F&xs=https%3A%2F%2Fwebtorrent.io%2Ftorrents%2Fwired-cd.torrent');

        cy.get('.torrent-name').should('have.text', 'The WIRED CD - Rip. Sample. Mash. Share')

    });

    it ('should fail for invalid magnet uri', function() {
        cy.torrentMagnet('magnet:?', false);
        cy.get('@addTorrents').its('response.body.code').should('eq', 'INVALID_MAGNET_URI');
        cy.get('#dialog-error').should('be.visible');
    });

    it('should allow access to qbittorrent web version', function () {
        cy.get('.open-qbittorrent-ui')
            .should('have.attr', 'href')
            // we can't test qbittorrent web directly because it won't work
            // since pages are opened in an iframe (which is what cypress does)
            .then((href) => {
                const webUiPath = String(href).replace(/\/?$/, '/');

                cy.request(webUiPath).then((response) => {
                    expect(response.status).to.eq(HTTP_OK);
                    expect(response.body).to.include('<title>qBittorrent WebUI</title>');
                });

                cy.request(`${webUiPath}api/v2/app/version`).then((response) => {
                    expect(response.status).to.eq(HTTP_OK);
                    expect(String(response.body)).to.match(/^v?\d/);
                });
            });
    });
});
